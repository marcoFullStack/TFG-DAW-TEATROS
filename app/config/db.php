<?php
/**
 * Clase de conexión a la base de datos usando PDO
 */
require_once 'config.php';

function getConexion() {
    // Data Source Name: define el driver, host, nombre de la DB y el charset
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // Opciones recomendadas de PDO
    $options = [
        // Lanza una excepción si ocurre un error SQL
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // Devuelve los datos como un array asociativo por defecto
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Desactiva la emulación de consultas preparadas para mayor seguridad
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // Retornamos la instancia de la conexión
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // En un entorno de desarrollo mostramos el error, en producción deberías loguearlo
        error_log($e->getMessage());
        exit("Error de conexión a la base de datos. Por favor, inténtelo más tarde.");
    }
}

// Inicialización de la variable global de conexión
$pdo = getConexion();