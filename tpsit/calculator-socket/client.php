<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Accesso non consentito');
}

$operazione = $_POST['operation'] ?? '';
$mode = $_POST['mode'] ?? 'basic';

$data = json_encode([
    'operation' => $operazione,
    'mode' => $mode
]);

$client = socket_create(AF_INET, SOCK_STREAM, 0);

if (socket_connect($client, "127.0.0.1", 12345)) {
    socket_write($client, $data, strlen($data));
    $risultato = socket_read($client, 1024);
    echo $risultato;
} else {
    echo "Errore di connessione al server";
}

socket_close($client);
?>