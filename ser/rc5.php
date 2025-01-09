<?php

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

function rotateLeft($value, $shift, $wordSize) {
    return (($value << $shift) | ($value >> ($wordSize - $shift))) & ((1 << $wordSize) - 1);
}

function rotateRight($value, $shift, $wordSize) {
    return (($value >> $shift) | ($value << ($wordSize - $shift))) & ((1 << $wordSize) - 1);
}

function generateRandomKey($length = 16) {
    return bin2hex(random_bytes($length));
}

$encrypted = '';
$decrypted = '';
$key = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $key = $_POST['key'];
    if (empty($key)) {
        $key = generateRandomKey(16);
    }

    $data = $_POST['data'];
    if (!empty($data)) {
        $encrypted = rc5Encrypt($key, $data);
        $decrypted = rc5Decrypt($key, $encrypted);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>RC5 Encryption</title>
</head>
<body>
    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($data)): ?>
        <p>Key: <?php echo htmlspecialchars($key); ?></p>
        <p>Encrypted: <?php echo bin2hex($encrypted); ?></p>
        <p>Decrypted: <?php echo htmlspecialchars($decrypted); ?></p>
    <?php else: ?>
        <p>No data provided.</p>
    <?php endif; ?>
</body>
</html>