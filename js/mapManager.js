"use strict";

const APIKEY = "AmJ_mxX9Oyp9fAL4xZO13xQ-TgFmHkik7fmbjzND4ntCfEG9RYGG1c-3Xobrg3UF";
const APIURL = "https://dev.virtualearth.net/REST/v1/Imagery/Map/AerialWithLabels?"

// Eventos
const eventNames = ["E3", "Gamescom", "MineCon"];
const eventDescription = ["La Electronic Entertainment Expo (también llamada algunas veces como Electronic Entertainment Expo 3, Expo 3 o Expo), más conocida por su abreviatura E3, es la convención de videojuegos más importante de la industria, en la que diversas compañías de videojuegos hablan de sus próximos lanzamientos, y algunas veces de su software y hardware. La exposición solo permitía la entrada a trabajadores de las empresas y periodistas, aunque a partir de 2017, cualquier persona podía acceder si compraba la entrada.", "La Gamescom (estilizada como gamescom) es la feria de electrónica de consumo interactiva más importante de Europa, en especial de videojuegos. Se celebra desde el año 2009 en el centro de convenciones Koelnmesse en Colonia, Alemania.", "MineCon o Minecraft Live es un evento virtual al que se puede acceder desde cualquier lugar del mundo. Estará repleto de noticias sobre el juego y los creadores de contenido, e incluirá una votación de la comunidad que tiene influencia en el juego."];
const eventDates = [new Date(2022, 6, 11), new Date(2022, 8, 24), new Date(2022, 8, 31)];
const eventLocations = [{ latitude: 34.0403207, longitude: -118.2695624 }, { latitude: 50.94257, longitude: 6.958976 }, { latitude: 28.4287799, longitude: -81.4620917 }];

class EventManager {

    selectEvent() {
        eventManager.eventID = $("select").val();

        eventManager.name = eventNames[eventManager.eventID];
        eventManager.description = eventDescription[eventManager.eventID];
        eventManager.date = eventDates[eventManager.eventID];
        eventManager.location = eventLocations[eventManager.eventID];

        eventManager.#printData();
    }

    #printData() {
        $("body>section>section").html(
            "<h2>" + eventManager.name + "</h2>" +
            // Si no hay geolocation no se añade el botón
            (mapManager.userLocation != null ? "<button onclick=\"mapManager.showDistance()\"> Mostrar Distancia </button>" : "") +
            "<button onclick=\"mapManager.showEvent()\"> Mostrar Evento </button>" +
            "<button onclick=\"mapManager.showStreet()\"> Mostrar Vista de Pájaro </button>" +
            "<h3> Mapa: </h3>" +
            // Se añade al img un onerror por si la API falla el usuario vea una imagen de error.
            "<img src=\"media/img/mapNotAvailable.png\" alt= \"Mapa con el evento seleccionado\" onerror= \"this.src='media/img/mapNotAvailable.png'\" />" +
            "<h3> Descripción: </h3>" +
            "<p>" + eventManager.description + "</p>" +
            "<h3> Cuando será: </h3>" +
            "<p> ¡Dentro de " + Math.ceil((eventManager.date.getTime() - new Date().getTime()) / (1000 * 3600 * 24)) + " días! </p>"
        );

        eventManager.map = mapManager.updateMap();
    }

}

class MapManager {

    constructor() {
        navigator.geolocation.getCurrentPosition(
            (loc) => { mapManager.userLocation = loc; },
            (loc) => { mapManager.userLocation = null; }
        );

    }

    updateMap() {
        if (mapManager.userLocation != null) {
            mapManager.showDistance();
        } else {
            // Geolocalization does not work
            mapManager.showEvent();
        }

        return mapManager.url;
    }

    updateImage() {
        $("body>section>section>img").attr("src", mapManager.url);
    }

    showEvent() {
        mapManager.url = APIURL
            + "pp=" + eventManager.location.latitude + "," + eventManager.location.longitude + ";;" + eventManager.name
            + "&key=" + APIKEY;

        mapManager.updateImage();
    }

    showDistance() {
        if (mapManager.userLocation != null) {
            mapManager.url = APIURL
                + "pp=" + eventManager.location.latitude + "," + eventManager.location.longitude + ";;" + eventManager.name
                + "&pp=" + mapManager.userLocation.coords.latitude + "," + mapManager.userLocation.coords.longitude + ";;" + "Usted"
                + "&key=" + APIKEY;

            mapManager.updateImage();
        } else {
            mapManager.showEvent();
        }
    }

    showStreet() {
        mapManager.url = "https://dev.virtualearth.net/REST/V1/Imagery/Map/Birdseye/"
            + eventManager.location.latitude + "," + eventManager.location.longitude
            + "/20?dir=270&ms=900,700&key=" + APIKEY;

        mapManager.updateImage();
    }
}

window.onerror = function (msg, url, line) {
    alert("Ha habido un error! -> " + msg);
}

let eventManager = new EventManager();
let mapManager = new MapManager();