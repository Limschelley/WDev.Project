<?php
// Include the database connection
include('connection.php');

// Get the POST data
$data = $_POST;

// Check if this is a user or product deletion request
if (isset($data['table'])) {
    switch ($data['table']) {
        case 'users':
            handleUserDeletion($data, $conn);
            break;
        case 'products':
            handleProductDeletion($data, $conn);
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid table specified.'
            ]);
            break;
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No table specified.'
    ]);
}

function handleUserDeletion($data, $conn) {
    // Validate the input
    if (isset($data['id']) && is_numeric($data['id'])) {
        $user_id = (int) $data['id'];

        try {
            // Prepare the SQL statement
            $command = "DELETE FROM users WHERE id = :user_id";
            $stmt = $conn->prepare($command);
            
            // Bind the parameter
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            
            // Execute the statement
            $stmt->execute();

            // Check if any rows were affected
            if ($stmt->rowCount() > 0) {
                echo json_encode([ 
                    'success' => true,
                    'message' => 'User successfully deleted.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No user found with the provided ID.'
                ]);
            }
        } catch (PDOException $e) {
            error_log('User Delete Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Error deleting user: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid user ID provided.'
        ]);
    }
}

function handleProductDeletion($data, $conn) {
    // Validate the input
    if (isset($data['id']) && is_numeric($data['id'])) {
        $product_id = (int) $data['id'];

        try {
            // First check if product exists in any orders
            $checkCommand = "SELECT COUNT(*) FROM order_product WHERE product = :product_id";
            $checkStmt = $conn->prepare($checkCommand);
            $checkStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $checkStmt->execute();
            $dependentCount = $checkStmt->fetchColumn();

            if ($dependentCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot delete product - it exists in orders.'
                ]);
                return;
            }

            // Check for dependent records in productsuppliers
            $checkSuppliersCommand = "SELECT COUNT(*) FROM productsuppliers WHERE product = :product_id";
            $checkSuppliersStmt = $conn->prepare($checkSuppliersCommand);
            $checkSuppliersStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $checkSuppliersStmt->execute();
            $supplierCount = $checkSuppliersStmt->fetchColumn();

            // If there are suppliers, delete them first
            if ($supplierCount > 0) {
                $deleteSuppliersCommand = "DELETE FROM productsuppliers WHERE product = :product_id";
                $deleteSuppliersStmt = $conn->prepare($deleteSuppliersCommand);
                $deleteSuppliersStmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
                $deleteSuppliersStmt->execute();
            }

            // Prepare the SQL statement for deleting the product
            $command = "DELETE FROM products WHERE id = :product_id";
            $stmt = $conn->prepare($command);
            
            // Bind the parameter
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            
            // Execute the statement
            $stmt->execute();

            // Check if any rows were affected
            if ($stmt->rowCount() > 0) {
                echo json_encode([ 
                    'success' => true,
                    'message' => 'Product successfully deleted.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No product found with the provided ID.'
                ]);
            }
        } catch (PDOException $e) {
            error_log('Product Delete Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'message' => 'Error deleting product: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product ID provided.'
        ]);
    }
}
