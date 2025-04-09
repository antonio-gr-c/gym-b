<?php
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\ApiKeyMiddleware;
use App\Middleware\JwtMiddleware;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Database.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->setBasePath('/api');

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-KEY');
});

$app->options('/{routes:.+}', function ($request, $response) {
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-KEY');
});

$database = new Database();
$db = $database->connect();

$app->post('/login', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    if (!isset($data['usuario']) || !isset($data['contraseña'])) {
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400)
                        ->write(json_encode(["error" => "Faltan datos"]));
    }

    $usuario = $data['usuario'];
    $contraseña = $data['contraseña'];

    $query = "SELECT id_doctor, contraseña FROM doctores WHERE usuario = :usuario";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doctor && password_verify($contraseña, $doctor['contraseña'])) {
        $payload = [
            "iat" => time(),
            "exp" => time() + 3600, // expira en 1 hora
            "id_doctor" => $doctor['id_doctor']
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $respuesta = [
            "token" => $jwt,
            "id_doctor" => $doctor['id_doctor']
        ];

        $response->getBody()->write(json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "Credenciales incorrectas"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
});

$app->group('', function ($group) use ($db) {
    $group->get('/consultas/{id_doctor}', function (Request $request, Response $response, array $args) use ($db) {
        $id_doctor = $args['id_doctor'];

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
                  WHERE dg.id_doctor = :id_doctor";

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

    $group->get('/doctores/{id_doctor}', function (Request $request, Response $response, array $args) use ($db) {
        $id_doctor = $args['id_doctor'];

        $query = "SELECT id_doctor, nombre, apellido_paterno, apellido_materno, usuario, telefono, correo, id_jefe, rango
                  FROM doctores 
                  WHERE id_doctor = :id_doctor
                  AND estado = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
        $stmt->execute();
        
        $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doctor) {
            $response->getBody()->write(json_encode(["error" => "Doctor no encontrado"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($doctor));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    $group->get('/doctores', function (Request $request, Response $response, array $args) use ($db) {
        $query = "SELECT id_doctor, nombre, apellido_paterno, apellido_materno, usuario, telefono, correo, id_jefe, rango
                  FROM doctores";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $doctor = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$doctor) {
            $response->getBody()->write(json_encode(["error" => "Doctor no encontrado"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($doctor));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    $group->get('/consulta/folio/{folio}', function (Request $request, Response $response, array $args) use ($db) {
        $folio = $args['folio'];

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
                    dg.observaciones,
                    dg.fecha
                  FROM diagnosticos dg
                  INNER JOIN pacientes p ON p.id_paciente = dg.id_paciente
                  INNER JOIN historia_clinica h ON h.id_paciente = p.id_paciente
                  WHERE dg.folio = :folio";  

        $stmt = $db->prepare($query);
        $stmt->bindParam(':folio', $folio, PDO::PARAM_STR); 
        $stmt->execute();
        $consulta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$consulta) {
            $response->getBody()->write(json_encode(["error" => "Consulta no encontrada para el folio: $folio"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode($consulta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    $group->post('/registrar-doctor', function (Request $request, Response $response) use ($db) {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['nombre'], $data['apellido_paterno'], $data['contraseña'], $data['telefono'], $data['correo'])) {
            $response->getBody()->write(json_encode(["error" => "Faltan datos requeridos"]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $query = "INSERT INTO doctores (nombre, apellido_paterno, apellido_materno, contraseña, telefono, correo, id_jefe, rango) 
                      VALUES (:nombre, :apellido_paterno, :apellido_materno, :contrasena, :telefono, :correo, :id_jefe, :rango)";

            $stmt = $db->prepare($query);
            $stmt->execute([
                ':nombre' => $data['nombre'],
                ':apellido_paterno' => $data['apellido_paterno'],
                ':apellido_materno' => $data['apellido_materno'] ?? null,
                ':contrasena' => password_hash($data['contraseña'], PASSWORD_DEFAULT),
                ':telefono' => $data['telefono'],
                ':correo' => $data['correo'],
                ':id_jefe' => $data['id_jefe'] ?? null,
                ':rango' => $data['rango'] ?? null
            ]);

            $response->getBody()->write(json_encode(["mensaje" => "Doctor registrado correctamente"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(["error" => "Error al registrar el doctor: " . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });

    $group->post('/registrar-consulta', function (Request $request, Response $response) use ($db) {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['id_doctor'], $data['nombre'], $data['apellido_paterno'], $data['edad'], $data['genero'], $data['pais'], $data['folio'])) {
            return $response->withStatus(400)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(["error" => "Faltan datos requeridos"]));
        }

        $fecha = isset($data['fecha']) ? $data['fecha'] : date('Y-m-d');

        try {
            $db->beginTransaction();

            $stmtPaciente = $db->prepare("INSERT INTO pacientes (nombre, apellido_paterno, apellido_materno, edad, genero, pais, telefono) 
                VALUES (:nombre, :apellido_paterno, :apellido_materno, :edad, :genero, :pais, :telefono)");
            $stmtPaciente->execute([
                ':nombre' => $data['nombre'],
                ':apellido_paterno' => $data['apellido_paterno'],
                ':apellido_materno' => $data['apellido_materno'] ?? null,
                ':edad' => $data['edad'],
                ':genero' => $data['genero'],
                ':pais' => $data['pais'],
                ':telefono' => $data['telefono'] ?? null
            ]);
            $id_paciente = $db->lastInsertId();

            $stmtHistoria = $db->prepare("INSERT INTO historia_clinica (presion_arterial, temperatura, frecuencia_cardiaca, saturacion_oxigeno, enfermedades_previas, medicacion, alergias, id_paciente, id_doctor) 
                VALUES (:presion_arterial, :temperatura, :frecuencia_cardiaca, :saturacion_oxigeno, :enfermedades_previas, :medicacion, :alergias, :id_paciente, :id_doctor)");
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

            $stmtDiagnostico = $db->prepare("INSERT INTO diagnosticos (folio, sintomas, diagnostico, observaciones, id_paciente, id_doctor, fecha) 
                VALUES (:folio, :sintomas, :diagnostico, :observaciones, :id_paciente, :id_doctor, :fecha)");
            $stmtDiagnostico->execute([
                ':folio' => $data['folio'],
                ':sintomas' => $data['sintomas'] ?? null,
                ':diagnostico' => $data['diagnostico'] ?? null,
                ':observaciones' => $data['observaciones'] ?? null,
                ':id_paciente' => $id_paciente,
                ':id_doctor' => $data['id_doctor'],
                ':fecha' => $fecha
            ]);

            $db->commit();

            $response->getBody()->write(json_encode([
                "mensaje" => "Consulta médica registrada correctamente",
                "id_paciente" => $id_paciente
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (Exception $e) {
            $db->rollBack();
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->write(json_encode(["error" => $e->getMessage()]));
        }
    });

    $group->post('/enviar-correo', function (Request $request, Response $response) {
        $data = json_decode($request->getBody()->getContents(), true);

        $destinatario = $data['destinatario'] ?? null;
        $pdfBase64 = $data['pdfBase64'] ?? null;
        $nombreArchivo = $data['nombreArchivo'] ?? 'consulta.pdf';

        if (!$destinatario || !$pdfBase64) {
            $response->getBody()->write(json_encode(["error" => "Faltan datos"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'aleztaro14@gmail.com';
            $mail->Password = 'hpvhufkmezxrzmje';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->CharSet = 'UTF-8';
            $mail->setFrom('aleztaro14@gmail.com', 'Clínica Médica Sanatia');
            $mail->addAddress($destinatario);
            $mail->isHTML(true);
            $mail->Subject = 'Consulta médica PDF';
            $mail->Body = 'Adjunto encontrarás la consulta médica en PDF.';

            $pdfData = base64_decode(preg_replace('#^data:application/pdf;base64,#', '', $pdfBase64));
            $mail->addStringAttachment($pdfData, $nombreArchivo, 'base64', 'application/pdf');

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

})->add(new JwtMiddleware());


$app->get('/existe/folio/{folio}', function (Request $request, Response $response, array $args) use ($db) {
    $folio = $args['folio'];

    $query = "SELECT folio FROM diagnosticos WHERE folio = :folio LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':folio', $folio, PDO::PARAM_STR);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    $respuesta = $resultado ? ["existe" => true] : ["existe" => false];
    $response->getBody()->write(json_encode($respuesta));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->run();