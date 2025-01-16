<?php
session_start();
if(!isset($_SESSION["id"]) || $_SESSION["user_type"] != "student") {
    header("location: login.php");
    exit;
}

require_once "../classes/Database.php";
require_once "../classes/Student.php";
require_once "../classes/Internship.php";
require_once "../classes/Agreement.php";
require_once "../classes/Company.php";

// Add at top with other action handlers:
if(isset($_GET['action']) && $_GET['action'] == 'apply' && isset($_GET['internship_id'])) {
    $agreement = new Agreement();
    $agreement->studente_id = $_SESSION['id'];
    $agreement->tirocinio_id = $_GET['internship_id'];
    $agreement->stato = 'proposto';
    if($agreement->create()) {
        header("Location: student_dashboard.php");
        exit;
    }
}

if(isset($_GET['action']) && $_GET['action'] == 'withdraw' && isset($_GET['id'])) {
    $agreement = new Agreement();
    $agreement->id = $_GET['id'];
    if($agreement->delete()) {
        header("Location: student_dashboard.php");
        exit;
    }
}

// Initialize objects and get data
$student = new Student();
$student->read($_SESSION["id"]);

$company = new Company();
$companies = $company->getAll();

$internship = new Internship();
$internships = $internship->getAll();

$agreement = new Agreement();
$agreements = $agreement->getAll();
$student_id = $_SESSION['id'];

// Count agreements
$pending_count = count(array_filter($agreements, function($a) use ($student_id) {
    return $a['studente_id'] == $student_id && $a['stato'] == 'proposto';
}));

$approved_count = count(array_filter($agreements, function($a) use ($student_id) {
    return $a['studente_id'] == $student_id && $a['stato'] == 'approvato';
}));

// Helper function to get company name
function getCompanyName($companies, $company_id) {
    foreach($companies as $company) {
        if($company['id'] == $company_id) {
            return $company['nome'];
        }
    }
    return "Unknown Company";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexusLink - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.png">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h5><?php echo htmlspecialchars($student->nome); ?></h5>
                        <small class="text-muted">Student Portal</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#overview">
                                <i class="bi bi-house-door"></i> Overview
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#internships">
                                <i class="bi bi-briefcase"></i> Available Internships
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#applications">
                                <i class="bi bi-file-text"></i> My Applications
                            </a>
                        </li>
                    </ul>
                    <hr>
                    <div class="px-3">
                        <a href="logout.php" class="btn btn-danger w-100">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Student Dashboard</h1>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-white-50">Pending Applications</div>
                                        <div class="huge"><?php echo $pending_count; ?></div>
                                    </div>
                                    <i class="bi bi-hourglass-split fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-white-50">Approved Applications</div>
                                        <div class="huge"><?php echo $approved_count; ?></div>
                                    </div>
                                    <i class="bi bi-check-circle fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Internships Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Available Internships</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped" id="internshipsTable">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Company</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach($internships as $row) {
                                    $has_applied = false;
                                    foreach($agreements as $agreement) {
                                        if($agreement['tirocinio_id'] == $row['id'] && 
                                           $agreement['studente_id'] == $student_id) {
                                            $has_applied = true;
                                            break;
                                        }
                                    }
                                    if(!$has_applied) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row["descrizione"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($row["durata"]) . " months</td>";
                                        echo "<td>" . htmlspecialchars(getCompanyName($companies, $row["azienda_id"])) . "</td>";
                                        echo "<td>
                                                <button class='btn btn-sm btn-success' onclick='applyForInternship({$row["id"]})'>
                                                    <i class='bi bi-check-circle'></i> Apply
                                                </button>
                                              </td>";
                                        echo "</tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- My Applications Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">My Applications</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped" id="applicationsTable">
                            <thead>
                                <tr>
                                    <th>Internship</th>
                                    <th>Company</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach($agreements as $row) {
                                    if($row['studente_id'] == $student_id) {
                                        echo "<tr>";
                                        foreach($internships as $internship) {
                                            if($internship['id'] == $row['tirocinio_id']) {
                                                echo "<td>" . htmlspecialchars($internship["descrizione"]) . 
                                                     " (" . htmlspecialchars($internship["durata"]) . " months)</td>";
                                                echo "<td>" . htmlspecialchars(getCompanyName($companies, $internship["azienda_id"])) . "</td>";
                                                break;
                                            }
                                        }
                                        echo "<td><span class='badge bg-" . 
                                             ($row["stato"] == "proposto" ? "warning" : 
                                             ($row["stato"] == "approvato" ? "success" : "danger")) . 
                                             "'>" . htmlspecialchars($row["stato"]) . "</span></td>";
                                        echo "<td>";
                                        if($row["stato"] == "proposto") {
                                            echo "<button class='btn btn-sm btn-danger' onclick='withdrawApplication({$row["id"]})'>
                                                    <i class='bi bi-x-circle'></i> Withdraw
                                                  </button>";
                                        }
                                        echo "</td></tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#internshipsTable').DataTable();
            $('#applicationsTable').DataTable();
        });

        function applyForInternship(internshipId) {
            if(confirm('Are you sure you want to apply for this internship?')) {
                window.location.href = '?action=apply&internship_id=' + internshipId;
            }
        }

        function withdrawApplication(applicationId) {
            if(confirm('Are you sure you want to withdraw this application?')) {
                window.location.href = '?action=withdraw&id=' + applicationId;
            }
        }
    </script>
</body>
</html>