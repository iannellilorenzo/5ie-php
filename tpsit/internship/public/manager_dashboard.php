<?php
session_start();
if(!isset($_SESSION["id"]) || $_SESSION["user_type"] != "manager") {
    header("location: login.php");
    exit;
}

require_once "../classes/Database.php";
require_once "../classes/Manager.php";
require_once "../classes/Student.php";
require_once "../classes/Company.php";
require_once "../classes/Agreement.php";
require_once "../classes/Internship.php";

// Initialize manager first
$manager = new Manager();
$manager->read($_SESSION["id"]);

// Handle actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    switch($_GET['action']) {
        case 'approve':
            if($manager->approveAgreement($_GET['id'])) {
                header("Location: manager_dashboard.php");
            }
            break;
            
        case 'reject':
            if($manager->rejectAgreement($_GET['id'])) {
                header("Location: manager_dashboard.php");
            }
            break;
    }
}

// Get companies data
$company = new Company();
$companies = $company->getAll();

// Get agreements data
$agreement = new Agreement();
$agreements = $agreement->getAll();

// Get students data for reference
$student = new Student();
$students = $student->getAll();

// Count stats
$pending_count = count(array_filter($agreements, function($a) {
    return $a['stato'] == 'proposto';
}));
$companies_count = count($companies);
$internship = new Internship();
$internships = $internship->getAll();
$internships_count = count($internships);

// Helper function
function getStudentName($students, $student_id) {
    foreach($students as $student) {
        if($student['id'] == $student_id) {
            return $student['nome'];
        }
    }
    return "Unknown Student";
}

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
                        <h5><?php echo htmlspecialchars($manager->nome); ?></h5>
                        <small class="text-muted">Manager Portal</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#overview">
                                <i class="bi bi-house-door"></i> Overview
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#applications">
                                <i class="bi bi-file-text"></i> Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#companies">
                                <i class="bi bi-building"></i> Companies
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
                    <h1 class="h2">Manager Dashboard</h1>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-4 col-sm-6 mb-3">
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

                    <div class="col-xl-4 col-sm-6 mb-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-white-50">Companies</div>
                                        <div class="huge"><?php echo $companies_count; ?></div>
                                    </div>
                                    <i class="bi bi-building fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-sm-6 mb-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-white-50">Active Internships</div>
                                        <div class="huge"><?php echo $internships_count; ?></div>
                                    </div>
                                    <i class="bi bi-briefcase fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Applications Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pending Applications</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped" id="applicationsTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Company</th>
                                    <th>Internship</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach($agreements as $row) {
                                    if($row['stato'] == 'proposto') {
                                        $internship_details = array_filter($internships, function($i) use ($row) {
                                            return $i['id'] == $row['tirocinio_id'];
                                        });
                                        $internship_details = reset($internship_details);
                                        
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars(getStudentName($students, $row['studente_id'])) . "</td>";
                                        echo "<td>" . htmlspecialchars(getCompanyName($companies, $internship_details['azienda_id'])) . "</td>";
                                        echo "<td>" . htmlspecialchars($internship_details['descrizione']) . 
                                             " (" . htmlspecialchars($internship_details['durata']) . " months)</td>";
                                        echo "<td><span class='badge bg-warning'>Pending</span></td>";
                                        echo "<td>
                                                <button class='btn btn-sm btn-success' onclick='approveApplication({$row["id"]})'>
                                                    <i class='bi bi-check-circle'></i> Approve
                                                </button>
                                                <button class='btn btn-sm btn-danger' onclick='rejectApplication({$row["id"]})'>
                                                    <i class='bi bi-x-circle'></i> Reject
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

                <!-- Companies Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Registered Companies</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped" id="companiesTable">
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Address</th>
                                    <th>Phone</th>
                                    <th>Active Internships</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach($companies as $row) {
                                    $company_internships = count(array_filter($internships, function($i) use ($row) {
                                        return $i['azienda_id'] == $row['id'];
                                    }));
                                    
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row["nome"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["indirizzo"]) . "</td>";
                                    echo "<td>" . htmlspecialchars($row["telefono"]) . "</td>";
                                    echo "<td>" . $company_internships . "</td>";
                                    echo "</tr>";
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
            $('#applicationsTable').DataTable();
            $('#companiesTable').DataTable();
        });

        function approveApplication(applicationId) {
            if(confirm('Are you sure you want to approve this application?')) {
                window.location.href = '?action=approve&id=' + applicationId;
            }
        }

        function rejectApplication(applicationId) {
            if(confirm('Are you sure you want to reject this application?')) {
                window.location.href = '?action=reject&id=' + applicationId;
            }
        }
    </script>
</body>
</html>