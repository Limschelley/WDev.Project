<?php
// Start the session.
session_start();
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

if ($_POST) {
    try {
        include('database/connection.php');

        $username = $_POST['username'];
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } else {
            $stmt = $conn->prepare('SELECT * FROM users WHERE email = :email');
            $stmt->bindParam(':email', $username);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);

            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $user['permissions'] = explode(',', $user['permissions']);
                $_SESSION['user'] = $user;
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Please make sure that username and password are correct.';
            }
        }
    } catch (PDOException $e) {
        $error_message = 'Database error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>IMS Login - Inventory Management System</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
</head>
<body id="loginBody">
    <?php if (!empty($error_message)) { ?>
        <div id="errorMessage">
            <strong>ERROR:</strong> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php } ?>
    <div class="container">
        <div class="loginHeader">
            <h1>IMS</h1>
            <p>Inventory Management System</p>
        </div>
        <div class="loginBody">
            <form action="login.php" method="POST">
                <div class="loginInputsContainer">
                    <label for="">Username</label>
                    <input placeholder="username" name="username" type="text" />
                </div>
                <div class="loginInputsContainer">
                    <label for="">Password</label>
                    <input placeholder="password" name="password" type="password" />
                </div>
                <div class="loginButtonContainer">
                    <button>login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
