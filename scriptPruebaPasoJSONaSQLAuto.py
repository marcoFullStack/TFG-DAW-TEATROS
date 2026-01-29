import json
import mysql.connector
import random
import requests
from datetime import datetime
import os
import re
from difflib import SequenceMatcher

RUN_LOG_FILE = "import_runs.txt"
CHANGE_LOG_FILE = "import_changes.txt"

def now_ts() -> str:
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")

def log(msg: str) -> None:
    line = f"[{now_ts()}] {msg}"
    print(line)
    with open(RUN_LOG_FILE, "a", encoding="utf-8") as f:
        f.write(line + "\n")

def log_change(msg: str) -> None:
    line = f"[{now_ts()}] {msg}"
    with open(CHANGE_LOG_FILE, "a", encoding="utf-8") as f:
        f.write(line + "\n")

def normalizar_nombre(nombre: str) -> str:
    if not nombre:
        return ""
    nombre = nombre.strip()

    base, ext = os.path.splitext(nombre)
    if ext.lower() in (".png", ".jpg", ".jpeg", ".gif", ".webp"):
        nombre = base

    def reemplazar_unicode(match):
        try:
            return chr(int(match.group(1), 16))
        except:
            return match.group(0)

    nombre = re.sub(r"#U([0-9A-Fa-f]{4})", reemplazar_unicode, nombre)
    return nombre.lower().strip()

def similitud_cadenas(a: str, b: str) -> float:
    return SequenceMatcher(None, a, b).ratio()

def buscar_imagen_local(nombre_busqueda: str, carpeta_imagenes: str, umbral_similitud=0.7):
    if not os.path.exists(carpeta_imagenes):
        return None

    nombre_normalizado = normalizar_nombre(nombre_busqueda)
    mejor_coincidencia = None
    mejor_similitud = 0.0

    for archivo in os.listdir(carpeta_imagenes):
        if archivo.lower().endswith((".png", ".jpg", ".jpeg", ".gif", ".webp")):
            nombre_archivo_normalizado = normalizar_nombre(archivo)
            sim = similitud_cadenas(nombre_normalizado, nombre_archivo_normalizado)
            if sim > mejor_similitud and sim >= umbral_similitud:
                mejor_similitud = sim
                mejor_coincidencia = archivo

    if not mejor_coincidencia:
        return None

    ruta = os.path.join(carpeta_imagenes, mejor_coincidencia).replace("\\", "/")
    if ruta.startswith("app/"):
        ruta = "./" + ruta[4:]
    return ruta

# ===================== config =====================

DB_NAME = "red_teatros_regional"

db_config_server = {"host": "localhost", "user": "root", "password": ""}
db_config_db = {"host": "localhost", "user": "root", "password": "", "database": DB_NAME}

API_TEATROS_URL = "https://analisis.datosabiertos.jcyl.es/api/explore/v2.1/catalog/datasets/red_teatros/records?limit=50"
API_OBRAS_URL = "https://dracor.org/api/v1/corpora/span"
LOCAL_OBRAS_JSON = "obras.json"

# ===================== SQL runner =====================

def ejecutar_sql_file_en_servidor(ruta_sql: str) -> None:
    if not os.path.exists(ruta_sql):
        raise FileNotFoundError(f"No existe el archivo SQL: {ruta_sql}")

    with open(ruta_sql, "r", encoding="utf-8") as f:
        sql = f.read()

    # Quita comentarios simples y líneas vacías (como ya hacías)
    lineas = []
    for linea in sql.splitlines():
        linea = linea.strip()
        if not linea or linea.startswith("--"):
            continue
        lineas.append(linea)

    sql_limpio = "\n".join(lineas)
    sentencias = [s.strip() for s in sql_limpio.split(";") if s.strip()]

    conn = mysql.connector.connect(**db_config_server)
    cursor = conn.cursor()
    try:
        for sentencia in sentencias:
            cursor.execute(sentencia)
        conn.commit()
    finally:
        cursor.close()
        conn.close()

# ===================== loaders =====================

def cargar_teatros(api_url: str, fallback_json_path: str):
    try:
        r = requests.get(api_url, timeout=12)
        r.raise_for_status()
        payload = r.json()
        results = payload.get("results", [])
        if isinstance(results, list) and results:
            log(f"✅ Teatros cargados desde API: {len(results)}")
            return results
        log("⚠️ API teatros sin results útiles. Usando JSON local...")
    except Exception as e:
        log(f"⚠️ Falló API teatros ({e}). Usando JSON local...")

    with open(fallback_json_path, "r", encoding="utf-8") as f:
        data_local = json.load(f)

    log(f"✅ Teatros cargados desde JSON local: {len(data_local)}")
    return data_local

def cargar_obras(api_url: str, fallback_json_path: str):
    try:
        r = requests.get(api_url, timeout=12)
        r.raise_for_status()
        payload = r.json()
        plays = payload.get("plays", [])
        if isinstance(plays, list) and plays:
            log(f"✅ Obras cargadas desde API Dracor: {len(plays)}")
            return plays
        log("⚠️ API Dracor sin plays útiles. Usando JSON local...")
    except Exception as e:
        log(f"⚠️ Falló API Dracor ({e}). Usando JSON local...")

    if not os.path.exists(fallback_json_path):
        raise FileNotFoundError(f"No existe el archivo local de obras: {fallback_json_path}")

    with open(fallback_json_path, "r", encoding="utf-8") as f:
        data_local = json.load(f)

    if isinstance(data_local, list):
        log(f"✅ Obras cargadas desde JSON local (lista): {len(data_local)}")
        return data_local

    if isinstance(data_local, dict) and isinstance(data_local.get("plays"), list):
        plays = data_local["plays"]
        log(f"✅ Obras cargadas desde JSON local (plays): {len(plays)}")
        return plays

    raise ValueError("Formato no válido en obras.json (lista o {'plays': [...]})")

# ===================== migración índice teatros (SIN AÑADIR CAMPOS) =====================

def asegurar_unique_teatros_sala_provincia(cursor):
    """
    Si vienes de un esquema viejo con UNIQUE(Sala), lo cambia a UNIQUE(Sala, Provincia).
    No añade columnas.
    """
    cursor.execute("SHOW INDEX FROM teatros")
    idx = cursor.fetchall()

    # Columnas (según MySQL): Key_name en posición 2, Column_name en 4
    key_cols = {}
    for row in idx:
        key_name = row[2]
        col_name = row[4]
        key_cols.setdefault(key_name, []).append(col_name)

    # Si existe el viejo uq_teatros_sala con solo Sala, lo eliminamos
    if "uq_teatros_sala" in key_cols:
        try:
            cursor.execute("ALTER TABLE teatros DROP INDEX uq_teatros_sala")
            log("🔧 Migración: eliminado UNIQUE antiguo uq_teatros_sala (Sala)")
        except Exception as e:
            log(f"⚠️ No pude eliminar uq_teatros_sala: {e}")

    # Crear el nuevo si no existe
    if "uq_teatros_sala_provincia" not in key_cols:
        try:
            cursor.execute("ALTER TABLE teatros ADD UNIQUE KEY uq_teatros_sala_provincia (Sala, Provincia)")
            log("🔧 Migración: creado UNIQUE uq_teatros_sala_provincia (Sala, Provincia)")
        except Exception as e:
            log(f"⚠️ No pude crear uq_teatros_sala_provincia: {e}")

# ===================== helpers =====================

def obtener_teatro_existente(cursor, sala: str, provincia: str):
    cursor.execute(
        """SELECT idTeatro, Entidad, Municipio, Direccion, CP, Telefono, Email, CapacidadMax, Latitud, Longitud
           FROM teatros
           WHERE Sala=%s AND Provincia=%s
           LIMIT 1""",
        (sala, provincia)
    )
    return cursor.fetchone()

def upsert_teatro_get_id(cursor, valores):
    """
    UPSERT basado en UNIQUE(Sala, Provincia).
    """
    sql = """
    INSERT INTO teatros
      (Sala, Entidad, Provincia, Municipio, Direccion, CP, Telefono, Email, CapacidadMax, Latitud, Longitud)
    VALUES
      (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
      idTeatro = LAST_INSERT_ID(idTeatro),
      Entidad = VALUES(Entidad),
      Municipio = VALUES(Municipio),
      Direccion = VALUES(Direccion),
      CP = VALUES(CP),
      Telefono = VALUES(Telefono),
      Email = VALUES(Email),
      CapacidadMax = VALUES(CapacidadMax),
      Latitud = VALUES(Latitud),
      Longitud = VALUES(Longitud)
    """
    cursor.execute(sql, valores)
    return cursor.lastrowid

def upsert_obra_get_id(cursor, valores):
    sql = """
    INSERT INTO obras
      (Titulo, Autor, Subtitulo, Anio, UrlDracor)
    VALUES
      (%s, %s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE
      idObra = LAST_INSERT_ID(idObra),
      Titulo = VALUES(Titulo),
      Autor = VALUES(Autor),
      Subtitulo = VALUES(Subtitulo),
      Anio = VALUES(Anio),
      UrlDracor = COALESCE(VALUES(UrlDracor), UrlDracor)
    """
    cursor.execute(sql, valores)
    return cursor.lastrowid

def reset_cartelera_de_hoy(cursor):
    cursor.execute("DELETE FROM horarios WHERE DATE(FechaHora) = CURDATE()")

# ===================== main =====================

def importar_todo(json_teatros_path: str):
    conn = None
    cursor = None

    teatros_insert = 0
    teatros_update = 0
    teatros_skip_sin_sala = 0
    teatros_skip_sin_prov = 0

    imgs_teatro_new = 0
    obras_insert = 0
    obras_update = 0
    imgs_obra_new = 0
    horarios_new = 0

    try:
        log("==== INICIO EJECUCIÓN ====")
        log("Ejecutando provisionalSql.sql ...")
        ejecutar_sql_file_en_servidor("provisionalSql.sql")
        log("✅ provisionalSql.sql ejecutado.")

        conn = mysql.connector.connect(**db_config_db)
        cursor = conn.cursor()

        # ✅ IMPORTANTÍSIMO: asegurar que la clave de teatros es (Sala, Provincia)
        asegurar_unique_teatros_sala_provincia(cursor)

        # ---- TEATROS ----
        log("Importando teatros y generando galerías...")
        data_teatros = cargar_teatros(API_TEATROS_URL, json_teatros_path)

        # detectar duplicados en la propia API por (sala, provincia)
        seen_api = set()

        sql_img_teatro = "INSERT IGNORE INTO imagenes_teatros (idTeatro, RutaImagen) VALUES (%s, %s)"
        teatro_ids = []

        for reg in data_teatros:
            fld = reg.get("fields", reg)

            sala = (fld.get("sala") or "").strip()
            provincia = (fld.get("provincia") or "").strip()

            if not sala:
                teatros_skip_sin_sala += 1
                log_change(f"SKIP teatro sin sala | recordid={reg.get('recordid')}")
                continue
            if not provincia:
                teatros_skip_sin_prov += 1
                log_change(f"SKIP teatro sin provincia | sala={sala} | recordid={reg.get('recordid')}")
                continue

            key = (sala.lower(), provincia.lower())
            if key in seen_api:
                log_change(f"DUPLICADO EN API (sala+prov) | sala={sala} | provincia={provincia} | recordid={reg.get('recordid')}")
                continue
            seen_api.add(key)

            coords = fld.get("coordenadas", None)
            lat = None
            lon = None
            if isinstance(coords, dict):
                lon = coords.get("lon")
                lat = coords.get("lat")
            elif isinstance(coords, (list, tuple)) and len(coords) >= 2:
                lat = coords[0]
                lon = coords[1]

            valores = (
                sala,
                fld.get("entidad"),
                provincia,
                fld.get("municipio"),
                fld.get("direccion"),
                fld.get("cp"),
                fld.get("telefono_s"),
                fld.get("email"),
                random.randint(100, 500),
                lat,
                lon
            )

            existente = obtener_teatro_existente(cursor, sala, provincia)

            # logs de cambios (si existe, comparamos campos “principales”)
            if existente:
                (idT, ent, mun, dire, cp, tel, em, cap, la, lo) = existente
                cambios = []
                def diff(nombre, old, new):
                    if (old or "") != (new or ""):
                        cambios.append(f"{nombre}: '{old}' -> '{new}'")

                diff("Entidad", ent, fld.get("entidad"))
                diff("Municipio", mun, fld.get("municipio"))
                diff("Direccion", dire, fld.get("direccion"))
                diff("CP", cp, fld.get("cp"))
                diff("Telefono", tel, fld.get("telefono_s"))
                diff("Email", em, fld.get("email"))
                # Capacidad es random, no la logueo como cambio “real” (si quieres lo logueo)
                if cambios:
                    log_change(f"UPDATE teatro | sala={sala} | provincia={provincia} | " + " | ".join(cambios))

            t_id = upsert_teatro_get_id(cursor, valores)
            teatro_ids.append(t_id)

            if existente:
                teatros_update += 1
            else:
                teatros_insert += 1

            img_local = buscar_imagen_local(sala, "app/images/teatros")
            if img_local:
                cursor.execute(sql_img_teatro, (t_id, img_local))
                if cursor.rowcount == 1:
                    imgs_teatro_new += 1
            else:
                for i in range(3):
                    img_url = f"https://picsum.photos/seed/teatro_{t_id}_{i}/1200/800"
                    cursor.execute(sql_img_teatro, (t_id, img_url))
                    if cursor.rowcount == 1:
                        imgs_teatro_new += 1

        # ---- OBRAS ----
        log("Descargando obras de Dracor (fallback a obras.json si falla) y asignando posters...")
        data_obras = cargar_obras(API_OBRAS_URL, LOCAL_OBRAS_JSON)

        sql_img_obra = "INSERT IGNORE INTO imagenes_obras (idObra, RutaImagen) VALUES (%s, %s)"
        obra_ids = []

        for play in data_obras[:40]:
            authors = play.get("authors") if isinstance(play, dict) else None
            autor = "Anónimo"
            if isinstance(authors, list) and authors:
                autor = authors[0].get("fullname") or authors[0].get("name") or "Anónimo"
            autor = autor or "Anónimo"

            titulo = play.get("title") if isinstance(play, dict) else None
            subtitulo = play.get("subtitle") if isinstance(play, dict) else None
            anio = play.get("yearNormalized") if isinstance(play, dict) else None
            uri = play.get("uri") if isinstance(play, dict) else None

            # pre-existencia para contar insert/update
            cursor.execute("SELECT idObra FROM obras WHERE (UrlDracor=%s AND %s IS NOT NULL) OR (Titulo=%s AND Autor=%s) LIMIT 1",
                           (uri, uri, titulo, autor))
            existe_obra = cursor.fetchone()

            o_id = upsert_obra_get_id(cursor, (titulo, autor, subtitulo, anio, uri))
            obra_ids.append(o_id)

            if existe_obra:
                obras_update += 1
            else:
                obras_insert += 1

            img_local = buscar_imagen_local(titulo, "app/images/obras")
            if img_local:
                cursor.execute(sql_img_obra, (o_id, img_local))
                if cursor.rowcount == 1:
                    imgs_obra_new += 1
            else:
                for i in range(2):
                    img_url = f"https://picsum.photos/seed/obra_{o_id}_{i}/800/1200"
                    cursor.execute(sql_img_obra, (o_id, img_url))
                    if cursor.rowcount == 1:
                        imgs_obra_new += 1

        # ---- CARTELERA: borrar HOY y regenerar ----
        log("🧹 Reseteando cartelera de HOY y regenerando...")
        reset_cartelera_de_hoy(cursor)

        sql_horario = "INSERT IGNORE INTO horarios (idTeatro, idObra, FechaHora, Precio) VALUES (%s, %s, %s, %s)"
        horas = ["17:00:00", "18:30:00", "19:00:00", "20:30:00", "21:00:00", "22:00:00"]
        fecha_hoy = datetime.now().date()

        for t_id in teatro_ids:
            obras_hoy = random.sample(obra_ids, 6) if len(obra_ids) >= 6 else random.choices(obra_ids, k=6)
            for i in range(6):
                fecha_hora = f"{fecha_hoy} {horas[i]}"
                precio = round(random.uniform(12, 40), 2)
                cursor.execute(sql_horario, (t_id, obras_hoy[i], fecha_hora, precio))
                if cursor.rowcount == 1:
                    horarios_new += 1

        conn.commit()

        cursor.execute("SELECT COUNT(*) FROM teatros")
        total_db = cursor.fetchone()[0]

        log("===================================")
        log("IMPORTACIÓN FINALIZADA")
        log(f"Teatros INSERT: {teatros_insert} | UPDATE: {teatros_update} | TOTAL DB: {total_db}")
        log(f"Teatros SKIP sin sala: {teatros_skip_sin_sala} | sin provincia: {teatros_skip_sin_prov}")
        log(f"Imágenes teatros nuevas: {imgs_teatro_new}")
        log(f"Obras INSERT: {obras_insert} | UPDATE: {obras_update}")
        log(f"Imágenes obras nuevas: {imgs_obra_new}")
        log(f"Horarios nuevos hoy: {horarios_new}")
        log(f"Logs: {RUN_LOG_FILE} (ejecución) | {CHANGE_LOG_FILE} (cambios)")
        log("===================================")
        log("==== FIN EJECUCIÓN ====")

        # ✅ chequeo final: si API dice 31 y DB no, te lo deja en cambios
        if len(data_teatros) != total_db:
            log_change(f"⚠️ DESCUADRE | API teatros={len(data_teatros)} | DB teatros={total_db}. "
                       f"Revisa si sigue existiendo un UNIQUE viejo o si hay salas duplicadas en misma provincia.")

    except Exception as e:
        if conn:
            try:
                conn.rollback()
            except:
                pass
        log(f"❌ Error durante la importación: {e}")
        raise
    finally:
        try:
            if cursor:
                cursor.close()
            if conn and conn.is_connected():
                conn.close()
        except:
            pass

if __name__ == "__main__":
    importar_todo("red_teatros.json")
