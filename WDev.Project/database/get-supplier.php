<?php
session_start();
require_once('../database/connection.php');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Supplier ID not provided']);
    exit;
}

$supplierId = $_GET['id'];

try {
    // Fetch supplier details
    $stmt = $conn->prepare("
        SELECT s.*, 
               CONCAT(u.first_name, ' ', u.last_name) as created_by_name
        FROM suppliers s
        LEFT JOIN users u ON s.created_by = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$supplierId]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplier) {
        echo json_encode(['error' => 'Supplier not found']);
        exit;
    }

    // Fetch products associated with this supplier
    $stmt = $conn->prepare("
        SELECT p.id, p.product_name
        FROM products p
        JOIN productsuppliers ps ON p.id = ps.product
        WHERE ps.supplier = ?
    ");
    $stmt->execute([$supplierId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Extract product IDs
    $productIds = array_column($products, 'id');

    // Prepare response
    $response = [
        'id' => $supplier['id'],
        'supplier_name' => $supplier['supplier_name'],
        'supplier_location' => $supplier['supplier_location'],
        'email' => $supplier['email'],
        'products' => $productIds,
        'created_at' => $supplier['created_at'],
        'updated_at' => $supplier['updated_at'],
        'created_by' => $supplier['created_by_name']
    ];

    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
