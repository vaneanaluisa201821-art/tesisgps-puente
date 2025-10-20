<?php
// puente.php - Recibe datos del Arduino y los envía a Firebase Realtime Database

header('Content-Type: application/json');

// ===== CONFIGURACIÓN FIREBASE =====
define('FIREBASE_URL', 'https://tesis-rutas-default-rtdb.firebaseio.com');
define('FIREBASE_AUTH', 'nNJG3KfrnUquYIodTuY9oWsq0CTvdpZOpsgXNKGi');

// ===== RECIBIR DATOS DEL ARDUINO =====
$mensaje = isset($_GET['msg']) ? $_GET['msg'] : null;
$latitud = isset($_GET['lat']) ? $_GET['lat'] : null;
$longitud = isset($_GET['lng']) ? $_GET['lng'] : null;
$velocidad = isset($_GET['vel']) ? $_GET['vel'] : null;

// Verificar que llegó al menos un dato
if (!$mensaje && !$latitud) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se recibieron datos'
    ]);
    exit;
}

// ===== PREPARAR DATOS PARA FIREBASE =====
$datos = [
    'mensaje' => $mensaje,
    'latitud' => $latitud,
    'longitud' => $longitud,
    'velocidad' => $velocidad,
    'timestamp' => time(),
    'fecha' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR']
];

// Limpiar valores null
$datos = array_filter($datos, function($value) {
    return $value !== null;
});

// ===== ENVIAR A FIREBASE =====
// Usar la ruta /datos_arduino y crear un nuevo registro con push
$firebase_endpoint = FIREBASE_URL . '/datos_arduino.json?auth=' . FIREBASE_AUTH;

$ch = curl_init($firebase_endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// ===== RESPONDER AL ARDUINO =====
if ($http_code >= 200 && $http_code < 300) {
    $firebase_response = json_decode($response, true);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Datos enviados a Firebase',
        'firebase_key' => isset($firebase_response['name']) ? $firebase_response['name'] : 'unknown',
        'data' => $datos
    ]);
    
    // Log local (opcional)
    $log = date('Y-m-d H:i:s') . " | SUCCESS | " . json_encode($datos) . "\n";
    @file_put_contents('log.txt', $log, FILE_APPEND);
    
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al enviar a Firebase',
        'http_code' => $http_code,
        'error' => $error,
        'response' => $response
    ]);
    
    // Log de errores
    $log = date('Y-m-d H:i:s') . " | ERROR | Code: $http_code | " . $error . "\n";
    @file_put_contents('error_log.txt', $log, FILE_APPEND);
}
?>
