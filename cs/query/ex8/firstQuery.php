<?php
// Database connection parameters
$host = 'localhost';
$db = 'scuola';
$user = 'root';
$pass = '';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db";

try {
    // Create a PDO instance
    $pdo = new PDO($dsn, $user, $pass);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Function to get all interrogations by a student
function getInterrogationsByStudent($pdo, $studentId) {
    $stmt = $pdo->prepare('SELECT * FROM interrogazioni WHERE Id_alunno = ?');
    $stmt->execute([$studentId]);
    return $stmt->fetchAll();
}

// Function to get all interrogations by a student in a specific subject
function getInterrogationsByStudentAndSubject($pdo, $studentId, $subjectId) {
    $stmt = $pdo->prepare('SELECT * FROM interrogazioni WHERE Id_alunno = ? AND id_materia = ?');
    $stmt->execute([$studentId, $subjectId]);
    return $stmt->fetchAll();
}

// Function to get all subjects in which interrogations have been conducted
function getSubjectsWithInterrogations($pdo) {
    $stmt = $pdo->query('SELECT DISTINCT materie.nome FROM interrogazioni JOIN materie ON interrogazioni.id_materia = materie.id');
    return $stmt->fetchAll();
}

// Example usage
$studentId = 1;
$subjectId = 2;

echo "Interrogations by student $studentId:\n";
$interrogations = getInterrogationsByStudent($pdo, $studentId);
foreach ($interrogations as $interrogation) {
    print_r($interrogation);
}

echo "\nInterrogations by student $studentId in subject $subjectId:\n";
$interrogations = getInterrogationsByStudentAndSubject($pdo, $studentId, $subjectId);
foreach ($interrogations as $interrogation) {
    print_r($interrogation);
}

echo "\nSubjects with interrogations:\n";
$subjects = getSubjectsWithInterrogations($pdo);
foreach ($subjects as $subject) {
    print_r($subject);
}
