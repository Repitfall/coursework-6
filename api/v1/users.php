<?php
function userCreate($login, $password, $nickname) {
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

    $query = "SELECT * FROM users WHERE id = ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'i', $inserted_id);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    $result = mysqli_fetch_assoc($result);
    
    $payload = [
        'id' => $result['id'],
        'login' => $result['login'],
        'password' => $result['password'],
        'role' => $result['role'],
        'exp' => time() + 3600
    ];

    $jwt = jwt_encode($payload);

    return ['token' => $jwt];
}

function userLogin($login, $password) {
    global $conn;
    
    if (empty($login) || empty($password)) {
        return ['error' => 'Неполные входные данные.'];
    }

    $password = hash('sha256', $password);
    $query = "SELECT * FROM users WHERE login = ? AND password = ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'ss', $login, $password);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    $result = mysqli_fetch_assoc($result);

    if (!$result) {
        return ['error' => 'Неправильный логин или пароль.'];
    }

    $payload = [
        'id' => $result['id'],
        'login' => $result['login'],
        'password' => $result['password'],
        'role' => $result['role'],
        'exp' => time() + 3600
    ];

    $jwt = jwt_encode($payload);

    return ['token' => $jwt];
}

function usersList($limit, $offset) {
    global $conn;
    
    if ($limit < 1) $limit = 1;
    if ($limit > 100) $limit = 100;
    if ($offset < 0) $offset = 0;

    $query = "SELECT nickname, date_reg FROM users LIMIT ? OFFSET ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'ii', $limit, $offset);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    while ($result_row = mysqli_fetch_assoc($result)) {
        $response[] = $result_row;
    }
    return $response;
}

function userSelect($id) {
    global $conn;
    
    $query = "SELECT nickname, date_reg FROM users WHERE id = ?";
    $query = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($query, 'i', $id);
    mysqli_stmt_execute($query);
    $result = mysqli_stmt_get_result($query);
    $result ? $response = mysqli_fetch_assoc($result) : $response = ['error' => 'Учетная запись не найдена.'];
    return $response;
}

switch ($method) {
    case 'GET':

        if ($request[4] == '') {
            if (empty($_GET['token'])) {
                http_response_code(401);
                $response = ['error' => 'Отсутствует токен.'];
            }else{
                if (jwt_decode($_GET['token'])->role < 1) {
                    http_response_code(401);
                    $response = ['error' => 'Недостаточно прав.'];
                }else{
                    empty($_GET['limit']) ? $limit = 10 : $limit = $_GET['limit'];
                    empty($_GET['offset']) ? $offset = 0 : $offset = $_GET['offset'];
                    $response = usersList($limit, $offset);
                }
            }
            break;

        }elseif (ctype_digit($request[4])) {
            if (empty($_GET['token'])) {
                http_response_code(401);
                $response = ['error' => 'Отсутствует токен.'];
            }else{
                $token = jwt_decode($_GET['token']);
                if ($token->role < 1 && $token->id != $request[4]) {
                    http_response_code(401);
                    $response = ['error' => 'Недостаточно прав.'];
                }else{
                    $response = userSelect($request[4]);
                }
            }
            break;

        }
        break;


    case 'POST':

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        switch ($request[4]) {
            case '':
                $response = userCreate($_POST['login'], $_POST['password'], $_POST['nickname']);
                break;
            case 'login':
                $response = userLogin($_POST['login'], $_POST['password']);
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