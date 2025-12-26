<?php
session_start();

//  Security: Validate CSRF token sent from JavaScript
if (!isset($_SESSION['csrf_token']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Load required models and database connection
require_once('Models/Database.php');
require_once('Models/Facility.php');

// Tell the browser we are returning JSON data
header('Content-Type: application/json');

// Grab search input and filter values (category and town) from the URL
$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$town = $_GET['town'] ?? '';

// Create a Facility instance and perform filtered search
$facilityObj = new Facility();
$results = $facilityObj->searchFacilitiesWithFilters($search, $category, $town, 0, 10);

// Output the search results as JSON
echo json_encode($results);
