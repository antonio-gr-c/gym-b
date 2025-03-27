<?php
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Database.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->setBasePath('/api');

$database = new Database();
$db = $database->connect();

//GETS

$app->get('/consultas/{id_doctor}', function (Request $request, Response $response, array $args) use ($db) {
    $id_doctor = $args['id_doctor'];

    // Consulta SQL corregida: Se obtiene `folio` desde `diagnosticos` y se elimina `d.folio`
    $query = "SELECT 
                p.id_paciente AS id,
                dg.folio,
                p.nombre,
                p.edad,
                p.genero,
                p.pais,
                h.presion_arterial AS presion,
                h.frecuencia_cardiaca AS frecuencia,
                h.temperatura,
                h.saturacion_oxigeno AS saturacion,
                h.alergias,
                h.enfermedades_previas AS enfermedades,
                h.medicacion,
                dg.sintomas,
                dg.diagnostico,
                dg.observaciones
              FROM pacientes p
              INNER JOIN historia_clinica h ON p.id_paciente = h.id_paciente
              INNER JOIN diagnosticos dg ON p.id_paciente = dg.id_paciente
              WHERE dg.id_doctor = :id_doctor";  // Se filtra por id_doctor en la tabla correcta

    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt->execute();
    $consultas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$consultas) {
        $response->getBody()->write(json_encode(["error" => "No se encontraron consultas para este doctor"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $response->getBody()->write(json_encode($consultas));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});



//datos de usuario
$app->get('/doctores/{id_doctor}', function (Request $request, Response $response, array $args) use ($db) {
    $id_doctor = $args['id_doctor'];

    // Consulta SQL para obtener los datos del doctor
    $query = "SELECT id_doctor, nombre, apellido_paterno, apellido_materno, usuario, telefono, correo, id_jefe 
              FROM doctores 
              WHERE id_doctor = :id_doctor";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt->execute();
    
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no se encuentra el doctor, retornar error
    if (!$doctor) {
        $response->getBody()->write(json_encode(["error" => "Doctor no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Retornar los datos del doctor en formato JSON
    $response->getBody()->write(json_encode($doctor));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});



//POSTS
$app->post('/login', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    if (!isset($data['usuario']) || !isset($data['contraseña'])) {
        $response->getBody()->write(json_encode(["error" => "Faltan datos"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $usuario = $data['usuario'];
    $contraseña = $data['contraseña'];

    // Buscar el usuario en la base de datos
    $query = "SELECT id_doctor, contraseña FROM doctores WHERE usuario = :usuario";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe y la contraseña es válida
    if ($doctor && password_verify($contraseña, $doctor['contraseña'])) {
        $respuesta = [
            "id_doctor" => $doctor['id_doctor']
        ];
        $response->getBody()->write(json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "Credenciales incorrectas"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
});

//registrar consulta
$app->post('/registrar-consulta', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    // Validación de datos
    if (!isset($data['id_doctor'], $data['nombre'], $data['apellido_paterno'], $data['edad'], $data['genero'], $data['pais'], $data['folio'])) {
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json')->write(json_encode(["error" => "Faltan datos requeridos"]));
    }

    try {
        $db->beginTransaction();

        // 🔹 1. Insertar paciente
        $queryPaciente = "INSERT INTO pacientes (nombre, apellido_paterno, apellido_materno, edad, genero, pais, telefono) 
                          VALUES (:nombre, :apellido_paterno, :apellido_materno, :edad, :genero, :pais, :telefono)";
        $stmtPaciente = $db->prepare($queryPaciente);
        $stmtPaciente->execute([
            ':nombre' => $data['nombre'],
            ':apellido_paterno' => $data['apellido_paterno'],
            ':apellido_materno' => $data['apellido_materno'] ?? NULL,
            ':edad' => $data['edad'],
            ':genero' => $data['genero'],
            ':pais' => $data['pais'],
            ':telefono' => $data['telefono'] ?? NULL
        ]);
        $id_paciente = $db->lastInsertId();

        // 🔹 2. Insertar historia clínica
        $queryHistoria = "INSERT INTO historia_clinica (presion_arterial, temperatura, frecuencia_cardiaca, saturacion_oxigeno, enfermedades_previas, medicacion, alergias, id_paciente, id_doctor) 
                          VALUES (:presion_arterial, :temperatura, :frecuencia_cardiaca, :saturacion_oxigeno, :enfermedades_previas, :medicacion, :alergias, :id_paciente, :id_doctor)";
        $stmtHistoria = $db->prepare($queryHistoria);
        $stmtHistoria->execute([
            ':presion_arterial' => $data['presion'],
            ':temperatura' => $data['temperatura'],
            ':frecuencia_cardiaca' => $data['frecuencia'],
            ':saturacion_oxigeno' => $data['saturacion'],
            ':enfermedades_previas' => $data['enfermedades'],
            ':medicacion' => $data['medicacion'],
            ':alergias' => $data['alergias'],
            ':id_paciente' => $id_paciente,
            ':id_doctor' => $data['id_doctor']
        ]);

        // 🔹 3. Insertar diagnóstico con el folio
        $queryDiagnostico = "INSERT INTO diagnosticos (folio, sintomas, diagnostico, observaciones, id_paciente, id_doctor) 
                             VALUES (:folio, :sintomas, :diagnostico, :observaciones, :id_paciente, :id_doctor)";
        $stmtDiagnostico = $db->prepare($queryDiagnostico);
        $stmtDiagnostico->execute([
            ':folio' => $data['folio'],
            ':sintomas' => $data['sintomas'],
            ':diagnostico' => $data['diagnostico'],
            ':observaciones' => $data['observaciones'],
            ':id_paciente' => $id_paciente,
            ':id_doctor' => $data['id_doctor']
        ]);

        $db->commit();

        $response->getBody()->write(json_encode([
            "mensaje" => "Consulta médica registrada correctamente",
            "id_paciente" => $id_paciente
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

    } catch (Exception $e) {
        $db->rollBack();
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->write(json_encode(["error" => $e->getMessage()]));
    }
});

//enviar correo

$app->post('/enviar-correo', function (Request $request, Response $response) {
    $data = json_decode($request->getBody()->getContents(), true);

    $destinatario = $data['destinatario'] ?? null;
    $pdfBase64 = $data['pdfBase64'] ?? null;
    $nombreArchivo = $data['nombreArchivo'] ?? 'consulta.pdf';

    if (!$destinatario || !$pdfBase64) {
        $response->getBody()->write(json_encode(["error" => "Faltan datos"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    require __DIR__ . '/../../vendor/autoload.php'; // Asegúrate de tener PHPMailer

    $mail = new PHPMailer(true);

    try {
        // Configuración SMTP para Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'aleztaro14@gmail.com';
        $mail->Password   = 'hpvhufkmezxrzmje';             
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Corregir codificación de caracteres
        $mail->CharSet = 'UTF-8';

        // Info del correo
        $mail->setFrom('TU_CORREO@gmail.com', 'Clínica Médica');
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->Subject = 'Consulta médica PDF';
        $mail->Body    = 'Adjunto encontrarás la consulta médica en PDF.';

        // ✅ Adjuntar PDF bien decodificado
        $pdfData = base64_decode(preg_replace('#^data:application/pdf;base64,#', '', $pdfBase64));
        $mail->addStringAttachment($pdfData, $nombreArchivo, 'base64', 'application/pdf');

        // Enviar
        $mail->send();
        $response->getBody()->write(json_encode(["mensaje" => "Correo enviado con éxito"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    } catch (Exception $e) {
        $response->getBody()->write(json_encode([
            "error" => "No se pudo enviar el correo: " . $mail->ErrorInfo
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});


// Middleware para permitir CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withStatus(200);
});

// Agregar Middleware para CORS
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

$app->run();
