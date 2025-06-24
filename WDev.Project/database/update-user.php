<?php 
// Include the database connection
include('connection.php');

// Get the POST data
$data = $_POST;

// Validate the input
if (isset($data['userId']) && is_numeric($data['userId']) &&
    isset($data['f_name']) && !empty($data['f_name']) &&
    isset($data['l_name']) && !empty($data['l_name']) &&
    isset($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {

    $userId = (int) $data['userId'];
    $firstName = htmlspecialchars($data['f_name'], ENT_QUOTES, 'UTF-8');
    $lastName = htmlspecialchars($data['l_name'], ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($data['email'], ENT_QUOTES, 'UTF-8');
    $permissions = isset($data['permissions']) ? $data['permissions'] : '';

    try {
        // Prepare the SQL statement for updating user information
        $command = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, permissions = :permissions WHERE id = :user_id";
        $stmt = $conn->prepare($command);
        
        // Bind the parameters
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':permissions', $permissions);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        // Execute the statement
        $stmt->execute();

        // Check if any rows were affected
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'User  information successfully updated.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No changes were made or user not found.'
            ]);
        }
    } catch (PDOException $e) {
        // Log the error message for debugging
        error_log('Update Error: ' . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Error processing request: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input data provided.'
    ]);
}
