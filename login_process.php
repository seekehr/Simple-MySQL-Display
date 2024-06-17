<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "BaNaNa@4";
$database = "upwork";

try {
    session_unset();
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE `name`=:username AND `password`=:password");
        $stmt->execute(['username' => $username, 'password' => $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        } else {
            echo "Login failed. Please check your username and password.\n\n\n";
        }
    }
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
