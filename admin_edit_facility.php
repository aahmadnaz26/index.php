<?php
// Start the session
session_start();

// Include necessary files
require_once('Models/Database.php');
require_once('Models/Facility.php');

// Check if the user is logged in as an admin
if (!isset($_SESSION['userType']) || $_SESSION['userType'] != 1) {
    die("Access denied. Admins only.");
}

// Initialize variables
$userName = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';
$successMessage = "";
$errorMessage = "";

// Create Facility object
$facilityObj = new Facility();

// Handle adding a facility
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addFacility'])) {
    try {
        // Validate inputs
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $houseNumber = trim($_POST['houseNumber']);
        $streetName = trim($_POST['streetName']);
        $town = trim($_POST['town']);
        $county = trim($_POST['county']);
        $lat = trim($_POST['lat']);
        $lng = trim($_POST['lng']);

        if (empty($title) || empty($category) || empty($description)) {
            throw new Exception("Title, category, and description are required fields.");
        }

        $facilityObj->addFacility($title, $category, $description, $houseNumber, $streetName, $town, $county, $lat, $lng, $userName);
        $successMessage = "Facility added successfully!";
    } catch (Exception $e) {
        $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
    }
}

// Handle editing a facility
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editFacility'])) {
    try {
        $facilityId = intval($_POST['facilityId']);
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        $houseNumber = trim($_POST['houseNumber']);
        $streetName = trim($_POST['streetName']);
        $town = trim($_POST['town']);
        $county = trim($_POST['county']);
        $lat = trim($_POST['lat']);
        $lng = trim($_POST['lng']);

        if (empty($title) || empty($category) || empty($description)) {
            throw new Exception("Title, category, and description are required fields.");
        }

        $facilityObj->editFacility($facilityId, $title, $category, $description, $houseNumber, $streetName, $town, $county, $lat, $lng);
        $successMessage = "Facility edited successfully!";
    } catch (Exception $e) {
        $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
    }
}

// Handle deleting a facility
if (isset($_GET['deleteFacility'])) {
    try {
        $facilityId = intval($_GET['deleteFacility']);
        $facilityObj->deleteFacility($facilityId);
        $successMessage = "Facility deleted successfully!";
    } catch (Exception $e) {
        $errorMessage = "Error: " . htmlspecialchars($e->getMessage());
    }
}

// Search for facilities
$searchTerm = isset($_POST['searchTerm']) ? htmlspecialchars($_POST['searchTerm']) : '';
$facilities = $facilityObj->getFacilities($searchTerm); // Assuming getFacilities accepts a search term
?>

<?php require('views/template/header.phtml'); ?>

<div class="container mt-5">
    <!-- Page Title -->
    <div class="text-center mb-5">
        <h2 class="display-4 font-weight-bold text-primary">Manage Facility</h2>
    </div>

    <!-- Display Messages -->
    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php elseif ($errorMessage): ?>
        <div class="alert alert-danger">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <!-- Add Facility Form -->
    <div class="card shadow-lg mb-5">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Add New Facility</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="admin_edit_facility.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="title" class="form-label font-weight-bold">Facility Title</label>
                        <input type="text" name="title" id="title" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label for="category" class="form-label font-weight-bold">Category</label>
                        <input type="text" name="category" id="category" class="form-control" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="description" class="form-label font-weight-bold">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <label for="houseNumber" class="form-label font-weight-bold">House Number</label>
                        <input type="text" name="houseNumber" id="houseNumber" class="form-control">
                    </div>
                    <div class="col-md-4 mb-4">
                        <label for="streetName" class="form-label font-weight-bold">Street Name</label>
                        <input type="text" name="streetName" id="streetName" class="form-control">
                    </div>
                    <div class="col-md-4 mb-4">
                        <label for="town" class="form-label font-weight-bold">Town</label>
                        <input type="text" name="town" id="town" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="county" class="form-label font-weight-bold">County</label>
                        <input type="text" name="county" id="county" class="form-control">
                    </div>
                    <div class="col-md-3 mb-4">
                        <label for="lat" class="form-label font-weight-bold">Latitude</label>
                        <input type="text" name="lat" id="lat" class="form-control">
                    </div>
                    <div class="col-md-3 mb-4">
                        <label for="lng" class="form-label font-weight-bold">Longitude</label>
                        <input type="text" name="lng" id="lng" class="form-control">
                    </div>
                </div>
                <button type="submit" name="addFacility" class="btn btn-primary">Add Facility</button>
            </form>
        </div>
    </div>

    <!-- Facility Search -->
    <form method="POST" action="admin_edit_facility.php" class="mb-5">
        <div class="input-group">
            <input type="text" name="searchTerm" class="form-control" placeholder="Search by title or category" value="<?php echo $searchTerm; ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <!-- Facility List -->
    <div class="card shadow-lg">
        <div class="card-header bg-dark text-white">
            <h4 class="mb-0">Existing Facilities</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($facilities as $facility): ?>
                        <tr>
                            <td><?php echo $facility['id']; ?></td>
                            <td><?php echo htmlspecialchars($facility['title']); ?></td>
                            <td><?php echo htmlspecialchars($facility['category']); ?></td>
                            <td><?php echo htmlspecialchars($facility['description']); ?></td>
                            <td><?php echo htmlspecialchars("{$facility['houseNumber']}, {$facility['streetName']}, {$facility['town']}, {$facility['county']}"); ?></td>
                            <td>
                                <a href="edit_facility.php?id=<?php echo $facility['id']; ?>" class="btn btn-warning">Edit</a>
                                <a href="admin_edit_facility.php?deleteFacility=<?php echo $facility['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require('views/template/footer.phtml'); ?>
