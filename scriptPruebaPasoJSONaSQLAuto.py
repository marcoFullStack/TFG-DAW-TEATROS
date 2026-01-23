# pip install mysql-connector-python
import json
import mysql.connector
import random

# 1. Configuración de la conexión a la base de datos
db_config = {
    "host": "localhost",
    "user": "root",     # Cambia esto por tu usuario de MySQL
    "password": "", # Cambia esto por tu contraseña
    "database": "red_teatros_regional"
}

def importar_teatros(json_file):
    try:
        # Conectar a MySQL
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()

        # Leer el archivo JSON
        with open(json_file, 'r', encoding='utf-8') as f:
            data = json.load(f)

        # Preparar la consulta SQL de inserción
        sql = """INSERT INTO teatros 
                 (Sala, Entidad, Provincia, Municipio, Direccion, CP, Telefono, Email, CapacidadMax, Latitud, Longitud) 
                 VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"""

        print(f"Iniciando importación de {len(data)} registros...")

        for registro in data:
            fields = registro.get("fields", {})
            
            # Extraer coordenadas (vienen como [lat, long])
            coordenadas = fields.get("coordenadas", [None, None])
            
            # Generar capacidad máxima aleatoria entre 0 y 50
            capacidad_random = random.randint(0, 50)

            # Preparar los valores para la fila
            valores = (
                fields.get("sala"),
                fields.get("entidad"),
                fields.get("provincia"),
                fields.get("municipio"),
                fields.get("direccion"),
                fields.get("cp"),
                fields.get("telefono_s"),
                fields.get("email"),
                capacidad_random,
                coordenadas[0], # Latitud
                coordenadas[1]  # Longitud
            )

            cursor.execute(sql, valores)

        # Confirmar los cambios
        conn.commit()
        print(f"¡Éxito! Se han importado {cursor.rowcount} teatros correctamente.")

    except mysql.connector.Error as err:
        print(f"Error de base de datos: {err}")
    except FileNotFoundError:
        print("Error: No se encontró el archivo red_teatros.json")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

if __name__ == "__main__":
    importar_teatros("red_teatros.json")