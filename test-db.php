<?php
require_once __DIR__ . '/src/Database.php';

$database = new Database();
$conn = $database->connect();

if ($conn) {
    echo "✅ Conexión exitosa a la base de datos.";
} else {
    echo "❌ Error en la conexión.";
}

