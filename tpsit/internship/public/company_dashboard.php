<?php
session_start();
if(!isset($_SESSION["id"]) || $_SESSION["user_type"] != "company") {
    header("location: login.php");
    exit;
}

require_once "../classes/Database.php";  
require_once "../classes/Company.php";
require_once "../classes/Internship.php";
require_once "../classes/Offer.php";

// Handle POST actions
if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_internship':
                $internship = new Internship();
                $internship->descrizione = $_POST['description'];
                $internship->durata = $_POST['duration'];
                $internship->azienda_id = $_SESSION['id'];
                if($internship->create()) {
                    header("Location: company_dashboard.php");
                }
                break;

            case 'add_offer':
                $offer = new Offer();
                $offer->titolo = $_POST['title'];
                $offer->descrizione = $_POST['description']; 
                $offer->stato = 'inserita';
                $offer->azienda_id = $_SESSION['id'];
                if($offer->create()) {
                    header("Location: company_dashboard.php");
                }
                break;
            case 'edit_internship':
                $internship = new Internship();
                $internship->id = $_POST['id'];
                $internship->descrizione = $_POST['description'];
                $internship->durata = $_POST['duration']; 
                $internship->azienda_id = $_SESSION['id'];
                if($internship->update()) {
                    header("Location: company_dashboard.php");
                }
                break;

            case 'edit_offer':
                $offer = new Offer();
                $offer->id = $_POST['id'];
                $offer->titolo = $_POST['title'];
                $offer->descrizione = $_POST['description'];
                $offer->stato = 'inserita';
                $offer->azienda_id = $_SESSION['id'];
                if($offer->update()) {
                    header("Location: company_dashboard.php");
                }
                break;
        }
    }
}

// Handle GET actions (deletes)
if(isset($_GET['action']) && isset($_GET['id'])) {
    switch($_GET['action']) {
        case 'delete_internship':
            $internship = new Internship();
            $internship->id = $_GET['id'];
            if($internship->delete()) {
                header("Location: company_dashboard.php");
            }
            break;

        case 'delete_offer':
            $offer = new Offer();
            $offer->id = $_GET['id'];
            if($offer->delete()) {
                header("Location: company_dashboard.php");
            }
            break;
    }
}

// Initialize data
$company = new Company();
$company->read($_SESSION["id"]);

$company_id = $_SESSION["id"];

$internship = new Internship();
$internships = array_filter($internship->getAll(), function($i) use ($company_id) {
    return $i['azienda_id'] == $company_id;
});
$internship_count = count($internships);

$offer = new Offer();
$offers = array_filter($offer->getAll(), function($o) use ($company_id) {
    return $o['azienda_id'] == $company_id;
});
$offers_count = count($offers);
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
                        <h5><?php echo htmlspecialchars($company->nome); ?></h5>
                        <small class="text-muted">Company Portal</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#overview">
                                <i class="bi bi-house-door"></i> Overview
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#internships">
                                <i class="bi bi-briefcase"></i> Internships
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#offers">
                                <i class="bi bi-file-text"></i> Offers
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
                <!-- Stats Cards -->
                <div class="row mb-4 mt-4">
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-white-50">Active Internships</div>
                                        <div class="huge"><?php echo $internship_count; ?></div>
                                    </div>
                                    <i class="bi bi-briefcase fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="small text-white-50">Active Offers</div>
                                        <div class="huge"><?php echo $offers_count; ?></div>
                                    </div>
                                    <i class="bi bi-file-text fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Internships Table -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Internships Management</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInternshipModal">
                            <i class="bi bi-plus"></i> New Internship
                        </button>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped" id="internshipsTable">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($internships as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['descrizione']); ?></td>
                                    <td><?php echo htmlspecialchars($row['durata']); ?> months</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editInternship(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['descrizione']); ?>', <?php echo $row['durata']; ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteInternship(<?php echo $row['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Offers Table -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Offers Management</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOfferModal">
                            <i class="bi bi-plus"></i> New Offer
                        </button>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped" id="offersTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($offers as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['titolo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['descrizione']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['stato'] == 'inserita' ? 'primary' : 
                                            ($row['stato'] == 'accettata' ? 'success' : 'danger'); ?>">
                                            <?php echo htmlspecialchars($row['stato']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="editOffer(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['titolo']); ?>', '<?php echo htmlspecialchars($row['descrizione']); ?>')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteOffer(<?php echo $row['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Internship Modal -->
    <div class="modal fade" id="addInternshipModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Internship</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="add_internship">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (months)</label>
                            <input type="number" class="form-control" id="duration" name="duration" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Internship</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Offer Modal -->
    <div class="modal fade" id="addOfferModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Offer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="add_offer">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="offer_description" class="form-label">Description</label>
                            <textarea class="form-control" id="offer_description" name="description" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Offer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Internship Modal -->
    <div class="modal fade" id="editInternshipModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Internship</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="edit_internship">
                    <input type="hidden" name="id" id="edit_internship_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_duration" class="form-label">Duration (months)</label>
                            <input type="number" class="form-control" id="edit_duration" name="duration" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Offer Modal -->
    <div class="modal fade" id="editOfferModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Offer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="edit_offer">
                    <input type="hidden" name="id" id="edit_offer_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_offer_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_offer_description" name="description" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#internshipsTable').DataTable();
            $('#offersTable').DataTable();
        });

        function editInternship(id, description, duration) {
            $('#edit_internship_id').val(id);
            $('#edit_description').val(description);
            $('#edit_duration').val(duration);
            $('#editInternshipModal').modal('show');
        }

        function editOffer(id, title, description) {
            $('#edit_offer_id').val(id);
            $('#edit_title').val(title); 
            $('#edit_offer_description').val(description);
            $('#editOfferModal').modal('show');
        }

        function deleteInternship(id) {
            if(confirm('Are you sure you want to delete this internship?')) {
                window.location.href = '?action=delete_internship&id=' + id;
            }
        }

        function deleteOffer(id) {
            if(confirm('Are you sure you want to delete this offer?')) {
                window.location.href = '?action=delete_offer&id=' + id;
            }
        }
    </script>
</body>
</html>