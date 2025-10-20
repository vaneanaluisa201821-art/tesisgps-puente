<?php
header('Content-Type: application/json');
define('FIREBASE_URL', 'https://tesis-rutas-default-rtdb.firebaseio.com');
define('FIREBASE_AUTH', 'nNJG3KfrnUquYIodTuY9oWsq0CTvdpZOpsgXNKGi');

$mensaje = $_GET['msg'] ?? null;
$latitud = $_GET['lat'] ?? null;
$longitud = $_GET['lng'] ?? null;
$velocidad = $_GET['vel'] ?? null;

if (!$mensaje && !$latitud) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron datos']);
    exit;
}

$datos = [
    'mensaje' => $mensaje,
    'latitud' => $latitud,
    'longitud' => $longitud,
    'velocidad' => $velocidad,
    'timestamp' => time(),
    'fecha' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR']
];

$firebase_endpoint = FIREBASE_URL . '/datos_arduino.json?auth=' . FIREBASE_AUTH;

$ch = curl_init($firebase_endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($http_code >= 200 && $http_code < 300) {
    $firebase_response = json_decode($response, true);
    echo json_encode([
        'status' => 'success',
        'message' => 'Datos enviados a Firebase',
        'firebase_key' => $firebase_response['name'] ?? 'unknown',
        'data' => $datos
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al enviar a Firebase',
        'http_code' => $http_code,
        'error' => $error,
        'response' => $response
    ]);
}
?>
