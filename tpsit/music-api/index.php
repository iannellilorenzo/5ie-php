<?php

function respondWithError($message, $statusCode) {
    $errorResponse = new stdClass();
    $errorResponse->success = false;
    $errorResponse->error = $message;
    $errorResponse->status = $statusCode;

    http_response_code($statusCode);
    die(json_encode($errorResponse, JSON_PRETTY_PRINT));
}

function respondWithSuccess($data) {
    $response = new stdClass();
    $response->success = true;
    $response->data = $data;

    http_response_code(200);
    die(json_encode($response, JSON_PRETTY_PRINT));
}

header("Content-Type: application/json; charset=UTF-8");
$hostname = "127.0.0.1";
$username = "lorenzoiannelli";
$password = "";
$db = "my_lorenzoiannelli";

$mysqli = new mysqli($hostname, $username, $password, $db);

if ($mysqli->connect_errno) {
    $response = new stdClass();
    $response->errorCode = 500;
    $response->errorMessage = $mysqli->connect_error;
    die(json_encode($response));
}


$method = $_SERVER["REQUEST_METHOD"];
$request = explode("/", $_SERVER["REQUEST_URI"]);

switch ($method) {
	case "GET":
    	if (strtolower($request[2]) === "artista" && !isset($request[3])) { // GET all artists
          $artists = $mysqli->query("SELECT * FROM `MUSICA_artisti`");

          if (!$artists) {
              $response = new stdClass();
              $response->errorCode = 500;
              $response->errorMessage = $mysqli->error;
              die(json_encode($response));
          }

          $artistsResponse = array();

          while ($artist = $artists->fetch_assoc()) {
              $art = new stdClass();
              $art->id = $artist['ID'];
              $art->nominativo = $artist['Nome'] . ' ' . $artist['Cognome'];
              $artistsResponse[] = $art;
          }

			die(json_encode($artistsResponse, JSON_PRETTY_PRINT));
        }
        
        if (strtolower($request[2]) === "artista" && isset($request[3]) && is_numeric($request[3])) {
            $id = $request[3];
            $artist = $mysqli->query("SELECT * FROM `MUSICA_artisti` WHERE ID={$id}");
            $artistData = $artist->fetch_assoc();
            
            if ($artistData) {
                $artistResponse = new stdClass();
                $artistResponse->id = $artistData['ID'];
                $artistResponse->nominativo = $artistData['Nome'] . ' ' . $artistData['Cognome'];
                $artistResponse->data_nascita = $artistData['Data_nascita'];
                $artistResponse->img_ref = $artistData['Immagine'];

                die(json_encode($artistResponse, JSON_PRETTY_PRINT));
            }
            
            // Error handling
            die(json_encode(["error" => "Artist not found"], JSON_PRETTY_PRINT));
        }
        
        if (strtolower($request[2]) === "artista" && isset($request[3]) && $request[3] === "img" && isset($request[4]) && is_numeric($request[4])) {
            $id = $request[4];
            // risolvere con prepare e non con query, causa injection
            $img = $mysqli->query("SELECT Immagine FROM MUSICA_artisti WHERE ID=" . $id);
            $image = $img->fetch_assoc()["Immagine"];
            header("Content-type: image/jpeg");
            die(file_get_contents("assets/" . $image));
        }
        break;
        
	case 'POST':
    	if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['file']['name'];
            $filetype = $_FILES['file']['type'];
            $filesize = $_FILES['file']['size'];

            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), $allowed)) {
                die("Error: Please select a valid file format.");
            }

            // Verify file size - 5MB maximum
            if ($filesize > 5 * 1024 * 1024) {
                die("Error: File size is larger than the allowed limit.");
            }

            // Verify MIME type of the file
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($filetype, $allowedMimeTypes)) {
                die("Error: Please select a valid file format.");
            }

            // Check whether file exists before uploading it
            if (file_exists("assets/" . $filename)) {
                die($filename . " already exists.");
            } else {
                move_uploaded_file($_FILES['file']['tmp_name'], "assets/" . $filename);
                $nome = $mysqli->real_escape_string($_POST['Nome']);
                $cognome = $mysqli->real_escape_string($_POST['Cognome']);
                $data_nascita = $mysqli->real_escape_string($_POST['Data_nascita']);
                $immagine = $mysqli->real_escape_string($filename);

                $query = "INSERT INTO MUSICA_artisti (Nome, Cognome, Data_nascita, Immagine) VALUES ('$nome', '$cognome', '$data_nascita', '$immagine')";
                if ($mysqli->query($query) === TRUE) {
                    http_response_code(201);
                    echo json_encode(["message" => "Artista creato con successo"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => $mysqli->error]);
                }
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Dati mancanti o file non valido"]);
        }
        break;
        
    case 'PUT':
        if (strtolower($request[2]) === "artista" && isset($request[3]) && is_numeric($request[3])) {
            $id = $request[3];

            $result = $mysqli->query("SELECT * FROM `MUSICA_artisti` WHERE ID={$id}");
            $originalData = $result->fetch_assoc();

            if (!$originalData) {
                respondWithError("Artist not found", 404);
            }

            // Parse form-data
            parse_str(file_get_contents("php://input"), $inputData);
            var_dump($inputData);

            if (!is_array($inputData)) {
                respondWithError("Invalid input format", 400);
            }

            $fieldsToUpdate = [];

            if (isset($inputData['Nome'])) {
                $fieldsToUpdate[] = "Nome = '" . $mysqli->real_escape_string($inputData['Nome']) . "'";
            }
            if (isset($inputData['Cognome'])) {
                $fieldsToUpdate[] = "Cognome = '" . $mysqli->real_escape_string($inputData['Cognome']) . "'";
            }
            if (isset($inputData['Data_nascita'])) {
                $fieldsToUpdate[] = "Data_nascita = '" . $mysqli->real_escape_string($inputData['Data_nascita']) . "'";
            }

            // Handle file upload if present
            /*
            if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['file']['name'];
                $filetype = $_FILES['file']['type'];
                $filesize = $_FILES['file']['size'];

                // Verify file extension
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                if (!in_array(strtolower($ext), $allowed)) {
                    respondWithError("Please select a valid file format.", 400);
                }

                // Verify file size - 5MB maximum
                if ($filesize > 5 * 1024 * 1024) {
                    respondWithError("File size is larger than the allowed limit.", 400);
                }

                // Verify MIME type of the file
                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($filetype, $allowedMimeTypes)) {
                    respondWithError("Please select a valid file format.", 400);
                }

                // Check whether file exists before uploading it
                if (file_exists("assets/" . $filename)) {
                    respondWithError($filename . " already exists.", 400);
                } else {
                    move_uploaded_file($_FILES['file']['tmp_name'], "assets/" . $filename);
                    $fieldsToUpdate[] = "Immagine = '" . $mysqli->real_escape_string($filename) . "'";
                }
            }
            */

            if (!empty($fieldsToUpdate)) {
                $updateQuery = "UPDATE `MUSICA_artisti` SET " . implode(", ", $fieldsToUpdate) . " WHERE ID={$id}";

                if ($mysqli->query($updateQuery)) {
                    respondWithSuccess($originalData);
                } else {
                    respondWithError("Failed to update artist data", 500);
                }
            } else {
                respondWithError("No fields provided to update", 400);
            }
        } else {
            respondWithError("Invalid request method or missing artist ID", 405);
        }
        break;

        
        case 'DELETE':
        	if (strtolower($request[2]) === "artista" && isset($request[3]) && is_numeric($request[3])) {
            	$id = $request[3];

                $result = $mysqli->query("SELECT * FROM `MUSICA_artisti` WHERE ID={$id}");
                $originalData = $result->fetch_assoc();

                if (!$originalData) {
                    respondWithError("Artist not found", 404);
                }

                if ($mysqli->query("DELETE FROM `MUSICA_artisti` WHERE ID={$id}")) {
                    respondWithSuccess(["original_data" => $originalData, "message" => "Artist successfully deleted."]);
                } else {
                    respondWithError("Failed to delete artist", 500);
                }
            } else {
                respondWithError("Invalid request method or missing artist ID", 405);
			}
            
            break;
}
?>