import json
import mysql.connector
import random
import requests
from datetime import datetime

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
            valores = (fld.get("sala"), fld.get("entidad"), fld.get("provincia"), fld.get("municipio"),
                       fld.get("direccion"), fld.get("cp"), fld.get("telefono_s"), fld.get("email"),
                       random.randint(100, 500), coords[0], coords[1])
            cursor.execute(sql_teatro, valores)
            t_id = cursor.lastrowid
            teatro_ids.append(t_id)

            # Insertar 3 imágenes aleatorias por teatro
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
        # Limitamos a las primeras 40 obras para no saturar la cartelera inicial
        for play in data_obras[:40]:
            autor = play["authors"][0]["fullname"] if play.get("authors") else "Anónimo"
            valores_obra = (play.get("title"), autor, play.get("subtitle"), 
                           play.get("yearNormalized"), play.get("uri"))
            
            cursor.execute(sql_obra, valores_obra)
            o_id = cursor.lastrowid
            obra_ids.append(o_id)

            # Insertar 2 imágenes (posters) por obra
            for i in range(2):
                img_url = f"https://picsum.photos/seed/obra_{o_id}_{i}/800/1200"
                cursor.execute(sql_img_obra, (o_id, img_url))

        # --- 3. GENERAR HORARIOS ---
        print("Sincronizando cartelera...")
        sql_horario = "INSERT INTO horarios (idTeatro, idObra, FechaHora) VALUES (%s, %s, %s)"
        horas_disponibles = ["17:00:00", "18:30:00", "19:00:00", "20:30:00", "21:00:00", "22:00:00"]
        fecha_hoy = datetime.now().date()

        for t_id in teatro_ids:
            # Seleccionamos 6 obras aleatorias para cada teatro
            obras_hoy = random.sample(obra_ids, 6)
            for i in range(6):
                fecha_hora = f"{fecha_hoy} {horas_disponibles[i]}"
                cursor.execute(sql_horario, (t_id, obras_hoy[i], fecha_hora))

        conn.commit()
        print("\n" + "="*30)
        print("IMPORTACIÓN FINALIZADA CON ÉXITO")
        print(f"Teatros: {len(teatro_ids)} (con 3 imágenes c/u)")
        print(f"Obras: {len(obra_ids)} (con 2 imágenes c/u)")
        print(f"Sesiones creadas: {len(teatro_ids) * 6}")
        print("="*30)

    except Exception as e:
        print(f"Error durante la importación: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    importar_todo("red_teatros.json")