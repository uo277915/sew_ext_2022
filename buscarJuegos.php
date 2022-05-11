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
        $gameController = new VideoGameController();

        $searchTerm = "";
        $filter = "";

        if(isset($_GET['submitFilter'])){
            $filter = $_GET['selection'];
        }

        echo "
        <h1> Buscador de Juegos </h1>
        
        <section>
        <form action='' method='get'>
            <input type=text name='searchTerm' />
            <input type=submit value='buscar' name='submitSearch'/>
        </form>
        
        <form action='' method='get'>
            <select name='selection'>";

        echo "<option value=''> Categoría </option>";
        foreach ($_SESSION['categorías'] as $cat) {
            $selected = $cat->id === $filter? "selected" : "";
            echo "<option value='$cat->id' $selected > $cat->name </option>";
        }
        
        echo " </select selected=''>
        <input type=submit value='filtrar' name='submitFilter'/>
        </form>";
        
        if(isset($_GET['submitSearch'])){
            $searchTerm = $_GET['searchTerm'];
            echo "<p> Buscando el termino: <i>\"$searchTerm\"</i> </p>";
        } 

        echo "</section>";
        
        $videojuegos = $dbController->getGames($searchTerm, $filter);
        $gameController->showGames($videojuegos);
        
        class VideoGameController {
            public function showGames($videojuegos) {
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

    class DataBaseController { 
        private $serverName;
        private $username;
        private $password;
        private $db;
        private $dbName;

        private $juegos;

        public function __construct() {
            $this->serverName = 'localhost';
            $this->username = 'DBUSER2021';
            $this->password = 'DBPSWD2021';
            $this->dbName = 'EsTimDB';

            $this->createDB();   
            $this->init();
        }

        private function createDB(){
            $conn =new mysqli('localhost', 'DBUSER2021', 'DBPSWD2021' , '');
            
            $result = mysqli_query($conn,"SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = 'estimdb'");

            if ($result->num_rows == 0) {
                $sqlScript = file('estimdb.sql');
                $query = '';
                foreach ($sqlScript as $line)	{
                    
                    $startWith = substr(trim($line), 0 ,2);
                    $endWith = substr(trim($line), -1 ,1);
                    
                    if (empty($line) || $startWith == '--' || $startWith == '/*' || $startWith == '//') {
                        continue;
                    }
                        
                    $query = $query . $line;
                    if ($endWith == ';') {
                        mysqli_query($conn,$query);
                        $query= '';		
                    }
                }
            }
        }

        private function init(){
            $this->loadGenres();
            $this->loadGames();
            $this->loadUsers();
            $this->loadPlays();
        }

        public function reloadDatabase() {
            $conn =new mysqli($this->serverName, $this->username, $this->password , '');
            
            $result = mysqli_query($conn,"SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = 'estimdb'");

            if ($result->num_rows != 0) {
                mysqli_query($conn,"DROP DATABASE $this->dbName");
            }

            $sqlScript = file('estimdb.sql');
            $query = '';
            foreach ($sqlScript as $line) {
                
                $startWith = substr(trim($line), 0 ,2);
                $endWith = substr(trim($line), -1 ,1);
                
                if (empty($line) || $startWith == '--' || $startWith == '/*' || $startWith == '//') {
                    continue;
                }
                    
                $query = $query . $line;
                if ($endWith == ';') {
                    mysqli_query($conn,$query);
                    $query= '';		
                }
            }

            $conn->close();

            $_SESSION['juegos'] = array();
            $_SESSION['usuarios'] = array();
            $_SESSION['categorías'] = array();

            $this->init();
        }
        
        private function loadGames() {
            if (empty($_SESSION['juegos'])){
                $_SESSION['juegos'] = array();
                $this->connectDB();

                $sql = "SELECT * FROM videogames";
                $result = $this->db->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $_SESSION['juegos'][$row["code"]] = new VideoJuego($row["code"], $row["name"], $_SESSION["categorías"][$row["category_id"]], $row["description"], $row["price"]);
                    }
                }
                
                $this->db->close();
            }
        }
        
        private function loadGenres() {
            if (empty($_SESSION['categorías'])){
                $_SESSION['categorías'] = array();
                $this->connectDB();

                $sql = "SELECT * FROM category";
                $result = $this->db->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $_SESSION['categorías'][$row["id"]] = new Categoria($row["id"], $row["name"], $row["description"]);
                    }
                }
                
                $this->db->close();
            }
        }
        
        private function loadUsers() {
            if (empty($_SESSION['usuarios'])){
                $_SESSION['usuarios'] = array();
                $this->connectDB();

                $sql = "SELECT * FROM user";
                $result = $this->db->query($sql);
                
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $_SESSION['usuarios'][$row["id"]] = new Usuario($row["id"], $row["nickname"], $row["status"], $row["birthDay"], $row["birthMonth"], $row["birthYear"], $row["profilePic"]);
                    }
                }
                
                $this->db->close();
            }
        }
        
        private function loadPlays() {
            $this->connectDB();

            foreach ($_SESSION['juegos'] as $videogame){
                $sql = "SELECT * FROM plays WHERE GameCode = '$videogame->code'";
                $result = $this->db->query($sql);
                
                $plays = array();

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $plays[] = new Plays($_SESSION['usuarios'][$row['UserID']], $row['HoursPlayed']);
                    }
                }

                $videogame->setPlays($plays);
            }
            
            $this->db->close();
            
        }

        private function connectDB() {

            $this->db = new mysqli($this->serverName,
                                    $this->username,
                                    $this->password,
                                    $this->dbName);
            
        }

        public function getGames($searchTerm, $filter) {
            $this->connectDB();

            $videojuegos = array();

            $sql = "SELECT code FROM videogames";
            $keyword = "WHERE";

            if (trim($searchTerm) !== "") {
                $sql .= " $keyword name = '$searchTerm'";
                $keyword = "AND";
            }

            if (trim($filter) !== "") {
                $sql .= " $keyword category_id = '$filter'";
                $keyword = "AND";
            }

            $result = $this->db->query($sql);
            
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    // Guardamos en la nueva lista los juegos buscados por el usuario.
                    $videojuegos[$row["code"]] = $_SESSION['juegos'][$row["code"]];
                }
            }

            return $videojuegos;
            
            $this->db->close();
        }
    }

    class VideoJuego {
            
        public $code;
        public $name;
        public $category;
        public $description;
        public $price;
        public $plays = array();

        public function __construct($code, $name, $category, $description, $price) {
            $this->code = $code;
            $this->name = $name;
            $this->category = $category;
            $this->description = $description;
            $this->price = $price;
        }

        public function setPlays($plays) {
            $this->plays = $plays;
        }

    }

    class Categoria {
            
        public $id;
        public $name;
        public $description;

        public function __construct($id, $name, $description) {
            $this->id = $id;
            $this->name = $name;
            $this->description = $description;
        }
    }

    class Usuario {
            
        public $id;
        public $nickname;
        public $status;
        public $birthDay;
        public $birthMonth;
        public $birthYear;
        public $profilePic;

        public function __construct($id, $nickname, $status, $birthDay, $birthMonth, $birthYear, $profilePic) {
            $this->id = $id;
            $this->nickname = $nickname;
            $this->status = $status;
            $this->birthDay = $birthDay;
            $this->birthMonth = $birthMonth;
            $this->birthYear = $birthYear;
            $this->profilePic = $profilePic;
        }

    }

    class Plays {
        public $user;
        public $timePlayed;

        public function __construct($user, $timePlayed) {
            $this->user = $user;
            $this->timePlayed = $timePlayed;
        }
    }

    ?>

</body>

<footer>
    <p> Hecho por: Andrés Martínez, uo277915. </p>
</footer>

</html>