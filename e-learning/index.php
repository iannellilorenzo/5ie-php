<?php
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

$param = explode('/', $path);
$pattern = '/^[A-Z\d]{6}$/';


switch ($method) {
    case 'GET':
        if (isset($param[3]) && substr($param[3], 0, 5) == 'corsi') {
            if (substr($param[3], 5, 1) === "?") {
                if (isset($_GET['data'])) {
                    getCoursesByDate($_GET['data']);
                } else if (isset($_GET['titolo'])) {
                    getCourseByTitle($_GET['titolo']);
                } else {
                    echo json_encode(["message" => "Invalid query parameter"]);
                }
            } else if (!isset($param[4])) {
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
        createCourse();
        break;
    case 'PUT':
        if (isset($param[3]) && $param[3] == 'corsi' && isset($param[4]) && preg_match($pattern, $param[4])) {
            updateCourse($param[4]);
        } else {
            echo json_encode(["message" => "Invalid path or course ID"]);
        }
        break;
    case 'DELETE':
        if (isset($param[3]) && $param[3] == 'corsi' && isset($param[4]) && preg_match($pattern, $param[4])) {
            deleteCourse($param[4]);
        } else {
            echo json_encode(["message" => "Invalid path or course ID"]);
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

function getCoursesByDate($date) {
    $jsonFile = 'corsi.json';
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $courses = json_decode($jsonData, true);
        $result = [];
        foreach ($courses as $course) {
            if ($course['data'] === $date) {
                $result[] = $course;
            }
        }
        if (!empty($result)) {
            header('Content-Type: application/json');
            echo json_encode($result);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode(["message" => "No courses found for the given date"]);
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

// Function to generate a unique ID
function generateId() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $id = '';
    for ($i = 0; $i < 6; $i++) {
        $id .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $id;
}

// Function to create a new course
function createCourse() {
    $jsonFile = 'corsi.json';
    $jsonData = file_get_contents('php://input'); // Read the raw POST data
    $newCourse = json_decode($jsonData, true);

    if (!isset($newCourse['titolo']) || !isset($newCourse['data']) || !isset($newCourse['materia']) || !isset($newCourse['costo']) || !isset($newCourse['descrizione']) || !isset($newCourse['durata'])) {
        echo json_encode(["message" => "Invalid course data"]);
        return;
    }

    if (file_exists($jsonFile)) {
        $courses = json_decode(file_get_contents($jsonFile), true);
    } else {
        $courses = [];
    }

    $newCourse = array_merge(['id' => generateId()], $newCourse); // Ensure 'id' is the first field
    $courses[] = $newCourse;

    file_put_contents($jsonFile, json_encode($courses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo json_encode($newCourse, JSON_UNESCAPED_UNICODE);
}

// Function to update an existing course
function updateCourse($id) {
    $jsonFile = 'corsi.json';
    $jsonData = file_get_contents('php://input'); // Read the raw PUT data
    $updatedData = json_decode($jsonData, true);

    if (!isset($updatedData)) {
        echo json_encode(["message" => "Invalid course data"]);
        return;
    }

    if (isset($updatedData['id'])) {
        echo json_encode(["message" => "Course ID must not be inside the body. Pass it via parameter."]);
        return;
    }
    
    if (file_exists($jsonFile)) {
        $courses = json_decode(file_get_contents($jsonFile), true);
    } else {
        echo json_encode(["message" => "Courses file not found"]);
        return;
    }

    $courseFound = false;
    foreach ($courses as &$course) {
        if ($course['id'] === $id) {
            $courseFound = true;
            foreach ($updatedData as $key => $value) {
                if ($key !== 'id') {
                    if ($key === 'costo' && is_array($value)) {
                        foreach ($value as $costoKey => $costoValue) {
                            $course['costo'][$costoKey] = $costoValue;
                        }
                    } else {
                        $course[$key] = $value;
                    }
                }
            }
            break;
        }
    }

    if (!$courseFound) {
        echo json_encode(["message" => "Course not found"]);
        return;
    }

    file_put_contents($jsonFile, json_encode($courses, JSON_PRETTY_PRINT));
    echo json_encode($course);
}

// Function to delete a course by ID
function deleteCourse($id) {
    $jsonFile = 'corsi.json';
    if (file_exists($jsonFile)) {
        $courses = json_decode(file_get_contents($jsonFile), true);
        $courseFound = false;
        foreach ($courses as $key => $course) {
            if ($course['id'] === $id) {
                $courseFound = true;
                unset($courses[$key]);
                break;
            }
        }

        if ($courseFound) {
            file_put_contents($jsonFile, json_encode(array_values($courses), JSON_PRETTY_PRINT));
            echo json_encode(["message" => "Course deleted successfully"]);
        } else {
            echo json_encode(["message" => "Course not found"]);
        }
    } else {
        echo json_encode(["message" => "Courses file not found"]);
    }
}

// Function to get a course by title substring
function getCourseByTitle($title) {
    $jsonFile = 'corsi.json';
    if (file_exists($jsonFile)) {
        $jsonData = file_get_contents($jsonFile);
        $courses = json_decode($jsonData, true);
        $results = [];
        foreach ($courses as $course) {
            if (stripos($course['titolo'], $title) !== false) {
                $results[] = [
                    'id' => $course['id'],
                    'titolo' => $course['titolo']
                ];
            }
        }
        if (!empty($results)) {
            header('Content-Type: application/json');
            echo json_encode($results);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode(["message" => "Course not found"]);
        }
    } else {
        header("HTTP/1.0 404 Not Found");
        echo json_encode(["message" => "File not found"]);
    }
}