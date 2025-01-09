<?php

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

function rc4Decrypt($key, $data) {
    return rc4Encrypt($key, $data); // RC4 is symmetric
}

$encrypted = '';
$decrypted = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['key']) && isset($_POST['data'])) {
        $key = $_POST['key'];
        $data = $_POST['data'];
        $encrypted = rc4Encrypt($key, $data);
        $decrypted = rc4Decrypt($key, $encrypted);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>RC4 Encryption</title>
</head>
<body>
    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['key']) && isset($_POST['data'])): ?>
        <p>Encrypted: <?php echo bin2hex($encrypted); ?></p>
        <p>Decrypted: <?php echo htmlspecialchars($decrypted); ?></p>
    <?php endif; ?>
</body>
</html>