<?php
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

$param = explode('/', $path);
$pattern = '/^[A-Z\d]{6}$/';

switch ($method) {
    case 'GET':
        if (isset($param[3]) && $param[3] == 'corsi') {
            if (!isset($param[4])) {
                getAllCourses();
            } else if (preg_match($pattern, $param[4])) {
                getCourse($param[4]);
            } else {
                echo json_encode(["message" => "Invalid course ID"]);
            }
        } else {
            echo json_encode(["message" => "Invalid path"]);
        }
        break;
    case 'POST':
        if (isset($param[2]) && $param[2] == 'corsi') {
            createCourse();
        } else {
            echo json_encode(["message" => "Invalid path"]);
        }
        break;
    case 'PUT':
        if (isset($param[2]) && $param[2] == 'corsi') {
            updateCourse($param[3]);
        } else {
            echo json_encode(["message" => "Invalid path"]);
        }
        break;
    case 'DELETE':
        if (isset($param[2]) && $param[2] == 'corsi') {
            deleteCourse($param[3]);
        } else {
            echo json_encode(["message" => "Invalid path"]);
        }
        break;
    default:
        echo json_encode(["message" => "Invalid method"]);
        break;
}

// Function to get all courses
function getAllCourses() {
    $jsonFile = 'corsi.json';
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $courses = json_decode($jsonData, true);
        $result = [];
        foreach ($courses as $course) {
            $result[] = [
                'id' => $course['id'],
                'titolo' => isset($course['titolo']) ? $course['titolo'] : 'No title'
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($result);
    } else {
        header("HTTP/1.0 404 Not Found");
        echo json_encode(["message" => "File not found"]);
    }
}

// Function to get a course by ID
function getCourse($id) {
    $jsonFile = 'corsi.json';
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $courses = json_decode($jsonData, true);
        $course = linearSearch($courses, $id);
        if ($course) {
            header('Content-Type: application/json');
            echo json_encode($course);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode(["message" => "Course not found"]);
        }
    } else {
        header("HTTP/1.0 404 Not Found");
        echo json_encode(["message" => "File not found"]);
    }
}

// Linear search function
function linearSearch($courses, $id) {
    foreach ($courses as $course) {
        if ($course['id'] === $id) {
            return $course;
        }
    }
    return null;
}
?>