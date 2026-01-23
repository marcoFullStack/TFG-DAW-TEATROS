async function peticion(url){
const respuesta=await fetch(url);
const datos=await respuesta.text();
// parsear a xml
// Dom parser pasa a tipo dom(accesible desde dom)
const parser=new DOMParser();
// a partir de texto me crea fichero en el formato que indique
const datosXML=parser.parseFromString(datos,"text/xml")
// console.log(datosXML)
return datosXML;
 
}
// Error cors
// peticion("https://objetos.estaticos-marca.com/rss/futbol/primera-division.xml")
// Acceso en local
const noticiasXML=await peticion("datos.xml");
// XML es como objeto document de HTML
const items=noticiasXML.querySelectorAll("item");
const main=document.querySelector("main");
const mainFragment=document.createDocumentFragment();
  Array.from(items).forEach(i=>{
    // console.log(i)
    const title=i.querySelector("title");
    const description=i.querySelector("description")
    // capturar etiqueta media:thumbnails con querySelector da error
    // const thumb=items.querySelector("media:thumbail");
    const thumb=i.getElementsByTagName("media:thumbnail")[0];
 
    // construir elementos del DOM que constituyen cada noticia
    const article=document.createElement("article");
    const h4=document.createElement("h4")
    const h5=document.createElement("h5")
    const img=document.createElement("img")
   
    h4.textContent=title.textContent;
    // html si tiene htmls
    h5.innerHTML=description.textContent;
    img.src=thumb.getAttribute("url");
    img.width=thumb.getAttribute("width");
    img.height=thumb.getAttribute("height")
    article.append(h4,h5,img);
    mainFragment.append(article)
})  
main.append(mainFragment)









/*async function peticion(url) {
  try {
    const respuesta = await fetch(url);
    if (!respuesta.ok) {
      throw new Error("Error en la respuesta");
    } else {
      const datos = await respuesta.json();
      loader.textContent = "Datos cargados";
      // tenemos que realizar el tratamiento de los datos dentro de la función de petición
      // porque, si tratamos los datos fuera de la función con datos.JSON=await peticion(.....) y los datos no están en el primer nivel
      // de indentacion, dará error, aunque sea un módule el script desde donde se llame, porque los datos
      // dependen del handler submit
      tratamientoDeDatos(datos);
    }
  } catch (error) {
    console.error("Error al pedir los datos", error);
  }
}
 
const tratamientoDeDatos = (datosImportadosJSON) => {
  // Limpiamos el contenedor antes de pintar nuevos resultados
  main.innerHTML = "";
 
  const resultados = datosImportadosJSON.results;
  let contador = 0;
 
  resultados.forEach((bibliotecas) => {
    //Crear estructura DOM a visualizar para contener los datos
    const article = document.createElement("article");
    const nombre = document.createElement("p");
    const localidad = document.createElement("p");
    const direccion = document.createElement("p");
    const divmap = document.createElement("div");
   
    // Importante: Leaflet necesita que el div tenga una altura definida
    divmap.style.height = "200px";
    divmap.style.width = "100%";
 
    contador++;
    divmap.setAttribute("id", `map${contador}`);
    nombre.textContent = bibliotecas.nombre_entidad;
    localidad.textContent = bibliotecas.localidad;
    direccion.textContent = bibliotecas.direccion;
 
    article.append(nombre, localidad, direccion, divmap);
    main.append(article);
    // los mapas no dejan fragment
    crearMapa(divmap, bibliotecas.latitud, bibliotecas.longitud_final);
  });
};
 
const crearMapa = (div, lat, lon) => {
  var map = L.map(div.getAttribute("id")).setView([lat, lon], 13);
  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution:
      '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map);
};
 
const main = document.getElementsByTagName("main")[0];
const loader = document.getElementById("loader");
// formulario
const formulario = document.forms[0];
const select = document.getElementById("provincias");
 
// Definimos el handler para poder esperar a la petición
const handlerSubmit =  (ev) => {
  ev.preventDefault();
  loader.textContent="--Cargando datos--"
  // console.log(select.value)
  // const fragmento = document.createDocumentFragment();
  // no puedo hacer async aqui porque no es lvl1 la funcion
 
  // Usamos el value del select directamente en la URL
  const provinciaSeleccionada = select.value;
 
   peticion(
    `https://analisis.datosabiertos.jcyl.es/api/explore/v2.1/catalog/datasets/bibliotecas-bibliobuses-y-puntos-de-servicio-movil-geolocalizados/records?refine=provincia%3A%22${provinciaSeleccionada}%22&refine=tipo%3A%22Biblioteca%22`,
  );
};
 
formulario.addEventListener("submit", handlerSubmit);*/


