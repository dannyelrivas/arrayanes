<?php
// =============================================
// FRACCIONAMIENTO ARRYANAES - Configuración
// =============================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'arryanaes');
define('DB_USER', 'lamsa');        // Cambiar según tu configuración
define('DB_PASS', 'Globos2020');            // Cambiar según tu configuración
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'Arryanaes');
define('APP_VERSION', '1.0');
define('SESSION_TIMEOUT', 3600); // 1 hora

// Nombres de los meses en español
define('MESES', [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
]);

// Monto mensual por defecto
define('MONTO_MENSUAL', 0.00);

// Conexión PDO
// function getDB(): PDO {
    function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
