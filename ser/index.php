<!DOCTYPE html>
<html>
<head>
    <title>RC4 and RC5 Tool</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toggleInputMethod() {
            var inputMethod = document.getElementById('inputMethod').value;
            var textInput = document.getElementById('textInput');
            var fileInputs = document.getElementById('fileInputs');
            var keyTextInput = document.getElementById('keyTextInput');
            var keyFileInput = document.getElementById('keyFileInput');
            var cipherFileInput = document.getElementById('cipherFile');
            var operation = document.getElementById('operation').value;
            
            if (inputMethod === 'text') {
                textInput.style.display = 'block';
                fileInputs.style.display = 'none';
                keyTextInput.style.display = 'block';
                keyFileInput.style.display = 'none';
                cipherFileInput.removeAttribute('required');
            } else {
                textInput.style.display = 'none';
                fileInputs.style.display = 'block';
                keyTextInput.style.display = 'none';
                keyFileInput.style.display = 'block';
                cipherFileInput.setAttribute('required', 'required');
            }

            if (operation === 'encrypt') {
                document.getElementById('fileLabel').innerText = 'File to Encrypt:';
            } else {
                document.getElementById('fileLabel').innerText = 'Cipher File:';
            }
        }

        function updateLabels() {
            var operation = document.getElementById('operation').value;
            if (operation === 'encrypt') {
                document.getElementById('dataLabel').innerText = 'Insert your data:';
                document.getElementById('keyLabel').innerText = 'Insert your key (Leave blank to generate one):';
            } else {
                document.getElementById('dataLabel').innerText = 'Insert your cipher data:';
                document.getElementById('keyLabel').innerText = 'Insert your key used for decryption:';
            }
            toggleInputMethod();
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">RC4 and RC5 Tool</h2>
        <form id="myForm" method="post" enctype="multipart/form-data" action="index.php">
            <div class="form-group">
                <label for="operation">Operation:</label>
                <select class="form-control" id="operation" name="operation" onchange="updateLabels()" required>
                    <option value="encrypt">Encrypt</option>
                    <option value="decrypt">Decrypt</option>
                </select>
            </div>
            <div class="form-group">
                <label for="inputMethod">Select input method:</label>
                <select class="form-control" id="inputMethod" name="inputMethod" onchange="toggleInputMethod()">
                    <option value="text">Text Input</option>
                    <option value="file">File Upload</option>
                </select>
            </div>

            <!-- Text Input Section -->
            <div class="form-group" id="textInput">
                <label id="dataLabel" for="data">Insert your data:</label>
                <input type="text" class="form-control" id="data" name="data">
            </div>

            <!-- File Input Section -->
            <div id="fileInputs" style="display: none;">
                <div class="form-group">
                    <label id="fileLabel" for="cipherFile">Cipher File:</label>
                    <div class="custom-file mb-3">
                        <input type="file" class="custom-file-input" id="cipherFile" name="cipherFile">
                        <label class="custom-file-label" for="cipherFile">Choose file</label>
                    </div>
                </div>
            </div>

            <!-- Text Key Input -->
            <div class="form-group" id="keyTextInput">
                <label id="keyLabel" for="key">Insert your key (Leave blank to generate one):</label>
                <input type="text" class="form-control" id="key" name="key">
            </div>

            <!-- Key File Input Section -->
            <div id="keyFileInput" style="display: none;">
                <div class="form-group">
                    <label for="keyFile">Key File:</label>
                    <div class="custom-file mb-3">
                        <input type="file" class="custom-file-input" id="keyFile" name="keyFile">
                        <label class="custom-file-label" for="keyFile">Choose key file</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="algorithm">Algorithm:</label>
                <select class="form-control" id="algorithm" name="algorithm" required>
                    <option value="rc4">RC4</option>
                    <option value="rc5">RC5</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>

        <?php
        // RC4 encryption function
        function rc4Encrypt($key, $data) {
            if (empty($key) || empty($data)) {
                return '';
            }

            $state = range(0, 255);
            $keyLength = strlen($key);
            $j = 0;
            
            // Key-scheduling algorithm (KSA)
            for ($i = 0; $i < 256; $i++) {
                $j = ($j + $state[$i] + ord($key[$i % $keyLength])) % 256;
                // Swap state[i] and state[j]
                $temp = $state[$i];
                $state[$i] = $state[$j];
                $state[$j] = $temp;
            }
            
            // Pseudo-random generation algorithm (PRGA)
            $i = 0;
            $j = 0;
            $result = '';
            
            for ($k = 0; $k < strlen($data); $k++) {
                $i = ($i + 1) % 256;
                $j = ($j + $state[$i]) % 256;
                
                // Swap state[i] and state[j]
                $temp = $state[$i];
                $state[$i] = $state[$j];
                $state[$j] = $temp;
                
                $t = ($state[$i] + $state[$j]) % 256;
                $keyStreamByte = $state[$t];
                $result .= chr(ord($data[$k]) ^ $keyStreamByte);
            }
            
            return $result;
        }

        // RC4 decryption function (same as encryption because RC4 is symmetric)
        function rc4Decrypt($key, $data) {
            return rc4Encrypt($key, $data);
        }

        // RC5 encryption function
        function rc5Encrypt($key, $plainText, $rounds = 12, $wordSize = 32) {
            $modulus = 1 << $wordSize;
            $mask = $modulus - 1;

            // Magic constants for RC5
            $P = 0xB7E15163;
            $Q = 0x9E3779B9;

            // Key preparation
            $keyWords = [];
            for ($i = 0; $i < strlen($key); $i += 4) {
                $keyWords[] = unpack('V', substr($key . "\0\0\0\0", $i, 4))[1];
            }

            $totalSubkeys = 2 * ($rounds + 1);
            $subkeys = [$P];
            for ($i = 1; $i < $totalSubkeys; $i++) {
                $subkeys[] = ($subkeys[$i - 1] + $Q) & $mask;
            }

            $keyIndex = $subkeyIndex = $valueA = $valueB = 0;
            $keyLength = count($keyWords);
            for ($k = 0; $k < 3 * max($totalSubkeys, $keyLength); $k++) {
                $valueA = $subkeys[$subkeyIndex] = rotateLeft(($subkeys[$subkeyIndex] + $valueA + $valueB) & $mask, 3, $wordSize);
                $valueB = $keyWords[$keyIndex] = rotateLeft(($keyWords[$keyIndex] + $valueA + $valueB) & $mask, ($valueA + $valueB) % $wordSize, $wordSize);
                $subkeyIndex = ($subkeyIndex + 1) % $totalSubkeys;
                $keyIndex = ($keyIndex + 1) % $keyLength;
            }

            $plainText = str_pad($plainText, ceil(strlen($plainText) / 8) * 8, "\0");
            $encryptedText = '';
            for ($i = 0; $i < strlen($plainText); $i += 8) {
                list(, $valueA, $valueB) = unpack('V2', substr($plainText, $i, 8));
                $valueA = ($valueA + $subkeys[0]) & $mask;
                $valueB = ($valueB + $subkeys[1]) & $mask;

                for ($j = 1; $j <= $rounds; $j++) {
                    $valueA = ($valueA ^ $valueB);
                    $valueA = rotateLeft($valueA, $valueB % $wordSize, $wordSize);
                    $valueA = ($valueA + $subkeys[2 * $j]) & $mask;

                    $valueB = ($valueB ^ $valueA);
                    $valueB = rotateLeft($valueB, $valueA % $wordSize, $wordSize);
                    $valueB = ($valueB + $subkeys[2 * $j + 1]) & $mask;
                }

                $encryptedText .= pack('V2', $valueA, $valueB);
            }

            return $encryptedText;
        }

        // RC5 decryption function
        function rc5Decrypt($key, $encryptedText, $rounds = 12, $wordSize = 32) {
            $modulus = 1 << $wordSize;
            $mask = $modulus - 1;

            // Magic constants for RC5
            $P = 0xB7E15163;
            $Q = 0x9E3779B9;

            // Key preparation
            $keyWords = [];
            for ($i = 0; $i < strlen($key); $i += 4) {
                $keyWords[] = unpack('V', substr($key . "\0\0\0\0", $i, 4))[1];
            }

            $totalSubkeys = 2 * ($rounds + 1);
            $subkeys = [$P];
            for ($i = 1; $i < $totalSubkeys; $i++) {
                $subkeys[] = ($subkeys[$i - 1] + $Q) & $mask;
            }

            $keyIndex = $subkeyIndex = $valueA = $valueB = 0;
            $keyLength = count($keyWords);
            for ($k = 0; $k < 3 * max($totalSubkeys, $keyLength); $k++) {
                $valueA = $subkeys[$subkeyIndex] = rotateLeft(($subkeys[$subkeyIndex] + $valueA + $valueB) & $mask, 3, $wordSize);
                $valueB = $keyWords[$keyIndex] = rotateLeft(($keyWords[$keyIndex] + $valueA + $valueB) & $mask, ($valueA + $valueB) % $wordSize, $wordSize);
                $subkeyIndex = ($subkeyIndex + 1) % $totalSubkeys;
                $keyIndex = ($keyIndex + 1) % $keyLength;
            }

            $blocks = str_split($encryptedText, 8);
            $decryptedText = "";

            foreach ($blocks as $block) {
                [$valueA, $valueB] = array_values(unpack('V2', $block));

                for ($i = $rounds; $i >= 1; $i--) {
                    $valueB = ($valueB - $subkeys[2 * $i + 1]) & $mask;
                    $valueB = rotateRight($valueB, $valueA % $wordSize, $wordSize);
                    $valueB = ($valueB ^ $valueA);

                    $valueA = ($valueA - $subkeys[2 * $i]) & $mask;
                    $valueA = rotateRight($valueA, $valueB % $wordSize, $wordSize);
                    $valueA = ($valueA ^ $valueB);
                }

                $valueB = ($valueB - $subkeys[1]) & $mask;
                $valueA = ($valueA - $subkeys[0]) & $mask;

                $decryptedText .= pack('V2', $valueA, $valueB);
            }

            return rtrim($decryptedText, "\0");
        }

        // Rotate bits to the left
        function rotateLeft($value, $shift, $wordSize) {
            return (($value << $shift) | ($value >> ($wordSize - $shift))) & ((1 << $wordSize) - 1);
        }

        // Rotate bits to the right
        function rotateRight($value, $shift, $wordSize) {
            return (($value >> $shift) | ($value << ($wordSize - $shift))) & ((1 << $wordSize) - 1);
        }

        // Generate a random key of specified length
        function generateRandomKey($length = 16) {
            return bin2hex(random_bytes($length));
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Get the key
            $key = '';
            if (isset($_FILES['keyFile']) && $_FILES['keyFile']['error'] == UPLOAD_ERR_OK) {
                $key = trim(file_get_contents($_FILES['keyFile']['tmp_name']));
            } else {
                if ($_POST['operation'] === 'decrypt') {
                    echo "<div class='mt-4'><p>No key file provided for decryption.</p></div>";
                    exit;
                } else {
                    $key = generateRandomKey(16);
                }
            }

            // Get the data
            $data = '';
            if ($_POST['inputMethod'] === 'text') {
                $data = $_POST['data'];
            } else {
                if (isset($_FILES['cipherFile']) && $_FILES['cipherFile']['error'] == UPLOAD_ERR_OK) {
                    $data = file_get_contents($_FILES['cipherFile']['tmp_name']);
                } else {
                    echo "<div class='mt-4'><p>No cipher file provided.</p></div>";
                    exit;
                }
            }

            if (!empty($data)) {
                $algorithm = $_POST['algorithm'];
                $operation = $_POST['operation'];

                if ($algorithm == 'rc4') {
                    if ($operation == 'encrypt') {
                        $encryptedData = rc4Encrypt($key, $data);
                        $cipherResult = bin2hex($encryptedData);
                        echo "<div class='mt-4'><p>Algorithm: RC4</p>";
                        echo "<p>Key: " . htmlspecialchars($key) . "</p>";
                        echo "<p>Encrypted (hex): " . bin2hex($encryptedData) . "</p></div>";
                    } else {
                        $decryptedData = rc4Decrypt($key, hex2bin($data));
                        $cipherResult = $decryptedData;
                        echo "<div class='mt-4'><p>Algorithm: RC4</p>";
                        echo "<p>Key: " . htmlspecialchars($key) . "</p>";
                        if (strlen($decryptedData) > 100) {
                            echo "<p>Decrypted text is too long to display. Please download the result file.</p>";
                        } else {
                            echo "<p>Decrypted: " . htmlspecialchars($decryptedData) . "</p>";
                        }
                        echo "</div>";
                    }
                } else if ($algorithm == 'rc5') {
                    if ($operation == 'encrypt') {
                        $encryptedData = rc5Encrypt($key, $data);
                        $cipherResult = bin2hex($encryptedData);
                        echo "<div class='mt-4'><p>Algorithm: RC5</p>";
                        echo "<p>Key: " . htmlspecialchars($key) . "</p>";
                        echo "<p>Encrypted (hex): " . bin2hex($encryptedData) . "</p></div>";
                    } else {
                        $decryptedData = rc5Decrypt($key, hex2bin($data));
                        $cipherResult = $decryptedData;
                        echo "<div class='mt-4'><p>Algorithm: RC5</p>";
                        echo "<p>Key: " . htmlspecialchars($key) . "</p>";
                        if (strlen($decryptedData) > 100) {
                            echo "<p>Decrypted text is too long to display. Please download the result file.</p>";
                        } else {
                            echo "<p>Decrypted: " . htmlspecialchars($decryptedData) . "</p>";
                        }
                        echo "</div>";
                    }
                }

                // Create downloadable result files
                $keyFilename = "key.txt";
                $cipherFilename = "cipher.txt";
                file_put_contents($keyFilename, $key);
                file_put_contents($cipherFilename, $cipherResult);
                echo "<a href='$keyFilename' class='btn btn-success mt-3' download>Download Key</a>";
                echo "<a href='$cipherFilename' class='btn btn-success mt-3 ml-2' download>Download Cipher</a>";
            } else {
                echo "<div class='mt-4'><p>No data provided.</p></div>";
            }
        }
        ?>
    </div>
</body>
</html>