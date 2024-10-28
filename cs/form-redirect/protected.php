<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user'] != 'iannelli') {
  echo "Accesso negato";
  exit;
}

echo "pagina protetta";
