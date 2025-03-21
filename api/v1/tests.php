<?php
function test_create($id, $title, $info) {
    global $conn;
    
    if (empty($title)) {
        return ['error' => 'Неполные входные данные.'];
    }

    $query = "INSERT INTO tests (owner, title, info) VALUES (?, ?, ?)";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'iss', $id, $title, $info);
    mysqli_stmt_execute($query);
    $inserted_id = mysqli_insert_id($conn);

    $query = "SELECT * FROM tests WHERE id = ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'i', $inserted_id);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    $result = mysqli_fetch_assoc($result);

    return $result;
}

function tests_list($limit, $offset) {
    global $conn;
    
    if ($limit < 1) $limit = 1;
    if ($limit > 100) $limit = 100;
    if ($offset < 0) $offset = 0;

    $query = "SELECT id, title FROM tests WHERE is_published = 1 LIMIT ? OFFSET ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'ii', $limit, $offset);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    while ($result_row = mysqli_fetch_assoc($result)) {
        $response[] = $result_row;
    }
    if ($response == null) $response = [];
    return $response;
}


switch ($method) {
    case 'GET':
        
        // Получение списка тестов
        if (empty($request[4])) {
                empty($_GET['limit']) ? $limit = 10 : $limit = $_GET['limit'];
                empty($_GET['offset']) ? $offset = 0 : $offset = $_GET['offset'];
                $response = tests_list($limit, $offset);
        }
        break;


    case 'POST':
        
        if (empty($request[4])) {
            $payload = jwt_input();
            $response = test_create($payload['id'], $_POST['title'], $_POST['info']);
        }
        break;

        
    case 'PUT':
        break;


    case 'DELETE':
        break;
        
    
    default:
        http_response_code(405);
        $response = ['error' => 'Метод не поддерживается.'];
        break;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>