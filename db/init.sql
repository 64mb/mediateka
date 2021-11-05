SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- table `category`
--

CREATE TABLE IF NOT EXISTS `category` (
  `id_cat` int(11) NOT NULL AUTO_INCREMENT,
  `value_cat` varchar(500) NOT NULL,
  PRIMARY KEY (`id_cat`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;

--
-- table data`category`
--

INSERT INTO `category` (`id_cat`, `value_cat`) VALUES
(1, 'Комедия'),
(2, 'Драма'),
(3, 'Документальный');

-- --------------------------------------------------------

--
-- table `film`
--

CREATE TABLE IF NOT EXISTS `film` (
  `id_film` int(11) NOT NULL AUTO_INCREMENT,
  `name_film` varchar(750) DEFAULT NULL,
  `age_film` varchar(100) DEFAULT NULL,
  `year_film` varchar(50) DEFAULT NULL,
  `director_film` varchar(500) DEFAULT NULL,
  `scenarist_film` varchar(500) DEFAULT NULL,
  `actors_film` varchar(800) DEFAULT NULL,
  `counrty_film` varchar(250) DEFAULT NULL,
  `minutes_film` varchar(50) DEFAULT NULL,
  `descipt_film` varchar(800) DEFAULT NULL,
  `thumb_film` varchar(500) DEFAULT NULL,
  `add_date_film` date NOT NULL,
  `path_film` varchar(500) DEFAULT NULL,
  `name_cats` varchar(800) NOT NULL,
  PRIMARY KEY (`id_film`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;

--
-- table data`film`
--

INSERT INTO `film` (`id_film`, `name_film`, `age_film`, `year_film`, `director_film`, `scenarist_film`, `actors_film`, `counrty_film`, `minutes_film`, `descipt_film`, `thumb_film`, `add_date_film`, `path_film`, `name_cats`) VALUES
(1, 'Тестовый', '', '', '', '', '', '', '', '', '', '', '', '');


UPDATE `film` SET `age_film` = '0+' WHERE id_film = 1; 
UPDATE `film` SET `year_film` = '2003' WHERE id_film = 1; 
UPDATE `film` SET `director_film` = 'Режисер Режисерович' WHERE id_film = 1; 
UPDATE `film` SET `scenarist_film` = 'Сценарист Сценарио' WHERE id_film = 1; 
UPDATE `film` SET `actors_film` = 'Актер Актеров' WHERE id_film = 1; 
UPDATE `film` SET `counrty_film` = 'Россия' WHERE id_film = 1; 
UPDATE `film` SET `minutes_film` = '2 мин. / 00:02' WHERE id_film = 1; 
UPDATE `film` SET `descipt_film` = 'Тестовый фильм, тестового сожержания' WHERE id_film = 1; 
UPDATE `film` SET `thumb_film` = 'sample' WHERE id_film = 1; 
UPDATE `film` SET `add_date_film` = '2018-11-03' WHERE id_film = 1; 
UPDATE `film` SET `path_film` = 'sample' WHERE id_film = 1; 
UPDATE `film` SET `name_cats` = 'Комедия/Драма' WHERE id_film = 1;

-- --------------------------------------------------------

--
-- table `film_category`
--

CREATE TABLE IF NOT EXISTS `film_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_film` int(11) NOT NULL,
  `id_category` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_category` (`id_category`),
  KEY `film_category_ibfk_2` (`id_film`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

--
-- table data`film_category`
--

INSERT INTO `film_category` (`id`, `id_film`, `id_category`) VALUES
(1, 1, 1),
(2, 1, 2);

-- --------------------------------------------------------

--
-- table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(200) NOT NULL,
  `password` varchar(500) NOT NULL,
  `token_user` varchar(500) NOT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `login` (`login`),
  KEY `token` (`token_user`(255))
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;

--
-- table data`user`
--

INSERT INTO `user` (`id_user`, `login`, `password`, `token_user`) VALUES
(1, 'admin', '$2a$10$DA1GwkdWXgF3D7tFIpjBuOQz5AwL0.pd23Zte8S.J/zzGsiwVIwhK', '');

--
-- table key `film_category`
--
ALTER TABLE `film_category`
  ADD CONSTRAINT `film_category_ibfk_1` FOREIGN KEY (`id_category`) REFERENCES `category` (`id_cat`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `film_category_ibfk_2` FOREIGN KEY (`id_film`) REFERENCES `film` (`id_film`) ON DELETE CASCADE ON UPDATE NO ACTION;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
