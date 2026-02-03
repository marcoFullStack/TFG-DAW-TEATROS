<?php
require_once __DIR__ . '/config.php';

/**
 * The function `getConexion` establishes a PDO database connection using the provided constants for
 * host, database name, charset, user, and password, with error handling in case of connection failure.
 * @returns The `getConexion` function is returning a new PDO (PHP Data Objects) instance that
 * represents a connection to a MySQL database.
 */
function getConexion() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        exit("Error de conexión a la base de datos. Por favor, inténtelo más tarde.");
    }
}

$pdo = getConexion();
