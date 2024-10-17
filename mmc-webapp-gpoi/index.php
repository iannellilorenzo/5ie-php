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
      // Return the auth URL for the client to handle
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'];
  $client = getClient();

  if (is_string($client)) {
    // If client is a string, it's the auth URL
    echo "<script>window.open('$client', '_blank', 'noopener');</script>";
    exit;
  }

  if ($action === 'createDoc') {
    $docTitle = $_POST['docTitle'];
    $templateDocId = '1cOKUjOhzvpXigS2PqDRiFsqGjY7s9I1TCrJNPJOcoGQ'; // Template Google Doc of All3
    $docId = copyGoogleDoc($templateDocId, $docTitle);

    if ($docId) {
      $_SESSION['docId'] = $docId;
      header('Location: ' . filter_var($_SERVER['PHP_SELF'], FILTER_SANITIZE_URL));
      exit;
    } else {
      echo "<script>alert('Failed to create document.');</script>";
    }
  }
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
?>

<!doctype html>
<html lang="en">
  <head>
    <title>MMC - DVR</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  </head>
  <body>
    <div class="container mt-5">
      <h1 class="text-center mb-4">Movimento Manuale Carichi (MMC) - Cosa puoi fare</h1>
      
      <!-- Form for Creating a Google Doc -->
      <form action="index.php" method="post">
        <div class="form-group">
          <label for="docTitle">Utilizza la nostra base creando un Documento Google</label>
          <input type="text" class="form-control" id="docTitle" name="docTitle" placeholder="Inserisci il titolo del documento" required>
        </div>
        <input type="hidden" id="action" name="action" value="">
        <button type="submit" class="btn btn-primary btn-block" onclick="setAction('createDoc')">Crea documento</button>
        <div id="docId">
          <?php
            if (isset($_SESSION['docId'])) {
              $docId = $_SESSION['docId'];
              $url = "https://docs.google.com/document/d/$docId";
              echo "Puoi aprire il documento Google <a href='$url' target='_blank'>cliccandomi</a>";
              unset($_SESSION['docId']); // Clear the session variable
            }
          ?>
        </div>
      </form>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcnd.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script>
      // Set the action value when the button is clicked
      function setAction(action) {
        document.getElementById('action').value = action;
      }
    </script>
  </body>
</html>