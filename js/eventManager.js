
"use strict";

/**
 * @constant
 * @type {string}
 * Clave necesaria para acceder a la API 
 */
const APIKEY = "AmJ_mxX9Oyp9fAL4xZO13xQ-TgFmHkik7fmbjzND4ntCfEG9RYGG1c-3Xobrg3UF";

/**
 * @constant 
 * @type {string}
 * URL inicial común de la API.
 */
const APIURL = "https://dev.virtualearth.net/REST/v1/Imagery/Map/AerialWithLabels?"

//#region EVENTOS

/**
 * @constant 
 * @type {string[]}
 * Lista con los nombres de los posibles eventos a elegir por el usuario
 */
const eventNames = ["E3", "Gamescom", "MineCon"];

/**
 * @constant 
 * @type {string[]}
 * Lista con las descripciones de los posibles eventos a elegir por el usuario
 */
const eventDescription = ["La Electronic Entertainment Expo (también llamada algunas veces como Electronic Entertainment Expo 3, Expo 3 o Expo), más conocida por su abreviatura E3, es la convención de videojuegos más importante de la industria, en la que diversas compañías de videojuegos hablan de sus próximos lanzamientos, y algunas veces de su software y hardware. La exposición solo permitía la entrada a trabajadores de las empresas y periodistas, aunque a partir de 2017, cualquier persona podía acceder si compraba la entrada.", "La Gamescom (estilizada como gamescom) es la feria de electrónica de consumo interactiva más importante de Europa, en especial de videojuegos. Se celebra desde el año 2009 en el centro de convenciones Koelnmesse en Colonia, Alemania.", "MineCon o Minecraft Live es un evento virtual al que se puede acceder desde cualquier lugar del mundo. Estará repleto de noticias sobre el juego y los creadores de contenido, e incluirá una votación de la comunidad que tiene influencia en el juego."];

/**
 * @constant 
 * @type {Date[]}
 * Lista con las descripciones de los posibles eventos a elegir por el usuario
 */
const eventDates = [new Date(2022, 6, 11), new Date(2022, 8, 24), new Date(2022, 8, 31)];

/**
 * @constant 
 * @type {{latitude: number, longitude: number}[]}
 * Lista con las localizaciones de los posibles eventos a elegir por el usuario
 */
const eventLocations = [{ latitude: 34.0403207, longitude: -118.2695624 }, { latitude: 50.94257, longitude: 6.958976 }, { latitude: 28.4287799, longitude: -81.4620917 }];

//#endregion

//#region CLASES

/**
 * Clase encargada de controlar los eventos de la página.
 * @author uo277915
 */
class EventManager {

    /**
     * Indica a la clase que un evento ha sido seleccionado.
     */
    selectEvent() {
        // Tomamos el valor del evento elegido en el 'select'
        this.eventID = $("select").val();

        // Guardamos en la clase las opciones elegidas
        this.name = eventNames[this.eventID];
        this.description = eventDescription[this.eventID];
        this.date = eventDates[this.eventID];
        this.location = eventLocations[this.eventID];

        // Mostramos al usuario los datos
        this.#printData();
    }

    /**
     * Muestra en la página web los datos del evento seleccionado actualmente.
     */
    #printData() {
        // Tomamos la sección de los datos (Sin usar el ID)
        $("body>section>section").html(
            "<h2>" + this.name + "</h2>" +
            // Si no hay geolocation no se añade el botón
            (mapManager.userLocation != null ? "<button onclick=\"mapManager.showDistance(eventManager)\"> Mostrar Distancia </button>" : "") +
            "<button onclick=\"mapManager.showEvent(eventManager)\"> Mostrar Evento </button>" +
            "<button onclick=\"mapManager.showStreet(eventManager)\"> Mostrar Vista de Pájaro </button>" +
            "<h3> Mapa: </h3>" +
            // Se añade al img un onerror por si la API falla el usuario vea una imagen de error.
            "<img src=\"media/img/mapNotAvailable.png\" alt= \"Mapa con el evento seleccionado\" onerror= \"this.src='media/img/mapNotAvailable.png'\" />" +
            "<h3> Descripción: </h3>" +
            "<p>" + this.description + "</p>" +
            "<h3> Cuando será: </h3>" +
            "<p> ¡Dentro de " + Math.ceil((this.date.getTime() - new Date().getTime()) / (1000 * 3600 * 24)) + " días! </p>"
        );

        // Pedimos al controlador del mapa que actualice el mapa.
        this.map = mapManager.updateMap(this);
    }

}

/**
 * Clase encargada de controlar los mapas.
 * @author uo277915
 */
class MapManager {

    /**
     * Toma la posición actual del usuario.
     */
    constructor() {
        navigator.geolocation.getCurrentPosition(
            (loc) => { mapManager.userLocation = loc; },
            (loc) => { mapManager.userLocation = null; }
        );

    }

    /**
     * Actualiza el mapa con el evento seleccionado. 
     * 
     * @param {EventManager} em El controlador de eventos con los datos del mismo.
     * @returns {string} La url del mapa seleccionado.
     */
    updateMap(em) {
        if (mapManager.userLocation != null) {
            // Si tenemos la ubicación del usuario mostramos al mismo y al evento en el mapa
            mapManager.showDistance(em);
        } else {
            // Si no tenemos la localización del usuario mostramos solo el evento en el mapa
            mapManager.showEvent(em);
        }

        return mapManager.url;
    }

    /**
     * Actualiza la imagen en el mapa.
     */
    updateImage() {
        $("body>section>section>img").attr("src", mapManager.url);
    }

    /**
     * Muestra unicamente el evento en el mapa.
     * @param {EventManager} em El controlador de eventos con los datos del mismo.
     */
    showEvent(em) {
        mapManager.url = APIURL
            + "pp=" + em.location.latitude + "," + em.location.longitude + ";;" + em.name
            + "&key=" + APIKEY;

        mapManager.updateImage();
    }

    /**
     * Muestra el evento y el usuario en el mapa.
     * @param {EventManager} em El controlador de eventos con los datos del mismo.
     */
    showDistance(em) {
        if (mapManager.userLocation != null) {
            mapManager.url = APIURL
                + "pp=" + em.location.latitude + "," + em.location.longitude + ";;" + em.name
                + "&pp=" + mapManager.userLocation.coords.latitude + "," + mapManager.userLocation.coords.longitude + ";;" + "Usted"
                + "&key=" + APIKEY;

            mapManager.updateImage();
        } else {
            mapManager.showEvent();
        }
    }

    /**
     * Muestra una vista de pájaro del evento en el mapa.
     * @param {EventManager} em El controlador de eventos con los datos del mismo.
     */
    showStreet(em) {
        mapManager.url = "https://dev.virtualearth.net/REST/V1/Imagery/Map/Birdseye/"
            + em.location.latitude + "," + em.location.longitude
            + "/20?dir=270&ms=900,700&key=" + APIKEY;

        mapManager.updateImage();
    }
}

// Avisamos al usuario si hay algún error.
window.onerror = function (msg) {
    alert("Ha habido un error! -> " + msg);
}
//#endregion

/**
 * @type {EventManager}
 * Controlador de eventos general.
 */
let eventManager = new EventManager();

/**
 * @type {MapManager}
 * Controlador de mapas general.
 */
let mapManager = new MapManager();

// @uo277915