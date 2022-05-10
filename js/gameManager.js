"use strict";

const APIURL = "https://www.speedrun.com/api/v1/";

// Juegos
const gameNames = ["Celeste", "Undertale", "Minecraft", "Stardew Valley", "Cuphead"];
const gameDescription = ["Ayuda a Madeline a sobrevivir a sus demonios internos en su viaje a la cima de la montaña Celeste, en este juego de plataformas súper ajustado hecho a mano de los creadores del clásico multijugador TowerFall."
,"Bienvenido a UNDERTALE. En este juego de rol, controlas a un humano que cae bajo tierra en el mundo de los monstruos. Ahora debes encontrar la salida... o quedarte atrapado para siempre."
,"Minecraft es un videojuego de construcción de tipo «mundo abierto» o sandbox creado originalmente por el sueco Markus Persson (conocido comúnmente como «Notch»),​ y posteriormente desarrollado por Mojang Studios (actualmente parte de Microsoft)."
,"Acabas de heredar la vieja parcela agrícola de tu abuelo de Stardew Valley. Decides partir hacia una nueva vida con unas herramientas usadas y algunas monedas. ¿Te ves capaz de vivir de la tierra y convertir estos campos descuidados en un hogar próspero?",
"Cuphead es un juego de acción clásico estilo \"dispara y corre\" que se centra en combates contra el jefe. Inspirado en los dibujos animados de los años 30, los aspectos visual y sonoro están diseñados con esmero empleando las mismas técnicas de la época, es decir, animación tradicional a mano, fondos de acuarela y grabaciones originales de jazz."];
const gameIDs = ["o1y9j9v6", "4d73n317", "j1npme6p", "9d3q7e1l", "w6jmm26j"];
// Estas ids se podrían sacar con la API, pero por no complicar mucho el código los decidí meter a mano.
const leaderboardIDs = ["7kjpl1gk", "02qgm7jd", "wkpn0vdr", "zdn81wed", "q25qoe82"]

class GameManager {

    selectGame() {
        this.eventID = $("select").val();

        this.name = gameNames[this.eventID];
        this.description = gameDescription[this.eventID];
        this.gameID = gameIDs[this.eventID];
        this.leaderboardID = leaderboardIDs[this.eventID];

        speedrunAPIManager.getGameData(this.gameID).then(
            (data) => this.#printData(data)
        );

    }

    async #printData(data) {

        console.log(data);

        let content = "";

        content += "<h2><a href=" + data["weblink"] + ">" + this.name + "</a></h2>" +
            "<h3> Fecha de salida: </h3>" +
            "<p>" + data["release-date"] + "</p>" +
            "<img src=" + data["assets"]["cover-large"]["uri"] + " alt='Imagen del juego'/>" +
            "<h3> Descripción: </h3>" +
            "<p>" + this.description + "</p>" +
            "<h3> Géneros: </h3>" +
            "<ul>";

            if(data.genres.length > 0){
        for (const genreID of data.genres) {
            let genre = await speedrunAPIManager.getGenre(genreID);
            content += "<li>" + genre + "</li>";
        }} else {
            content += "<li> No especificado </li>";
        }

        content += "</ul>";

        let leaderboard = await speedrunAPIManager.getLeaderboard(this.gameID, this.leaderboardID);

        content += "<h3> <a href=" + leaderboard["weblink"] + "> Clasificación: </a> </h3>";

        for (let i = 0; i < 3; i++) {
            let run = leaderboard["runs"][i];
            let user = run.run.players[0].rel === "user" ? await speedrunAPIManager.getPlayer(run.run.players[0].id) : run.run.players[0].name;
            console.log(run);
            content += "<h4> #" + run.place + " - " + user + " </h4>"
            content += "<p> Tiempo: " + Utils.sToTime(run.run.times["primary_t"]) + " </p>"
            if (run.run.comment !== null){
                content += "<p> Comentario del usuario: <i>\"" + run.run.comment + "\"</i> </p>"
            }
            content += "<p> Fecha de subida: " + run.run.date + " </p>"
            content += "<p> <a href='" + run.run.videos.links[0].uri + "'> Video del record </a></p>"
        }

        $("body>section>section").html(
            content
        );
    }

}

class SpeedrunAPIManager {

    async getGameData(gameId) {
        this.url = APIURL + "games/" + gameId;

        return await this.loadData().then((data) => {
            return data.data;
        }
        );
    }

    async getGenre(genreId) {
        this.url = APIURL + "genres/" + genreId;

        return await this.loadData().then((data) => {
            return data.data.name;
        }
        );
    }

    async getLeaderboard(gameId, leaderboardId) {
        this.url = APIURL + "leaderboards/" + gameId + "/category/" + leaderboardId;

        return await this.loadData().then((data) => {
            return data.data;
        }
        );
    }

    async getPlayer(userId) {
        console.log(userId)
        this.url = APIURL + "users/" + userId;

        return await this.loadData().then((data) => {
            return data.data.names.international;
        }
        );
    }

    async loadData() {
        let dataObtained = {};

        await $.ajax({
            dataType: "json",
            url: this.url,
            method: 'GET',
            success: function (data) {
                dataObtained = data;
            },
            error: function () {
                $("body > section > section").html("<p> Ha habido un problema obteniendo datos desde <a href='https://www.speedrun.com/'>Speedrun.com </a></p>");
            }
        });

        return dataObtained;
    }

}

class Utils {
    static sToTime(duration) {
        return new Date(duration * 1000).toISOString().substring(11, 23);
      }
}

let speedrunAPIManager = new SpeedrunAPIManager();
let gameManager = new GameManager();