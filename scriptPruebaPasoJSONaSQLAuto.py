import json
import mysql.connector
import random
import requests
from datetime import datetime, timedelta

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
        with open(json_teatros, 'r', encoding='utf-8') as f:
            data_teatros = json.load(f)

        sql_teatro = """INSERT INTO teatros 
                        (Sala, Entidad, Provincia, Municipio, Direccion, CP, Telefono, Email, CapacidadMax, Latitud, Longitud) 
                        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""
        
        teatro_ids = []
        for reg in data_teatros:
            fld = reg.get("fields", {})
            coords = fld.get("coordenadas", [None, None])
            valores = (fld.get("sala"), fld.get("entidad"), fld.get("provincia"), fld.get("municipio"),
                       fld.get("direccion"), fld.get("cp"), fld.get("telefono_s"), fld.get("email"),
                       random.randint(10, 50), coords[0], coords[1])
            cursor.execute(sql_teatro, valores)
            teatro_ids.append(cursor.lastrowid)

        # --- 2. IMPORTAR OBRAS (Desde API) ---
        print("Descargando obras de Dracor...")
        response = requests.get("https://dracor.org/api/v1/corpora/span")
        data_obras = response.json().get("plays", [])

        sql_obra = """INSERT INTO obras (Titulo, Autor, Subtitulo, Anio, UrlDracor) 
                      VALUES (%s, %s, %s, %s, %s)"""
        
        obra_ids = []
        for play in data_obras:
            autor = play["authors"][0]["fullname"] if play.get("authors") else "Anónimo"
            valores_obra = (
                play.get("title"),
                autor,
                play.get("subtitle"),
                play.get("yearNormalized"),
                play.get("uri")
            )
            cursor.execute(sql_obra, valores_obra)
            obra_ids.append(cursor.lastrowid)

        # --- 3. GENERAR HORARIOS (6 obras por teatro) ---
        print("Generando horarios...")
        sql_horario = "INSERT INTO horarios (idTeatro, idObra, FechaHora) VALUES (%s, %s, %s)"
        
        horas_disponibles = ["12:00:00", "16:00:00", "18:00:00", "19:30:00", "21:00:00", "22:30:00"]
        fecha_hoy = datetime.now().date()

        for t_id in teatro_ids:
            # Seleccionamos 6 obras aleatorias para este teatro
            obras_hoy = random.sample(obra_ids, 6)
            for i in range(6):
                fecha_hora = f"{fecha_hoy} {horas_disponibles[i]}"
                cursor.execute(sql_horario, (t_id, obras_hoy[i], fecha_hora))

        conn.commit()
        print(f"Importación finalizada:")
        print(f"- {len(teatro_ids)} Teatros")
        print(f"- {len(obra_ids)} Obras")
        print(f"- {len(teatro_ids) * 6} Sesiones de horarios creadas")

    except Exception as e:
        print(f"Error: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    importar_todo("red_teatros.json")