<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

require __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function jwt_encode($payload) {
    return JWT::encode($payload, JWT_SECRET_KEY, 'HS256');
}

function jwt_decode($jwt) {
    global $conn;
    
    try {
        $decode = JWT::decode($jwt, new Key(JWT_SECRET_KEY, 'HS256'));
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => "Токен недействителен."]);
        die();
    }
    
    $query = "SELECT COUNT(*) as count FROM users WHERE id = ? AND login = ? AND password = ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'sss', $decode->id, $decode->login, $decode->password);
    mysqli_stmt_execute($query);
    mysqli_stmt_bind_result($query, $count);
    mysqli_stmt_fetch($query);

    if ($count == 1) return $decode;
    http_response_code(401);
    echo json_encode(['error' => 'Токен недействителен.'], JSON_UNESCAPED_UNICODE);
    die();
}

header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
$conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка подключения БД.'], JSON_UNESCAPED_UNICODE);
    die();
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', explode('?', $_SERVER['REQUEST_URI'])[0]);
if ($request[1] == 'api') {
    $version = $request[2];
    $object = $request[3];
}

if ($version == 'v1') {
    if ($object == '') {
        http_response_code(404);
        echo json_encode(['error' => 'Объект не указан.'], JSON_UNESCAPED_UNICODE);
        die();
    }
    $object_file = "$version/$object.php";
    if (file_exists($object_file)) {
        include $object_file;
    }else{
        http_response_code(404);
        echo json_encode(['error' => 'Объект не найден.'], JSON_UNESCAPED_UNICODE);
    }
}else{
    http_response_code(404);
    echo json_encode(['error' => 'Версия API не найдена.'], JSON_UNESCAPED_UNICODE);
}
?>