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

function extractResponsesFromHtml($htmlContent) {
  $dom = new DOMDocument();
  @$dom->loadHTML($htmlContent);
  $responses = [];

  $paragraphs = $dom->getElementsByTagName('p');
  foreach ($paragraphs as $paragraph) {
    $spans = $paragraph->getElementsByTagName('span');
    if ($spans->length == 2) {
      $question = trim($spans->item(0)->nodeValue);
      $answer = trim($spans->item(1)->nodeValue);
      $responses[] = ['question' => $question, 'answer' => $answer];
    }
  }

  return $responses;
}

function generateResult($responses) {
  $firstFiveAnswers = array_slice(array_column($responses, 'answer'), 0, 5);
  if (count($firstFiveAnswers) == 5 && array_unique($firstFiveAnswers) === ['NO']) {
    return 'Va tutto bene';
  } elseif (in_array('SELEZIONA', $firstFiveAnswers)) {
    return 'Compilare tutto il file';
  } else {
    return 'Non va bene';
  }
}

$docId = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');
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
      $responses = extractResponsesFromHtml($htmlContent);
      $result = generateResult($responses);
      echo json_encode(['result' => $result]);
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
    <div id="doc-content" class="invisible">
      <!-- Content will be loaded here -->
    </div>
    <div id="output" class="mt-5">
      <!-- Output will be displayed here -->
    </div>
  </body>
</html>