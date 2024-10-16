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
      
      <!-- Form for Creating a Google Doc and Uploading a .doc File -->
      <form action="index.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
          <label for="docTitle">Utilizza la nostra base creando un Documento Google</label>
          <input type="text" class="form-control" id="docTitle" name="docTitle" placeholder="Inserisci il titolo del documento" required>
        </div>
        <input type="hidden" id="action" name="action" value="">
        <button type="submit" class="btn btn-primary btn-block" onclick="setAction('createDoc')">Crea documento</button>
        <div id="docId">L'id del tuo documento appena creato è il seguente, che trovi anche nel documento stesso: </div>
        <div class="form-group">
          <label for="fileUpload">Hai già il file compilato? Inviacelo e noi lo controlleremo per te</label>
          <div class="custom-file">
            <input type="file" class="custom-file-input" id="fileUpload" name="fileUpload" accept=".doc">
            <label class="custom-file-label" for="fileUpload">Scegli il file</label>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-block" onclick="setAction('uploadFile')">Crea Google Doc e Carica File</button>
      </form>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcnd.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script>
      // Update the label of the custom file input with the selected file name
      $('#fileUpload').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
      });

      // Set the action value when the button is clicked
      function setAction(action) {
        document.getElementById('action').value = action;
      }
    </script>
  </body>
</html>

<?php 
  // Include the Google API PHP Client
  require __DIR__ . '/vendor/autoload.php';
  use Google\Client;
  use Google\Service\Docs;

  // Define the paths to the credentials and token files so that the Google API Client can access them
  // anytime instead of logging in every time
  define('CREDENTIALS_PATH', __DIR__ . '/credentials.json');
  define('TOKEN_PATH', __DIR__ . '/token.json');

  // Function to authenticate with Google OAuth 2.0
  function getClient()
  {
    $client = new Client();
    $client->setApplicationName('Google Docs API PHP');
    $client->setScopes([Docs::DOCUMENTS, 'https://www.googleapis.com/auth/drive']);
    $client->setAuthConfig(CREDENTIALS_PATH);
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    if (file_exists(TOKEN_PATH)) {
      $accessToken = json_decode(file_get_contents(TOKEN_PATH), true);
      $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it is expired, get a new one.
    if ($client->isAccessTokenExpired()) {
      if ($client->getRefreshToken()) {
        // Refresh the token
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
      } else {
        // Create the authorization URL
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        printf("Enter the verification code: ");

        // Handle the redirection in a web context
        if (isset($_GET['code'])) {
          $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
          $client->setAccessToken($token);

          // Save the token to a file
          if (!file_exists(dirname(TOKEN_PATH))) {
            mkdir(dirname(TOKEN_PATH), 0700, true);
          }
          file_put_contents(TOKEN_PATH, json_encode($client->getAccessToken()));
        } else {
          exit; // If there's no code, exit the script
        }
      }
    }

    return $client;
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a Google Doc
    $docTitle = $_POST['docTitle'];
    $docId = createGoogleDoc($docTitle);
    echo "<script>document.getElementById(\"docId\").innerText += \" {$docId}\";</script>";
    echo "<script>window.open('https://docs.google.com/document/d/{$docId}', '_blank', 'noopener');</script>";
  }

  function createGoogleDoc($title) {
    // Authenticate the client 
    $client = getClient();

    // Create a Google Docs service
    $service = new Google\Service\Docs($client);

    // Create a new Google Doc
    $document = new Google\Service\Docs\Document([
      'title' => $title
    ]);

    // Create the actual Google Doc
    $createdDoc = $service->documents->create($document);

    return $createdDoc->getDocumentId();
  }
?>