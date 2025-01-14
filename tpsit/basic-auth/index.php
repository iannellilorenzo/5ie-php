<?php
file_put_contents("log.txt", trim(file_get_contents("log.txt") . "\n" . json_encode($_SERVER))); // crea il file prima

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="My Realm"');
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['code' => 401, 'message' => 'Unauthorized: No credentials provided.']);
    exit;
} else {
    $username = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];

    // Verifica le credenziali
    $whitelist = json_decode(file_get_contents('whitelist.json'), true);
    $user = array_filter($whitelist, function($user) use ($username, $password) {
        return $user['username'] === $username && $user['password'] === $password;
    });

    if (!empty($user)) {
        $user = array_values($user)[0]; // Prendi il primo utente corrispondente

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            echo json_encode(['code' => 200, 'message' => 'GET request is allowed for whitelisted users.']);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            if ($user['role'] === 'admin') {
                echo json_encode(['code' => 200, 'message' => 'Request allowed for admin.']);
            } else {
                header('HTTP/1.0 403 Forbidden');
                echo json_encode(['code' => 403, 'message' => 'Forbidden: You do not have the necessary permissions.']);
            }
        } else {
            header('HTTP/1.0 405 Method Not Allowed');
            echo json_encode(['code' => 405, 'message' => 'Request method not supported.']);
        }
    } else {
        header('HTTP/1.0 403 Forbidden');
        echo json_encode(['code' => 403, 'message' => 'Invalid credentials.']);
    }
}