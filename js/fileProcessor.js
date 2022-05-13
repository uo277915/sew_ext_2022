"use strict";

/**
 * Clase encargada de procesar archivos.
 * @author uo277915
 */
class FileProcessor {

    /**
     * Inicializa las variables necesarias de la clase.
     */
    constructor() {
        
        /**
         * @type {UsuarioXML[]}
         * Lista de usuarios a añadir. 
         */
        this.usuarios = [];
        
        /**
         * @type {AmistadXML[]}
         * Lista de amistades a añadir. 
         */
        this.amistades = [];
        
        /**
         * @type {boolean}
         * Marca si se ha encontrado o no un error. 
         */
        this.error = false;
    }

    /**
     * Procesa un archivo 
     */
    processFile() {
        // Tomamos el archivo del input.
        var archivo = document.getElementById("inputFile").files[0];

        // Indicamos que el archivo debe ser XML.
        var tipoTexto = /text.xml/;

        if (archivo.type.match(tipoTexto)) {
            var lector = new FileReader();
            lector.onload = this.processOnLoad;
            lector.readAsText(archivo);
        }
        else {
            showError("El documento debe ser XML.");
        }
    }

    /**
     * Procesa un archivo tras leerse.
     * @param {ProgressEvent<FileReader>} event Evento obtenido tras leer.
     */
    processOnLoad(event) {
        let text = event.target.result;
        fileProcessor.processText(text);
    }

    /**
     * Procesa una string de texto.
     * @param {string} text Texto a procesar
     */
    processText(text) {
        this.text = text;

        // Parseamos el XML para acceder al mismo de forma mas cómoda.
        var parser = new DOMParser();
        var xmlDoc = parser.parseFromString(this.text, "text/xml");

        // Accedemos a cada nodo hijo de "usuarios".
        Array.from(xmlDoc.getElementsByTagName("usuarios")[0].children).forEach((node) => {
            if (node.tagName === "usuario") {
                // Procesamos el nodo como usuario.
                this.processUsuario(node);
            }
        })

        // Generamos el output obtenido en la página.
        this.printOutput();

        //Si no hay error lo mostramos.
        if (!this.error) {
            $("textarea").val(this.output);
            document.getElementById("submitProcess").disabled = false;
        }
    }

    /**
     * Procesa un nodo de usuario y lo convierte en una clase UsuarioXML.
     * @param {Element} userNode Nodo representando un usuario.
     * @returns {UsuarioXML} Un usuario procesado.
     */
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

        // Accedemos a cada nodo hijo de "usuario".
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

    /**
     * Procesa la tag datos de un usuario.
     * @param {UsuarioXML} usuario Usuario que esta siendo procesado.
     * @param {Element} dataNode Nodo de la etiqueta de 'Datos'. 
     */
    processDatos(usuario, dataNode) {

        // Accedemos a cada nodo hijo de "datos".
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

    /**
     * Procesa la tag amigos de un usuario.
     * @param {UsuarioXML} usuario Usuario que esta siendo procesado.
     * @param {Element} friendsNode Nodo de la etiqueta 'Amigos'.
     */
    processAmigos(usuario, friendsNode) {

        // Accedemos a cada nodo hijo de "Amigos".
        Array.from(friendsNode.children).forEach((node) => {
            if (node.tagName === "usuario") {
                let amigo = this.processUsuario(node);
                this.amistades.push(new AmistadXML(usuario.id, amigo.id));
            }
        })
    }

    /**
     * Convierte los datos obtenidos tras el procesamiento en un JSON.
     * @returns {string} Una string definiendo un JSON con los datos a añadir a la base.
     */
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

/**
 * Clase que representa un usuario del XML.
 * @author uo277915
 */
class UsuarioXML {

    /**
     * inicializa los datos más básicos del usuario.
     * @param {string} id Identificador del usuario. 
     * @param {string} nickname Apodo del usuario.
     */
    constructor(id, nickname) {
        this.id = id;
        this.nickname = nickname;
    }

    /**
     * Establece la fecha de nacimiento del usuario.
     * @param {number} day día de nacimiento.
     * @param {number} month mes de nacimiento.
     * @param {number} year año de nacimiento.
     */
    setDate(day, month, year) {
        this.day = day;
        this.month = month;
        this.year = year;
    }

    /**
     * Establece el estado del usuario.
     * @param {string} status 
     */
    setStatus(status) {
        this.status = status;
    }

    /**
     * Establece la foto de perfil del usuario.
     * @param {string} profilePic 
     */
    setProfilePic(profilePic) {
        this.profilePic = profilePic;
    }
}

/**
 * Clase que representa un amigo del XML.
 * @author uo277915
 */
class AmistadXML {
    /**
     * 
     * @param {string} senderID ID del usuario que inició amistad. 
     * @param {string} receiverID ID del segundo usuario de la amistad.
     */
    constructor(senderID, receiverID) {
        this.senderID = senderID;
        this.receiverID = receiverID;
    }
}

/**
 * Procesador de archivos general.
 * @type {FileProcessor}
 */
let fileProcessor = new FileProcessor();

// @uo277915