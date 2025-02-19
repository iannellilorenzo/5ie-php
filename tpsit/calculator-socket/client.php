<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Accesso non consentito');
}

$operazione = $_POST['operation'] ?? '';

// Crea il socket client
$client = socket_create(AF_INET, SOCK_STREAM, 0);

if (socket_connect($client, "127.0.0.1", 12345)) {
    // Invia l'operazione al server
    socket_write($client, $operazione, strlen($operazione));
    
    // Ricevi il risultato dal server
    $risultato = socket_read($client, 1024);
    
    echo $risultato;
} else {
    echo "Errore di connessione al server";
}

socket_close($client);
?>