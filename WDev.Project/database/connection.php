<?php
	$servername = 'localhost';
	$username = 'root';
	$password = '';

	try {
		$conn = new PDO("mysql:host=$servername;dbname=inventory_main", $username, $password);
		// set the PDO error mode to exception.
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (\Exception $e) {
		$error_message = $e->getMessage();
	}

?>
