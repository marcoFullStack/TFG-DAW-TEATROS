--
-- Base de datos: `red_teatros_regional`
--
CREATE DATABASE IF NOT EXISTS red_teatros_regional;
USE red_teatros_regional;

-- --------------------------------------------------------

--
-- Estructura de tabla para `admins`
--
CREATE TABLE `admins` (
  `idAdmin` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(160) NOT NULL,
  `Email` varchar(180) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` datetime DEFAULT NULL,
  PRIMARY KEY (`idAdmin`),
  UNIQUE KEY `uq_admins_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Admin por defecto
INSERT INTO `admins` (`Nombre`, `Email`, `PasswordHash`) VALUES
('jaime', 'jaime@jaime.es', '$2y$10$49n..yHt5JZwc38d6T81Gu5QjkTuPe7BV.OGcmh/Vh3peEqQc29ha');

-- --------------------------------------------------------

--
-- Estructura de tabla para `usuarios` (Anteriormente socios)
--
CREATE TABLE `usuarios` (
  `idUsuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(160) NOT NULL,
  `Email` varchar(180) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `FotoPerfil` varchar(255) DEFAULT NULL,
  `Puntos` int(11) DEFAULT 0,
  `FechaAlta` date NOT NULL DEFAULT (CURRENT_DATE),
  PRIMARY KEY (`idUsuario`),
  UNIQUE KEY `uq_usuarios_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para `teatros`
--
CREATE TABLE `teatros` (
  `idTeatro` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Sala` varchar(255) NOT NULL,
  `Entidad` varchar(255) DEFAULT NULL,
  `Provincia` varchar(100) NOT NULL,
  `Municipio` varchar(100) NOT NULL,
  `Direccion` varchar(255) DEFAULT NULL,
  `CP` varchar(10) DEFAULT NULL,
  `Telefono` varchar(100) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `CapacidadMax` smallint(5) UNSIGNED NOT NULL,
  `Latitud` decimal(10, 8) DEFAULT NULL,
  `Longitud` decimal(11, 8) DEFAULT NULL,
  PRIMARY KEY (`idTeatro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para `visitas_ranking`
-- (Permite al usuario marcar dónde ha estado y ganar puntos)
-- --------------------------------------------------------
CREATE TABLE `visitas_ranking` (
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `idTeatro` int(10) UNSIGNED NOT NULL,
  `FechaVisita` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idUsuario`, `idTeatro`),
  CONSTRAINT `fk_visitas_usuarios` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_visitas_teatros` FOREIGN KEY (`idTeatro`) REFERENCES `teatros` (`idTeatro`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Estructura de tabla para `galeria_revision`
-- (Imágenes subidas por usuarios que el admin debe aprobar)
-- --------------------------------------------------------
CREATE TABLE `galeria_revision` (
  `idImagen` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `idTeatro` int(10) UNSIGNED NOT NULL,
  `RutaImagen` varchar(255) NOT NULL,
  `Estado` enum('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
  `FechaSubida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idImagen`),
  CONSTRAINT `fk_galeria_usuarios` FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_galeria_teatros` FOREIGN KEY (`idTeatro`) REFERENCES `teatros` (`idTeatro`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------
-- VOLCADO DE DATOS (Basado en el JSON proporcionado)
