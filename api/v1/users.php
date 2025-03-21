<?php
function user_create($login, $password, $nickname) {
    global $conn;
    
    if (empty($login) || empty($password) || empty($nickname)) {
        return ['error' => 'Неполные входные данные.'];
    }
    
    $password = hash('sha256', $password);
    $query = "INSERT INTO users (login, password, nickname) VALUES (?, ?, ?)";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'sss', $login, $password, $nickname);
    try {
        mysqli_stmt_execute($query);
    } catch (mysqli_sql_exception $e) {
        return ['error' => 'Неуникальный логин.'];
    }
    $inserted_id = mysqli_insert_id($conn);

    $query = "SELECT id, login, role FROM users WHERE id = ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'i', $inserted_id);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    $result = mysqli_fetch_assoc($result);
    
    jwt_output($result['id'], $result['login'], $result['role']);
    die();
}

function user_login($login, $password) {
    global $conn;
    
    if (empty($login) || empty($password)) {
        return ['error' => 'Неполные входные данные.'];
    }

    $password = hash('sha256', $password);
    $query = "SELECT id, login, role FROM users WHERE login = ? AND password = ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'ss', $login, $password);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    $result = mysqli_fetch_assoc($result);

    if (!$result) {
        return ['error' => 'Неправильный логин или пароль.'];
    }

    jwt_output($result['id'], $result['login'], $result['role']);
    die();
}

function users_list($limit, $offset) {
    global $conn;
    
    if ($limit < 1) $limit = 1;
    if ($limit > 100) $limit = 100;
    if ($offset < 0) $offset = 0;

    $query = "SELECT id, nickname, date_reg FROM users LIMIT ? OFFSET ?";
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

function user_select($id) {
    global $conn;
    
    $query = "SELECT nickname, date_reg FROM users WHERE id = ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'i', $id);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    $result = mysqli_fetch_assoc($result);
    if ($result != null) {
        $response = $result;
    }else{
        $response = ['error' => 'Учетная запись не найдена.'];
    }
    return $response;
}

switch ($method) {
    case 'GET':

        // Получение списка пользователей
        // Роль: модератор
        if (empty($request[4])) {
            $payload = jwt_input();
            if ($payload['role'] < ROLE_MODERATOR) {
                http_response_code(403);
                $response = ['error' => 'Недостаточно прав.'];
            }else{
                empty($_GET['limit']) ? $limit = 10 : $limit = $_GET['limit'];
                empty($_GET['offset']) ? $offset = 0 : $offset = $_GET['offset'];
                $response = users_list($limit, $offset);
            }
            break;

        // Получение конкретного пользователя
        }elseif (ctype_digit($request[4])) {
            $response = user_select($request[4]);
        }
        break;


    case 'POST':

        switch ($request[4]) {
            
            // Регистрация пользователя
            case '':
                $response = user_create($_POST['login'], $_POST['password'], $_POST['nickname']);
                break;

            // Авторизация пользователя
            case 'login':
                $response = user_login($_POST['login'], $_POST['password']);
                break;
            
            default:
                http_response_code(404);
                $response = ['error' => 'Действие не найдено.'];
                break;
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