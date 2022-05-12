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
    session_start();

    $dbController = new DataBaseController();
    $userController = new UserController();

    $textAreaText = "";

    if (isset($_POST["submitProcess"])) {
        $input = $_POST["processedFile"];
        $textAreaText = $userController->processUsers($input, $dbController);
    }

    if (isset($_POST["reloadDB"])) {
        $dbController->reloadDatabase();
    }

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

    $userController->showUsers($_SESSION["usuarios"]);

    #region Controladores

    class UserController
    {
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

        public function processUsers($input, $dbController)
        {
            $json = json_decode($input, true);

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

            foreach ($json["XML"]["amistades"] as $friends) {
                $senderID = $friends["sender_id"];
                $receiverID = $friends["receiver_id"];

                $dbController->createFriendship($senderID, $receiverID);
            }

            $dbController->updateFriends();

            return $dbController->getOutput();
        }
    }

    class DataBaseController
    {
        private $serverName;
        private $username;
        private $password;
        private $db;
        private $dbName;

        private $juegos;

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

        private function init()
        {
            $this->loadGenres();
            $this->loadGames();
            $this->loadUsers();

            $this->loadPlaysForUser();
            $this->loadFriends();
        }

        public function reloadDatabase()
        {
            $conn = new mysqli(
                $this->serverName,
                $this->username,
                $this->password,
                ""
            );

            $result = mysqli_query(
                $conn,
                "SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = 'estimdb'"
            );

            if ($result->num_rows != 0) {
                mysqli_query($conn, "DROP DATABASE $this->dbName");
            }

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

            $conn->close();

            $_SESSION["juegos"] = [];
            $_SESSION["usuarios"] = [];
            $_SESSION["categorías"] = [];

            $this->init();
        }

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

        private function loadUsers()
        {
            if (empty($_SESSION["usuarios"])) {
                $this->forceLoadUsers();
            }
        }

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

        public function updateFriends()
        {
            $this->forceLoadUsers();
            $this->loadPlaysForUser();
            $this->loadFriends();
        }

        public function getOutput()
        {
            return $this->output;
        }

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

    class VideoJuego
    {
        public $code;
        public $name;
        public $category;
        public $description;
        public $price;
        public $plays = [];

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

        public function setPlays($plays)
        {
            $this->plays = $plays;
        }
    }

    class Categoria
    {
        public $id;
        public $name;
        public $description;

        public function __construct($id, $name, $description)
        {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
        }
    }

    class Usuario
    {
        public $id;
        public $nickname;
        public $status;
        public $birthDay;
        public $birthMonth;
        public $birthYear;
        public $profilePic;
        public $friends = [];
        public $favGamePlay = null;

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

        public function setFriends($friends)
        {
            $this->friends = $friends;
        }

        public function setfavGamePlay($game)
        {
            $this->favGamePlay = $game;
        }
    }

    class Plays
    {
        public $game;
        public $user;
        public $timePlayed;

        public function __construct($game, $user, $timePlayed)
        {
            $this->game = $game;
            $this->user = $user;
            $this->timePlayed = $timePlayed;
        }
    }

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