<?php
session_start();
$checkField1 = 'admin1';
$checkField2 = 'admin';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_COOKIE['block']) && $_COOKIE['block'] == 'blocked') {
        echo "You are blocked for 2 minutes due to multiple failed login attempts.";
        exit;
    }

    $field1 = $_POST['field1'];
    $field2 = $_POST['field2'];
    $interest = $_POST['interest'];

    if (!isset($field1) || !isset($field2) || !isset($interest)) {
        exit;
    }

    if ($checkField1 == $field1 && $checkField2 == $field2 && !isset($_COOKIE['accesses'])) {
        $_SESSION['user'] = 'iannelli';
        setcookie('interest', $interest, time() + (86400 * 30), "/"); // 86400 = 1 day
        setcookie('accesses', 1, time() + (60 * 5), "/");
        setcookie('failed_attempts', 0, time() - 3600, "/"); // Reset failed attempts
        header('Location: protected.php');
        echo "Login Success";
        exit;
    } elseif ($checkField1 == $field1 && $checkField2 == $field2) {
        $_SESSION['user'] = 'iannelli';
        setcookie('interest', $interest, time() + (86400 * 30), "/"); // 86400 = 1 day
        setcookie('failed_attempts', 0, time() - 3600, "/"); // Reset failed attempts
        setcookie('accesses', $_COOKIE['accesses'] + 1, time() + (60 * 5), "/");
        header('Location: protected.php');
        echo "Login Success";
        exit;
    } else {
        $failed_attempts = isset($_COOKIE['failed_attempts']) ? $_COOKIE['failed_attempts'] + 1 : 1;
        setcookie('failed_attempts', $failed_attempts, time() + (60 * 5), "/");

        if ($failed_attempts >= 3) {
            setcookie('block', 'blocked', time() + (60 * 2), "/");
            echo "You are blocked for 2 minutes due to multiple failed login attempts.";
        } else {
            echo "Login Failed";
        }
        session_unset();
        session_abort();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet">
    <title>Form Example</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Login Form</h3>
                    </div>
                    <div class="card-body">
                        <form action="index.php" method="post">
                            <div class="form-group">
                                <label for="field1">Username:</label>
                                <input type="text" id="field1" name="field1" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="field2">Password:</label>
                                <input type="password" id="field2" name="field2" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="interest">Interest:</label>
                                <select name="interest" id="interest" class="form-control">
                                    <option value="music">Music</option>
                                    <option value="movies">Movies</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
</body>
</html>