<?php
session_start();
require_once '../config/database.php';

// Only allow POST requests from logged in agents
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Agent' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_properties.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Collect and sanitize form data
$address = trim($_POST['address'] ?? '');
$city = trim($_POST['city'] ?? '');
$size = trim($_POST['size'] ?? '');
$bedrooms = trim($_POST['bedrooms'] ?? '');
$year_built = trim($_POST['year_built'] ?? '');
$price = trim($_POST['price'] ?? '');
$rent = trim($_POST['rent'] ?? '');
$seller_id = trim($_POST['seller_id'] ?? '');
$agent_id = $_SESSION['user_id'];

// Basic validation
if ($address && $city && $size && $bedrooms && $year_built && $price && $rent && $seller_id) {
    try {
        $query = "INSERT INTO Properties (Address, City, Size_sqft, Bedrooms, YearBuilt, Price, Rent, SellerID) VALUES (:address, :city, :size, :bedrooms, :year_built, :price, :rent, :seller_id)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':address' => $address,
            ':city' => $city,
            ':size' => $size,
            ':bedrooms' => $bedrooms,
            ':year_built' => $year_built,
            ':price' => $price,
            ':rent' => $rent,
            ':seller_id' => $seller_id
        ]);
        header('Location: manage_properties.php?success=1');
        exit();
    } catch (PDOException $e) {
        // Show error for debugging
        echo "<h2>Database Error:</h2><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        exit();
    }
} else {
    echo "<h2>Validation Error:</h2><pre>All fields are required.</pre>";
    exit();
} 