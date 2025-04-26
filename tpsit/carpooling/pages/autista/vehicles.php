<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set page title
$pageTitle = "My Vehicles";

// Set root path for includes
$rootPath = "../../";

// Check if user is logged in and is a driver
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'autista') {
    // Redirect to login
    header("Location: {$rootPath}login.php");
    exit();
}

// Include database and models
require_once $rootPath . 'api/config/database.php';
require_once $rootPath . 'api/models/Automobile.php';
require_once $rootPath . 'api/models/Viaggio.php';

// Initialize
$error = null;
$success = null;
$vehicles = [];

// Handle vehicle deletion if requested
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $automobileModel = new Automobile($conn);
        $viaggioModel = new Viaggio($conn);
        
        // Check if vehicle is being used in any trips
        $trips = $viaggioModel->getAll(['auto_id' => $_GET['delete']]);
        
        if (!empty($trips)) {
            $error = "Cannot delete vehicle that is assigned to trips. Please reassign or cancel those trips first.";
        } else {
            // Delete the vehicle
            $automobileModel->delete($_GET['delete']);
            $success = "Vehicle deleted successfully!";
        }
    } catch (Exception $e) {
        $error = "Error deleting vehicle: " . $e->getMessage();
    }
}

try {
    // Connect to database
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get driver's vehicles
    $automobileModel = new Automobile($conn);
    $vehicles = $automobileModel->getAll(['id_autista' => $_SESSION['user_id']]);
    
} catch (Exception $e) {
    $error = "Error retrieving vehicles: " . $e->getMessage();
}

// Include header
include $rootPath . 'includes/header.php';

// Include navbar
include $rootPath . 'includes/navbar.php';
?>

<div class="container-wrapper">
<!-- Vehicles Management Content -->
<div class="container py-5">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">My Vehicles</h1>
        <a href="<?php echo $rootPath; ?>pages/autista/add-vehicle.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i> Add New Vehicle
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Vehicles List -->
    <div class="row g-4">
        <?php if (empty($vehicles)): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-car-front text-muted" style="font-size: 3.5rem;"></i>
                        </div>
                        <h4 class="text-muted">No vehicles added yet</h4>
                        <p class="text-muted mb-4">Add your first vehicle to start offering rides</p>
                        <a href="<?php echo $rootPath; ?>pages/autista/add-vehicle.php" class="btn btn-primary btn-lg">
                            Add Your First Vehicle
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?php echo htmlspecialchars($vehicle['marca'] . ' ' . $vehicle['modello']); ?></h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="vehicleActionDropdown<?php echo $vehicle['targa']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="vehicleActionDropdown<?php echo $vehicle['targa']; ?>">
                                        <li>
                                            <a class="dropdown-item" href="<?php echo $rootPath; ?>pages/autista/edit-vehicle.php?id=<?php echo urlencode($vehicle['targa']); ?>">
                                                <i class="bi bi-pencil me-2"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteVehicleModal" 
                                                    data-vehicle-id="<?php echo htmlspecialchars($vehicle['targa']); ?>"
                                                    data-vehicle-name="<?php echo htmlspecialchars($vehicle['marca'] . ' ' . $vehicle['modello']); ?>">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="small text-muted">License Plate</div>
                                <p class="mb-0 fw-medium"><?php echo htmlspecialchars($vehicle['targa']); ?></p>
                            </div>
                            
                            <hr class="my-3">
                            
                            <div class="d-grid gap-2">
                                <a href="<?php echo $rootPath; ?>pages/autista/create-trip.php?vehicle=<?php echo urlencode($vehicle['targa']); ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-plus-circle me-2"></i>Create Trip with This Vehicle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($vehicles)): ?>
    <div class="text-center mt-4">
        <a href="<?php echo $rootPath; ?>pages/autista/add-vehicle.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i> Add Another Vehicle
        </a>
    </div>
    <?php endif; ?>
</div>
</div>

<!-- Delete Vehicle Modal -->
<div class="modal fade" id="deleteVehicleModal" tabindex="-1" aria-labelledby="deleteVehicleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteVehicleModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this vehicle: <strong id="vehicleNameToDelete"></strong>?</p>
                <p class="text-danger mb-0"><strong>Warning:</strong> This action cannot be undone, and you won't be able to use this vehicle for trips anymore.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete Vehicle</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle deletion modal
    const deleteModal = document.getElementById('deleteVehicleModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const vehicleId = button.getAttribute('data-vehicle-id');
            const vehicleName = button.getAttribute('data-vehicle-name');
            
            document.getElementById('vehicleNameToDelete').textContent = vehicleName;
            document.getElementById('confirmDeleteBtn').href = '?delete=' + encodeURIComponent(vehicleId);
        });
    }
    
    // Auto-dismiss alerts after 4 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 4000);
});
</script>

<?php
// Include footer
include $rootPath . 'includes/footer.php';
?>