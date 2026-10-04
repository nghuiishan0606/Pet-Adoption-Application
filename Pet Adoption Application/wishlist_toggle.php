<?php
// Asynchronous Wishlist Toggle Endpoint
require_once 'includes/db_connect.php';
require_once 'includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['status' => 'unauthorized', 'message' => 'Please log in first.']);
    exit();
}

$user_id = $_SESSION['user_session']['id'];
$pet_id = filter_input(INPUT_GET, 'pet_id', FILTER_VALIDATE_INT);

if (!$pet_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid pet ID.']);
    exit();
}

try {
    // Check if already wishlisted
    $stmt = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND pet_id = ?");
    $stmt->execute([$user_id, $pet_id]);
    $wishlist_item = $stmt->fetch();
    
    if ($wishlist_item) {
        // Remove it
        $delete_stmt = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND pet_id = ?");
        $delete_stmt->execute([$user_id, $pet_id]);
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        // Add it
        $insert_stmt = $pdo->prepare("INSERT INTO wishlists (user_id, pet_id) VALUES (?, ?)");
        $insert_stmt->execute([$user_id, $pet_id]);
        echo json_encode(['status' => 'success', 'action' => 'added']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
