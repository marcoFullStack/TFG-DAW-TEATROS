CREATE DATABASE IF NOT EXISTS red_teatros_regional;
USE red_teatros_regional;

-- 1. Administradores
CREATE TABLE IF NOT EXISTS `admins` (
  `idAdmin` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Nombre` varchar(160) NOT NULL,
  `Email` varchar(180) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_login` datetime DEFAULT NULL,
  PRIMARY KEY (`idAdmin`),
  UNIQUE KEY `uq_admins_email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `admins` (`Nombre`, `Email`, `PasswordHash`)
VALUES ('jaime', 'jaime@jaime.es', '$2y$10$49n..yHt5JZwc38d6T81Gu5QjkTuPe7BV.OGcmh/Vh3peEqQc29ha')
ON DUPLICATE KEY UPDATE
  `Nombre` = VALUES(`Nombre`),
  `PasswordHash` = VALUES(`PasswordHash`);

-- 2. Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
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

-- 3. Teatros y sus imágenes
CREATE TABLE IF NOT EXISTS `teatros` (
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
  PRIMARY KEY (`idTeatro`),
  UNIQUE KEY `uq_teatros_sala_provincia` (`Sala`, `Provincia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `imagenes_teatros` (
  `idImagenTeatro` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `idTeatro` int(10) UNSIGNED NOT NULL,
  `RutaImagen` varchar(255) NOT NULL,
  PRIMARY KEY (`idImagenTeatro`),
  UNIQUE KEY `uq_img_teatro` (`idTeatro`, `RutaImagen`),
  CONSTRAINT `fk_img_teatros`
    FOREIGN KEY (`idTeatro`) REFERENCES `teatros` (`idTeatro`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 4. Obras y sus imágenes
CREATE TABLE IF NOT EXISTS `obras` (
  `idObra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `Titulo` varchar(255) NOT NULL,
  `Autor` varchar(255) NOT NULL DEFAULT 'Anónimo',
  `Subtitulo` text DEFAULT NULL,
  `Anio` int(4) DEFAULT NULL,
  `UrlDracor` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idObra`),
  UNIQUE KEY `uq_obras_url` (`UrlDracor`),
  UNIQUE KEY `uq_obras_titulo_autor` (`Titulo`, `Autor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `imagenes_obras` (
  `idImagenObra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `idObra` int(10) UNSIGNED NOT NULL,
  `RutaImagen` varchar(255) NOT NULL,
  PRIMARY KEY (`idImagenObra`),
  UNIQUE KEY `uq_img_obra` (`idObra`, `RutaImagen`),
  CONSTRAINT `fk_img_obras`
    FOREIGN KEY (`idObra`) REFERENCES `obras` (`idObra`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 5. Horarios, Visitas y Galerías de Usuario
CREATE TABLE IF NOT EXISTS `horarios` (
  `idHorario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `idTeatro` int(10) UNSIGNED NOT NULL,
  `idObra` int(10) UNSIGNED NOT NULL,
  `FechaHora` datetime NOT NULL,
  `Precio` decimal(6,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`idHorario`),
  UNIQUE KEY `uq_horario` (`idTeatro`, `idObra`, `FechaHora`),
  KEY `idx_horarios_teatro` (`idTeatro`),
  KEY `idx_horarios_obra` (`idObra`),
  CONSTRAINT `fk_horarios_teatros`
    FOREIGN KEY (`idTeatro`) REFERENCES `teatros` (`idTeatro`) ON DELETE CASCADE,
  CONSTRAINT `fk_horarios_obras`
    FOREIGN KEY (`idObra`) REFERENCES `obras` (`idObra`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `visitas_ranking` (
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `idTeatro` int(10) UNSIGNED NOT NULL,
  `FechaVisita` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idUsuario`, `idTeatro`),
  CONSTRAINT `fk_visitas_usuarios`
    FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_visitas_teatros`
    FOREIGN KEY (`idTeatro`) REFERENCES `teatros` (`idTeatro`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `galeria_revision` (
  `idImagen` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `idTeatro` int(10) UNSIGNED NOT NULL,
  `RutaImagen` varchar(255) NOT NULL,
  `Estado` enum('pendiente', 'aprobada', 'rechazada') DEFAULT 'pendiente',
  `FechaSubida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idImagen`),
  CONSTRAINT `fk_galeria_usuarios`
    FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_galeria_teatros`
    FOREIGN KEY (`idTeatro`) REFERENCES `teatros` (`idTeatro`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `compras_entradas` (
  `idCompra` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `idUsuario` int(10) UNSIGNED NOT NULL,
  `idHorario` int(10) UNSIGNED NOT NULL,
  `Entradas` smallint(5) UNSIGNED NOT NULL,
  `FechaCompra` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idCompra`),
  KEY `idx_compras_horario` (`idHorario`),
  KEY `idx_compras_usuario` (`idUsuario`),
  CONSTRAINT `fk_compras_usuario`
    FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`) ON DELETE CASCADE,
  CONSTRAINT `fk_compras_horario`
    FOREIGN KEY (`idHorario`) REFERENCES `horarios` (`idHorario`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
