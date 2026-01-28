import json
import mysql.connector
import random
import requests
from datetime import datetime
import os
import re
from difflib import SequenceMatcher

def normalizar_nombre(nombre):
    """Normaliza un nombre para comparación: elimina extensiones, convierte a minúsculas y limpia caracteres especiales"""
    if not nombre:
        return ""
    # Eliminar extensión
    nombre_sin_ext = os.path.splitext(nombre)[0]

    # Decodificar entidades HTML como #U00cd -> Í, #U00d3 -> Ó, etc.
    def reemplazar_unicode(match):
        try:
            codigo = match.group(1)
            return chr(int(codigo, 16))
        except:
            return match.group(0)

    nombre_sin_ext = re.sub(r'#U([0-9A-Fa-f]{4})', reemplazar_unicode, nombre_sin_ext)

    # Convertir a minúsculas y limpiar espacios
    return nombre_sin_ext.lower().strip()

def similitud_cadenas(a, b):
    """Calcula la similitud entre dos cadenas (0.0 a 1.0)"""
    return SequenceMatcher(None, a, b).ratio()

def buscar_imagen_local(nombre_busqueda, carpeta_imagenes, umbral_similitud=0.7):
    """
    Busca una imagen en la carpeta local que coincida con el nombre dado.
    Retorna la ruta relativa de la imagen si la encuentra, None si no.
    """
    if not os.path.exists(carpeta_imagenes):
        return None

    nombre_normalizado = normalizar_nombre(nombre_busqueda)
    mejor_coincidencia = None
    mejor_similitud = 0

    for archivo in os.listdir(carpeta_imagenes):
        if archivo.lower().endswith(('.png', '.jpg', '.jpeg', '.gif', '.webp')):
            nombre_archivo_normalizado = normalizar_nombre(archivo)
            similitud = similitud_cadenas(nombre_normalizado, nombre_archivo_normalizado)

            if similitud > mejor_similitud and similitud >= umbral_similitud:
                mejor_similitud = similitud
                mejor_coincidencia = archivo

    if mejor_coincidencia:
        ruta_completa = os.path.join(carpeta_imagenes, mejor_coincidencia)
        ruta_completa = ruta_completa.replace('\\', '/')
        if ruta_completa.startswith("app/"):
            ruta_completa = "./" + ruta_completa[4:]
        return ruta_completa

    return None

# Configuración de la conexión
db_config = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "red_teatros_regional"
}

def importar_todo(json_teatros):
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()

        # --- 1. IMPORTAR TEATROS ---
        print("Importando teatros y generando galerías...")
        with open(json_teatros, 'r', encoding='utf-8') as f:
            data_teatros = json.load(f)

        sql_teatro = """INSERT INTO teatros
                        (Sala, Entidad, Provincia, Municipio, Direccion, CP, Telefono, Email, CapacidadMax, Latitud, Longitud)
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        sql_img_teatro = "INSERT INTO imagenes_teatros (idTeatro, RutaImagen) VALUES (%s, %s)"

        teatro_ids = []
        for reg in data_teatros:
            fld = reg.get("fields", {})
            coords = fld.get("coordenadas", [None, None])
            valores = (
                fld.get("sala"), fld.get("entidad"), fld.get("provincia"), fld.get("municipio"),
                fld.get("direccion"), fld.get("cp"), fld.get("telefono_s"), fld.get("email"),
                random.randint(100, 500), coords[0], coords[1]
            )
            cursor.execute(sql_teatro, valores)
            t_id = cursor.lastrowid
            teatro_ids.append(t_id)

            # Buscar imagen local del teatro por nombre
            nombre_teatro = fld.get("sala")
            img_local = buscar_imagen_local(nombre_teatro, "app/images/teatros")

            if img_local:
                cursor.execute(sql_img_teatro, (t_id, img_local))
            else:
                for i in range(3):
                    img_url = f"https://picsum.photos/seed/teatro_{t_id}_{i}/1200/800"
                    cursor.execute(sql_img_teatro, (t_id, img_url))

        # --- 2. IMPORTAR OBRAS (Desde API Dracor) ---
        print("Descargando obras de Dracor y asignando posters...")
        response = requests.get("https://dracor.org/api/v1/corpora/span")
        data_obras = response.json().get("plays", [])

        sql_obra = """INSERT INTO obras (Titulo, Autor, Subtitulo, Anio, UrlDracor)
                      VALUES (%s, %s, %s, %s, %s)"""
        sql_img_obra = "INSERT INTO imagenes_obras (idObra, RutaImagen) VALUES (%s, %s)"

        obra_ids = []
        for play in data_obras[:40]:
            autor = play["authors"][0]["fullname"] if play.get("authors") else "Anónimo"
            valores_obra = (
                play.get("title"), autor, play.get("subtitle"),
                play.get("yearNormalized"), play.get("uri")
            )

            cursor.execute(sql_obra, valores_obra)
            o_id = cursor.lastrowid
            obra_ids.append(o_id)

            # Buscar imagen local de la obra por título
            titulo_obra = play.get("title")
            img_local = buscar_imagen_local(titulo_obra, "app/images/obras")

            if img_local:
                cursor.execute(sql_img_obra, (o_id, img_local))
            else:
                for i in range(2):
                    img_url = f"https://picsum.photos/seed/obra_{o_id}_{i}/800/1200"
                    cursor.execute(sql_img_obra, (o_id, img_url))

        # --- 3. GENERAR HORARIOS (AHORA CON PRECIO RANDOM 12..40) ---
        print("Sincronizando cartelera...")
        sql_horario = "INSERT INTO horarios (idTeatro, idObra, FechaHora, Precio) VALUES (%s, %s, %s, %s)"
        horas_disponibles = ["17:00:00", "18:30:00", "19:00:00", "20:30:00", "21:00:00", "22:00:00"]
        fecha_hoy = datetime.now().date()

        sesiones_creadas = 0
        for t_id in teatro_ids:
            obras_hoy = random.sample(obra_ids, 6)
            for i in range(6):
                fecha_hora = f"{fecha_hoy} {horas_disponibles[i]}"
                precio = round(random.uniform(12, 40), 2)  # <-- NUEVO: precio random 12-40
                cursor.execute(sql_horario, (t_id, obras_hoy[i], fecha_hora, precio))
                sesiones_creadas += 1

        conn.commit()
        print("\n" + "=" * 30)
        print("IMPORTACIÓN FINALIZADA CON ÉXITO")
        print(f"Teatros: {len(teatro_ids)} (con 3 imágenes c/u)")
        print(f"Obras: {len(obra_ids)} (con 2 imágenes c/u)")
        print(f"Sesiones creadas: {sesiones_creadas} (con Precio 12-40€)")
        print("=" * 30)

    except Exception as e:
        print(f"Error durante la importación: {e}")
    finally:
        try:
            if conn.is_connected():
                cursor.close()
                conn.close()
        except:
            pass

def actualizar_imagenes_existentes():
    """Actualiza las imágenes de teatros y obras que ya existen en la base de datos"""
    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()

        print("\n" + "=" * 50)
        print("ACTUALIZANDO IMÁGENES EXISTENTES")
        print("=" * 50)

        # --- ACTUALIZAR IMÁGENES DE TEATROS ---
        print("\n🎭 Procesando teatros...")
        cursor.execute("SELECT idTeatro, Sala FROM teatros")
        teatros = cursor.fetchall()

        teatros_actualizados = 0
        for idTeatro, nombreTeatro in teatros:
            img_local = buscar_imagen_local(nombreTeatro, "app/images/teatros")

            if img_local:
                cursor.execute("DELETE FROM imagenes_teatros WHERE idTeatro = %s", (idTeatro,))
                cursor.execute(
                    "INSERT INTO imagenes_teatros (idTeatro, RutaImagen) VALUES (%s, %s)",
                    (idTeatro, img_local)
                )
                print(f"  ✅ {nombreTeatro} -> {img_local}")
                teatros_actualizados += 1
            else:
                print(f"  ⚠️  {nombreTeatro} -> No se encontró imagen")

        # --- ACTUALIZAR IMÁGENES DE OBRAS ---
        print(f"\n🎬 Procesando obras...")
        cursor.execute("SELECT idObra, Titulo FROM obras")
        obras = cursor.fetchall()

        obras_actualizadas = 0
        for idObra, tituloObra in obras:
            img_local = buscar_imagen_local(tituloObra, "app/images/obras")

            if img_local:
                cursor.execute("DELETE FROM imagenes_obras WHERE idObra = %s", (idObra,))
                cursor.execute(
                    "INSERT INTO imagenes_obras (idObra, RutaImagen) VALUES (%s, %s)",
                    (idObra, img_local)
                )
                print(f"  ✅ {tituloObra} -> {img_local}")
                obras_actualizadas += 1
            else:
                print(f"  ⚠️  {tituloObra} -> No se encontró imagen")

        conn.commit()

        print("\n" + "=" * 50)
        print("ACTUALIZACIÓN COMPLETADA")
        print(f"Teatros actualizados: {teatros_actualizados}/{len(teatros)}")
        print(f"Obras actualizadas: {obras_actualizadas}/{len(obras)}")
        print("=" * 50)

    except Exception as e:
        print(f"Error durante la actualización: {e}")
    finally:
        try:
            if conn.is_connected():
                cursor.close()
                conn.close()
        except:
            pass

if __name__ == "__main__":
    import sys

    # Si se pasa el argumento "actualizar", solo actualiza imágenes
    if len(sys.argv) > 1 and sys.argv[1] == "actualizar":
        actualizar_imagenes_existentes()
    else:
        importar_todo("red_teatros.json")
