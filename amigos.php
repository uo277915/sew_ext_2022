<!DOCTYPE HTML>

<html lang="es">
<head>
    <!-- Datos que describen el documento -->
    <meta charset="UTF-8"/>
    <title>Es Tim! - Relaciones de Amistad</title>
    <meta name="description" content="Esta página contiene un registro de los usuarios del sistema y de sus relaciones de amistad. En ella también
            puedes procesar un archivo de amistad para registrar nuevas relaciones.">
    <meta name="author" content="Andrés Martínez Rodríguez, UO277915">
    <meta name="keywords" content="videogames, games, steam, gaming, juegos, videojuegos, jugar">
    <meta name="viewport" content="width=device-width, initial scale=1.0"/>
    <link rel="icon" href="media/img/logo.png" type="image/x-icon"/>
    <link rel="stylesheet" type="text/css" href="css/general.css"/>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="js/fileProcessor.js"></script>
</head>

<nav>
    <ul>
        <li><a href="index.html"> <img src="media/img/logo.png" alt="Logo de la página web"/> </a></li>
        <li><a href="index.html"> Inicio </a></li>
        <li><a href="historiaVideojuegos.html"> Historia </a></li>
        <li><a href="opinion.html"> Tu Opinión </a></li>
        <li><a href="eventos.html"> Información Eventos </a></li>
        <li><a href="infoJuegos.html"> Información Juegos </a></li>
        <li><a href="buscarJuegos.php"> Buscador Juegos </a></li>
        <li><a href="amigos.php"> Amigos </a></li>
    </ul>
</nav>

<body>
    <?php
    // Iniciamos una sesión para evitar acceder a la base de datos lo más posible.
    session_start();

    /** Controlador de la base de datos general */
    $dbController = new DataBaseController();
    /** Controlador de la videojuegos general */
    $userController = new UserController();

    // Inicializamos el texto de la area de texto.
    $textAreaText = "";

    // Si el usuario ha elegido procesar el XML lo procesamos
    if (isset($_POST["submitProcess"])) {
        $input = $_POST["processedFile"];
        $textAreaText = $userController->processUsers($input, $dbController);
    }

    // Si el usuario pide recargar la base de datos, la recargamos.
    if (isset($_POST["reloadDB"])) {
        $dbController->reloadDatabase();
    }

    // Mostramos el inicio de la página
    echo "
        <h1> Relaciones de Amistad </h1>
        
        <section>
            <form action='' method='post'>
                <fieldset>
                    <legend> Introduce tu archivo aquí </legend>
                    <input type=file id='inputFile' name='inputFile' onchange='fileProcessor.processFile()' />
                </fieldset>

                <fieldset>
                    <legend> Información del proceso </legend>
                    <textArea readonly name='processedFile' rows=5 cols=50>$textAreaText</textArea>
                </fieldset>

                <input type=submit value='Añadir al sistema' name='submitProcess' id='submitProcess' disabled/>
            </form>
        </section>";

    // Mostramos los usuarios.
    $userController->showUsers($_SESSION["usuarios"]);

    #region Controladores

    /**
     * Clase que controla los usuarios
     * @author uo277915
     */
    class UserController
    {
        /**
         * Muestra los usuarios en la página.
         *
         * @param Usuario[] $users Lista de usuarios a mostrar.
         */
        public function showUsers($users)
        {
            foreach ($users as $user) {
                $favGamePlay = $user->favGamePlay;
                echo "
                        <section>
                            <h2> $user->nickname </h2>
                            <p><i> \"$user->status\" </i></p>
                            <img src='$user->profilePic' alt='Imagen de $user->nickname' onerror=\"this.src='media/img/usuarios/default.png'\" />
                            <p> <h3>Cumpleaños:</h3> <i> $user->birthDay/$user->birthMonth/$user->birthYear</i> </p>";

                if ($user->favGamePlay !== null) {
                    $game = $user->favGamePlay->game;
                    $hours = $user->favGamePlay->timePlayed;

                    echo "<h3> Su juego favorito es: </h3>
                            <p> <b> $game->name <b> - ¡Ha jugado $hours horas! <p> 
                            ";
                }

                if (count($user->friends) > 0) {
                    echo "
                            <h3> Este usuario es amigo de: </h3>
                            <ul>";

                    foreach ($user->friends as $friend) {
                        echo "<li><b>$friend->nickname</b></li>";
                    }
                    echo "</ul>";
                }
                echo "</section>";
            }
        }

        /**
         * Procesa el JSON obtenido de ECMAScript.
         *
         * @param string $input El texto con el JSON.
         * @param DataBaseController $dbController El controlador de la base de datos.
         * @return string El texto después de procesar.
         */
        public function processUsers($input, $dbController)
        {
            // Descodificamos la string a JSON.
            $json = json_decode($input, true);

            // Procesamos cada usuario.
            foreach ($json["XML"]["usuarios"] as $user) {
                $id = $user["id"];
                $nickname = $user["nickname"];
                $birthDay = null;
                $birthMonth = null;
                $birthYear = null;
                $status = null;
                $profilePic = "";

                if (array_key_exists("birthDay", $user)) {
                    $birthDay = $user["birthDay"];
                    $birthMonth = $user["birthMonth"];
                    $birthYear = $user["birthYear"];
                }

                if (array_key_exists("status", $user)) {
                    $status = $user["status"];
                }

                if (array_key_exists("profilePic", $user)) {
                    $profilePic = $user["profilePic"];
                }

                // Creamos los usuarios
                $dbController->createUser(
                    new Usuario(
                        $id,
                        $nickname,
                        $status,
                        $birthDay,
                        $birthMonth,
                        $birthYear,
                        $profilePic
                    )
                );
            }

            // Procesamos las amistades
            foreach ($json["XML"]["amistades"] as $friends) {
                $senderID = $friends["sender_id"];
                $receiverID = $friends["receiver_id"];

                $dbController->createFriendship($senderID, $receiverID);
            }

            $dbController->updateFriends();

            return $dbController->getOutput();
        }
    }

    
    /**
     * Clase encargada de controlar la base de datos.
     * @author uo277915
     */
    class DataBaseController
    {
         /**
         * Nombre del servidor
         * @var string
         */
        private $serverName;
        /**
         * Usuario de la base de datos
         * @var string
         */
        private $username;
        /**
         * contraseña de la base de datos
         * @var string
         */
        private $password;
        /**
         * Nombre de la base de datos
         * @var string
         */
        private $dbName;
        /**
         * Puntero a la base de datos
         * @var mysqli
         */
        private $db;

        /**
         * Inicializa los datos de la base.
         */
        public function __construct()
        {
            $this->serverName = "localhost";
            $this->username = "DBUSER2021";
            $this->password = "DBPSWD2021";
            $this->dbName = "EsTimDB";
            $this->output = "";

            $this->createDB();
            $this->init();
        }

        /**
         * Creamos la base de datos si no existe aún.
         */
        private function createDB()
        {
            $conn = new mysqli("localhost", "DBUSER2021", "DBPSWD2021", "");

            $result = mysqli_query(
                $conn,
                "SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = 'estimdb'"
            );

            if ($result->num_rows == 0) {
                $sqlScript = file("estimdb.sql");
                $query = "";
                foreach ($sqlScript as $line) {
                    $startWith = substr(trim($line), 0, 2);
                    $endWith = substr(trim($line), -1, 1);

                    if (
                        empty($line) ||
                        $startWith == "--" ||
                        $startWith == "/*" ||
                        $startWith == "//"
                    ) {
                        continue;
                    }

                    $query = $query . $line;
                    if ($endWith == ";") {
                        mysqli_query($conn, $query);
                        $query = "";
                    }
                }
            }
        }

        /**
         * Inicializa las diferentes variables de la sesión.
         */
        private function init()
        {
            $this->loadGenres();
            $this->loadGames();
            $this->loadUsers();

            $this->loadPlaysForUser();
            $this->loadFriends();
        }

       /**
         * Reinicia la base de datos.
         */
        public function reloadDatabase()
        {
            // Nos conectamos a SQL.
            $conn = new mysqli(
                $this->serverName,
                $this->username,
                $this->password,
                ""
            );

            // Comprobamos si ya existe la base.
            $result = mysqli_query(
                $conn,
                "SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = 'estimdb'"
            );

            // Si existe la borramos
            if ($result->num_rows != 0) {
                mysqli_query($conn, "DROP DATABASE $this->dbName");
            }

            // Creamos la base de datos desde 0.
            $sqlScript = file("estimdb.sql");
            $query = "";
            foreach ($sqlScript as $line) {
                $startWith = substr(trim($line), 0, 2);
                $endWith = substr(trim($line), -1, 1);

                if (
                    empty($line) ||
                    $startWith == "--" ||
                    $startWith == "/*" ||
                    $startWith == "//"
                ) {
                    continue;
                }

                $query = $query . $line;
                if ($endWith == ";") {
                    mysqli_query($conn, $query);
                    $query = "";
                }
            }

            // Cerramos la conexión.
            $conn->close();

            // Reiniciamos las variables de sesión.
            $_SESSION["juegos"] = [];
            $_SESSION["usuarios"] = [];
            $_SESSION["categorías"] = [];

            $this->init();
        }

        /**
         * Carga los juegos de la base de datos en la sesión.
         */
        private function loadGames()
        {
            if (empty($_SESSION["juegos"])) {
                $_SESSION["juegos"] = [];
                $this->connectDB();

                $sql = "SELECT * FROM videogames";
                $result = $this->db->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $_SESSION["juegos"][$row["code"]] = new VideoJuego(
                            $row["code"],
                            $row["name"],
                            $_SESSION["categorías"][$row["category_id"]],
                            $row["description"],
                            $row["price"]
                        );
                    }
                }

                $this->db->close();
            }
        }

        /**
         * Carga los géneros de la base de datos en la sesión.
         */
        private function loadGenres()
        {
            if (empty($_SESSION["categorías"])) {
                $_SESSION["categorías"] = [];
                $this->connectDB();

                $sql = "SELECT * FROM category";
                $result = $this->db->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $_SESSION["categorías"][$row["id"]] = new Categoria(
                            $row["id"],
                            $row["name"],
                            $row["description"]
                        );
                    }
                }

                $this->db->close();
            }
        }

        /**
         * Carga los usuarios de la base de datos en la sesión.
         */
        private function loadUsers()
        {
            if (empty($_SESSION["usuarios"])) {
                $this->forceLoadUsers();
            }
        }

        /**
         * Carga los usuarios de la base de datos de forma forzosa en la sesión.
         */
        private function forceLoadUsers()
        {
            $_SESSION["usuarios"] = [];
            $this->connectDB();

            $sql = "SELECT * FROM user";
            $result = $this->db->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $_SESSION["usuarios"][$row["id"]] = new Usuario(
                        $row["id"],
                        $row["nickname"],
                        $row["status"],
                        $row["birthDay"],
                        $row["birthMonth"],
                        $row["birthYear"],
                        $row["profilePic"]
                    );
                }
            }

            $this->db->close();
        }

        /**
         * Carga los juegos que juega cada usuario de la base de datos en la sesión.
         */
        private function loadPlaysForUser()
        {
            $this->connectDB();

            foreach ($_SESSION["usuarios"] as $user) {
                $sql = "SELECT * FROM plays WHERE userID = '$user->id' ORDER BY HoursPlayed DESC";
                $result = $this->db->query($sql);

                $plays = [];

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $user->setfavGamePlay(
                        new Plays(
                            $_SESSION["juegos"][$row["GameCode"]],
                            $_SESSION["usuarios"][$row["UserID"]],
                            $row["HoursPlayed"]
                        )
                    );
                }
            }

            $this->db->close();
        }

        
        /**
         * Carga las amistades de la base de datos en la sesión.
         */
        private function loadFriends()
        {
            $this->connectDB();

            foreach ($_SESSION["usuarios"] as $user) {
                $sql = "SELECT * FROM friendsWith WHERE senderID = '$user->id' OR receiverID = '$user->id'";

                $result = $this->db->query($sql);

                $friends = [];

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if ($row["senderID"] === $user->id) {
                            $friends[] =
                                $_SESSION["usuarios"][$row["receiverID"]];
                        } else {
                            $friends[] =
                                $_SESSION["usuarios"][$row["senderID"]];
                        }
                    }
                }

                $user->setFriends($friends);
            }

            $this->db->close();
        }

        /**
         * Crea un usuario en la base de datos.
         *
         * @param Usuario $user Usuario a añadir.
         */
        public function createUser($user)
        {
            $old = $this->getUser($user->id);

            if ($old != null) {
                if ($old->nickname != $user->nickname) {
                    $this->output .=
                        "ERROR de BD: No puede haber dos usuarios con la misma ID. \n";
                } else {
                    $this->output .= "El usuario $user->nickname ($user->id) ya existe. \n";
                }
            } else {
                $this->connectDB();

                $sql = "INSERT INTO `user`(`id`, `nickname`, `status`, `birthDay`, `birthMonth`, `birthYear`, `profilePic`) 
                VALUES ('$user->id','$user->nickname','$user->status','$user->birthDay','$user->birthMonth','$user->birthYear','$user->profilePic')";

                if ($this->db->query($sql) === true) {
                    $this->output .= "Usuario $user->nickname ($user->id) añadido correctamente! \n";
                } else {
                    $this->output .= "ERROR de DB: Error al añadir el usuario $user->nickname ($user->id)";
                }

                $this->db->close();
            }
        }

        /**
         * Crea una amistad entre dos usuarios en la base de datos.
         *
         * @param string $senderID ID del usuario que inició amistad. 
         * @param string $receiverID ID del segundo usuario de la amistad.
         */
        public function createFriendship($senderID, $receiverID)
        {
            $exists = $this->existsFriendship($senderID, $receiverID);

            if ($exists) {
                $this->output .= "La relación de amistad entre $senderID y $receiverID ya existía. \n";
            } else {
                $this->connectDB();

                $sql = "INSERT INTO `friendswith`(`senderID`, `receiverID`) VALUES ('$senderID', '$receiverID')";

                if ($this->db->query($sql) === true) {
                    $this->output .= "Relación de amistad entre $senderID y $receiverID añadida correctamente! \n";
                } else {
                    $this->output .= "ERROR de DB: Error al añadir la relación de amistad entre $senderID y $receiverID";
                }

                $this->db->close();
            }
        }

        /**
         * Devuelve un usuario de la base de datos.
         *
         * @param string $id ID del usuario a buscar.
         * @return (Usuario|null) usuario encontrado.
         */
        private function getUser($id)
        {
            $this->connectDB();

            $sql = "SELECT * FROM user WHERE id = $id";
            $result = $this->db->query($sql);

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user = new Usuario(
                    $row["id"],
                    $row["nickname"],
                    $row["status"],
                    $row["birthDay"],
                    $row["birthMonth"],
                    $row["birthYear"],
                    $row["profilePic"]
                );
            } else {
                $user = null;
            }

            $this->db->close();
            return $user;
        }

        /**
         * Comprueba si existe una amistad entre dos usuarios.
         *
         * @param string $senderID ID del usuario que inició amistad. 
         * @param string $receiverID ID del segundo usuario de la amistad.
         * @return boolean whether it exists.
         */
        private function existsFriendship($senderID, $receiverID)
        {
            $this->connectDB();

            $sql = "SELECT * FROM friendsWith WHERE senderID = $senderID AND receiverID = $receiverID";
            $result = $this->db->query($sql);

            if ($result->num_rows > 0) {
                $exists = true;
            } else {
                $exists = false;
            }

            $this->db->close();
            return $exists;
        }

        /**
         * Recarga los amigos de la base de datos.
         */
        public function updateFriends()
        {
            $this->forceLoadUsers();
            $this->loadPlaysForUser();
            $this->loadFriends();
        }

        /**
         * Devuelve el texto del procesado.
         *
         * @return string El texto tras procesar.
         */
        public function getOutput()
        {
            return $this->output;
        }

        /**
         * Conecta al usuario en la base de datos.
         */
        private function connectDB()
        {
            $this->db = new mysqli(
                $this->serverName,
                $this->username,
                $this->password,
                $this->dbName
            );
        }
    }

    #endregion

    #region Clases de objetos

    /**
     * Clase que representa un videojuego.
     * @author uo277915
     */
    class VideoJuego
    {
        /**
         * Código que describe el videojuego.
         *
         * @var string
         */
        public $code;
        /**
         * Nombre del videojuego.
         *
         * @var string
         */
        public $name;
        /**
         * Categoría del videojuego.
         *
         * @var Category
         */
        public $category;
        /**
         * Descripción del videojuego.
         *
         * @var string
         */
        public $description;
        /**
         * Precio del videojuego.
         *
         * @var double
         */
        public $price;
        /**
         * Jugadores que juegan el videojuego.
         *
         * @var Plays[]
         */
        public $plays = [];

        /**
         * Inicializa el VideoJuego
         *
         * @param string $code Código que describe el videojuego.
         * @param string $name Nombre del videojuego.
         * @param Category $category Categoría del videojuego.
         * @param string $description Descripción del videojuego.
         * @param double $price Precio del videojuego.
         */
        public function __construct(
            $code,
            $name,
            $category,
            $description,
            $price
        ) {
            $this->code = $code;
            $this->name = $name;
            $this->category = $category;
            $this->description = $description;
            $this->price = $price;
        }

        /**
         * Establece los jugadores que juegan al juego.
         *
         * @param Play[] $plays Jugadores que juegan el videojuego.
         */
        public function setPlays($plays)
        {
            $this->plays = $plays;
        }
    }

   /**
     * Clase que representa una categoría.
     * @author uo277915
     */
    class Categoria
    {
        /**
         * ID que describe la categoría.
         *
         * @var string
         */
        public $id;
        /**
         * Nombre de la categoría.
         *
         * @var string
         */
        public $name;
        /**
         * Descripción de la categoría.
         *
         * @var string
         */
        public $description;

        /**
         * Inicializa la categoría.
         *
         * @param string $id ID que describe la categoría.
         * @param string $name Nombre de la categoría.
         * @param string $description Descripción de la categoría.
         */
        public function __construct($id, $name, $description)
        {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
        }
    }

    /**
     * Clase que representa un usuario.
     * @author uo277915
     */
    class Usuario
    {
        /**
         * ID que describe el usuario.
         *
         * @var string
         */
        public $id;
        /**
         * Apodo del usuario.
         *
         * @var string
         */
        public $nickname;
        /**
         * Estado del usuario.
         *
         * @var string
         */
        public $status;
        /**
         * Dia del cumpleaños del usuario.
         *
         * @var number
         */
        public $birthDay;
        /**
         * Mes del cumpleaños del usuario.
         *
         * @var number
         */
        public $birthMonth;
        /**
         * Año del cumpleaños del usuario.
         *
         * @var number
         */
        public $birthYear;
        /**
         * Enlace a la foto de perfil del usuario.
         *
         * @var string
         */
        public $profilePic;
        /**
         * Lista de amigos.
         *
         * @var Usuario[]
         */
        public $friends = [];
        /**
         * Juego favorito (con más horas jugadas).
         *
         * @var string
         */
        public $favGamePlay = null;
        
        /**
         * Inicializa el usuario.
         *
         * @param string $id ID que describe el usuario.
         * @param string $nickname Apodo del usuario.
         * @param string $status Estado del usuario.
         * @param number $birthDay Dia del cumpleaños del usuario.
         * @param number $birthMonth Mes del cumpleaños del usuario.
         * @param number $birthYear Año del cumpleaños del usuario.
         * @param string $profilePic Enlace a la foto de perfil del usuario.
         */
        public function __construct(
            $id,
            $nickname,
            $status,
            $birthDay,
            $birthMonth,
            $birthYear,
            $profilePic
        ) {
            $this->id = $id;
            $this->nickname = $nickname;
            $this->status = $status;
            $this->birthDay = $birthDay;
            $this->birthMonth = $birthMonth;
            $this->birthYear = $birthYear;
            $this->profilePic = $profilePic;
        }

        /**
         * Establece las amistades del usuario
         *
         * @param Usuario[] $friends Lista de amistades del usuario.
         */
        public function setFriends($friends)
        {
            $this->friends = $friends;
        }

        /**
         * Establece el juego favorito del usuario.
         *
         * @param VideoJuego $game Juego favorito del usuario.
         */
        public function setfavGamePlay($game)
        {
            $this->favGamePlay = $game;
        }
    }

    /**
     * Clase que representa un usuario jugando a un juego.
     * @author uo277915
     */
    class Plays
    {
        /**
         * Juego que juega el usuario.
         *
         * @var VideoJuego
         */
        public $game;
        /**
         * Usuario que juega el juego.
         *
         * @var Usuario
         */
        public $user;
        /**
         * Tiempo, en horas, que se ha jugado al juego.
         *
         * @var number
         */
        public $timePlayed;

        /**
         * Inicializa la clase.
         *
         * @param VideoJuego $game Juego que juega el usuario.
         * @param Usuario $user Usuario que juega el juego.
         * @param number $timePlayed Tiempo, en horas, que se ha jugado al juego.
         */
        public function __construct($game, $user, $timePlayed)
        {
            $this->game = $game;
            $this->user = $user;
            $this->timePlayed = $timePlayed;
        }
    }

    //@uo277915

#endregion
?>
    <form action='' method='POST'>
        <input type="submit" name="reloadDB" value="Recargar Base de datos" />
    </form>
</body>

<footer>
    <p> Hecho por: Andrés Martínez, uo277915. </p>
</footer>

</html>