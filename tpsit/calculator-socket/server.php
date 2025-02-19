<?php
// Crea il socket server
$server = socket_create(AF_INET, SOCK_STREAM, 0);
socket_bind($server, "127.0.0.1", 12345);
socket_listen($server, 3);

echo "Server in ascolto sulla porta 12345...\n";

while (true) {
    // Accetta la connessione dal client
    $client = socket_accept($server);
    
    // Ricevi l'operazione dal client
    $input = socket_read($client, 1024);
    echo "Operazione ricevuta: " . $input . "\n";
    
    // Controlla se l'input contiene caratteri non validi
    $input_pulito = preg_replace('/[^0-9\+\-\*\/\(\)\.]/', '', $input);
    
    if ($input !== $input_pulito) {
        $risposta = "Errore: caratteri non validi nell'operazione. Usa solo numeri e operatori (+,-,*,/,.,())";
    } else {
        try {
            $risultato = eval("return " . $input_pulito . ";");
            if ($risultato === false) {
                $risposta = "Errore: operazione non valida";
            } else {
                $risposta = (string)$risultato;
            }
        } catch (Exception $e) {
            $risposta = "Errore nel calcolo";
        }
    }
    
    // Invia il risultato al client
    socket_write($client, $risposta, strlen($risposta));
    
    // Chiudi la connessione
    socket_close($client);
}

socket_close($server);