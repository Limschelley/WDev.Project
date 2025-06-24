<?php 

// Start the session.
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if the required POST variables are set
if (!isset($_POST['product_name'], $_POST['description'], $_POST['pid'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

$product_name = $_POST['product_name'];
$description = $_POST['description'];
$pid = $_POST['pid'];

// Upload or move the file to our directory
$target_dir = "../uploads/products/";
$file_name_value = NULL;

// Check if an image is uploaded
if (isset($_FILES['img']) && $_FILES['img']['tmp_name'] !== '') {	
    $file_data = $_FILES['img'];
    $file_name = $file_data['name'];
    $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
    $file_name = 'product-' . time() . '.' . $file_ext;

    // Check if the uploaded file is an image
    $check = getimagesize($file_data['tmp_name']);
    if ($check) {
        // Move the file
        if (move_uploaded_file($file_data['tmp_name'], $target_dir . $file_name)) {
            // Save the file_name to the database. 
            $file_name_value = $file_name;
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Uploaded file is not an image.']);
        exit;
    }
}

// Update the product record
try {
    include('connection.php');

    // Prepare the SQL statement
    $sql = "UPDATE products 
            SET 
            product_name = ?,  
            description = ?" . ($file_name_value ? ", img = ?" : "") . " 
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    // Execute the statement with the appropriate parameters
    if ($file_name_value) {
        $stmt->execute([$product_name, $description, $file_name_value, $pid]);
    } else {
        $stmt->execute([$product_name, $description, $pid]);
    }

    // Delete the old values from productsuppliers
    $sql = "DELETE FROM productsuppliers WHERE product = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$pid]);

    // Loop through the suppliers and add records
    $suppliers = isset($_POST['suppliers']) ? $_POST['suppliers'] : [];
    foreach ($suppliers as $supplier) {
        $supplier_data = [
            'supplier_id' => $supplier,
            'product_id' => $pid,
            'updated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $sql = "INSERT INTO productsuppliers (supplier, product, updated_at, created_at) 
                VALUES (:supplier_id, :product_id, :updated_at, :created_at)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($supplier_data);
    }

    $response = [
        'success' => true,
        'message' => "<strong>$product_name</strong> successfully updated in the system."
    ];
    
} catch (\Exception $e) {
    $response = [
        'success' => false,
        'message' => "Error processing your request: " . $e->getMessage()
    ];
}

// Return the response as JSON
echo json_encode($response);

