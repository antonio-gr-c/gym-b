<?php

// URL del endpoint
$url = "http://localhost:8000/api/registrar-consulta";

// Función para generar nombres aleatorios
function generarNombre() {
    $nombres = ['Carlos', 'Ana', 'Luis', 'María', 'José', 'Elena', 'Javier', 'Mónica', 'Fernando', 'Lucía'];
    return $nombres[array_rand($nombres)];
}

function generarApellido() {
    $apellidos = ['Pérez', 'Gómez', 'Rodríguez', 'López', 'Fernández', 'Martínez', 'Sánchez', 'Ramírez'];
    return $apellidos[array_rand($apellidos)];
}

// Función para enviar la solicitud POST al endpoint
function enviarConsulta($datos) {
    global $url;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['http_code' => $httpCode, 'response' => json_decode($response, true)];
}

// Ciclo para generar y enviar 100 registros
for ($i = 1; $i <= 100; $i++) {
    $id_doctor = ($i % 3) + 1; // Alternar entre 1, 2 y 3

    $paciente = [
        'folio' => 'RX-2024' . str_pad($i, 3, '0', STR_PAD_LEFT),
        'nombre' => generarNombre(),
        'apellido_paterno' => generarApellido(),
        'apellido_materno' => generarApellido(),
        'edad' => rand(18, 80),
        'genero' => rand(0, 1) ? 'Masculino' : 'Femenino',
        'pais' => 'México',
        'telefono' => '55' . rand(10000000, 99999999),
        'presion' => rand(110, 140) . '/' . rand(70, 90),
        'frecuencia' => rand(60, 100),
        'temperatura' => rand(35, 39) + (rand(0, 9) / 10),
        'saturacion' => rand(90, 100),
        'enfermedades' => 'Hipertensión',
        'medicacion' => 'Losartán',
        'alergias' => 'Ninguna',
        'sintomas' => 'Dolor de cabeza',
        'diagnostico' => 'Hipertensión',
        'observaciones' => 'Control mensual recomendado',
        'id_doctor' => $id_doctor
    ];

    $resultado = enviarConsulta($paciente);
    echo "Registro $i -> HTTP {$resultado['http_code']} - ";
    echo isset($resultado['response']['mensaje']) ? $resultado['response']['mensaje'] : "Error: " . json_encode($resultado['response']);
    echo PHP_EOL;
}

echo "✅ Registro de 100 consultas completado.";
?>