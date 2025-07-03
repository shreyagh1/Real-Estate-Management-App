<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle property deletion
if (isset($_POST['delete_property'])) {
    $property_id = $_POST['property_id'];
    $query = "DELETE FROM Properties WHERE PropertyID = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$property_id]);
}

// Get all properties with seller information
$query = "SELECT p.*, s.Name as SellerName 
          FROM Properties p 
          LEFT JOIN Sellers s ON p.SellerID = s.SellerID 
          ORDER BY p.PropertyID DESC";
$stmt = $db->query($query);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all sellers for the dropdown
$sellers_query = "SELECT SellerID, Name FROM Sellers ORDER BY Name";
$sellers_stmt = $db->query($sellers_query);
$sellers = $sellers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - Real Estate Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Properties</h1>
            <nav>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
                <a href="../logout.php" class="btn">Logout</a>
            </nav>
        </header>

        <section class="properties-list">
            <h2>All Properties</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Address</th>
                            <th>City</th>
                            <th>Size</th>
                            <th>Bedrooms</th>
                            <th>Year Built</th>
                            <th>Price</th>
                            <th>Rent</th>
                            <th>Seller</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $property): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($property['PropertyID']); ?></td>
                                <td><?php echo htmlspecialchars($property['Address']); ?></td>
                                <td><?php echo htmlspecialchars($property['City']); ?></td>
                                <td><?php echo htmlspecialchars($property['Size_sqft']); ?> sq ft</td>
                                <td><?php echo htmlspecialchars($property['Bedrooms']); ?></td>
                                <td><?php echo htmlspecialchars($property['YearBuilt']); ?></td>
                                <td><?php echo $property['Price'] ? 'Rs.' . number_format($property['Price'], 2) : 'N/A'; ?></td>
                                <td><?php echo $property['Rent'] ? 'Rs.' . number_format($property['Rent'], 2) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($property['SellerName'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="edit_property.php?id=<?php echo $property['PropertyID']; ?>" class="btn btn-secondary">Edit</a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="property_id" value="<?php echo $property['PropertyID']; ?>">
                                        <button type="submit" name="delete_property" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this property?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html> 