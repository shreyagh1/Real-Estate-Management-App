<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is agent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Agent') {
    header("Location: ../login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get property details
$property_id = isset($_GET['id']) ? $_GET['id'] : null;
if (!$property_id) {
    $_SESSION['error_message'] = "No property ID provided.";
    header("Location: manage_properties.php");
    exit();
}

// Get property details
$query = "SELECT DISTINCT p.*, s.Name as SellerName 
          FROM Properties p 
          LEFT JOIN Sellers s ON p.SellerID = s.SellerID 
          LEFT JOIN Transactions t ON p.PropertyID = t.PropertyID 
          WHERE p.PropertyID = :property_id AND t.AgentID = :agent_id";
$stmt = $db->prepare($query);
$stmt->execute([
    ':property_id' => $property_id,
    ':agent_id' => $_SESSION['user_id']
]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    $_SESSION['error_message'] = "Property not found or you don't have permission to edit this property.";
    header("Location: manage_properties.php");
    exit();
}

// Get all sellers for the dropdown
$query = "SELECT * FROM Sellers";
$stmt = $db->prepare($query);
$stmt->execute();
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        // Update property record
        $query = "UPDATE Properties 
                  SET Address = :address, 
                      City = :city, 
                      Size = :size, 
                      Bedrooms = :bedrooms, 
                      YearBuilt = :year_built, 
                      Price = :price, 
                      Rent = :rent, 
                      SellerID = :seller_id 
                  WHERE PropertyID = :property_id AND AgentID = :agent_id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':address' => $_POST['address'],
            ':city' => $_POST['city'],
            ':size' => $_POST['size'],
            ':bedrooms' => $_POST['bedrooms'],
            ':year_built' => $_POST['year_built'],
            ':price' => $_POST['price'],
            ':rent' => $_POST['rent'],
            ':seller_id' => $_POST['seller_id'],
            ':property_id' => $property_id,
            ':agent_id' => $_SESSION['user_id']
        ]);

        $db->commit();
        $_SESSION['success_message'] = "Property updated successfully!";
        header("Location: manage_properties.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Error updating property: " . $e->getMessage();
        header("Location: manage_properties.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Property - Real Estate Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>Agent Portal</h3>
            </div>

            <ul class="list-unstyled components">
                <li>
                    <a href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="active">
                    <a href="manage_properties.php">
                        <i class="bi bi-house"></i> Manage Properties
                    </a>
                </li>
                <li>
                    <a href="view_transactions.php">
                        <i class="bi bi-cash"></i> View Transactions
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-info">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="ms-auto">
                        <span class="navbar-text">
                            Edit Property
                        </span>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Edit Property</h5>
                                <form action="edit_property.php?id=<?php echo htmlspecialchars($property_id); ?>" method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="address" name="address" 
                                                   value="<?php echo htmlspecialchars($property['Address']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" name="city" 
                                                   value="<?php echo htmlspecialchars($property['City']); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="size" class="form-label">Size (sq ft)</label>
                                            <input type="number" class="form-control" id="size" name="size" 
                                                   value="<?php echo htmlspecialchars($property['Size']); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="bedrooms" class="form-label">Bedrooms</label>
                                            <input type="number" class="form-control" id="bedrooms" name="bedrooms" 
                                                   value="<?php echo htmlspecialchars($property['Bedrooms']); ?>" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="year_built" class="form-label">Year Built</label>
                                            <input type="number" class="form-control" id="year_built" name="year_built" 
                                                   value="<?php echo htmlspecialchars($property['YearBuilt']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="price" class="form-label">Price (Rs.)</label>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   value="<?php echo htmlspecialchars($property['Price']); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="rent" class="form-label">Rent (Rs.)</label>
                                            <input type="number" class="form-control" id="rent" name="rent" 
                                                   value="<?php echo htmlspecialchars($property['Rent']); ?>" required>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label for="seller_id" class="form-label">Seller</label>
                                            <select class="form-select" id="seller_id" name="seller_id" required>
                                                <option value="">Select Seller</option>
                                                <?php foreach ($sellers as $seller): ?>
                                                    <option value="<?php echo $seller['SellerID']; ?>" 
                                                            <?php echo $seller['SellerID'] == $property['SellerID'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($seller['Name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Update Property</button>
                                            <a href="manage_properties.php" class="btn btn-secondary">Cancel</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html> 