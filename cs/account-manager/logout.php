<?php
session_start();
session_unset();
session_destroy();
setcookie("username", "", time() - 3600, "/"); // Clear the cookie
header("Location: sign_in.php");
exit();