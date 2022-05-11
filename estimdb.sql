-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2022 at 02:48 AM
-- Server version: 10.4.21-MariaDB
-- PHP Version: 8.0.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `estimdb`
--
CREATE DATABASE IF NOT EXISTS `estimdb` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `estimdb`;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS category;
CREATE TABLE `category` (
  `id` varchar(20) NOT NULL,
  `name` varchar(20) NOT NULL,
  `description` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`id`, `name`, `description`) VALUES
('cg_0', 'Acción', 'Juegos de lucha y peleas. Basados en ejercicios de repetición (por ejemplo, pulsar un botón para que el personaje ejecute una acción).'),
('cg_1', 'Arcade', 'Juegos de plataformas, laberintos, aventuras. El usuario debe superar pantallas para seguir jugando. Imponen un ritmo rápido y requieren tiempos de reacción mínimos.'),
('cg_2', 'Deportivo', 'Juegos de fútbol, tenis, baloncesto y conducción. Recrean diversos deportes. Requieren habilidad, rapidez y precisión. '),
('cg_3', 'Estrategia', 'Juegos de aventuras, rol, juegos de guerra…Consisten en trazar una estrategia para superar al contrincante. Exigen concentración, saber administrar recursos, pensar y definir estrategias.'),
('cg_4', 'Simulación', 'Juegos de aviones, simuladores de una situación o instrumentales… Permiten experimentar e investigar el funcionamiento de máquinas, fenómenos, situaciones y asumir el mando.'),
('cg_5', 'Juegos de Rol', 'Juegos de habilidad, preguntas y respuestas…La tecnología informática que sustituye al material tradicional del juego y hasta al adversario.'),
('cg_6', 'Juegos musicales', 'Juegos que inducen a la interacción del jugador con la música y cuyo objetivo es seguir los patrones de una canción. ');

-- --------------------------------------------------------

--
-- Table structure for table `friendswith`
--

DROP TABLE IF EXISTS `friendswith`;
CREATE TABLE `friendswith` (
  `senderID` varchar(20) NOT NULL,
  `receiverID` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `friendswith`
--

INSERT INTO `friendswith` (`senderID`, `receiverID`) VALUES
('u_0', 'u_1'),
('u_0', 'u_3'),
('u_0', 'u_4'),
('u_2', 'u_3'),
('u_3', 'u_4');

-- --------------------------------------------------------

--
-- Table structure for table `plays`
--

DROP TABLE IF EXISTS `plays`;
CREATE TABLE `plays` (
  `GameCode` varchar(20) NOT NULL,
  `UserID` varchar(20) NOT NULL,
  `HoursPlayed` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `plays`
--

INSERT INTO `plays` (`GameCode`, `UserID`, `HoursPlayed`) VALUES
('v_0', 'u_3', 5),
('v_1', 'u_1', 606),
('v_10', 'u_1', 1002),
('v_12', 'u_4', 500),
('v_3', 'u_2', 121),
('v_3', 'u_3', 98),
('v_6', 'u_0', 2),
('v_7', 'u_2', 60),
('v_9', 'u_0', 300),
('v_9', 'u_3', 69);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` varchar(20) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `status` varchar(1000) NOT NULL,
  `birthDay` int(11) NOT NULL,
  `birthMonth` int(11) NOT NULL,
  `birthYear` int(11) NOT NULL,
  `profilePic` varchar(1000) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `nickname`, `status`, `birthDay`, `birthMonth`, `birthYear`, `profilePic`) VALUES
('u_0', 'gamerBoy99', 'Soy un gamer REAL.', 10, 11, 2000, ''),
('u_1', 'JuanitoElBueno', 'Soy el más bueno de los que hay!!!!', 2, 5, 1983, 'media/img/usuarios/juanito.png'),
('u_2', 'ElMuchachoDeLosOjosTristes', '\"Ni una simple sonrisa, ni un poco de luz\r\nEn sus ojos profundos\r\nNi siquiera reflejo de algún pensamiento\r\nQue alegre su mundo\r\n\r\nHay tristeza en sus ojos hablando y callando\r\nY bailando conmigo\r\nUna pena lejana que llega a mi alma\r\nY se hace cariño\"', 1, 1, 1981, 'media/img/usuarios/muchacho.png'),
('u_3', 'pepito', 'Hey! Im using whatsapp.', 29, 8, 1999, ''),
('u_4', 'maripili', 'Holii! Soy Maripili!', 19, 11, 1973, 'media/img/usuarios/mari.png');

-- --------------------------------------------------------

--
-- Table structure for table `videogames`
--

DROP TABLE IF EXISTS `videogames`;
CREATE TABLE `videogames` (
  `code` varchar(20) NOT NULL,
  `name` varchar(20) NOT NULL,
  `category_id` varchar(20) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `price` double NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `videogames`
--

INSERT INTO `videogames` (`code`, `name`, `category_id`, `description`, `price`) VALUES
('v_0', 'Celeste', 'cg_1', 'Ayuda a Madeline a sobrevivir a los demonios de su interior en su viaje hasta la cima de la montaña Celeste, en este ajustadísimo plataforma, obra de los creadores de TowerFall. Enfréntate a cientos de desafíos diseñados a mano, devela retorcidos secretos y, y reconstruye el misterio de la montaña.', 19.99),
('v_1', 'Undertale', 'cg_5', 'Bienvenido a UNDERTALE. En este juego de rol, controlas a un humano que cae bajo tierra en el mundo de los monstruos. Ahora debes encontrar la salida... o quedarte atrapado para siempre.', 9.99),
('v_10', 'Hollow Knight', 'cg_0', '¡Forja tu propio camino en Hollow Knight! Una aventura épica a través de un vasto reino de insectos y héroes que se encuentra en ruinas. Explora cavernas tortuosas, combate contra criaturas corrompidas y entabla amistad con extraños insectos, todo en un estilo clásico en 2D dibujado a mano.', 14.99),
('v_11', 'Hades', 'cg_0', 'Desafía al dios de los muertos y protagoniza una salvaje fuga del Inframundo en este juego de exploración de mazmorras de los creadores de Bastion, Transistor y Pyre.', 20.99),
('v_12', 'Hatsune Miku VR', 'cg_6', '¡Hatsune Miku, la cantante virtual mundialmente famosa, te invita a su juego musical de RV! Consigue una puntuación perfecta mientras canta y baila al ritmo de sus canciones más conocidas.', 22.99),
('v_13', 'Rocket League', 'cg_2', '¡Te damos la bienvenida a este híbrido de alta potencia que mezcla fútbol de estilo arcade y vehículos caóticos!\r\n¡personaliza tu coche, salta al campo y compite en uno de los juegos deportivos mejor valorados de todos los tiempos!', 0),
('v_2', 'Stardew Valley', 'cg_4', 'Acabas de heredar la vieja parcela agrícola de tu abuelo de Stardew Valley. Decides partir hacia una nueva vida con unas herramientas usadas y algunas monedas. ¿Te ves capaz de vivir de la tierra y convertir estos campos descuidados en un hogar próspero?', 13.99),
('v_3', 'CupHead', 'cg_1', 'Cuphead es un juego de acción clásico estilo \\\"dispara y corre\\\" que se centra en combates contra el jefe. Inspirado en los dibujos animados de los años 30, los aspectos visual y sonoro están diseñados con esmero empleando las mismas técnicas de la época, es decir, animación tradicional a mano, fondos de acuarela y grabaciones originales de jazz.', 19.99),
('v_4', 'Untitled Goose Game', 'cg_0', 'Untitled Goose Game\" es un juego bufonesco de tipo \"sandbox\" (no lineal) y de sigilo o acción furtiva en el que tú eres un ganso suelto haciendo fechorías por una aldea desprevenida. Date una vuelta por el pueblo, desde los patios de las casas hasta las tiendas de la calle mayor o los jardines, gastando bromas, robando gorros, dando graznidos y fastidiando a todo el mundo en general.\r\n', 16.79),
('v_5', 'Papers, Please', 'cg_3', '¡Enhorabuena!\r\nSu nombre ha salido elegido en la lotería de trabajo.Preséntese inmediatamente ante el Ministerio de Admisiones, en el puesto fronterizo de Grestin.Se le proporcionará una vivienda de clase 8 en Grestin Oriental, en la cual podrá alojarse junto a su familia.Gloria a Arstotzka.\r\n', 8.99),
('v_6', 'Unpacking', 'cg_1', 'Unpacking es un juego relajante de lógica acerca de la sensación familiar de sacar pertenencias de cajas para colocarlas en un nuevo hogar. Mitad juego de bloques, mitad juego de decoración, podrás crear habitaciones agradables mientras descubres pistas de la vida que estás desempacando.', 19.99),
('v_7', 'Little Nightmares', 'cg_3', '¡Sumérgete en la enigmática atmósfera de Little Nightmares y enfréntate a los miedos de tu infancia! Ayuda a Seis a escapar de Las Fauces, un misterioso navío donde moran ánimas corrompidas en busca de su próxima comida...\r\n', 19.99),
('v_8', 'Ori and the Blind Fo', 'cg_3', '\"Ori and the Blind Forest\" cuenta la historia de un joven huérfano destinado a realizar heroicas hazañas, todo ello en un juego de acción y plataforma visualmente impresionante de Moon Studios.', 4.99),
('v_9', 'Journey', 'cg_3', 'Explora el antiguo y misterioso mundo de Journey a medida que vuelas por ruinas y planeas por desiertos de arena para descubrir sus secretos.', 12.49);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `friendswith`
--
ALTER TABLE `friendswith`
  ADD PRIMARY KEY (`senderID`,`receiverID`),
  ADD KEY `FRIENDSWITH_RECEIVER_FK` (`receiverID`);

--
-- Indexes for table `plays`
--
ALTER TABLE `plays`
  ADD PRIMARY KEY (`GameCode`,`UserID`),
  ADD KEY `PLAYS_USER_FK` (`UserID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `videogames`
--
ALTER TABLE `videogames`
  ADD PRIMARY KEY (`code`),
  ADD KEY `VIDEOGAMES_CATEGORY_FK` (`category_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `friendswith`
--
ALTER TABLE `friendswith`
  ADD CONSTRAINT `FRIENDSWITH_RECEIVER_FK` FOREIGN KEY (`receiverID`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FRIENDSWITH_SENDER_FK` FOREIGN KEY (`senderID`) REFERENCES `user` (`id`);

--
-- Constraints for table `plays`
--
ALTER TABLE `plays`
  ADD CONSTRAINT `PLAYS_USER_FK` FOREIGN KEY (`UserID`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `PLAYS_VIDEOGAME_FK` FOREIGN KEY (`GameCode`) REFERENCES `videogames` (`code`);

--
-- Constraints for table `videogames`
--
ALTER TABLE `videogames`
  ADD CONSTRAINT `VIDEOGAMES_CATEGORY_FK` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
