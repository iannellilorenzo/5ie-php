
<?php
session_start();
$checkField1 = 'admin1';
$checkField2 = 'admin';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $field1 = $_POST['field1'];
    $field2 = $_POST['field2'];

    if (!isset($field1) && !isset($field2)) {
        exit;
    }

    if ($checkField1 == $field1 && $checkField2 == $field2 && isset($_COOKIE['accesses'])) {
        $_SESSION['user'] = 'iannelli';
        setcookie('accesses', $_COOKIE['accesses'] + 1, time() + (60 * 5), "/");
        header('Location: protected.php');
        echo "Login Success";
        exit;
    } elseif ($checkField1 == $field1 && $checkField2 == $field2 && !isset($_COOKIE['accesses'])) {
        $_SESSION['user'] = 'iannelli';
        setcookie('accesses', 1, time() + (60 * 5), "/");
        header('Location: protected.php');
        echo "Login Success";
        exit;
    } else {
        session_unset();
        session_abort();
        echo "Login Failed";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Example</title>
</head>
<body>
    <form action="index.php" method="post">
        <label for="field1">Field 1:</label>
        <input type="text" id="field1" name="field1" required><br><br>
        
        <label for="field2">Field 2:</label>
        <input type="password" id="field2" name="field2" required><br><br>
        
        <input type="submit" value="Submit">
    </form>
</body>
</html>