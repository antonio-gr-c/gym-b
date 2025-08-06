<?php
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\ApiKeyMiddleware;
use App\Middleware\JwtMiddleware;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Database.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

$app = AppFactory::create();
$app->setBasePath('/backend/public/api');


$app->addRoutingMiddleware();


$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-KEY');
});

$app->add(function (Request $request, $handler) {
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-KEY')
            ->withStatus(200);
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-KEY');
});


$database = new Database();
$db = $database->connect();


//ENDPOINTS GYM

$app->get('/gym/actualizar-dias-restantes', function (Request $request, Response $response) use ($db) {
    // Asegurarnos de trabajar en hora local
    date_default_timezone_set('America/Mexico_City');

    // Fecha de hoy
    $hoy    = new DateTime('now');
    $hoyStr = $hoy->format('Y-m-d');

    // 1) No correr en domingo
    if ((int)$hoy->format('w') === 0) {
        $response->getBody()->write(json_encode([
            "mensaje" => "Hoy es domingo, no se actualizan los días restantes."
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // 2) Cargar y normalizar las fechas inhábiles
    $inhStmt   = $db->query("SELECT fecha FROM dias_inhabiles");
    $raw       = $inhStmt->fetchAll(PDO::FETCH_COLUMN);
    $inhabiles = array_map(function($d) {
        return (new DateTime($d))->format('Y-m-d');
    }, $raw);

    // 3) No correr en día inhábil
    if (in_array($hoyStr, $inhabiles, true)) {
        $response->getBody()->write(json_encode([
            "mensaje" => "Hoy es un día inhábil, no se actualizan los días restantes."
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // 4) Sólo una actualización por día
    $stmt = $db->prepare("
        SELECT valor
          FROM configuracion_gym
         WHERE clave = 'ultima_actualizacion_dias_restantes'
    ");
    $stmt->execute();
    if ($stmt->fetchColumn() === $hoyStr) {
        $response->getBody()->write(json_encode([
            "mensaje" => "Ya se actualizó hoy."
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // 5) Registros de clientes activos y con días pendientes
    $registros = $db
      ->query("
        SELECT vp.id, vp.fecha, vp.dias_restantes, vp.fecha_ultima_actualizacion
          FROM ventas_paquetes_gym vp
          JOIN clientes_gym c ON c.id_cliente = vp.id_cliente
         WHERE c.activo = TRUE
           AND vp.dias_restantes > 0
           AND vp.fecha_pausa IS NULL
      ")
      ->fetchAll(PDO::FETCH_ASSOC);

    // 6) Contar sólo L-S y no inhábiles
    foreach ($registros as $r) {
        $inicio      = new DateTime($r['fecha_ultima_actualizacion'] ?: $r['fecha']);
        $diasPasados = 0;
        $cursor      = clone $inicio;

        while ($cursor <= $hoy) {
            $dow      = (int)$cursor->format('w');        // 0=dom, 1=lun ... 6=sáb
            $fechaTxt = $cursor->format('Y-m-d');
            if ($dow >= 1 && $dow <= 6 && !in_array($fechaTxt, $inhabiles, true)) {
                $diasPasados++;
            }
            $cursor->modify('+1 day');
        }

        $nuevo = max(0, $r['dias_restantes'] - $diasPasados);
        $upd   = $db->prepare("
            UPDATE ventas_paquetes_gym
               SET dias_restantes            = :nuevo,
                   fecha_ultima_actualizacion = :hoy
             WHERE id = :id
        ");
        $upd->execute([
            ':nuevo' => $nuevo,
            ':hoy'   => $hoyStr,
            ':id'    => $r['id'],
        ]);
    }

    // 7) Guardar la fecha de esta actualización
    $db->prepare("
        UPDATE configuracion_gym
           SET valor = :h
         WHERE clave = 'ultima_actualizacion_dias_restantes'
    ")->execute([':h' => $hoyStr]);

    // 8) Respuesta final
    $response->getBody()->write(json_encode([
        "mensaje" => "Días restantes actualizados correctamente (lunes a sábado, sin inhábiles)."
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});





$app->get('/gym/dias-inhabiles', function (Request $request, Response $response) use ($db) {
    // 1) Obtener todos los días inhábiles
    $stmt = $db->query("
        SELECT id, fecha, descripcion
          FROM dias_inhabiles
         ORDER BY fecha
    ");
    $dias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) Preparar y devolver la respuesta JSON
    $payload = ['dias_inhabiles' => $dias];
    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});


$app->post('/gym/dias-inhabiles', function (Request $request, Response $response) use ($db) {
    // 1) Obtener datos del body
    $body = $request->getParsedBody();
    $fecha       = $body['fecha'] ?? null;         // Espera formato 'YYYY-MM-DD'
    $descripcion = $body['descripcion'] ?? null;

    // 2) Validaciones básicas
    if (!$fecha) {
        $response->getBody()->write(json_encode([
            "error" => "El campo 'fecha' es obligatorio."
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }
    // Validar formato de fecha
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) {
        $response->getBody()->write(json_encode([
            "error" => "El campo 'fecha' debe tener el formato YYYY-MM-DD."
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }

    // 3) Insertar en la base
    try {
        $stmt = $db->prepare("
            INSERT INTO dias_inhabiles (fecha, descripcion)
            VALUES (:fecha, :descripcion)
        ");
        $stmt->bindParam(':fecha', $fecha);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->execute();

        $nuevoId = $db->lastInsertId();

        // 4) Devolver registro creado
        $payload = [
            "id"          => (int)$nuevoId,
            "fecha"       => $fecha,
            "descripcion" => $descripcion
        ];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);

    } catch (PDOException $e) {
        // Manejar clave duplicada u otros errores de BD
        $msg = $e->getCode() === '23000'
            ? "Ya existe un día inhábil con esa fecha."
            : "Error al insertar: " . $e->getMessage();

        $response->getBody()->write(json_encode([
            "error" => $msg
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});



$app->delete('/gym/dias-inhabiles/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = (int)$args['id'];

    // 1) Verificar que exista el día inhábil
    $check = $db->prepare("
        SELECT 1
          FROM dias_inhabiles
         WHERE id = :id
    ");
    $check->bindParam(':id', $id, PDO::PARAM_INT);
    $check->execute();

    if (!$check->fetch()) {
        $response->getBody()->write(json_encode([
            "error" => "Día inhábil no encontrado"
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    }

    // 2) Eliminar el registro
    $del = $db->prepare("
        DELETE FROM dias_inhabiles
         WHERE id = :id
    ");
    $del->bindParam(':id', $id, PDO::PARAM_INT);
    $del->execute();

    // 3) Devolver confirmación
    $response->getBody()->write(json_encode([
        "mensaje" => "Día inhábil eliminado correctamente"
    ]));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});



$app->post('/gym/login', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    if (!isset($data['usuario']) || !isset($data['contraseña'])) {
        $response->getBody()->write(json_encode(["error" => "Faltan datos"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $usuario = $data['usuario'];
    $contraseña = $data['contraseña'];

    $query = "SELECT id_usuario, contraseña FROM usuarios_gym WHERE usuario = :usuario";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    $usuario_encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario_encontrado && password_verify($contraseña, $usuario_encontrado['contraseña'])) {
        $payload = [
            "iat" => time(),
            "exp" => time() + 10800,
            "id_usuario" => $usuario_encontrado['id_usuario']
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $respuesta = [
            "token" => $jwt,
            "id_usuario" => $usuario_encontrado['id_usuario']
        ];

        $response->getBody()->write(json_encode($respuesta));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "Credenciales incorrectas"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
});


$app->post('/gym/hash', function (Request $request, Response $response) {
    $data = json_decode($request->getBody()->getContents(), true);

    if (!isset($data['contraseña'])) {
        $response->getBody()->write(json_encode(["error" => "Falta la contraseña"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $contraseña = $data['contraseña'];
    $hash = password_hash($contraseña, PASSWORD_BCRYPT);

    $response->getBody()->write(json_encode(["hash" => $hash]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->post('/gym/clientes', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    $campos = ['nombre', 'apellido_paterno', 'apellido_materno', 'edad', 'telefono'];
    foreach ($campos as $campo) {
        if (empty($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Falta el campo: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    $query = "INSERT INTO clientes_gym (nombre, apellido_paterno, apellido_materno, edad, telefono, activo)
              VALUES (:nombre, :apellido_paterno, :apellido_materno, :edad, :telefono, TRUE)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data['nombre']);
    $stmt->bindParam(':apellido_paterno', $data['apellido_paterno']);
    $stmt->bindParam(':apellido_materno', $data['apellido_materno']);
    $stmt->bindParam(':edad', $data['edad']);
    $stmt->bindParam(':telefono', $data['telefono']);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Cliente agregado correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(["error" => "Error al agregar el cliente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});


$app->get('/gym/productos', function (Request $request, Response $response) use ($db) {
    $query = "SELECT * FROM productos_gym";
    $stmt = $db->query($query);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($productos));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->post('/gym/productos', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    $campos = ['nombre', 'precio', 'stock_minimo', 'stock_actual'];
    foreach ($campos as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Falta el campo: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    $query = "INSERT INTO productos_gym (nombre, precio, stock_minimo, stock_actual)
              VALUES (:nombre, :precio, :stock_minimo, :stock_actual)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data['nombre']);
    $stmt->bindParam(':precio', $data['precio']);
    $stmt->bindParam(':stock_minimo', $data['stock_minimo']);
    $stmt->bindParam(':stock_actual', $data['stock_actual']);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Producto agregado correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(["error" => "Error al agregar el producto"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});


$app->put('/gym/productos/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];
    $data = json_decode($request->getBody()->getContents(), true);

    $campos = ['nombre', 'precio', 'stock_minimo', 'stock_actual'];
    foreach ($campos as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Falta el campo: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    $query = "UPDATE productos_gym SET 
                nombre = :nombre, 
                precio = :precio, 
                stock_minimo = :stock_minimo, 
                stock_actual = :stock_actual 
              WHERE id_producto = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data['nombre']);
    $stmt->bindParam(':precio', $data['precio']);
    $stmt->bindParam(':stock_minimo', $data['stock_minimo']);
    $stmt->bindParam(':stock_actual', $data['stock_actual']);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Producto actualizado correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "Error al actualizar el producto"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->put('/gym/productos/estado/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];

    // Obtener el estado actual del producto
    $stmt = $db->prepare("SELECT activo FROM productos_gym WHERE id_producto = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        $response->getBody()->write(json_encode(["error" => "Producto no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Invertir el estado actual
    $nuevoEstado = !$producto['activo'];

    $update = $db->prepare("UPDATE productos_gym SET activo = :activo WHERE id_producto = :id");
    $update->bindParam(':activo', $nuevoEstado, PDO::PARAM_BOOL);
    $update->bindParam(':id', $id);

    if ($update->execute()) {
        $mensaje = $nuevoEstado ? "Producto activado" : "Producto desactivado";
        $response->getBody()->write(json_encode(["mensaje" => $mensaje]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo actualizar el estado del producto"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});



$app->get('/gym/servicios', function (Request $request, Response $response) use ($db) {
    $query = "SELECT * FROM servicios_gym";
    $stmt = $db->query($query);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($servicios));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->post('/gym/servicios', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    if (!isset($data['nombre']) || !isset($data['costo'])) {
        $response->getBody()->write(json_encode(["error" => "Faltan datos obligatorios"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $query = "INSERT INTO servicios_gym (nombre, costo, activo) 
              VALUES (:nombre, :costo, TRUE)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data['nombre']);
    $stmt->bindParam(':costo', $data['costo']);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Servicio agregado correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo agregar el servicio"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->put('/gym/servicios/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];
    $data = json_decode($request->getBody()->getContents(), true);

    $campos = ['nombre', 'costo', 'activo'];
    foreach ($campos as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Falta el campo: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    $query = "UPDATE servicios_gym SET 
                nombre = :nombre, 
                costo = :costo, 
                activo = :activo 
              WHERE id_servicio = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data['nombre']);
    $stmt->bindParam(':costo', $data['costo']);
    $stmt->bindParam(':activo', $data['activo'], PDO::PARAM_BOOL);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Servicio actualizado correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "Error al actualizar el servicio"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->put('/gym/servicios/estado/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];

    // Obtener el estado actual del servicio
    $stmt = $db->prepare("SELECT activo FROM servicios_gym WHERE id_servicio = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$servicio) {
        $response->getBody()->write(json_encode(["error" => "Servicio no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Invertir el estado manualmente como 0 o 1
    $nuevoEstado = $servicio['activo'] ? 0 : 1;

    $update = $db->prepare("UPDATE servicios_gym SET activo = :activo WHERE id_servicio = :id");
    $update->bindParam(':activo', $nuevoEstado);
    $update->bindParam(':id', $id);

    if ($update->execute()) {
        $mensaje = $nuevoEstado ? "Servicio activado" : "Servicio desactivado";
        $response->getBody()->write(json_encode(["mensaje" => $mensaje]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo actualizar el estado del servicio"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/gym/promociones', function (Request $request, Response $response) use ($db) {
    $query = "SELECT * FROM promociones_gym WHERE activo = true ";
    $stmt = $db->query($query);
    $promocionesBD = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $promociones = array_map(function ($promo) {
        $tipos = [];
        if ($promo['producto']) $tipos[] = 'producto';
        if ($promo['servicio']) $tipos[] = 'servicio';
        if ($promo['paquete']) $tipos[] = 'paquete';

        return [
            'id' => (int)$promo['id_promocion'],
            'nombre' => $promo['nombre'],
            'descuento' => (float)$promo['descuento'],
            'tipo' => implode(', ', $tipos),
            'activo' => (bool)$promo['activo']
        ];
    }, $promocionesBD);

    $response->getBody()->write(json_encode($promociones));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->get('/gym/promociones-total', function (Request $request, Response $response) use ($db) {
    $query = "SELECT * FROM promociones_gym ";
    $stmt = $db->query($query);
    $promocionesBD = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $promociones = array_map(function ($promo) {
        $tipos = [];
        if ($promo['producto']) $tipos[] = 'producto';
        if ($promo['servicio']) $tipos[] = 'servicio';
        if ($promo['paquete']) $tipos[] = 'paquete';

        return [
            'id' => (int)$promo['id_promocion'],
            'nombre' => $promo['nombre'],
            'descuento' => (float)$promo['descuento'],
            'tipo' => implode(', ', $tipos),
            'activo' => (bool)$promo['activo']
        ];
    }, $promocionesBD);

    $response->getBody()->write(json_encode($promociones));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->post('/gym/promociones', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    $campos = ['nombre', 'descuento', 'producto', 'servicio', 'paquete'];
    foreach ($campos as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Falta el campo: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    $query = "INSERT INTO promociones_gym (nombre, descuento, producto, servicio, paquete) 
              VALUES (:nombre, :descuento, :producto, :servicio, :paquete)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data['nombre']);
    $stmt->bindParam(':descuento', $data['descuento']);
    $stmt->bindParam(':producto', $data['producto'], PDO::PARAM_BOOL);
    $stmt->bindParam(':servicio', $data['servicio'], PDO::PARAM_BOOL);
    $stmt->bindParam(':paquete', $data['paquete'], PDO::PARAM_BOOL);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Promoción registrada correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo registrar la promoción"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->put('/gym/promociones/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];
    $data = json_decode($request->getBody()->getContents(), true);

    $campos = ['nombre', 'descuento', 'producto', 'servicio', 'paquete'];
    foreach ($campos as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Falta el campo: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    $query = "UPDATE promociones_gym SET 
                nombre = :nombre, 
                descuento = :descuento, 
                producto = :producto, 
                servicio = :servicio, 
                paquete = :paquete 
              WHERE id_promocion = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $data['nombre']);
    $stmt->bindParam(':descuento', $data['descuento']);
    $stmt->bindParam(':producto', $data['producto'], PDO::PARAM_BOOL);
    $stmt->bindParam(':servicio', $data['servicio'], PDO::PARAM_BOOL);
    $stmt->bindParam(':paquete', $data['paquete'], PDO::PARAM_BOOL);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Promoción actualizada correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo actualizar la promoción"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->put('/gym/promociones/estado/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];

    // Verificar si la promoción existe
    $stmt = $db->prepare("SELECT activo FROM promociones_gym WHERE id_promocion = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $promocion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promocion) {
        $response->getBody()->write(json_encode(["error" => "Promoción no encontrada"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Cambiar estado
    $nuevoEstado = $promocion['activo'] ? 0 : 1;

    $update = $db->prepare("UPDATE promociones_gym SET activo = :activo WHERE id_promocion = :id");
    $update->bindParam(':activo', $nuevoEstado);
    $update->bindParam(':id', $id);

    if ($update->execute()) {
        $mensaje = $nuevoEstado ? "Promoción activada" : "Promoción desactivada";
        $response->getBody()->write(json_encode(["mensaje" => $mensaje]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo actualizar el estado de la promoción"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/gym/paquetes', function (Request $request, Response $response) use ($db) {
    $query = "SELECT * FROM paquetes_gym";
    $stmt = $db->query($query);
    $paquetes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($paquetes));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});



$app->post('/gym/paquetes', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    $camposObligatorios = ['nombre', 'descripcion', 'precio', 'duracion', 'unidad', 'tipo_paquete'];
    foreach ($camposObligatorios as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Faltan el campo: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }


    // Asignar variables antes de bindParam
    $nombre = $data['nombre'];
    $descripcion = $data['descripcion'];
    $precio = $data['precio'];
    $duracion = $data['duracion'];
    $unidad = $data['unidad'];
    $visita_unica = $data['visita_unica'] ?? false;
    $semana = $data['semana'] ?? false;
    $quincena = $data['quincena'] ?? false;
    $mensualidad_gym = $data['mensualidad_gym'] ?? false;
    $personal_trainer = $data['personal_trainer'] ?? false;
    $nutriologo = $data['nutriologo'] ?? false;
    $fisioterapia = $data['fisioterapia'] ?? false;
    $area_funcional = $data['area_funcional'] ?? false;
    $consulta_medica = $data['consulta_medica'] ?? false;
    $consulta_psicologica = $data['consulta_psicologica'] ?? false;
    $area_kids = $data['area_kids'] ?? false;
    $funcional_adultos = $data['funcional_adultos'] ?? false;
    $area_pesas = $data['area_pesas'] ?? false;
    $area_fisioterapia = $data['area_fisioterapia'] ?? false;
    $gym_general = $data['gym_general'] ?? false;
    $tipo_paquete = $data['tipo_paquete'];
    $estado = $data['estado'] ?? true;

    $query = "INSERT INTO paquetes_gym (
        nombre, descripcion, precio, duracion, unidad,
        visita_unica, semana, quincena, mensualidad_gym, personal_trainer, nutriologo, fisioterapia, area_funcional, consulta_medica, consulta_psicologica,
        area_kids, funcional_adultos, area_pesas, area_fisioterapia, gym_general,
        tipo_paquete, estado
    ) VALUES (
        :nombre, :descripcion, :precio, :duracion, :unidad,
        :visita_unica, :semana, :quincena, :mensualidad_gym, :personal_trainer, :nutriologo, :fisioterapia, :area_funcional, :consulta_medica, :consulta_psicologica,
        :area_kids, :funcional_adultos, :area_pesas, :area_fisioterapia, :gym_general,
        :tipo_paquete, :estado
    )";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':precio', $precio);
    $stmt->bindParam(':duracion', $duracion);
    $stmt->bindParam(':unidad', $unidad);
    $stmt->bindParam(':visita_unica', $visita_unica, PDO::PARAM_BOOL);
    $stmt->bindParam(':semana', $semana, PDO::PARAM_BOOL);
    $stmt->bindParam(':quincena', $quincena, PDO::PARAM_BOOL);
    $stmt->bindParam(':mensualidad_gym', $mensualidad_gym, PDO::PARAM_BOOL);
    $stmt->bindParam(':personal_trainer', $personal_trainer, PDO::PARAM_BOOL);
    $stmt->bindParam(':nutriologo', $nutriologo, PDO::PARAM_BOOL);
    $stmt->bindParam(':fisioterapia', $fisioterapia, PDO::PARAM_BOOL);
    $stmt->bindParam(':area_funcional', $area_funcional, PDO::PARAM_BOOL);
    $stmt->bindParam(':consulta_medica', $consulta_medica, PDO::PARAM_BOOL);
    $stmt->bindParam(':consulta_psicologica', $consulta_psicologica, PDO::PARAM_BOOL);
    $stmt->bindParam(':area_kids', $area_kids, PDO::PARAM_BOOL);
    $stmt->bindParam(':funcional_adultos', $funcional_adultos, PDO::PARAM_BOOL);
    $stmt->bindParam(':area_pesas', $area_pesas, PDO::PARAM_BOOL);
    $stmt->bindParam(':area_fisioterapia', $area_fisioterapia, PDO::PARAM_BOOL);
    $stmt->bindParam(':gym_general', $gym_general, PDO::PARAM_BOOL);
    $stmt->bindParam(':tipo_paquete', $tipo_paquete);
    $stmt->bindParam(':estado', $estado, PDO::PARAM_BOOL);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Paquete registrado correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo registrar el paquete"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});




$app->put('/gym/paquetes/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];
    $data = json_decode($request->getBody()->getContents(), true);

    $camposObligatorios = ['nombre', 'descripcion', 'precio', 'duracion', 'unidad', 'tipo_paquete'];
    foreach ($camposObligatorios as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Falta el campo: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }


    // Asignar variables antes de bindParam
    $nombre = $data['nombre'];
    $descripcion = $data['descripcion'];
    $precio = $data['precio'];
    $duracion = $data['duracion'];
    $unidad = $data['unidad'];
    $tipo_paquete = $data['tipo_paquete'];
    $estado = $data['estado'] ?? true;

    // Servicios y áreas
    $visita_unica = $data['visita_unica'] ?? false;
    $semana = $data['semana'] ?? false;
    $quincena = $data['quincena'] ?? false;
    $mensualidad_gym = $data['mensualidad_gym'] ?? false;
    $personal_trainer = $data['personal_trainer'] ?? false;
    $nutriologo = $data['nutriologo'] ?? false;
    $fisioterapia = $data['fisioterapia'] ?? false;
    $area_funcional = $data['area_funcional'] ?? false;
    $consulta_medica = $data['consulta_medica'] ?? false;
    $consulta_psicologica = $data['consulta_psicologica'] ?? false;
    $area_kids = $data['area_kids'] ?? false;
    $funcional_adultos = $data['funcional_adultos'] ?? false;
    $area_pesas = $data['area_pesas'] ?? false;
    $area_fisioterapia = $data['area_fisioterapia'] ?? false;
    $gym_general = $data['gym_general'] ?? false;

    $query = "UPDATE paquetes_gym SET 
        nombre = :nombre,
        descripcion = :descripcion,
        precio = :precio,
        duracion = :duracion,
        unidad = :unidad,
        visita_unica = :visita_unica,
        semana = :semana,
        quincena = :quincena,
        mensualidad_gym = :mensualidad_gym,
        personal_trainer = :personal_trainer,
        nutriologo = :nutriologo,
        fisioterapia = :fisioterapia,
        area_funcional = :area_funcional,
        consulta_medica = :consulta_medica,
        consulta_psicologica = :consulta_psicologica,
        area_kids = :area_kids,
        funcional_adultos = :funcional_adultos,
        area_pesas = :area_pesas,
        area_fisioterapia = :area_fisioterapia,
        gym_general = :gym_general,
        tipo_paquete = :tipo_paquete,
        estado = :estado
        WHERE id_paquete = :id";

    $stmt = $db->prepare($query);

    // Enlazar todas las variables
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':descripcion', $descripcion);
    $stmt->bindParam(':precio', $precio);
    $stmt->bindParam(':duracion', $duracion);
    $stmt->bindParam(':unidad', $unidad);
    $stmt->bindParam(':visita_unica', $visita_unica, PDO::PARAM_BOOL);
    $stmt->bindParam(':semana', $semana, PDO::PARAM_BOOL);
    $stmt->bindParam(':quincena', $quincena, PDO::PARAM_BOOL);
    $stmt->bindParam(':mensualidad_gym', $mensualidad_gym, PDO::PARAM_BOOL);
    $stmt->bindParam(':personal_trainer', $personal_trainer, PDO::PARAM_BOOL);
    $stmt->bindParam(':nutriologo', $nutriologo, PDO::PARAM_BOOL);
    $stmt->bindParam(':fisioterapia', $fisioterapia, PDO::PARAM_BOOL);
    $stmt->bindParam(':area_funcional', $area_funcional, PDO::PARAM_BOOL);
    $stmt->bindParam(':consulta_medica', $consulta_medica, PDO::PARAM_BOOL);
    $stmt->bindParam(':consulta_psicologica', $consulta_psicologica, PDO::PARAM_BOOL);
    $stmt->bindParam(':area_kids', $area_kids, PDO::PARAM_BOOL);
    $stmt->bindParam(':funcional_adultos', $funcional_adultos, PDO::PARAM_BOOL);
    $stmt->bindParam(':area_pesas', $area_pesas, PDO::PARAM_BOOL);
    $stmt->bindParam(':area_fisioterapia', $area_fisioterapia, PDO::PARAM_BOOL);
    $stmt->bindParam(':gym_general', $gym_general, PDO::PARAM_BOOL);
    $stmt->bindParam(':tipo_paquete', $tipo_paquete);
    $stmt->bindParam(':estado', $estado, PDO::PARAM_BOOL);
    $stmt->bindParam(':id', $id);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Paquete actualizado correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo actualizar el paquete"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});


$app->put('/gym/paquetes/estado/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];

    // Verificar si el paquete existe y obtener el estado actual
    $stmt = $db->prepare("SELECT estado FROM paquetes_gym WHERE id_paquete = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $paquete = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paquete) {
        $response->getBody()->write(json_encode(["error" => "Paquete no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Invertir el estado actual
    $nuevoEstado = $paquete['estado'] ? 0 : 1;

    $update = $db->prepare("UPDATE paquetes_gym SET estado = :estado WHERE id_paquete = :id");
    $update->bindParam(':estado', $nuevoEstado, PDO::PARAM_BOOL);
    $update->bindParam(':id', $id);

    if ($update->execute()) {
        $mensaje = $nuevoEstado ? "Paquete activado" : "Paquete desactivado";
        $response->getBody()->write(json_encode(["mensaje" => $mensaje]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo cambiar el estado del paquete"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/gym/general', function (Request $request, Response $response) use ($db) {
    // Obtener productos
    $productosStmt = $db->query("SELECT id_producto AS id, nombre, precio, stock_actual AS stock FROM productos_gym WHERE activo = TRUE");
    $productos = $productosStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productos as &$p) {
        $p['tipo'] = 'producto';
    }

    // Obtener servicios
    $serviciosStmt = $db->query("SELECT id_servicio AS id, nombre, costo AS precio FROM servicios_gym WHERE activo = TRUE");
    $servicios = $serviciosStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($servicios as &$s) {
        $s['tipo'] = 'servicio';
    }

    // Obtener paquetes
    $paquetesStmt = $db->query("SELECT id_paquete AS id, nombre, precio FROM paquetes_gym WHERE estado = TRUE");
    $paquetes = $paquetesStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($paquetes as &$p) {
        $p['tipo'] = 'paquete';
    }

    // Unificar resultados
    $datos = array_merge($productos, $servicios, $paquetes);

    $response->getBody()->write(json_encode($datos));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->post('/gym/ventas/productos', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    $campos = ['cantidad', 'precio', 'descuento', 'id_producto'];
    foreach ($campos as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Faltan datos"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // Validar existencia del producto
    $stmtProducto = $db->prepare("SELECT COUNT(*) FROM productos_gym WHERE id_producto = :id_producto");
    $stmtProducto->bindParam(':id_producto', $data['id_producto']);
    $stmtProducto->execute();
    if ($stmtProducto->fetchColumn() == 0) {
        $response->getBody()->write(json_encode(["error" => "Producto no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $query = "INSERT INTO ventas_productos_gym (cantidad, precio, descuento, id_producto)
              VALUES (:cantidad, :precio, :descuento, :id_producto)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cantidad', $data['cantidad']);
    $stmt->bindParam(':precio', $data['precio']);
    $stmt->bindParam(':descuento', $data['descuento']);
    $stmt->bindParam(':id_producto', $data['id_producto']);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Venta registrada correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo registrar la venta"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->put('/gym/productos/stock/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id_producto = $args['id'];
    $data = json_decode($request->getBody()->getContents(), true);

    if (!isset($data['stock_actual'])) {
        $response->getBody()->write(json_encode(["error" => "Falta el campo: stock_actual"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $stock = $data['stock_actual'];

    // Validar existencia del producto
    $stmtVerifica = $db->prepare("SELECT COUNT(*) FROM productos_gym WHERE id_producto = :id");
    $stmtVerifica->bindParam(':id', $id_producto);
    $stmtVerifica->execute();

    if ($stmtVerifica->fetchColumn() == 0) {
        $response->getBody()->write(json_encode(["error" => "Producto no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Actualizar el stock
    $stmt = $db->prepare("UPDATE productos_gym SET stock_actual = :stock WHERE id_producto = :id");
    $stmt->bindParam(':stock', $stock);
    $stmt->bindParam(':id', $id_producto);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Stock actualizado correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo actualizar el stock"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->post('/gym/ventas/servicios', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    // Validar campos requeridos
    $camposRequeridos = ['cantidad', 'precio', 'id_servicio', 'id_cliente'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Falta el campo requerido: $campo"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // Valores opcionales con defaults
    $descuento = $data['descuento'] ?? 0.00;
    $fecha_agendada = $data['fecha_agendada'] ?? null;
    $total = $data['precio'] * $data['cantidad'] - $descuento;

    // Validar existencia del servicio
    $stmtServicio = $db->prepare("SELECT COUNT(*) FROM servicios_gym WHERE id_servicio = :id_servicio");
    $stmtServicio->bindParam(':id_servicio', $data['id_servicio']);
    $stmtServicio->execute();
    if ($stmtServicio->fetchColumn() == 0) {
        $response->getBody()->write(json_encode(["error" => "Servicio no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Validar existencia del cliente
    $stmtCliente = $db->prepare("SELECT COUNT(*) FROM clientes_gym WHERE id_cliente = :id_cliente");
    $stmtCliente->bindParam(':id_cliente', $data['id_cliente']);
    $stmtCliente->execute();
    if ($stmtCliente->fetchColumn() == 0) {
        $response->getBody()->write(json_encode(["error" => "Cliente no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Insertar la venta
    $query = "INSERT INTO venta_servicios_gym 
              (cantidad, precio, descuento, total, fecha, fecha_agendada, id_servicio, id_cliente)
              VALUES (:cantidad, :precio, :descuento, :total, NOW(), :fecha_agendada, :id_servicio, :id_cliente)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cantidad', $data['cantidad']);
    $stmt->bindParam(':precio', $data['precio']);
    $stmt->bindParam(':descuento', $descuento);
    $stmt->bindParam(':total', $total);
    $stmt->bindParam(':fecha_agendada', $fecha_agendada);
    $stmt->bindParam(':id_servicio', $data['id_servicio']);
    $stmt->bindParam(':id_cliente', $data['id_cliente']);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode([
            "mensaje" => "Venta de servicio registrada correctamente",
            "total" => $total,
            "id_venta" => $db->lastInsertId()
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo registrar la venta"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/gym/clientes', function (Request $request, Response $response) use ($db) {
    $query = "SELECT * FROM clientes_gym";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($clientes));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->post('/gym/ventas/paquetes', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    // Validar campos obligatorios
    foreach (["id_paquete","id_cliente","precio"] as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode(["error" => "Faltan datos"]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }

    // id_promocion es opcional
    $id_promocion = isset($data['id_promocion']) ? $data['id_promocion'] : null;

    // Validar existencia del paquete y obtener duracion
    $stmtP = $db->prepare("SELECT duracion FROM paquetes_gym WHERE id_paquete = :id_paquete");
    $stmtP->bindParam(':id_paquete', $data['id_paquete']);
    $stmtP->execute();
    $paquete = $stmtP->fetch(PDO::FETCH_ASSOC);
    if (!$paquete) {
        $response->getBody()->write(json_encode(["error" => "Paquete no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    $duracion = (int)$paquete['duracion'];

    // Validar existencia del cliente
    $stmtC = $db->prepare("SELECT COUNT(*) FROM clientes_gym WHERE id_cliente = :id_cliente");
    $stmtC->bindParam(':id_cliente', $data['id_cliente']);
    $stmtC->execute();
    if ($stmtC->fetchColumn() == 0) {
        $response->getBody()->write(json_encode(["error" => "Cliente no encontrado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // Calcular dias_restantes
    $meses = isset($data['meses']) ? (int)$data['meses'] : 1;
    $descuento = $data['descuento'] ?? 0;
    $dias_restantes = $duracion * $meses;

    // Preparar inserción
    $query = "INSERT INTO ventas_paquetes_gym (id_paquete, id_cliente, meses, precio, descuento, dias_restantes, id_promocion)
              VALUES (:id_paquete, :id_cliente, :meses, :precio, :descuento, :dias_restantes, :id_promocion)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id_paquete', $data['id_paquete']);
    $stmt->bindParam(':id_cliente', $data['id_cliente']);
    $stmt->bindParam(':meses', $meses);
    $stmt->bindParam(':precio', $data['precio']);
    $stmt->bindParam(':descuento', $descuento);
    $stmt->bindParam(':dias_restantes', $dias_restantes);
    $stmt->bindParam(':id_promocion', $id_promocion);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["mensaje" => "Venta de paquete registrada correctamente"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(["error" => "No se pudo registrar la venta"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->get('/gym/clientes/paquetes', function (Request $request, Response $response) use ($db) {
    $sql = "
        SELECT
            c.id_cliente,
            CONCAT(c.nombre, ' ', c.apellido_paterno, ' ', c.apellido_materno) AS nombre_completo,
            c.edad,
            c.telefono,
            c.activo,
            pg.nombre AS paquete,
            vp.dias_restantes
        FROM clientes_gym c
        LEFT JOIN (
            SELECT *
            FROM ventas_paquetes_gym
            WHERE dias_restantes > 0
            ORDER BY fecha DESC
        ) vp ON vp.id_cliente = c.id_cliente
        LEFT JOIN paquetes_gym pg ON pg.id_paquete = vp.id_paquete
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($result));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});


$app->put('/gym/clientes/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = $args['id'];
    $data = json_decode($request->getBody()->getContents(), true);

    // Validar campos obligatorios
    foreach (['nombre','apellido_paterno','apellido_materno','edad','telefono','activo'] as $campo) {
        if (!isset($data[$campo])) {
            $response->getBody()->write(json_encode([
                "error" => "Falta el campo: $campo"
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    // Variables para bindParam
    $nombre            = $data['nombre'];
    $apellido_paterno  = $data['apellido_paterno'];
    $apellido_materno  = $data['apellido_materno'];
    $edad              = $data['edad'];
    $telefono          = $data['telefono'];
    $activo            = $data['activo'];

    $query = "
        UPDATE clientes_gym SET
            nombre            = :nombre,
            apellido_paterno  = :apellido_paterno,
            apellido_materno  = :apellido_materno,
            edad              = :edad,
            telefono          = :telefono,
            activo            = :activo
        WHERE id_cliente = :id
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':nombre',           $nombre);
    $stmt->bindParam(':apellido_paterno', $apellido_paterno);
    $stmt->bindParam(':apellido_materno', $apellido_materno);
    $stmt->bindParam(':edad',             $edad);
    $stmt->bindParam(':telefono',         $telefono);
    $stmt->bindParam(':activo',           $activo, PDO::PARAM_BOOL);
    $stmt->bindParam(':id',               $id);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode([
            "mensaje" => "Cliente actualizado correctamente"
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } else {
        $response->getBody()->write(json_encode([
            "error" => "No se pudo actualizar el cliente"
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

$app->put('/gym/clientes/estado/{id}', function (Request $request, Response $response, array $args) use ($db) {
    $id = (int)$args['id'];

    // 1) Obtener estado actual
    $stmt = $db->prepare("
        SELECT activo
          FROM clientes_gym
         WHERE id_cliente = :id
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $response->getBody()->write(json_encode([
            "error" => "Cliente no encontrado"
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    }

    // 2) Calcular nuevo estado e invertirlo
    $nuevoEstado = $cliente['activo'] ? 0 : 1;
    $mensaje     = $nuevoEstado
        ? "Cliente activado"
        : "Cliente desactivado";

    // 3) Guardar el nuevo estado
    $upd = $db->prepare("
        UPDATE clientes_gym
           SET activo = :activo
         WHERE id_cliente = :id
    ");
    $upd->bindParam(':activo', $nuevoEstado, PDO::PARAM_BOOL);
    $upd->bindParam(':id', $id, PDO::PARAM_INT);

    if ($upd->execute()) {
        $response->getBody()->write(json_encode([
            "mensaje" => $mensaje
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    } else {
        $response->getBody()->write(json_encode([
            "error" => "No se pudo actualizar el estado del cliente"
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});



$app->post('/gym/acceso', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);

    // Validar que venga el teléfono
    if (empty($data['telefono'])) {
        $response->getBody()->write(json_encode([
            'acceso'  => false,
            'mensaje' => 'Falta el campo: telefono'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    $telefono = $data['telefono'];

    // Buscar cliente por teléfono
    $stmtC = $db->prepare("SELECT id_cliente FROM clientes_gym WHERE telefono = :telefono");
    $stmtC->bindParam(':telefono', $telefono);
    $stmtC->execute();
    $cliente = $stmtC->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        $response->getBody()->write(json_encode([
            'acceso'  => false,
            'mensaje' => 'Cliente no encontrado'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    $id_cliente = $cliente['id_cliente'];

    // Buscar la última venta de paquete activa (dias_restantes > 0)
    $stmtV = $db->prepare("
        SELECT vp.dias_restantes, p.nombre AS paquete, vp.id_promocion
        FROM ventas_paquetes_gym vp
        JOIN paquetes_gym p ON p.id_paquete = vp.id_paquete
        WHERE vp.id_cliente = :id_cliente
          AND vp.dias_restantes > 0
        ORDER BY vp.fecha DESC
        LIMIT 1
    ");
    $stmtV->bindParam(':id_cliente', $id_cliente);
    $stmtV->execute();
    $venta = $stmtV->fetch(PDO::FETCH_ASSOC);

    if (!$venta) {
        $response->getBody()->write(json_encode([
            'acceso'  => false,
            'mensaje' => 'Acceso denegado: membresía vencida'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // Buscar datos de la promoción si existe
    $nombre_promocion = null;
    $descuento_promocion = null;
    if (!empty($venta['id_promocion'])) {
        $stmtPromo = $db->prepare("SELECT nombre, descuento FROM promociones_gym WHERE id_promocion = :id_promocion");
        $stmtPromo->bindParam(':id_promocion', $venta['id_promocion']);
        $stmtPromo->execute();
        $promo = $stmtPromo->fetch(PDO::FETCH_ASSOC);
        if ($promo) {
            $nombre_promocion = $promo['nombre'];
            $descuento_promocion = $promo['descuento'];
        }
    }

    // Registrar la entrada en la tabla entradas
    $insert = $db->prepare("INSERT INTO entradas (fecha) VALUES (NOW())");
    $insert->execute();

    // Acceso concedido
    $response->getBody()->write(json_encode([
        'acceso'         => true,
        'dias_restantes' => (int)$venta['dias_restantes'],
        'paquete'        => $venta['paquete'],
        'promocion'      => $nombre_promocion,
        'descuento'      => $descuento_promocion
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});


$app->get('/gym/dashboard', function (Request $request, Response $response) use ($db) {
    // Ventas del día - Productos
    $stmt = $db->prepare("
        SELECT IFNULL(SUM((precio) ),0) 
        FROM ventas_productos_gym 
        WHERE DATE(fecha) = CURDATE()
    ");
    $stmt->execute();
    $ventasProdDia = (float)$stmt->fetchColumn();

    // Ventas del día - Servicios
    $stmt = $db->prepare("
        SELECT IFNULL(SUM(total),0) 
        FROM venta_servicios_gym 
        WHERE DATE(fecha) = CURDATE()
    ");
    $stmt->execute();
    $ventasServDia = (float)$stmt->fetchColumn();

    // Ventas del día - Paquetes
    $stmt = $db->prepare("
       SELECT IFNULL(SUM((precio)),0) 
        FROM ventas_paquetes_gym 
        WHERE DATE(fecha) = CURDATE()
    ");
    $stmt->execute();
    $ventasPaqDia = (float)$stmt->fetchColumn();

    $ventas_dia = $ventasProdDia + $ventasServDia + $ventasPaqDia;

    // Ventas del mes - Productos
    $stmt = $db->prepare("
        SELECT IFNULL(SUM((precio)),0) 
        FROM ventas_productos_gym 
        WHERE YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE())
    ");
    $stmt->execute();
    $ventasProdMes = (float)$stmt->fetchColumn();

    // Ventas del mes - Servicios
    $stmt = $db->prepare("
        SELECT IFNULL(SUM(total),0) 
        FROM venta_servicios_gym 
        WHERE YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE())
    ");
    $stmt->execute();
    $ventasServMes = (float)$stmt->fetchColumn();

    // Ventas del mes - Paquetes
    $stmt = $db->prepare("
        SELECT IFNULL(SUM((precio)),0) 
        FROM ventas_paquetes_gym 
        WHERE YEAR(fecha)=YEAR(CURDATE()) AND MONTH(fecha)=MONTH(CURDATE())
    ");
    $stmt->execute();
    $ventasPaqMes = (float)$stmt->fetchColumn();

    $ventas_mes = $ventasProdMes + $ventasServMes + $ventasPaqMes;

    // Clientes registrados
    $stmt = $db->query("SELECT COUNT(*) FROM clientes_gym");
    $clientes_registrados = (int)$stmt->fetchColumn();

    // Membresías activas (dias_restantes > 0)
    $stmt = $db->query("SELECT COUNT(*) FROM ventas_paquetes_gym WHERE dias_restantes > 0");
    $membresias_activas = (int)$stmt->fetchColumn();

    // Asistencias hoy
    $stmt = $db->query("SELECT COUNT(*) FROM entradas WHERE DATE(fecha)=CURDATE()");
    $asistencias_hoy = (int)$stmt->fetchColumn();

    // Membresías por vencer (< 10 días restantes)
    $stmt = $db->query("SELECT COUNT(*) FROM ventas_paquetes_gym WHERE dias_restantes > 0 AND dias_restantes < 10");
    $membresias_por_vencer = (int)$stmt->fetchColumn();

    $dashboard = [
        'ventas_dia'            => $ventas_dia,
        'ventas_mes'            => $ventas_mes,
        'clientes_registrados'  => $clientes_registrados,
        'membresias_activas'    => $membresias_activas,
        'asistencias_hoy'       => $asistencias_hoy,
        'membresias_por_vencer' => $membresias_por_vencer,
    ];

    $response->getBody()->write(json_encode($dashboard));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});

$app->get('/gym/entradas/semana', function (Request $request, Response $response) use ($db) {
    // Contar por día (0 = Lunes … 6 = Domingo) solo de la semana actual
    $stmt = $db->prepare(<<<'SQL'
        SELECT
          WEEKDAY(fecha) AS dia_num,
          COUNT(*)       AS cantidad
        FROM entradas
        WHERE YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)
        GROUP BY dia_num
        ORDER BY dia_num
    SQL
    );
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar todos los días en 0
    $dias = [
      'Lunes'     => 0,
      'Martes'    => 0,
      'Miércoles' => 0,
      'Jueves'    => 0,
      'Viernes'   => 0,
      'Sábado'    => 0,
      'Domingo'   => 0
    ];

    // Rellenar con los datos reales
    foreach ($rows as $r) {
        $idx = (int)$r['dia_num'];
        $nombre = array_keys($dias)[$idx];
        $dias[$nombre] = (int)$r['cantidad'];
    }

    // Construir salida
    $salida = [];
    foreach ($dias as $dia => $cant) {
        $salida[] = ['dia' => $dia, 'cantidad' => $cant];
    }

    $response->getBody()->write(json_encode($salida));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});

$app->get('/gym/ventas/servicios', function (Request $request, Response $response) use ($db) {
    $params = $request->getQueryParams();
    $fecha  = $params['fecha'] ?? null;

    // 1) Consulta base con filtro de sólo fechas agendadas >= hoy
    $query = "
        SELECT 
            vs.id_venta,
            vs.fecha,
            vs.fecha_agendada,
            vs.precio,
            vs.cantidad,
            vs.total,
            s.nombre AS nombre_servicio,
            CONCAT(c.nombre, ' ', c.apellido_paterno) AS nombre_cliente
        FROM venta_servicios_gym vs
        JOIN servicios_gym s ON s.id_servicio = vs.id_servicio
        JOIN clientes_gym c ON c.id_cliente = vs.id_cliente
        WHERE DATE(vs.fecha_agendada) >= CURDATE()
    ";

    // 2) Si llega parámetro `fecha`, lo añadimos como filtro adicional
    if ($fecha) {
        $query .= " AND (DATE(vs.fecha) = :fecha OR DATE(vs.fecha_agendada) = :fecha)";
    }

    $stmt = $db->prepare($query);

    // 3) Bind de parámetro si aplica
    if ($fecha) {
        $stmt->bindParam(':fecha', $fecha);
    }

    // 4) Ejecutar y devolver resultados
    $stmt->execute();
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response->getBody()->write(json_encode($ventas, JSON_UNESCAPED_UNICODE));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});



//FIN ENDPOINTS GYM



//ENDPOINTS CLIENTES GYM

$app->get('/gym/usuarios', function (Request $request, Response $response) use ($db) {
    $sql = "SELECT id_usuario, usuario FROM usuarios_gym";
    try {
        $stmt = $db->query($sql);
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($clientes));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (PDOException $e) {
        $error = ["error" => $e->getMessage()];
        $response->getBody()->write(json_encode($error));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// Endpoint para verificar estado del permiso
$app->get('/gym/corte-caja/estado', function (Request $request, Response $response) use ($db) {
    $stmt = $db->query("SELECT permiso_activo, ultimo_corte FROM permisos_corte_caja WHERE id = 1");
    $estado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response->getBody()->write(json_encode($estado));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// Endpoint para cambiar estado del permiso (solo admin)
$app->post('/gym/corte-caja/toggle-permiso', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);
    $id_usuario = $data['id_usuario'] ?? null;
    
    if ($id_usuario != 1) {
        $response->getBody()->write(json_encode(["error" => "No autorizado"]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }
    
    // Cambiar el estado del permiso
    $db->query("UPDATE permisos_corte_caja SET permiso_activo = NOT permiso_activo WHERE id = 1");
    
    // Obtener y devolver el nuevo estado
    $stmt = $db->query("SELECT permiso_activo FROM permisos_corte_caja WHERE id = 1");
    $nuevoEstado = $stmt->fetchColumn();
    
    $response->getBody()->write(json_encode(["permiso_activo" => (bool)$nuevoEstado]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// Endpoint para generar reporte de corte de caja
$app->post('/gym/corte-caja/reporte', function (Request $request, Response $response) use ($db) {
    $data = json_decode($request->getBody()->getContents(), true);
    $idUsuario = $data['id_usuario'] ?? null;

    if (!$idUsuario) {
        $response->getBody()->write(json_encode([
            "error" => "ID de usuario no proporcionado"
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $esAdmin = ($idUsuario == 1); // Admin identificado (ya no usaremos excepción más adelante)

    // 1. Verificar si los cortes están permitidos
    $stmtPermiso = $db->query("SELECT permiso_activo FROM permisos_corte_caja WHERE id = 1");
    $permisoActivo = (bool) $stmtPermiso->fetchColumn();

    if (!$permisoActivo && $idUsuario != 1) { // Solo admin puede saltarse permiso inactivo
        $response->getBody()->write(json_encode([
            "error" => "Los cortes de caja están desactivados temporalmente"
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // 2. Verificar si ya se hizo un corte hoy (ahora aplica para TODOS, incluso admin)
    $stmtUltimoCorte = $db->query("SELECT ultimo_corte FROM permisos_corte_caja WHERE id = 1");
    $ultimoCorte = $stmtUltimoCorte->fetchColumn();

    if ($ultimoCorte && date('Y-m-d', strtotime($ultimoCorte)) === date('Y-m-d')) {
        $response->getBody()->write(json_encode([
            "error" => "Ya se realizó un corte de caja hoy. Debe resetearlo primero.",
            "ultimo_corte" => $ultimoCorte
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // 2. Verificar horario laboral 
    /*
    date_default_timezone_set('America/Mexico_City');
    $horaActual = (int) date('H');

    if (($horaActual < 8 || $horaActual >= 22)) {
        $response->getBody()->write(json_encode([
            "error" => "El corte de caja solo está disponible de 8:00 a 22:00 hrs"
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }
    */

    // 3. Verificar si ya se hizo un corte hoy (ahora bloquea a todos, incluso admin)
    $stmtUltimoCorte = $db->query("SELECT ultimo_corte FROM permisos_corte_caja WHERE id = 1");
    $ultimoCorte = $stmtUltimoCorte->fetchColumn();

    if ($ultimoCorte && date('Y-m-d', strtotime($ultimoCorte)) === date('Y-m-d')) {
        $response->getBody()->write(json_encode([
            "error" => "Ya se realizó un corte de caja hoy",
            "ultimo_corte" => $ultimoCorte
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    // 4. Obtener ventas del día
    $ventasDia = [];

    // Productos
    $stmtProd = $db->query("
        SELECT p.nombre, vp.cantidad, vp.precio, vp.descuento, 
               (vp.precio) AS total
        FROM ventas_productos_gym vp
        JOIN productos_gym p ON p.id_producto = vp.id_producto
        WHERE DATE(vp.fecha) = CURDATE()
    ");
    $ventasDia['productos'] = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

    // Servicios
    $stmtServ = $db->query("
        SELECT s.nombre, vs.cantidad, vs.precio, vs.descuento, vs.total
        FROM venta_servicios_gym vs
        JOIN servicios_gym s ON s.id_servicio = vs.id_servicio
        WHERE DATE(vs.fecha) = CURDATE()
    ");
    $ventasDia['servicios'] = $stmtServ->fetchAll(PDO::FETCH_ASSOC);

    // Paquetes
    $stmtPaq = $db->query("
        SELECT pg.nombre, vp.meses, vp.precio, vp.descuento, 
               (vp.precio) AS total
        FROM ventas_paquetes_gym vp
        JOIN paquetes_gym pg ON pg.id_paquete = vp.id_paquete
        WHERE DATE(vp.fecha) = CURDATE()
    ");
    $ventasDia['paquetes'] = $stmtPaq->fetchAll(PDO::FETCH_ASSOC);

    // 5. Calcular total general
    $totalProductos = array_sum(array_column($ventasDia['productos'], 'total'));
    $totalServicios = array_sum(array_column($ventasDia['servicios'], 'total'));
    $totalPaquetes = array_sum(array_column($ventasDia['paquetes'], 'total'));
    $ventasDia['total_general'] = $totalProductos + $totalServicios + $totalPaquetes;

    // 6. Registrar la fecha del último corte
    $db->query("UPDATE permisos_corte_caja SET ultimo_corte = NOW() WHERE id = 1");

    // 7. Enviar respuesta
    $response->getBody()->write(json_encode($ventasDia));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

// Endpoint para reiniciar el contador de cortes (solo admin)
$app->post('/gym/corte-caja/reset', function (Request $request, Response $response) use ($db) {
    // Verificar que sea admin (puedes implementar tu lógica de autenticación aquí)
    
    $db->query("UPDATE permisos_corte_caja SET ultimo_corte = NULL WHERE id = 1");
    
    $response->getBody()->write(json_encode([
        "mensaje" => "Contador de corte diario reiniciado",
        "permiso_activo" => true
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});



$app->run();