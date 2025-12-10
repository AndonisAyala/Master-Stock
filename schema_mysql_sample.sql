-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para master-stocks
CREATE DATABASE IF NOT EXISTS `master-stocks` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `master-stocks`;

-- Volcando estructura para tabla master-stocks.categoria
CREATE TABLE IF NOT EXISTS `categoria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla master-stocks.checkproducto
CREATE TABLE IF NOT EXISTS `checkproducto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idProduct` int NOT NULL DEFAULT '0',
  `statusCheck` tinyint NOT NULL DEFAULT (0),
  `dateCheck` datetime NOT NULL DEFAULT (now()),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idProduct` (`idProduct`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla master-stocks.departamento
CREATE TABLE IF NOT EXISTS `departamento` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla master-stocks.imagenes
CREATE TABLE IF NOT EXISTS `imagenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `idProducto` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `nameImg` varchar(50) NOT NULL DEFAULT '0',
  `url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT (now()),
  `hora_subida` time NOT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT (now()),
  `subido` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla master-stocks.invproducto
CREATE TABLE IF NOT EXISTS `invproducto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL,
  `descripcion` text NOT NULL,
  `marca` varchar(100) NOT NULL,
  `posicion` varchar(50) NOT NULL,
  `cantidad` int NOT NULL DEFAULT '0',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla master-stocks.marca
CREATE TABLE IF NOT EXISTS `marca` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descripcion` char(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla master-stocks.producto
CREATE TABLE IF NOT EXISTS `producto` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `idE` char(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci NOT NULL DEFAULT '',
  `descripcion` char(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `autos` char(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `codigo` char(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `marca` int NOT NULL DEFAULT (0),
  `departamento` int NOT NULL DEFAULT (0),
  `categoria` int NOT NULL DEFAULT (0),
  `puesto` char(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `cantidad` int NOT NULL DEFAULT (0),
  `av` int NOT NULL DEFAULT '0',
  `morocha` int NOT NULL DEFAULT '0',
  `almacen` char(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_spanish2_ci NOT NULL DEFAULT '0',
  `costo` float NOT NULL DEFAULT (0),
  `precio1` float NOT NULL DEFAULT '0',
  `precio2` float NOT NULL DEFAULT '0',
  `precio3` float NOT NULL,
  `peso` float DEFAULT '0',
  `upload` tinyint DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_spanish2_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
