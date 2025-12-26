<?php
session_start();

// Force the response type to JSON
header('Content-Type: application/json');

// Check request method and validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // CSRF token is missing or doesn't match — block the request
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF token validation failed.']);
        exit();
    }
} else {
    //  Only POST requests are allowed for this endpoint
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

// 🔌 Include database connection and Facility class
require_once('Models/Database.php');
require_once('Models/Facility.php');

try {
    //  Make sure both required POST values are present
    if (!isset($_POST['facility_id'], $_POST['comments'])) {
        throw new Exception('Missing facility_id or comments.');
    }

    // Sanitize and assign input values
    $facilityObj = new Facility();
    $facilityId = (int)$_POST['facility_id'];
    $comment = trim($_POST['comments']);

    // List of allowed predefined comments
    $allowedComments = [
        'Bin is full',
        'Not working',
        'Often busy',
        'One charger not working',
        'Always lots available',
        'Great way to get around',
        'Great to charge your phone but bring a cable'
    ];

    // Block unexpected comment input  validation for security
    if (!in_array($comment, $allowedComments)) {
        throw new Exception('Invalid comment selected.');
    }

    // 💾 Try updating the comment for the selected facility
    $success = $facilityObj->updateFacilityComment($facilityId, $comment);

    // If the update fails, report back with a message
    if (!$success) {
        throw new Exception('Failed to update comment (facility not found or no changes made).');
    }

    //  Success response
    echo json_encode([
        'success' => true,
        'message' => 'Comment updated successfully.',
        'data' => [
            'facility_id' => $facilityId,
            'comment' => $comment
        ]
    ]);

} catch (Exception $e) {
    // Return error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
