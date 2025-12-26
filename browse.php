<?php
// Database connection
require_once('Models/Database.php');
$db = new Database();
$conn = $db->connect();
// Pagination setup
$recordsPerPage = 10; // Number of records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ?
    (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;
// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
// Modify the query to filter results based on search term
$totalQuery = "SELECT COUNT(*) as total FROM ecoFacilities WHERE title LIKE 
:search OR category LIKE :search OR description LIKE :search";
$totalStmt = $conn->prepare($totalQuery);
$totalStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);

$totalStmt->execute();
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalRecords = $totalResult['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);
// Fetch facilities for the current page with search filter
$query = "SELECT * FROM ecoFacilities WHERE title LIKE :search OR category 
LIKE :search OR description LIKE :search LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
$stmt->bindValue(':limit', $recordsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php require('views/template/header.phtml'); ?>
    <!-- Main Content -->
    <div class="container mt-5">

        <h2 class="text-center mb-4">Browse Facilities</h2>

        <!-- Search Bar -->

        <div class="search-bar mb-4">

            <form method="get" class="form-inline mb-4">

              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group w-100">

                    <div class="input-group shadow-lg" style="max-width: 600px;
margin: 0 auto;">

                        <input type="text" name="search" class="form-control w-
75" placeholder="Search for facilities..." value="<?php echo
                        htmlspecialchars($search); ?>" style="border-radius: 30px 0 0 30px;
padding: 15px; font-size: 16px;">

                        <button type="submit" class="btn btn-primary"
                                style="border-radius: 0 30px 30px 0; padding: 15px 20px; font-size: 16px;">

                            <i class="fas fa-search"></i> Search

                        </button>

                    </div>

                </div>

            </form>

        </div>

        <?php if (count($facilities) > 0): ?>

            <!-- Table Container with responsive class -->

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead>

                    <tr>

                        <th>#</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Address</th>
                        <th>Coordinates</th>
                        <th>Contributor</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($facilities as $index => $facility): ?>

                        <tr>

                            <td><?php echo htmlspecialchars($facility['id']);
                                ?></td>


                            <td><?php echo
                                htmlspecialchars($facility['title']); ?></td>

                            <td><?php echo
                                htmlspecialchars($facility['category']); ?></td>

                            <td><?php echo
                                htmlspecialchars($facility['description']); ?></td>

                            <td>

                                <?php echo
                                    htmlspecialchars($facility['houseNumber']) . ' ' .
                                    htmlspecialchars($facility['streetName']); ?>,

                                <?php echo htmlspecialchars($facility['town'])
                                    . ', ' . htmlspecialchars($facility['county']); ?>

                            </td>
                            <td>

                                Latitude: <?php echo
                                htmlspecialchars($facility['lat']); ?>,

                                Longitude: <?php echo
                                htmlspecialchars($facility['lng']); ?>

                            </td>
                            <td><?php echo
                                htmlspecialchars($facility['contributor']); ?></td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <!-- Pagination -->

            <nav aria-label="Page navigation">

                <ul class="pagination justify-content-center">

                    <?php

                    // Number of page links to show at a time

                    $visiblePages = 5;

                    $startPage = max(1, $page - floor($visiblePages / 2));

                    $endPage = min($totalPages, $startPage + $visiblePages -
                        1);

                    // Adjust startPage if we're at the end

                    $startPage = max(1, $endPage - $visiblePages + 1);

                    ?>

                    <!-- First Page -->

                    <?php if ($page > 1): ?>

                        <li class="page-item">

                            <a class="page-link" href="?page=1&search=<?php
                            echo urlencode($search); ?>">First</a>

                        </li>

                    <?php endif; ?>

                    <!-- Previous Button -->

                    <?php if ($page > 1): ?>

                        <li class="page-item">

                            <a class="page-link" href="?page=<?php echo $page -
                                1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>

                        </li>

                    <?php endif; ?>

                    <!-- Page Number Links -->

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>

                        <li class="page-item <?php echo ($i == $page) ?
                            'active' : ''; ?>">


                            <a class="page-link" href="?page=<?php echo $i;
                            ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>

                        </li>

                    <?php endfor; ?>

                    <!-- Next Button -->

                    <?php if ($page < $totalPages): ?>

                        <li class="page-item">

                            <a class="page-link" href="?page=<?php echo $page +
                                1; ?>&search=<?php echo urlencode($search); ?>">Next</a>

                        </li>

                    <?php endif; ?>

                    <!-- Last Page -->

                    <?php if ($page < $totalPages): ?>

                        <li class="page-item">

                            <a class="page-link" href="?page=<?php echo
                            $totalPages; ?>&search=<?php echo urlencode($search); ?>">Last</a>

                        </li>

                    <?php endif; ?>

                </ul>

            </nav>

        <?php else: ?>

            <p class="text-center">No facilities found. Try searching for
                something else!</p>

        <?php endif; ?>
    </div>
<?php echo "Total Records: $totalRecords <br>"; ?>
<?php echo "Total Pages: $totalPages <br>"; ?>
<?php require('views/template/footer.phtml'); ?>