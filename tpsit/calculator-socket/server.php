<?php
// Crea il socket server
$server = socket_create(AF_INET, SOCK_STREAM, 0);
socket_bind($server, "127.0.0.1", 12345);
socket_listen($server, 3);

echo "Server in ascolto sulla porta 12345...\n";

function scientific_calc($expression) {
    // Sostituisce i simboli speciali
    $expression = str_replace('π', M_PI, $expression);
    $expression = str_replace('e', M_E, $expression);
    
    // Gestisce le funzioni matematiche
    $expression = preg_replace_callback('/sin\((.*?)\)/', function($m) {
        return sin(eval("return {$m[1]};"));
    }, $expression);
    
    $expression = preg_replace_callback('/cos\((.*?)\)/', function($m) {
        return cos(eval("return {$m[1]};"));
    }, $expression);
    
    $expression = preg_replace_callback('/tan\((.*?)\)/', function($m) {
        return tan(eval("return {$m[1]};"));
    }, $expression);
    
    $expression = preg_replace_callback('/log\((.*?)\)/', function($m) {
        return log10(eval("return {$m[1]};"));
    }, $expression);
    
    $expression = preg_replace_callback('/ln\((.*?)\)/', function($m) {
        return log(eval("return {$m[1]};"));
    }, $expression);
    
    $expression = preg_replace_callback('/sqrt\((.*?)\)/', function($m) {
        return sqrt(eval("return {$m[1]};"));
    }, $expression);
    
    return $expression;
}

while (true) {
    $client = socket_accept($server);
    $input = socket_read($client, 1024);
    
    // Decodifica input come JSON
    $data = json_decode($input, true);
    $expression = $data['operation'] ?? '';
    
    echo "Operazione ricevuta: " . $expression . "\n";
    
    // Permette solo numeri e operatori di base
    $pattern = '/[^0-9\+\-\*\/\(\)\.]/';
    $input_pulito = preg_replace($pattern, '', $expression);
    
    if ($expression !== $input_pulito) {
        $risposta = "Errore: caratteri non validi nell'operazione";
    } else {
        try {
            $risultato = eval("return " . $input_pulito . ";");
            $risposta = ($risultato === false) ? "Errore: operazione non valida" : (string)$risultato;
        } catch (Exception $e) {
            $risposta = "Errore nel calcolo";
        }
    }
    
    socket_write($client, $risposta, strlen($risposta));
    socket_close($client);
}
