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
    preg_match_all('/<p[^>]*>\s*<span[^>]*>.*?<\/span>\s*<span[^>]*>(SI|NO|SELEZIONA|\d*|&gt;\d*|BUONO|SCARSO)<\/span>\s*<\/p>/u', $htmlContent, $matches);

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

function extractValuesFromTablesUsingRegex($htmlContent, $tableIndex = 1) {
  $values = [];

  // Regex per trovare tutte le tabelle
  preg_match_all('/<table.*?>(.*?)<\/table>/is', $htmlContent, $tables);

  // Verifica se l'indice della tabella è valido
  if (isset($tables[1][$tableIndex])) {
    $selectedTable = $tables[1][$tableIndex];
  } else {
    return "Indice della tabella non valido.";
  }

  // Itera sulla tabella selezionata
  // Regex per trovare tutte le righe, ignorando la prima riga
  preg_match_all('/<tr.*?>(.*?)<\/tr>/is', $selectedTable, $rows);
  for ($i = 1; $i < count($rows[1]); $i++) {
    // Regex per trovare tutte le celle, ignorando la prima colonna
    preg_match_all('/<td.*?>(.*?)<\/td>/is', $rows[1][$i], $cells);
    for ($j = 1; $j < count($cells[1]); $j++) {
      // Regex per trovare i valori numerici nei tag <span>
      preg_match('/<span.*?>(.*?)<\/span>/is', $cells[1][$j], $span);
      if (isset($span[1])) {
        $value = trim($span[1]);
        if (is_numeric($value) || strpos($value, '>') === 0) {
          $values[] = $value;
        }
      }
    }
  }

  if ($tableIndex === 1 && count($values) % 5 !== 0) {
    return "Compilare tutta la riga o lasciare vuote tutte le celle";
  } 
  
  if ($tableIndex !== 1 && count($values) !== 1) {
    return "Un solo valore selezionabile, cancellare gli altri valori presenti nelle celle.";
  }

  return $values;
}

// Funzione che associa ogni fattore alla sua descrizione
function createAssociativeArrayWithCoefficients($descriptions, $values, $coefficients) {
  $associativeArray = [];

  // Associa ogni descrizione al suo valore e coefficiente corrispondente
  for ($i = 0; $i < count($descriptions); $i++) {
    $associativeArray[$descriptions[$i]] = [
      'value' => $values[$i],
      'coefficient' => $coefficients[$descriptions[$i]][$values[$i]]
    ];
  }

  return $associativeArray;
}

function printResponsesForDebugging($responses) {
  echo "<pre>";
  print_r($responses);
  echo "</pre>";
}

function printAssociativeArrayForDebugging($associativeArray) {
  echo "<pre>" . htmlspecialchars(print_r($associativeArray, true)) . "</pre>";
}

function deleteLinesFromGoogleDoc($documentId, $startIndex) {
    $client = getClient();
    $service = new Google\Service\Docs($client);

    // Ottieni il contenuto del documento
    $document = $service->documents->get($documentId);
    $content = $document->getBody()->getContent();
    $lastElement = end($content);
    $endIndex = $lastElement->getEndIndex() - 1;

    // Crea una richiesta di aggiornamento per rimuovere le righe specificate
    $requests = [
        new Google\Service\Docs\Request([
            'deleteContentRange' => [
                'range' => [
                    'startIndex' => $startIndex,
                    'endIndex' => $endIndex
                ]
            ]
        ])
    ];

    // Esegui la richiesta di aggiornamento
    $batchUpdateRequest = new Google\Service\Docs\BatchUpdateDocumentRequest([
        'requests' => $requests
    ]);
    $service->documents->batchUpdate($documentId, $batchUpdateRequest);

    return "Le righe da $startIndex a $endIndex sono state cancellate.";
}

function getMaxWeightFromTable($array) {
  $values = [];

  // Itera attraverso l'array e raccoglie i valori agli indici specificati
  for ($i = 2; $i < count($array); $i += 5) {
    if (isset($array[$i]) && is_numeric($array[$i])) {
      $values[] = $array[$i];
    }
  }

  // Ritorna il valore più alto tra quelli raccolti
  if (!empty($values)) {
    return max($values);
  }

  return null; // Ritorna null se non ci sono valori validi
}

function appendTextToGoogleDoc($documentId, $text) {
    $client = getClient();
    $service = new Google\Service\Docs($client);

    $document = $service->documents->get($documentId);
    $content = $document->getBody()->getContent();

    $lastElement = end($content);
    $endIndex = $lastElement->getEndIndex();

    $requests = [
        new Google\Service\Docs\Request([
            'insertText' => [
                'location' => [
                    'index' => $endIndex - 1
                ],
                'text' => $text
            ]
        ])
    ];

    // Esegui la richiesta di aggiornamento
    $batchUpdateRequest = new Google\Service\Docs\BatchUpdateDocumentRequest([
        'requests' => $requests
    ]);
    $service->documents->batchUpdate($documentId, $batchUpdateRequest);

    return "Il testo è stato aggiunto alla fine del documento.";
}

// Funzione per generare il risultato basato sulle risposte
function generateResult($htmlContent, $docId) {
  $firstFiveResponses = extractFirstStructureResponsesUsingRegex($htmlContent, 0);
  if (in_array('SELEZIONA', $firstFiveResponses)) {
    return 'Cinque: Compilare tutto il file.';
  }
  
  $stringIfGood = 'Date le condizioni di lavoro descritte nelle risposte fornite alle domande specificate sopra, non è necessario effettuare alcuna azione riguardo il rischio di movimento manuale dei carichi. La situazione lavorativa si trova quindi in regola con la normativa vigente.';
  if (!in_array('SI', $firstFiveResponses)) {
    echo deleteLinesFromGoogleDoc($docId, 439);
    appendTextToGoogleDoc($docId, $stringIfGood);
    return null;
  }

  $sixResponses = extractFirstStructureResponsesUsingRegex($htmlContent, 1);
  $sixth = array_pop($sixResponses);
  
  if ($sixth === 'SELEZIONA') {
    return 'Sesta: Compilare tutto il file';
  }

  if ($sixth === 'NO') {
    echo deleteLinesFromGoogleDoc($docId, 548);
    appendTextToGoogleDoc($docId, $stringIfGood);
    return null;
  }
  
  $sevenResponses = extractFirstStructureResponsesUsingRegex($htmlContent, 2);
  $seventh = array_pop($sevenResponses);

  if ($seventh === 'SELEZIONA') {
    return 'Settima: Compilare tutto il file';
  }

  if ($seventh === 'SI') {
    echo deleteLinesFromGoogleDoc($docId, 650);
    appendTextToGoogleDoc($docId, $stringIfGood);
    return null;
  }

  $tableValues = extractValuesFromTablesUsingRegex($htmlContent);
  if (getType($tableValues) === 'string') {
    return "Tabella `OGGETTI DI PESO SUPERIORE O UGUALE A 3 KG MOVIMENTATI MANUALMENTE NELL'ARCO DELLA GIORNATA LAVORATIVA`: {$tableValues}";
  }
  $maxWeight = getMaxWeightFromTable($tableValues);

  $responsesBeforeCalculations = extractResponsesFromSecondStructureUsingRegex($htmlContent);
  $fifteenResponsesBC = array_slice($responsesBeforeCalculations, 0, 15);
  $unique = array_unique($fifteenResponsesBC);
  
  if (count($unique) === 1 && $unique[0] === 'SI') {
    echo deleteLinesFromGoogleDoc($docId, 2954);
    appendTextToGoogleDoc($docId, $stringIfGood);
    return null;
  }
  
  if (in_array('SELEZIONA', $fifteenResponsesBC)) {
    return 'Fino alla 15: Compilare tutte le risposte prima di procedere';
  }

  $lastThreeResponsesBC = array_slice($responsesBeforeCalculations, -3);
  if (in_array('SELEZIONA', $lastThreeResponsesBC)) {
    return 'Dalla 16 alla fine: Compilare tutte le risposte prima di procedere';
  }

  $weightConstant = extractValuesFromTablesUsingRegex($htmlContent, 3);
  if (getType($weightConstant) === 'string') {
    return "Tabella `COSTANTE DI PESO`: {$weightConstant}";
  }
  
  $lastSevenResponses = array_slice(extractFirstStructureResponsesUsingRegex($htmlContent, 9), -7);
  if (in_array('SELEZIONA', $lastSevenResponses)) {
    return 'Dalla 16 alla fine: Compilare tutte le risposte prima di procedere';
  }

  $descriptions = [
    'altezza',
    'dislocazione',
    'distanza',
    'dislocazione_angolare',
    'giudizio',
    'sollevamento_un_gesto',
    'due_operatori'
  ];

  $coefficients = [
    'altezza' => [
      '0' => 0.77,
      '25' => 0.85,
      '50' => 0.93,
      '75' => 1.00,
      '100' => 0.93,
      '125' => 0.85,
      '150' => 0.78,
      '&gt;175' => 0.00
    ],
    'dislocazione' => [
      '25' => 1.00,
      '30' => 0.97,
      '40' => 0.93,
      '50' => 0.91,
      '70' => 0.88,
      '100' => 0.87,
      '170' => 0.86,
      '&gt;175' => 0.00
    ],
    'distanza' => [
      '25' => 1.00,
      '30' => 0.83,
      '40' => 0.63,
      '50' => 0.50,
      '55' => 0.45,
      '60' => 0.42,
      '&gt;63' => 0.00
    ],
    'dislocazione_angolare' => [
      '0' => 1.00,
      '30' => 0.90,
      '60' => 0.81,
      '90' => 0.71,
      '120' => 0.52,
      '135' => 0.57,
      '&gt;135' => 0.00
    ],
    'giudizio' => [
      'BUONO' => 1.00,
      'SCARSO' => 0.90
    ],
    'sollevamento_un_gesto' => [
      'NO' => 1.00,
      'SI' => 0.6
    ],
    'due_operatori' => [
      'NO' => 1.00,
      'SI' => 0.85
    ]
  ];

  $lastSevenResponses = createAssociativeArrayWithCoefficients($descriptions, $lastSevenResponses, $coefficients);
  
  $frequency = extractValuesFromTablesUsingRegex($htmlContent, 4);
  if (getType($frequency) === 'string') {
    return "Tabella `FREQUENZA DI SOLLEVAMENTO`: {$frequency}";
  }

  // CP x A x B x C x D x E x F x G x H
  $recommendedWeight = $weightConstant[0] * $lastSevenResponses['altezza']['coefficient'] * $lastSevenResponses['dislocazione']['coefficient'] * $lastSevenResponses['distanza']['coefficient'] * $lastSevenResponses['dislocazione_angolare']['coefficient'] * $lastSevenResponses['giudizio']['coefficient'] * $lastSevenResponses['sollevamento_un_gesto']['coefficient'] * $lastSevenResponses['due_operatori']['coefficient'] * $frequency[0];
  
  $defString = '';
  if ($recommendedWeight === 0) {
    $defString = 'Date le condizioni di lavoro descritte nelle risposte fornite alle domande specificate sopra, è necessario procedere al più presto alla riprogettazione del compito.';
    appendTextToGoogleDoc($docId, $defString);
    return null;
  }
  
  $weightIndex = $maxWeight / $recommendedWeight;
  $defString = "Il peso limite raccomandato è {$recommendedWeight}Kg, calcolato con un peso massimo di {$maxWeight}Kg. L'indice di sollveamento è {$weightIndex}. ";

  if ($weightIndex <= 0.85) {
    $defString .= 'Date le condizioni di lavoro descritte nelle risposte fornite alle domande specificate sopra, la situazione lavorativa è in area verde e per questo non sono necessari interventi specifici. ';
  } elseif (0.85 < $weightIndex && $weightIndex < 1) {
    $defString .= 'Date le condizioni di lavoro descritte nelle risposte fornite alle domande specificate sopra, la situazione lavorativa è in area gialla e per questo si consiglia l\'attivazione dei corsi di formazione e, a discrezione del medico, la sorveglianza sanitaria del personale addetto. ';
  } elseif (1 < $weightIndex && $weightIndex < 1.25) {
    $defString .= 'Date le condizioni di lavoro descritte nelle risposte fornite alle domande specificate sopra, la situazione lavorativa è in area rossa e per questo si richiede un intervento di prevenzione primaria, inoltre è necessario attivare la sorveglianza sanitaria del personale addetto. ';
  } else {
    $defString .= 'Date le condizioni di lavoro descritte nelle risposte fornite alle domande specificate sopra, la situazione lavorativa è in area rossa e per questo si richiede un intervento immediato di prevenzione primaria, inoltre è necessario attivare la sorveglianza sanitaria del personale addetto. ';
  }

  $defString .= 'Dopo aver effettuato le modifiche necessarie, si consiglia di ripetere il calcolo per verificare nuovamente la situazione lavorativa.';

  appendTextToGoogleDoc($docId, $defString);

  return "Il documento google è stato modificato in base ai dati forniti, si prega di ricontrollare.";
}

$docId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: text/html');
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
    $result = generateResult($htmlContent, $docId);
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
  </body>
</html>