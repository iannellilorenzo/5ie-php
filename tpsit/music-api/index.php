<?php

// Function to respond with an error message and status code
function respondWithError($message, $statusCode) {
    $errorResponse = new stdClass();
    $errorResponse->success = false;
    $errorResponse->error = $message;
    $errorResponse->status = $statusCode;

    http_response_code($statusCode);
    die(json_encode($errorResponse, JSON_PRETTY_PRINT));
}

// Function to respond with success data
function respondWithSuccess($data) {
    $response = new stdClass();
    $response->success = true;
    $response->data = $data;

    http_response_code(200);
    die(json_encode($response, JSON_PRETTY_PRINT));
}

// Set the content type to JSON
header("Content-Type: application/json; charset=UTF-8");

// Database connection parameters
$hostname = "127.0.0.1";
$username = "lorenzoiannelli";
$password = "";
$db = "my_lorenzoiannelli";

// Create a new MySQLi connection
$mysqli = new mysqli($hostname, $username, $password, $db);

// Check for connection errors
if ($mysqli->connect_errno) {
    $response = new stdClass();
    $response->errorCode = 500;
    $response->errorMessage = $mysqli->connect_error;
    die(json_encode($response));
}

// Get the HTTP request method
$method = $_SERVER["REQUEST_METHOD"];
// Split the request URI into an array
$request = explode("/", $_SERVER["REQUEST_URI"]);

// Handle different request methods
switch ($method) {
    case "GET":
        // GET all artists
        if (strtolower($request[2]) === "artista" && !isset($request[3])) {
            $artists = $mysqli->query("SELECT * FROM `MUSICA_artisti`");

            if (!$artists) {
                respondWithError($mysqli->error, 500);
            }

            $artistsResponse = array();

            // Fetch all artists and add them to the response array
            while ($artist = $artists->fetch_assoc()) {
                $art = new stdClass();
                $art->id = $artist['ID'];
                $art->nominativo = $artist['Nome'] . ' ' . $artist['Cognome'];
                $artistsResponse[] = $art;
            }

            respondWithSuccess($artistsResponse);
        }

        // GET single artist by ID
        if (strtolower($request[2]) === "artista" && isset($request[3]) && is_numeric($request[3]) && !isset($request[4])) {
            $id = $request[3];
            $stmt = $mysqli->prepare("SELECT * FROM `MUSICA_artisti` WHERE ID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $artistData = $result->fetch_assoc();

            if ($artistData) {
                $artistResponse = new stdClass();
                $artistResponse->id = $artistData['ID'];
                $artistResponse->nominativo = $artistData['Nome'] . ' ' . $artistData['Cognome'];
                $artistResponse->data_nascita = $artistData['Data_nascita'];
                $artistResponse->img_ref = $artistData['Immagine'];

                respondWithSuccess($artistResponse);
            }

            respondWithError("Artist not found", 404);
        }

        // GET artist image
        if (strtolower($request[2]) === "artista" && isset($request[3]) && $request[3] === "img" && isset($request[4]) && is_numeric($request[4])) {
            $id = $request[4];
            $stmt = $mysqli->prepare("SELECT Immagine FROM MUSICA_artisti WHERE ID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $image = $result->fetch_assoc()["Immagine"];
            header("Content-type: image/jpeg");
            die(file_get_contents("assets/img/" . $image));
        }

        // Get artist's tracks
        if (strtolower($request[2]) === "artista" && isset($request[3]) && is_numeric($request[3]) && $request[4] === "brani") {
            $artistId = $request[3];

            // Prepare SQL query to fetch songs (brani) for the given artist ID
            $query = "
                SELECT b.ID, b.Titolo, b.Album, b.Durata, b.Mp3
                FROM MUSICA_brani AS b
                JOIN MUSICA_brani_artisti AS ba ON b.ID = ba.ID_BRANO
                WHERE ba.ID_ARTISTA = ?
            ";

            // Prepare statement
            if ($stmt = $mysqli->prepare($query)) {
                $stmt->bind_param("i", $artistId); // Bind the artist ID to the query
                $stmt->execute(); // Execute the query

                // Get the result
                $result = $stmt->get_result();

                // Prepare response array
                $brani = array();

                // Fetch all songs and add them to the response array
                while ($row = $result->fetch_assoc()) {
                    $brano = new stdClass();
                    $brano->id = $row['ID'];
                    $brano->titolo = $row['Titolo'];
                    $brano->album = $row['Album'];
                    $brano->durata = $row['Durata'];
                    $brano->mp3 = $row['Mp3']; // Can be null or an actual filename
                    $brani[] = $brano;
                }

                // Respond with the data in JSON format
                respondWithSuccess($brani); // Use your function to return the success response
            } else {
                // Handle query preparation failure
                respondWithError("Error preparing query", 500);
            }
        }

        // Get track's playable mp3 by id
        if (isset($request[2]) && $request[2] === 'brano' && isset($request[3]) && is_numeric($request[3]) && !isset($request[4])) {
            $id = $request[3];

            // Validate and sanitize ID
            $id = $mysqli->real_escape_string($id);

            // Fetch the MP3 filename from the database
            $query = "SELECT Mp3 FROM MUSICA_brani WHERE ID = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();

            if ($data && $data['Mp3']) {
                $mp3File = 'assets/mp3/' . $data['Mp3'];
                // Check if the file exists
                if (file_exists($mp3File)) {

                    // Set headers to serve the MP3 file
                    header('Content-Type: audio/mpeg');
                    header('Content-Length: ' . filesize($mp3File));
                    header('Content-Disposition: inline; filename="' . basename($mp3File) . '"');

                    // Output the file content
                    readfile($mp3File);
                    exit;
                } else {
                    // File not found
                    respondWithError("MP3 file not found", 404);
                }
            }
        }
        break;

    case 'POST':
        // Handle file upload and artist creation
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['file']['name'];
            $filetype = $_FILES['file']['type'];
            $filesize = $_FILES['file']['size'];

            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), $allowed)) {
                respondWithError("Error: Please select a valid file format.", 400);
            }

            // Verify file size - 5MB maximum
            if ($filesize > 5 * 1024 * 1024) {
                respondWithError("Error: File size is larger than the allowed limit.", 400);
            }

            // Verify MIME type of the file
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($filetype, $allowedMimeTypes)) {
                respondWithError("Error: Please select a valid file format.", 400);
            }

            // Check whether file exists before uploading it
            if (file_exists("assets/img/" . $filename)) {
                respondWithError("$filename already exists.", 400);
            } else {
                move_uploaded_file($_FILES['file']['tmp_name'], "assets/img/" . $filename);
                $nome = $mysqli->real_escape_string($_POST['Nome']);
                $cognome = $mysqli->real_escape_string($_POST['Cognome']);
                $data_nascita = $mysqli->real_escape_string($_POST['Data_nascita']);
                $immagine = $mysqli->real_escape_string($filename);

                // Insert new artist into the database
                $stmt = $mysqli->prepare("INSERT INTO MUSICA_artisti (Nome, Cognome, Data_nascita, Immagine) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $nome, $cognome, $data_nascita, $immagine);
                if ($stmt->execute()) {
                    // Get the ID of the newly inserted artist
                    $newId = $stmt->insert_id;

                    // Return the created data, including the new ID
                    $newArtist = new stdClass();
                    $newArtist->id = $newId;
                    $newArtist->nominativo = $nome . ' ' . $cognome;
                    $newArtist->data_nascita = $data_nascita;
                    $newArtist->img_ref = $filename;

                    respondWithSuccess($newArtist);
                } else {
                    respondWithError($stmt->error, 500);
                }
            }
        } else {
            respondWithError("Dati mancanti o file non valido", 400);
        }
        break;

    case 'PUT':
        $id = $request[3];

        // Fetch content and determine boundary
        $raw_data = file_get_contents('php://input');
        $boundary = substr($raw_data, 0, strpos($raw_data, "\r\n"));

        // Fetch each part
        $parts = array_slice(explode($boundary, $raw_data), 1);
        $data = array();
        $oldData = null; // Variable to store the original data

        foreach ($parts as $part) {
            // If this is the last part, break
            if ($part == "--\r\n") break;

            // Separate content from headers
            $part = ltrim($part, "\r\n");
            list($raw_headers, $body) = explode("\r\n\r\n", $part, 2);

            // Parse the headers list
            $raw_headers = explode("\r\n", $raw_headers);
            $headers = array();
            foreach ($raw_headers as $header) {
                list($name, $value) = explode(':', $header);
                $headers[strtolower($name)] = ltrim($value, ' ');
            }

            // Retrieve the old artist data from the database using prepared statement
            $stmt = $mysqli->prepare("SELECT * FROM MUSICA_artisti WHERE ID = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $artistData = $result->fetch_assoc();

            if ($artistData) {
                $oldData = $artistData; // Store the original data
            }

            // Parse the Content-Disposition to get the field name, etc.
            if (isset($headers['content-disposition'])) {
                $filename = null;
                preg_match(
                    '/^(.+); *name="([^"]+)"(; *filename="([^"]+)")?/',
                    $headers['content-disposition'],
                    $matches
                );
                list(, $type, $name) = $matches;
                isset($matches[4]) and $filename = $matches[4];

                // Handle fields
                switch ($name) {
                    case 'file':
                        // Save the new file and remove the old one if provided
                        file_put_contents('assets/img/' . $filename, $body);
                        if ($oldData && isset($oldData['Immagine']) && file_exists('assets/img/' . $oldData['Immagine'])) {
                            unlink('assets/img/' . $oldData['Immagine']);
                        }
                        break;
                    default:
                        // Add other fields
                        $data[$name] = substr($body, 0, strlen($body) - 2); // Clean up any extra characters
                        break;
                }
            }
        }

        // If no changes to data, return old data
        if (!$data) {
            respondWithError("No data provided to update.", 400);
        }

        // Prepare and update the fields that were provided
        $updateFields = [];
        $params = [];
        $types = "";

        // Build the update query dynamically based on provided fields
        if (isset($data['nome'])) {
            $updateFields[] = "Nome = ?";
            $params[] = $data['nome'];
            $types .= "s";
        }
        if (isset($data['cognome'])) {
            $updateFields[] = "Cognome = ?";
            $params[] = $data['cognome'];
            $types .= "s";
        }
        if (isset($data['data_nascita'])) {
            $updateFields[] = "Data_nascita = ?";
            $params[] = $data['data_nascita'];
            $types .= "s";
        }
        if (isset($data['immagine'])) {
            $updateFields[] = "Immagine = ?";
            $params[] = $data['immagine'];
            $types .= "s";
        }

        if (empty($updateFields)) {
            respondWithError("No valid fields to update.", 400);
        }

        // Remove the last two chars of the last string to prevent the a comma before the WHERE clause
        $params[] = $id;
        $types .= "i"; // ID is an integer

        // Prepare the UPDATE statement
        $query = "UPDATE MUSICA_artisti SET " . implode(", ", $updateFields);
        $query = rtrim($query, ',');
        $query .= " WHERE ID = ?";
        $stmt = $mysqli->prepare($query);

        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);

        // Execute the statement
        if ($stmt->execute()) {
            // Return the old object as the response
            $oldResponse = new stdClass();
            $oldResponse->id = $oldData['ID'];
            $oldResponse->nominativo = $oldData['Nome'] . ' ' . $oldData['Cognome'];
            $oldResponse->data_nascita = $oldData['Data_nascita'];
            $oldResponse->img_ref = $oldData['Immagine'];

            respondWithSuccess($oldResponse); // Send old data as the response
        } else {
            respondWithError("Failed to update artist.", 500);
        }

        break;

    case 'DELETE':
        // Handle artist deletion
        if (isset($request[2]) && $request[2] === 'artista') {
            $id = $request[3];

            // Validate and sanitize ID
            $id = $mysqli->real_escape_string($id);

            // Fetch the artist's existing data
            $artistQuery = "SELECT * FROM MUSICA_artisti WHERE ID = ?";
            $artistStmt = $mysqli->prepare($artistQuery);
            $artistStmt->bind_param("i", $id);
            $artistStmt->execute();
            $artistResult = $artistStmt->get_result();
            $artistData = $artistResult->fetch_assoc();

            if (!$artistData) {
                http_response_code(404);
                echo json_encode(["error" => "Artista not found"]);
                exit;
            }

            // Fetch all tracks associated with the artist
            $tracksQuery = "
                SELECT b.ID, b.Titolo, b.Album, b.Durata, b.Mp3
                FROM MUSICA_brani b
                INNER JOIN MUSICA_brani_artisti ba ON b.ID = ba.ID_BRANO
                WHERE ba.ID_ARTISTA = ?";
            $tracksStmt = $mysqli->prepare($tracksQuery);
            $tracksStmt->bind_param("i", $id);
            $tracksStmt->execute();
            $tracksResult = $tracksStmt->get_result();
            $tracksData = $tracksResult->fetch_all(MYSQLI_ASSOC);

            // Start transaction
            $mysqli->begin_transaction();

            // Delete associations in MUSICA_brani_artisti
            $deleteAssociationsQuery = "DELETE FROM MUSICA_brani_artisti WHERE ID_ARTISTA = ?";
            $assocStmt = $mysqli->prepare($deleteAssociationsQuery);
            $assocStmt->bind_param("i", $id);
            if (!$assocStmt->execute()) {
                $mysqli->rollback();
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete artist-track associations"]);
                exit;
            }

            // Optional: Delete orphan tracks
            $deleteOrphanTracksQuery = "
                DELETE b 
                FROM MUSICA_brani b
                LEFT JOIN MUSICA_brani_artisti ba ON b.ID = ba.ID_BRANO
                WHERE ba.ID_BRANO IS NULL";
            if (!$mysqli->query($deleteOrphanTracksQuery)) {
                $mysqli->rollback();
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete orphan tracks"]);
                exit;
            }

            // Delete the artist
            $deleteArtistQuery = "DELETE FROM MUSICA_artisti WHERE ID = ?";
            $artistStmt = $mysqli->prepare($deleteArtistQuery);
            $artistStmt->bind_param("i", $id);
            if (!$artistStmt->execute()) {
                $mysqli->rollback();
                http_response_code(500);
                echo json_encode(["error" => "Failed to delete artist"]);
                exit;
            }

            // Commit transaction
            if (!$mysqli->commit()) {
                http_response_code(500);
                echo json_encode(["error" => "Failed to commit the transaction"]);
                exit;
            }

            // Respond with the deleted data
            echo json_encode([
                "message" => "Artista and associated data deleted successfully",
                "deleted_artist" => $artistData,
                "deleted_tracks" => $tracksData
            ]);
        }
        break;
}