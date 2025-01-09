<!DOCTYPE html>
<html>
<head>
    <title>Encryption Example</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function setFormAction() {
            var form = document.getElementById('myForm');
            var rc4 = document.getElementById('rc4').checked;
            var encrypt = document.getElementById('encrypt').checked;
            if (rc4) {
                form.action = encrypt ? 'index.php?algorithm=rc4&operation=encrypt' : 'index.php?algorithm=rc4&operation=decrypt';
            } else {
                form.action = encrypt ? 'index.php?algorithm=rc5&operation=encrypt' : 'index.php?algorithm=rc5&operation=decrypt';
            }
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Encryption Example</h2>
        <form id="myForm" method="post" onsubmit="setFormAction()">
            <div class="form-group">
                <label for="data">Insert below your data:</label>
                <input type="text" class="form-control" id="data" name="data" required>
            </div>
            <div class="form-group">
                <label for="key">Insert below your key used for hashing (Leave blank to generate one):</label>
                <input type="text" class="form-control" id="key" name="key">
            </div>
            <div class="form-group">
                <label>Algorithm:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="rc4" name="algorithm" value="rc4" required>
                    <label class="form-check-label" for="rc4">RC4</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="rc5" name="algorithm" value="rc5" required>
                    <label class="form-check-label" for="rc5">RC5</label>
                </div>
            </div>
            <div class="form-group">
                <label>Operation:</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="encrypt" name="operation" value="encrypt" required>
                    <label class="form-check-label" for="encrypt">Encrypt</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" id="decrypt" name="operation" value="decrypt" required>
                    <label class="form-check-label" for="decrypt">Decrypt</label>
                </div>
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
        function rc5Encrypt($key, $text, $rounds = 12, $wordSize = 32) {
            $modulus = 1 << $wordSize; // Modulus (2^wordSize)
            $mask = $modulus - 1; // Mask for modulo operations

            // Magic constants for RC5
            $P = 0xB7E15163; 
            $Q = 0x9E3779B9; 

            // Key preparation
            $keyWords = [];
            for ($i = 0; $i < strlen($key); $i += 4) {
                $keyWords[] = unpack('V', substr($key . "\0\0\0\0", $i, 4))[1];
            }

            $totalSubkeys = 2 * ($rounds + 1); // Number of subkeys
            $subkeys = [$P];
            for ($i = 1; $i < $totalSubkeys; $i++) {
                $subkeys[] = ($subkeys[$i - 1] + $Q) & $mask;
            }

            // Key mixing
            $keyIndex = $subkeyIndex = $valueA = $valueB = 0;
            $keyLength = count($keyWords);
            for ($k = 0; $k < 3 * max($totalSubkeys, $keyLength); $k++) {
                $valueA = $subkeys[$subkeyIndex] = rotateLeft(($subkeys[$subkeyIndex] + $valueA + $valueB) & $mask, 3, $wordSize);
                $valueB = $keyWords[$keyIndex] = rotateLeft(($keyWords[$keyIndex] + $valueA + $valueB) & $mask, ($valueA + $valueB) % $wordSize, $wordSize);
                $subkeyIndex = ($subkeyIndex + 1) % $totalSubkeys;
                $keyIndex = ($keyIndex + 1) % $keyLength;
            }

            // Divide the text into blocks
            $text = str_pad($text, (strlen($text) + 7) & ~7, "\0");
            $blocks = str_split($text, 8);
            $encryptedText = "";

            foreach ($blocks as $block) {
                [$valueA, $valueB] = array_values(unpack('V2', $block));

                // Encryption phase
                $valueA = ($valueA + $subkeys[0]) & $mask;
                $valueB = ($valueB + $subkeys[1]) & $mask;

                for ($i = 1; $i <= $rounds; $i++) {
                    $valueA = ($valueA ^ $valueB);
                    $valueA = rotateLeft($valueA, $valueB % $wordSize, $wordSize);
                    $valueA = ($valueA + $subkeys[2 * $i]) & $mask;

                    $valueB = ($valueB ^ $valueA);
                    $valueB = rotateLeft($valueB, $valueA % $wordSize, $wordSize);
                    $valueB = ($valueB + $subkeys[2 * $i + 1]) & $mask;
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
            $key = $_POST['key'];
            if (empty($key)) {
                $key = generateRandomKey(16);
            }

            $data = $_POST['data'];
            $algorithm = $_GET['algorithm'];
            $operation = $_GET['operation'];

            if (!empty($data)) {
                if ($algorithm == 'rc4') {
                    if ($operation == 'encrypt') {
                        $encryptedData = rc4Encrypt($key, $data);
                        echo "<div class='mt-4'><p>Key: " . htmlspecialchars($key) . "</p>";
                        echo "<p>Encrypted: " . bin2hex($encryptedData) . "</p></div>";
                    } else {
                        $decryptedData = rc4Decrypt($key, hex2bin($data));
                        echo "<div class='mt-4'><p>Key: " . htmlspecialchars($key) . "</p>";
                        echo "<p>Decrypted: " . htmlspecialchars($decryptedData) . "</p></div>";
                    }
                } else {
                    if ($operation == 'encrypt') {
                        $encryptedData = rc5Encrypt($key, $data);
                        echo "<div class='mt-4'><p>Key: " . htmlspecialchars($key) . "</p>";
                        echo "<p>Encrypted: " . bin2hex($encryptedData) . "</p></div>";
                    } else {
                        $decryptedData = rc5Decrypt($key, hex2bin($data));
                        echo "<div class='mt-4'><p>Key: " . htmlspecialchars($key) . "</p>";
                        echo "<p>Decrypted: " . htmlspecialchars($decryptedData) . "</p></div>";
                    }
                }
            } else {
                echo "<div class='mt-4'><p>No data provided.</p></div>";
            }
        }
        ?>
    </div>
</body>
</html>