<?php
require __DIR__ . '/vendor/autoload.php';
use Google\Client;
use Google\Service\Docs;
use Google\Service\Drive;

session_start();

define('CREDENTIALS_PATH', __DIR__ . '/credentials.json');
define('TOKEN_PATH', __DIR__ . '/token.json');

function getClient() {
  $client = new Client();
  $client->setApplicationName('Google Docs API PHP');
  $client->setScopes([Docs::DOCUMENTS, Drive::DRIVE]);
  $client->setAuthConfig(CREDENTIALS_PATH);
  $client->setAccessType('offline');
  $client->setPrompt('select_account consent');

  if (file_exists(TOKEN_PATH)) {
    $accessToken = json_decode(file_get_contents(TOKEN_PATH), true);
    $client->setAccessToken($accessToken);
  }

  if ($client->isAccessTokenExpired()) {
    if ($client->getRefreshToken()) {
      $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    } else {
      return $client->createAuthUrl();
    }
  }

  return $client;
}

if (isset($_GET['code'])) {
  $client = new Client();
  $client->setAuthConfig(CREDENTIALS_PATH);
  $client->authenticate($_GET['code']);
  $accessToken = $client->getAccessToken();
  file_put_contents(TOKEN_PATH, json_encode($accessToken));
  header('Location: ' . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
  exit;
}

function copyGoogleDoc($templateDocId, $title) {
  $client = getClient();
  $driveService = new Drive($client);

  $fileMetadata = new Drive\DriveFile([
    'name' => $title
  ]);

  $copiedFile = $driveService->files->copy($templateDocId, $fileMetadata);

  return $copiedFile->id;
}

function exportGoogleDocAsHtml($docId) {
  $client = getClient();
  $driveService = new Drive($client);

  $response = $driveService->files->export($docId, 'text/html', array('alt' => 'media'));
  $content = $response->getBody()->getContents();
  
  return $content;
}

/*
Gets the first five responses from the HTML content, which have a structure of this type:
<p>
  <span>
    Question
  </span>
  <span>
    Answer
  </span>
</p>
*/
function extractFirstStructureResponsesUsingRegex($htmlContent, $responsesOffset = 0) {
    $responses = [];

    // Nuova regex per catturare 'SÌ', 'NO', o 'SELEZIONA' nel secondo tag <span> del paragrafo, con gestione accentate
    preg_match_all('/<p[^>]*>\s*<span[^>]*>.*?<\/span>\s*<span[^>]*>(SI|NO|SELEZIONA)<\/span>\s*<\/p>/u', $htmlContent, $matches);

    // Cicla i match saltando i primi $responsesOffset risultati
    for ($i = 0; $i < count($matches[1]); $i++) {
        $response = trim($matches[1][$i]);
        $responses[] = $response;
        if (count($responses) >= 5 + $responsesOffset) {
          break;
        }
    }

    return $responses;
}



/*
Gets other responses from the HTML content, which have a structure of this type:
<p>
  <span>
    <br>
  </span>
  <span>
    domanda che non mi serve avere
  </span>
</p>
<p>
  <span>
    risposta che mi serve avere
  </span>
</p>
*/
function extractResponsesFromSecondStructureUsingRegex($htmlContent) {
  $responses = [];
  $validResponses = ['SI', 'NO', 'SELEZIONA'];

  // Regex per trovare tutte le risposte nella struttura specificata, considerando gli attributi nei tag <span>
  // e assicurando che la risposta inizi con "S" o "N" maiuscole
  preg_match_all('/<p[^>]*>\s*<span[^>]*>(.*?)<\/span>\s*<\/p>/s', $htmlContent, $matches);

  // Aggiungi le risposte all'array, ignorando le prime $startIndex risposte
  for ($i = 0; $i < count($matches[1]); $i++) {
    $response = trim($matches[1][$i]);
    // Valida la risposta
    if (in_array($response, $validResponses)) {
      $responses[] = $response;
    }
  }

  array_pop($responses); // Rimuovi l'ultima risposta, che non è necessaria
  array_pop($responses); // Rimuovi la penultima risposta, che non è necessaria

  return $responses;
}

function extractValuesFromFirstTableUsingRegex($htmlContent) {
  $values = [];

  // Regex per trovare la tabella con l'intestazione specificata
  preg_match('/<table[^>]*>.*?<tr[^>]*>.*?OGGETTI DI PESO SUPERIORE O UGUALE A 3 KG MOVIMENTATI MANUALMENTE.*?<\/tr>(.*?)<\/table>/is', $htmlContent, $tableMatch);

  if (isset($tableMatch[1])) {
    // Regex per trovare tutte le righe, ignorando la prima riga di intestazione
    preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableMatch[1], $rows);
    for ($i = 1; $i < count($rows[1]); $i++) {
      // Regex per trovare tutte le celle
      preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $rows[1][$i], $cells);
      $rowValues = [
        'descrizione' => '',
        'numero_oggetti_sollevati' => '',
        'frequenza' => '',
        'peso' => '',
        'durata' => '',
        'numero_lavoratori_coinvolti' => ''
      ];
      $allEmpty = true;
      $hasEmpty = false;

      for ($j = 0; $j < count($cells[1]); $j++) {
        // Regex per trovare i valori nei tag <span>
        preg_match('/<span[^>]*>(.*?)<\/span>/is', $cells[1][$j], $span);
        if (isset($span[1])) {
          $value = trim($span[1]);
          if (!empty($value)) {
            $allEmpty = false;
            switch ($j) {
              case 0:
                $rowValues['descrizione'] = $value;
                break;
              case 1:
                $rowValues['numero_oggetti_sollevati'] = $value;
                break;
              case 2:
                $rowValues['frequenza'] = $value;
                break;
              case 3:
                $rowValues['peso'] = $value;
                break;
              case 4:
                $rowValues['durata'] = $value;
                break;
              case 5:
                $rowValues['numero_lavoratori_coinvolti'] = $value;
                break;
            }
          } else {
            $hasEmpty = true;
          }
        } else {
          $hasEmpty = true;
        }
      }

      if ($allEmpty) {
        // Ignora la riga se tutte le celle sono vuote
        continue;
      } elseif ($hasEmpty) {
        // Aggiungi un messaggio se c'è almeno una cella vuota
        return "Compilare tutta la riga o lasciare vuote tutte le celle";
      } else {
        // Aggiungi i valori della riga se tutte le celle hanno un valore
        $values[] = $rowValues;
      }
    }
  }

  if (empty($values)) {
    return "Compilare interamente almeno una riga della tabella.";
  }

  return $values;
}

function extractValuesFromTablesUsingRegex($htmlContent) {
  $values = [];

  // Regex per trovare tutte le tabelle
  preg_match_all('/<table.*?>(.*?)<\/table>/is', $htmlContent, $tables);

  foreach ($tables[1] as $table) {
    // Regex per trovare tutte le righe, ignorando la prima riga
    preg_match_all('/<tr.*?>(.*?)<\/tr>/is', $table, $rows);
    for ($i = 1; $i < count($rows[1]); $i++) {
      // Regex per trovare tutte le celle, ignorando la prima colonna
      preg_match_all('/<td.*?>(.*?)<\/td>/is', $rows[1][$i], $cells);
      for ($j = 1; $j < count($cells[1]); $j++) {
        // Regex per trovare i valori numerici nei tag <span>
        preg_match('/<span.*?>(.*?)<\/span>/is', $cells[1][$j], $span);
        if (isset($span[1]) && is_numeric(trim($span[1])) && trim($span[1] !== '1')) {
          $values[] = trim($span[1]);
        }
      }
    }
  }

  if (count($values) !== 1) {
    return "Un solo valore selezionabile, cancellare gli altri valori presenti nelle celle.";
  }

  return $values;
}

// Funzione che associa ogni fattore alla sua descrizione


function printResponsesForDebugging($responses) {
  echo "<pre>";
  print_r($responses);
  echo "</pre>";
}

// Funzione per generare il risultato basato sulle risposte
function generateResult($firstFiveResponses, $htmlContent) {
  if (in_array('SELEZIONA', $firstFiveResponses)) {
    return 'Cinque: Compilare tutto il file.';
  }
  
  if (!in_array('SI', $firstFiveResponses)) {
    return 'Cinque: A seguito delle risposte fornite, non è necessario effettuare alcuna azione riguardo il rischio di movimento manuale dei carichi. La situazione si trova quindi in regola con la normativa vigente.';
  }

  $sixResponses = extractFirstStructureResponsesUsingRegex($htmlContent, 1);
  $sixth = array_pop($sixResponses);
  
  if ($sixth === 'SELEZIONA') {
    return 'Sesta: Compilare tutto il file';
  }

  if ($sixth === 'NO') {
    return 'Sesta: A seguito delle risposte fornite, non è necessario effettuare alcuna azione riguardo il rischio di movimento manuale dei carichi. La situazione si trova quindi in regola con la normativa vigente.';
  }
  
  $sevenResponses = extractFirstStructureResponsesUsingRegex($htmlContent, 2);
  $seventh = array_pop($sevenResponses);

  if ($seventh === 'SELEZIONA') {
    return 'Settima: Compilare tutto il file';
  }

  if ($seventh === 'SI') {
    return 'Settima: A seguito delle risposte fornite, non è necessario effettuare alcuna azione riguardo il rischio di movimento manuale dei carichi. La situazione si trova quindi in regola con la normativa vigente.';
  }

  $tableValues = extractValuesFromFirstTableUsingRegex($htmlContent);
  if (getType($tableValues) === 'string') {
    return "Tabella `OGGETTI DI PESO SUPERIORE O UGUALE A 3 KG MOVIMENTATI MANUALMENTE NELL'ARCO DELLA GIORNATA LAVORATIVA`: {$tableValues}";
  }

  $lastResponses = extractResponsesFromSecondStructureUsingRegex($htmlContent);
  $fifteenResponses = array_slice($lastResponses, 0, 15);
  $unique = array_unique($fifteenResponses);
  
  if (count($unique) === 1 && $unique[0] === 'SI') {
    return 'Fino alla 15: A seguito delle risposte fornite, non è necessario effettuare alcuna azione riguardo il rischio di movimento manuale dei carichi. La situazione si trova quindi in regola con la normativa vigente.';
  }
  
  if (in_array('SELEZIONA', $fifteenResponses)) {
    return 'Fino alla 15: Compilare tutte le risposte prima di procedere';
  }

  $lastThreeResponses = array_slice($lastResponses, -3);
  if (in_array('SELEZIONA', $lastThreeResponses)) {
    return 'Dalla 16 alla fine: Compilare tutte le risposte prima di procedere';
  }

  $weightConstant = extractValuesFromTablesUsingRegex($htmlContent);
  if (getType($weightConstant) === 'string') {
    return "Tabella `COSTANTE DI PESO`: {$weightConstant}";
  }
  
  // Da qui in poi è tutto con combobox o da calcolare con la prima tabella
}

$docId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: text/html');
  try {
    if ($_POST['action'] === 'createDoc') {
      $docTitle = $_POST['docTitle'];
      $templateDocId = '1Pj8iTzPnyZjy-37ecy5cWWZgEJElZBb115_jypSzVwk';

      $docId = copyGoogleDoc($templateDocId, $docTitle);

      if ($docId) {
        $_SESSION['docId'] = $docId;
        $docUrl = "https://docs.google.com/document/d/$docId";
        echo json_encode(['docId' => $docId, 'docUrl' => $docUrl]);
        exit;
      } else {
        echo json_encode(['error' => 'Failed to create document.']);
        exit;
      }
    } elseif ($_POST['action'] === 'exportDoc') {
      $docId = $_POST['docId'];
      $htmlContent = exportGoogleDocAsHtml($docId);
      // echo $htmlContent; // Debug
      $firstFiveResponses = extractFirstStructureResponsesUsingRegex($htmlContent, 0);
      $result = generateResult($firstFiveResponses, $htmlContent);
      echo $result;
      exit;
    }
  } catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
  }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <title>MMC - DVR</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
  </head>
  <body>
    <div id="loading">
      <img src="assets/loading.gif" alt="Loading...">
    </div>
    <button id="check-results-btn" class="btn btn-primary" onclick="checkResults()">Controlla i risultati</button>
    <div class="container mt-5" id="main-content">
      <h1 class="text-center mb-4">Movimento Manuale Carichi (MMC) - Cosa puoi fare</h1>
      
      <!-- Form for Creating a Google Doc -->
      <form id="create-doc-form" action="index.php" method="post">
        <input type="hidden" name="action" value="createDoc">
        <div class="form-group">
          <label for="docTitle">Utilizza la nostra base creando un Documento Google</label>
          <input type="text" class="form-control" id="docTitle" name="docTitle" placeholder="Inserisci il titolo del documento" required>
        </div>
        <button type="submit" class="btn btn-primary">Crea Documento</button>
      </form>

      <!-- Form for Checking Results of an Existing Google Doc -->
      <form id="check-doc-form" action="index.php" method="post" class="mt-4">
        <input type="hidden" name="action" value="exportDoc">
        <div class="form-group">
          <label for="docId">Inserisci l'ID del Documento Google per controllare i risultati</label>
          <input type="text" class="form-control" id="docId" name="docId" placeholder="Inserisci l'ID del documento" required>
        </div>
        <button type="submit" class="btn btn-secondary">Controlla i Risultati</button>
      </form>
    </div>
    <div id="output" class="mt-5">
      <!-- Output will be displayed here -->
    </div>
  </body>
</html>