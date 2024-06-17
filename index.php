<?php 
// Database connection
$servername = "localhost";
$username = "root";
$password = "BaNaNa@4";
$database = "upwork";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->query("CREATE TABLE IF NOT EXISTS ftd (
        fid VARCHAR(255),
        email VARCHAR(255),
        email_password VARCHAR(255),
        extension INT,
        phone_number VARCHAR(20),
        whatsApp ENUM('Yes', 'No'),
        viber ENUM('Yes', 'No'),
        messanger ENUM('Yes', 'No'),
        dob VARCHAR(255),
        `address` VARCHAR(255),
        country VARCHAR(255),
        date_created VARCHAR(255),
        front_id VARCHAR(255),
        back_id VARCHAR(255),
        selfie_front VARCHAR(255),
        selfie_back VARCHAR(255),
        remark TEXT,
        profile_picture VARCHAR(255),
        our_network VARCHAR(255),
        client_network VARCHAR(255),
        `broker` VARCHAR(255)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS filler (
        first_name VARCHAR(255),
        last_name VARCHAR(255),
        email VARCHAR(255),
        phone_number VARCHAR(20),
        country VARCHAR(255),
        date_created VARCHAR(255),
        our_network VARCHAR(255),
        client_network VARCHAR(255),
        `broker` VARCHAR(255)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS traffic (
        first_name VARCHAR(255),
        last_name VARCHAR(255),
        email VARCHAR(255),
        phone_number VARCHAR(20),
        country VARCHAR(255),
        date_created VARCHAR(255),
        our_network VARCHAR(255),
        client_network VARCHAR(255),
        `broker` VARCHAR(255)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        order_date VARCHAR(255),
        table_origin VARCHAR(255),
        first_name VARCHAR(255),
        last_name VARCHAR(255),
        email VARCHAR(255),
        phone_number VARCHAR(20),
        country VARCHAR(255),
        for_day VARCHAR(255),
        work_hours VARCHAR(255),
        our_network_today VARCHAR(255),
        client_network_today VARCHAR(255),
        broker_today VARCHAR(255)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255),
        `login` VARCHAR(255),
        `password` VARCHAR(255),
        `role` ENUM('super', 'order', 'filler', 'ftd')
    )");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$roles = ["super", "order", "filler", "ftd"];

function canViewTable($role, $table): bool {
    switch ($role) {
        case "super":
            return true;
        case "order":
            if ($table === "orders" || $table === "ftd" || $table === "traffic" || $table === "filler") {
                return true;
            } else return false;
        case "filler":
            if ($table === "filler" || $table === "traffic") {
                return true;
            } else return false;
        case "ftd":
            if ($table === "ftd") {
                return true;
            } else return false; 
        default:
            return false;         
    }
    return false;
}

// Check if user is logged in and has a valid role
session_start();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ["super", "order", "filler", "ftd"])) {
    header("Location: login.html");
    exit;
}

$role = $_SESSION['role'];

$tables = ["ftd", "filler", "traffic", "orders"];
// Check if the requested table is valid and viewable by the user's role
if (isset($_GET['table'])) {
    $table = $_GET['table'];
    if (!canViewTable($role, $table)) {
        die("You do not have permission to view this table.");
    }    
} else {
    $letsGo = false;
    foreach ($tables as $t) {
        if (canViewTable($role, $t)) {
            $table = $t;
            $letsGo = true;
        }
    }
    if (!$letsGo) {
        die("You cannot view any table");
    }
}

// Display table switching dropdown
echo "<form method='get'>";
echo "Switch Table: <select name='table' onchange='this.form.submit()'>";
foreach ($tables as $tbl) {
    $selected = $tbl == $table ? "selected" : "";
    echo "<option value='$tbl' $selected>$tbl</option>";
}
echo "</select>";
echo "</form>";


if (isset($_GET['action']) && $_GET['action'] == 'add') {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $columns = array_keys($_POST);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        $sql = "INSERT INTO `$table` (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->execute($_POST);
        header("Location: ?table=$table");
        exit;
    } else {
        echo "<form method='post'>";
        foreach ($conn->query("DESCRIBE `$table`") as $column) {
            echo $column['Field'] . ": <input type='text' name='" . $column['Field'] . "'><br>";
        }
        echo "<input type='submit' value='Add'>";
        echo "</form>";
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $columns = array_keys($_POST);
        $assignments = array_map(fn($col) => "$col = :$col", $columns);
        $sql = "UPDATE `$table` SET " . implode(", ", $assignments) . " WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $_POST['id'] = $_GET['id'];
        $stmt->execute($_POST);
        header("Location: ?table=$table");
        exit;
    } else {
        $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = :id");
        $stmt->execute(['id' => $_GET['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "<form method='post'>";
        foreach ($row as $col => $val) {
            echo "$col: <input type='text' name='$col' value='$val'><br>";
        }
        echo "<input type='submit' value='Update'>";
        echo "</form>";
    }
}


if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    header("Location: ?table=$table");
    exit;
}


if ($table) {
    $stmt = $conn->query("SELECT * FROM `$table`");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1'>";
    echo "<tr>";
    if (empty($rows)) {
        echo "No rows available!";
    } else {
        foreach (array_keys($rows[0]) as $col) {
            echo "<th>$col</th>";
        }
    }
    echo "<th>Actions</th>";
    echo "</tr>";

    foreach ($rows as $row) {
        echo "<tr>";
        foreach ($row as $col => $val) {
            echo "<td>$val</td>";
        }
        echo "<td>";
        echo "<a href='?table=$table&action=edit&id=" . $row['id'] . "'>Edit</a> ";
        echo "<a href='?table=$table&action=delete&id=" . $row['id'] . "'>Delete</a>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<a href='?table=$table&action=add'>Add New Row</a>";
}
?>
