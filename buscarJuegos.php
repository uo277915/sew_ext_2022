<!DOCTYPE HTML>

<html lang="es">
<head>
    <!-- Datos que describen el documento -->
    <meta charset="UTF-8"/>
    <title>Es Tim! - Registro de juegos</title>
    <meta name="description" content="Buscador de juegos que se pueden encontrar en la base de datos de la página para jugar a ellos.">
    <meta name="author" content="Andrés Martínez Rodríguez, UO277915">
    <meta name="keywords" content="videogames, games, steam, gaming, juegos, videojuegos, jugar">
    <meta name="viewport" content="width=device-width, initial scale=1.0"/>
    <link rel="icon" href="media/img/logo.png" type="image/x-icon"/>
    <link rel="stylesheet" type="text/css" href="css/general.css"/>
</head>

<body>

    <header>
        <nav>
            <ul>
                <li><a href="/"> <img src="media/img/logo.png" alt="Logo de la página web"/> </a></li>
                <li><a href="index.html"> Inicio </a></li>
                <li><a href="historiaVideojuegos.html"> Historia </a></li>
                <li><a href="opinion.html"> Tu Opinión </a></li>
                <li><a href="eventos.html"> Información Eventos </a></li>
                <li><a href="infoJuegos.html"> Clasificaciones Juegos </a></li>
                <li><a href="buscarJuegos.php"> Buscador Juegos </a></li>
                <li><a href="amigos.php"> Amigos </a></li>
            </ul>
        </nav>

        <h1> Buscador de Juegos </h1>
        
        <section>
            <p> En esta página puedes buscar los juegos que tenemos en el sistema. Puedes filtrar utilizando las herramientas a continuación. </p>
            <p> Recuerda que si tienes problemas con la base de datos, puedes reiniciarla al final de esta página. </p>
        </section>
    </header>

    <?php
    // Iniciamos una sesión para evitar acceder a la base de datos lo más posible.
    session_start();


    /** Controlador de la base de datos general */
    $dbController = new DataBaseController();

    // Si el usuario pide recargar la base de datos, la recargamos.
    if (isset($_POST["reloadDB"])) {
        $dbController->reloadDatabase();
    }

    /** Controlador de la videojuegos general */
    $gameController = new VideoGameController();

    // Inicializamos el término de búsqueda y filtro.
    $searchTerm = "";
    $filter = "";

    // Si el usuario ha seleccionado un filtro o buscado algo lo guardamos
    if (isset($_GET["submitSearch"])) {
        $searchTerm = $_GET["searchTerm"];

        if (trim($searchTerm) == "") {
            $filter = $_GET["selection"];
        }
    }

    // Mostramos el inicio de la página
    echo "        
        <section>
        <h2> ¡Usa estas herramientas para buscar el juego que estás buscando! </h2>
        <form action='#' method='get'>
            <label for='searchTerm'> Busca con la siguiente barra de búsqueda. </label>
            <input type=text name='searchTerm' id='searchTerm' />

            <label for='selection'> Puedes filtrar el genero con esta lista. </label>
            <select name='selection' id='selection'>";

    echo "<option value=''> Categoría </option>";

    // Mostramos todas las categorías de la Base de datos (guardadas en sesión)
    foreach ($_SESSION["categorías"] as $cat) {
        // Si el usuario había elegido una la marcamos como seleccionada.
        $selected = $cat->id === $filter ? "selected" : "";
        echo "<option value='$cat->id' $selected > $cat->name </option>";
    }

    echo " </select selected=''>
        <input type=submit value='buscar' name='submitSearch'/>
        </form>";


    echo "</section>";

    if (trim($searchTerm) != "") {
        echo "<p> Buscando el termino: <i>\"$searchTerm\"</i> </p>";
    }
    if ($filter != '') {
        echo "<p> Se esta filtrando por género. </p>";
    }

    // Obtenemos los juegos de la base de datos con el termino a buscar y el filtro seleccionados.
    /** Videojuegos que cumplen con la petición del usuario */
    $videojuegos = $dbController->getGames($searchTerm, $filter);

    // Mostramos los juegos en pantalla.
    $gameController->showGames($videojuegos);

    /**
     * Clase encargada de controlar los videojuegos.
     * @author uo277915
     */
    class VideoGameController
    {
        /**
         * Muestra los videojuegos en la página.
         *
         * @param VideoJuego[] $videojuegos Lista de videojuegos a mostrar.
         */
        public function showGames($videojuegos)
        {
            foreach ($videojuegos as $juego) {
                $category = $juego->category;
                echo "
                        <section>
                            <h2> $juego->name </h2>
                            <p><i> \"$juego->description\" </i></p>
                            <img src='media/img/juegos/$juego->code.png' alt='Imagen del juego $juego->name' onerror=\"this.src='media/img/juegos/default.png'\" />
                            <p> <h3>Género:</h3> <i> $category->name </i> </p>
                            <p> <h3>Precio:</h3> <i> $juego->price €</i> </p>";
                if (count($juego->plays) > 0) {
                    echo "
                            <h3> ¡Algunos jugadores que lo han disfrutado! </h3>
                            <ul>";

                    foreach ($juego->plays as $play) {
                        $user = $play->user;
                        echo "<li> <b>$user->nickname</b> ha jugado $play->timePlayed horas! </li>";
                    }
                    echo "</ul>";
                }
                echo "</section>";
            }
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
         * Lista de juegos.
         * @var VideoJuego[]
         */
        private $juegos;

        /**
         * Inicializa los datos de la base.
         */
        public function __construct()
        {
            $this->serverName = "localhost";
            $this->username = "DBUSER2021";
            $this->password = "DBPSWD2021";
            $this->dbName = "estimdb";

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
            $this->loadPlays();
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
        }

        /**
         * Carga los usuarios que juegan a cada juego de la base de datos en la sesión.
         */
        private function loadPlays()
        {
            $this->connectDB();

            foreach ($_SESSION["juegos"] as $videogame) {
                $sql = "SELECT * FROM plays WHERE GameCode = '$videogame->code'";
                $result = $this->db->query($sql);

                $plays = [];

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $plays[] = new Plays(
                            $_SESSION["juegos"][$row["GameCode"]],
                            $_SESSION["usuarios"][$row["UserID"]],
                            $row["HoursPlayed"]
                        );
                    }
                }

                $videogame->setPlays($plays);
            }

            $this->db->close();
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

        /**
         * Devuelve los juegos de la base de datos pasados los filtros.
         *
         * @param string $searchTerm Filtro de búsqueda de texto.
         * @param string $filter Filtro de género.
         * @return VideoJuego[] Videojuegos que cumplen los criterios.
         */
        public function getGames($searchTerm, $filter)
        {
            $this->connectDB();

            $videojuegos = [];

            $sql = "SELECT code FROM videogames";
            $keyword = "WHERE";

            if (trim($searchTerm) !== "") {
                $sql .= " $keyword name LIKE '%$searchTerm%'";
                $keyword = "AND";
            }

            if (trim($filter) !== "") {
                $sql .= " $keyword category_id = '$filter'";
                $keyword = "AND";
            }

            $sql .= " ORDER BY name ";

            $result = $this->db->query($sql);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    // Guardamos en la nueva lista los juegos buscados por el usuario.
                    $videojuegos[$row["code"]] =
                        $_SESSION["juegos"][$row["code"]];
                }
            }

            return $videojuegos;

            $this->db->close();
        }
    }

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
    ?>

    <form action='#' method='POST'>
        <input type="submit" name="reloadDB" value="Recargar Base de datos" />
    </form>

    <footer>
    <p> Hecho por: Andrés Martínez, uo277915. </p>
    </footer>

</body>


</html>