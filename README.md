# 🎭 TFG-DAW-TEATROS  
## Red de Teatros de Castilla y León

Proyecto desarrollado como **Trabajo de Fin de Grado (TFG)** del Ciclo Formativo de Grado Superior en **Desarrollo de Aplicaciones Web (DAW)** en el **IES Galileo**.

La aplicación consiste en una plataforma web que centraliza y gestiona información sobre los **teatros, obras y cartelera de Castilla y León**, utilizando datos abiertos y ofreciendo herramientas de consulta, visualización y análisis para los usuarios.

---

## 📌 Descripción del proyecto

La **Red de Teatros de Castilla y León** es una aplicación web que permite:

- Consultar un **catálogo completo de teatros** por provincia y municipio.
- Visualizar la **cartelera de obras** con fechas, horarios y precios.
- Explorar los teatros mediante un **mapa interactivo**.
- Acceder a **estadísticas y rankings** de uso.
- Interactuar con un **chat inteligente preprogramado**, basado en reglas y consultas controladas a la base de datos.
- Gestionar usuarios y contenidos desde un **panel de administración**.

Los datos se obtienen a partir de **fuentes abiertas** y se almacenan en una base de datos propia para mejorar el rendimiento y la fiabilidad del sistema.

---

## 🧠 Características destacadas

- Backend desarrollado en **PHP** con acceso a base de datos **MySQL**.
- Frontend con **HTML5, CSS3 y JavaScript (Vanilla JS)**.
- Diseño **responsive** y accesible.
- Automatización de carga y actualización de datos mediante **scripts en Python**.
- Uso de **Leaflet** para mapas interactivos y **Chart.js** para visualización de datos.
- Chat inteligente basado en reglas (no IA generativa).
- Despliegue en entorno real mediante **InfinityFree**.

---

## 🗂️ Estructura del proyecto

TFG-DAW-TEATROS/
├── app/
│ ├── api/ # Endpoints JSON (chat, ranking)
│ ├── config/ # Configuración y conexión BBDD
│ ├── DAO/ # Acceso a datos
│ ├── models/ # Modelos de entidades
│ ├── views/ # Vistas (index, user, admin)
│ ├── js/ # Scripts JavaScript
│ ├── styles/ # Hojas de estilo CSS
│ ├── images/ # Recursos gráficos
│ └── uploads/ # Archivos subidos
├── provisionalSql.sql
└── index.php
scriptPruebaPasoJSONaSQLAuto.py


---

## 🗄️ Fuentes de datos

- **Datos abiertos de la Junta de Castilla y León** (Red de Teatros).
- **DraCor API** (corpus de obras teatrales).
- Archivos JSON locales como respaldo en caso de fallo de las APIs.

---

## 🚀 Despliegue

La aplicación está desplegada en un entorno de producción accesible públicamente en:

👉 **https://paginateatros.gt.tc/**

El despliegue se ha realizado en **InfinityFree**, adaptando rutas, estructura de archivos y configuración a las limitaciones del hosting compartido.

---

## 🛠️ Tecnologías utilizadas

- **PHP**
- **MySQL**
- **JavaScript**
- **HTML5 / CSS3**
- **Python**
- **Leaflet**
- **Chart.js**

### Software y herramientas
- Visual Studio Code  
- GitHub  
- Trello  
- XAMPP  
- MySQL Workbench  
- phpMyAdmin  
- Draw.io 
- ClipChamp 

---

## 👥 Autores

Proyecto realizado por el equipo **Teatros Nova**:

- Marco Gómez Zazo  
- Álvaro de Paz Gómez  
- Jaime Rodríguez-Gachs Casero

---

## 📄 Licencia

Este proyecto se ha desarrollado con fines **educativos y académicos** como Trabajo de Fin de Grado.  
No está destinado a uso comercial.

---

## ℹ️ Nota final

Este repositorio refleja el desarrollo completo de una aplicación web realista, incluyendo análisis, diseño, implementación, pruebas y despliegue en producción, siguiendo las buenas prácticas aprendidas durante el ciclo formativo.
