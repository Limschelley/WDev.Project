<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Set headers for JSON response
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

// Validate and sanitize input data
try {
    // Check required fields
    $supplier_name = isset($_POST['supplier_name']) ? trim($_POST['supplier_name']) : '';
    $supplier_location = isset($_POST['supplier_location']) ? trim($_POST['supplier_location']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $supplier_id = isset($_POST['sid']) ? (int)$_POST['sid'] : 0;

    // Validate input
    if (empty($supplier_name) || empty($supplier_location) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $supplier_id <= 0) {
        throw new Exception('Invalid input data.');
    }

    // Include database connection
    require_once('connection.php');

    // Set PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Start transaction
    $conn->beginTransaction();

    // Update the supplier record
    $sql = "UPDATE suppliers 
            SET supplier_name = :supplier_name, 
                supplier_location = :supplier_location, 
                email = :email 
            WHERE id = :supplier_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':supplier_name' => $supplier_name,
        ':supplier_location' => $supplier_location,
        ':email' => $email,
        ':supplier_id' => $supplier_id
    ]);

    // Delete the old product-supplier associations
    $sql = "DELETE FROM productsuppliers WHERE supplier = :supplier_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':supplier_id' => $supplier_id]);

    // Insert new product-supplier associations
    $products = isset($_POST['products']) ? $_POST['products'] : [];
    foreach ($products as $product) {
        $supplier_data = [
            'supplier_id' => $supplier_id,
            'product_id' => (int)$product,
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $sql = "INSERT INTO productsuppliers (supplier, product, updated_at, created_at) 
                VALUES (:supplier_id, :product_id, :updated_at, :created_at)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($supplier_data);
    }

    // Commit transaction
    $conn->commit();

    // Generate success response
    $response = [
        'success' => true,
        'message' => "<strong>$supplier_name</strong> successfully updated in the system."
    ];

} catch (PDOException $e) {
    $conn->rollBack(); // Rollback transaction on error
    error_log('Database Update Supplier Error: ' . $e->getMessage()); // Log error for debugging
    $response['message'] = "Database error: " . $e->getMessage();
} catch (Exception $e) {
    $conn->rollBack(); // Rollback transaction on error
    $response['message'] = $e->getMessage();
}


echo json_encode($response);
?>
