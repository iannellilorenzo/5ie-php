<?php
session_start();

$failed_attempts = 0;

if (isset($_COOKIE['failed_attempts'])) {
    $failed_attempts = $_COOKIE['failed_attempts'];
    if ($failed_attempts >= 3) {
        $last_attempt_time = $_COOKIE['last_attempt_time'];
        $current_time = time();
        $time_diff = $current_time - $last_attempt_time;

        if ($time_diff < 120) {
            die("You have exceeded the maximum number of login attempts. Please try again after 2 minutes.");
        } else {
            setcookie('failed_attempts', '', time() - 3600, '/');
            setcookie('last_attempt_time', '', time() - 3600, '/');
            $failed_attempts = 0;
        }
    }
}
?>

<!doctype html>
<html lang="en">
  <head>
    <title>Login Form</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  </head>
  <body>
    <div class="container mt-5">
      <h1 class="text-center mb-4">Login</h1>
        <div class="alert alert-warning text-center">
          Failed Attempts: <?php echo $failed_attempts; ?>
        </div>
      <form action="login.php" method="post">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
      </form>
    </div>
  </body>
</html>