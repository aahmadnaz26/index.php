<?php
session_start();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database connection and Facility model
require_once('Models/Database.php');
require_once('Models/Facility.php');

// Include the reusable header HTML
require('Views/template/header.phtml');

// Retrieve logged-in user's name, or show "Guest" if not logged in
$userName = isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest';

// Check if a search query was submitted
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';

// Pagination setup
$limit = 10; // How many records to show per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Sorting setup (ASC = A-Z by default)
$sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC';

// Create the Facility object to access methods
$facilityObj = new Facility();

// Get data for category and town dropdown filters
$allCategories = $facilityObj->getAllCategories();
$allTowns = $facilityObj->getAllTowns();

// Fetch facilities based on search OR show all with pagination
if ($search) {
    $facilities = $facilityObj->searchFacilities($search, $start, $limit, $sortOrder);
    $totalRecords = count($facilities);
} else {
    $facilities = $facilityObj->getFacilities($start, $limit, $sortOrder);
    $totalRecords = $facilityObj->getTotalFacilities();
}

// Get all facilities (for displaying pins on the map)
$allFacilities = $facilityObj->getAllFacilities();

// Total number of pages for pagination
$totalPages = ceil($totalRecords / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <!-- Include Leaflet map CSS and JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Bootstrap and Font Awesome -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Page styling -->
    <style>
        #map {
            height: 400px;
            width: 100%;
            margin-bottom: 20px;
        }
        .welcome-message {
            margin-top: 80px;
        }
        .search-bar {
            margin-bottom: 20px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .active-row {
            background-color: #f0f7ff !important;
            box-shadow: inset 3px 0 0 #0066cc;
            transition: all 0.2s ease;
        }
        .active-row td {
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Welcome Message -->
    <div class="welcome-message mb-5 text-center">
        <h1 class="display-4 text-primary font-weight-bold">Welcome, <?php echo $userName; ?>!</h1>
        <p class="text-muted">We're glad to have you on the platform. Explore our facilities with ease.</p>
    </div>

    <!-- Logout Button -->
    <div class="text-center mb-4">
        <a href="logout.php" class="btn btn-danger btn-lg px-5 py-3 font-weight-bold shadow-lg rounded-pill">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- Search Bar -->
    <div class="search-bar mb-4">
        <form method="GET" action="login_dashboard.php" class="d-flex flex-column align-items-center gap-3">
            <!-- Search Box + Clear Button -->
            <div class="d-flex align-items-center gap-2 w-100" style="max-width: 800px;">
                <!-- Search Input -->
                <div class="position-relative flex-grow-1">
                    <input type="text" id="live-search" class="form-control rounded-pill shadow-sm pr-5"
                           placeholder="Search facilities..." autocomplete="off">

                    <!-- AJAX Spinner -->
                    <div id="live-search-spinner" class="real-circle-spinner" style="display: none;"></div>

                    <!-- AJAX Result Suggestions -->
                    <div id="search-results" class="list-group position-absolute w-100 zindex-dropdown"
                         style="top: 100%; z-index: 1050;"></div>
                </div>

                <!-- Clear Button -->
                <button type="button" id="clear-search" class="btn clear-btn rounded-pill shadow-sm px-4">
                    Clear
                </button>
            </div>

            <!-- Filters -->
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-3">
                <select id="filter-category" class="form-select rounded-pill shadow-sm px-3" style="max-width: 250px;">
                    <option value="">All Categories</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['id']); ?>"><?= htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="filter-town" class="form-select rounded-pill shadow-sm px-3" style="max-width: 200px;">
                    <option value="">All Towns</option>
                    <?php foreach ($allTowns as $town): ?>
                        <option value="<?= htmlspecialchars($town); ?>"><?= htmlspecialchars($town); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Show "Edit Facilities" button only to users who are managers -->
    <?php if (isset($_SESSION['userType']) && $_SESSION['userType'] == 1): ?>
        <div class="text-center mb-4">
            <a href="admin_edit_facility.php?page=<?php echo $page; ?>&search=<?php echo $search; ?>&sortOrder=<?php echo $sortOrder; ?>"
               class="btn btn-warning btn-lg px-5 py-3 font-weight-bold shadow-lg rounded-pill">
                <i class="fas fa-edit"></i> Edit Facilities
            </a>
        </div>
    <?php endif; ?>

    <!-- Map Container -->
    <div id="map"></div>

    <!-- Facility Table -->
    <?php if (count($facilities) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped mt-4">
                <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Location</th>
                    <th>Coordinates</th>
                    <th>Contributor</th>
                    <th>Comments</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($facilities as $facility): ?>
                    <tr class="facility-row"
                        data-id="<?= htmlspecialchars($facility['id']); ?>"
                        data-lat="<?= htmlspecialchars($facility['lat']); ?>"
                        data-lng="<?= htmlspecialchars($facility['lng']); ?>">
                        <td><?= htmlspecialchars($facility['id']); ?></td>
                        <td><?= htmlspecialchars($facility['title']); ?></td>
                        <td><?= htmlspecialchars($facility['category']); ?></td>
                        <td><?= htmlspecialchars($facility['description']); ?></td>
                        <td><?= htmlspecialchars($facility['houseNumber']) . ', ' . htmlspecialchars($facility['streetName']) . ', ' . htmlspecialchars($facility['town']) . ', ' . htmlspecialchars($facility['county']); ?></td>
                        <td>Latitude: <?= htmlspecialchars($facility['lat']); ?>, <br>Longitude: <?= htmlspecialchars($facility['lng']); ?></td>
                        <td><?= htmlspecialchars($facility['contributor']); ?></td>
                        <td>
                            <form method="post" action="update_comments.php">
                                <!-- CSRF protection -->
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="facility_id" value="<?= $facility['id']; ?>">

                                <!-- Dropdown of comment options -->
                                <select name="comments" class="form-control">
                                    <option value="">Select a comment</option>
                                    <?php
                                    $statusComments = [
                                        'Bin is full', 'Not working', 'Often busy',
                                        'One charger not working', 'Always lots available',
                                        'Great way to get around', 'Great to charge your phone but bring a cable'
                                    ];
                                    foreach ($statusComments as $comment) {
                                        $selected = ($facility['comments'] == $comment) ? "selected" : "";
                                        echo "<option value='" . htmlspecialchars($comment) . "' $selected>" . htmlspecialchars($comment) . "</option>";
                                    }
                                    ?>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm mt-1">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="login_dashboard.php?page=1&search=<?= $search; ?>&sortOrder=<?= $sortOrder; ?>">&laquo; First</a></li>
                    <li class="page-item"><a class="page-link" href="login_dashboard.php?page=<?= $page - 1; ?>&search=<?= $search; ?>&sortOrder=<?= $sortOrder; ?>">&lsaquo; Prev</a></li>
                <?php endif; ?>

                <?php
                $visiblePages = 5;
                $startPage = max(1, $page - floor($visiblePages / 2));
                $endPage = min($totalPages, $startPage + $visiblePages - 1);
                if ($startPage > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= ($i === $page) ? 'active' : ''; ?>"><a class="page-link" href="login_dashboard.php?page=<?= $i; ?>&search=<?= $search; ?>&sortOrder=<?= $sortOrder; ?>"><?= $i; ?></a></li>
                <?php endfor; ?>
                <?php if ($endPage < $totalPages): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>

                <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="login_dashboard.php?page=<?= $page + 1; ?>&search=<?= $search; ?>&sortOrder=<?= $sortOrder; ?>">Next &rsaquo;</a></li>
                    <li class="page-item"><a class="page-link" href="login_dashboard.php?page=<?= $totalPages; ?>&search=<?= $search; ?>&sortOrder=<?= $sortOrder; ?>">Last &raquo;</a></li>
                <?php endif; ?>
            </ul>
        </nav>

    <?php else: ?>
        <div class="alert alert-warning text-center">No facilities found matching your search criteria.</div>
    <?php endif; ?>
</div>

<!-- Export facilities to JS for map -->
<script>
    var allFacilities = <?php echo json_encode($allFacilities); ?>;
</script>

<!-- Scripts -->
<script src="js/map.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- AJAX Submission for Comments -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form[action="update_comments.php"]').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const button = form.querySelector('button[type="submit"]');
                button.disabled = true;
                button.textContent = 'Updating...';

                const response = await fetch('update_comments.php', {
                    method: 'POST',
                    body: new FormData(form)
                });

                button.disabled = false;
                button.textContent = 'Update';

                if (response.ok) {
                    alert('Comment updated successfully!');
                } else {
                    alert('Failed to update comment.');
                }

                if (typeof renderFacilities === 'function') renderFacilities(allFacilities);
            });
        });
    });

    // Make CSRF token available to JavaScript
    const csrfToken = "<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>";
</script>

<!-- Live search script -->
<script src="js/liveSearch.js"></script>
</body>
</html>
