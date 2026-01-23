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
-- Nota: La CapacidadMax se genera con FLOOR(RAND()*(50-0+1)+0)
-- --------------------------------------------------------

INSERT INTO `teatros` (`Sala`, `Entidad`, `Provincia`, `Municipio`, `Direccion`, `CP`, `Telefono`, `Email`, `CapacidadMax`, `Latitud`, `Longitud`) VALUES
('AUDITORIO SAN FRANCISCO', 'AYUNTAMIENTO DE AVILA', 'Avila', 'Avila', 'Pza. del Mercado chico, 1', '05001', '920-354015', 'aperezg@ayuntavila.com', FLOOR(RAND()*51), 40.6600661, -4.6924474),
('PALACIO DE CONGRESOS Y EXPOSICIONES LIENZO NORTE', 'AYUNTAMIENTO DE AVILA', 'Avila', 'Avila', 'Pza. del Mercado chico, 1', '05001', '920-354015', 'aperezg@ayuntavila.com', FLOOR(RAND()*51), 40.6603995, -4.7062226),
('TEATRO CLUNIA', 'AYUNTAMIENTO DE BURGOS', 'Burgos', 'Burgos', 'Paseo del Espolón, s/n.', '09003', '947-288840', 'imc@aytoburgos.es', FLOOR(RAND()*51), 42.3403123, -3.7089036),
('AUDITORIO DE LA CASA DE CULTURA', 'AYUNTAMIENTO DE ARANDA DE DUERO', 'Burgos', 'Aranda de Duero', 'Pza. del Trigo, 9', '09400', '947-511275', 'cultura@arandadeduero.es', FLOOR(RAND()*51), 41.6712181, -3.6877189),
('TEATRO APOLO', 'AYUNTAMIENTO DE MIRANDA DE EBRO', 'Burgos', 'Miranda de Ebro', 'Pza. de España, 8', '09200', '947-349138', 'fcardero@mirandadeebro.es', FLOOR(RAND()*51), 42.6837893, -2.9531086),
('TEATRO BERGIDUM', 'AYUNTAMIENTO DE PONFERRADA', 'León', 'Ponferrada', 'C/ Ancha, nº 15', '24400', '987-429774', 'teatro@ponferrada.org', FLOOR(RAND()*51), 42.5465503, -6.5891852),
('AUDITORIO CIUDAD DE LEÓN', 'AYUNTAMIENTO DE LEON', 'León', 'León', 'Avda. Ordoño II, nº 10', '24001', '987-878337', 'senador.gonzalez@aytoleon.es', FLOOR(RAND()*51), 42.6024340, -5.5793251),
('TEATRO PRINCIPAL', 'AYUNTAMIENTO DE PALENCIA', 'Palencia', 'Palencia', 'Pza. Mayor, 1.', '34001', '979-718100', 'palcalde@aytopalencia.es', FLOOR(RAND()*51), 42.0097760, -4.5307714),
('TEATRO LICEO', 'FUNDACIÓN SALAMANCA CIUDAD DE CULTURA', 'Salamanca', 'Salamanca', 'Plaza del Liceo s/n', '37002', '923-281716', 'igallego@ciudaddecultura.org', FLOOR(RAND()*51), 40.9662752, -5.6625281),
('TEATRO JUAN BRAVO', 'DIPUTACION DE SEGOVIA', 'Segovia', 'Segovia', 'Pza. Mayor, 6', '40001', '921-460036', 'direccion@teatrojuanbravo.org', FLOOR(RAND()*51), 40.9505424, -4.1234622);

COMMIT;