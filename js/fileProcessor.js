"use strict";

class FileProcessor {

    constructor() {
        this.usuarios = [];
        this.amistades = [];
        this.error = false;
    }

    processFile() {
        this.file = document.getElementById("inputFile").files[0];
        var archivo = document.getElementById("inputFile").files[0];
        var tipoTexto = /text.*/;
        if (archivo.type.match(tipoTexto)) {
            var lector = new FileReader();
            lector.onload = this.processOnLoad;
            lector.readAsText(archivo);
        }
        else {
            showError("El documento debe ser XML.");
        }
    }

    processOnLoad(event) {
        let text = event.target.result;
        fileProcessor.processText(text);
    }

    processText(text) {
        this.text = text;

        // Parseamos el XML para acceder al mismo de forma mas cómoda.
        var parser = new DOMParser();
        var xmlDoc = parser.parseFromString(this.text, "text/xml");

        Array.from(xmlDoc.getElementsByTagName("usuarios")[0].children).forEach((node) => {
            if (node.tagName === "usuario") {
                this.processUsuario(node);
            }
        })

        this.printOutput();

        if (!this.error) {
            $("textarea").val(this.output);
            document.getElementById("submitProcess").disabled = false;
        }
    }

    processUsuario(userNode) {
        let id = userNode.getAttribute("id");
        let nickname = userNode.getAttribute("nickname");

        let usuario = null;
        if (this.usuarios[id] != null) {
            if (this.usuarios[id].nickname != nickname) {
                this.showError("¡No puede haber dos usuarios diferentes con la misma id!");
            }
            usuario = this.usuarios[id];
        } else {
            usuario = new UsuarioXML(id, nickname);
        }

        Array.from(userNode.children).forEach((node) => {
            switch (node.tagName) {
                case "datos":
                    this.processDatos(usuario, node);
                    break;
                case "amigos":
                    this.processAmigos(usuario, node);
                    break;
            }
        })

        this.usuarios[usuario.id] = usuario;
        
        return usuario;
    }

    processDatos(usuario, dataNode) {

        Array.from(dataNode.children).forEach((node) => {
            switch (node.tagName) {
                case "fechaNacimiento":
                    let day = node.getAttribute("dia");
                    let month = node.getAttribute("mes");
                    let year = node.getAttribute("año");

                    usuario.setDate(day, month, year);
                    break;

                case "estado":
                    let status = node.getAttribute("mensaje");

                    usuario.setStatus(status);
                    break;

                case "fotoPerfil":
                    let profilePic = node.getAttribute("href");

                    usuario.setProfilePic(profilePic);
                    break;
            }
        })
    }

    processAmigos(usuario, friendsNode) {

        Array.from(friendsNode.children).forEach((node) => {
            if (node.tagName === "usuario") {
                let amigo = this.processUsuario(node);
                this.amistades.push(new AmistadXML(usuario.id, amigo.id));
            }
        })
    }

    printOutput() {
        if (this.error) {
            return;
        }

        let text = "{\n\"XML\": {\n";
        
        // Usuarios encontrados
        text += "\"usuarios\": ["
        this.usuarios.forEach((usuario) => {
            text += "\n{\n"
            text += "\"id\": \"" + usuario.id + "\",\n"
            text += "\"nickname\": \"" + usuario.nickname + "\""
            if (usuario.day != null) {
                text += ",\n\"birthDay\": \"" + usuario.day + "\""
                text += ",\n\"birthMonth\": \"" + usuario.month + "\""
                text += ",\n\"birthYear\": \"" + usuario.year + "\""
            }
            if (usuario.status != null) {
                text += ",\n\"status\": \"" + usuario.status + "\""
            }
            if (usuario.profilePic != null) {
                text += ",\n\"profilePic\": \"" + usuario.profilePic + "\""
            }
            text += "\n},"
        })
        text = text.slice(0, -1);
        text += "\n],\n"

        // Amistades encontradas
        text += "\"amistades\": ["
        this.amistades.forEach((amistad) => {
            text += "\n{\n"
            text += "\"sender_id\": \"" + amistad.senderID + "\",\n"
            text += "\"receiver_id\": \"" + amistad.receiverID + "\"\n"
            text += "},"
        })
        text = text.slice(0, -1);
        text += "\n]\n"

        this.output = text + "}\n}";
    }

    showError(error) {
        $("textarea").val("ERROR: " + error);
        this.error = true;
    }
}

class UsuarioXML {
    constructor(id, nickname) {
        this.id = id;
        this.nickname = nickname;
    }

    setDate(day, month, year) {
        this.day = day;
        this.month = month;
        this.year = year;
    }

    setStatus(status) {
        this.status = status;
    }

    setProfilePic(profilePic) {
        this.profilePic = profilePic;
    }
}

class AmistadXML {
    constructor(senderID, receiverID) {
        this.senderID = senderID;
        this.receiverID = receiverID;
    }
}

let fileProcessor = new FileProcessor();