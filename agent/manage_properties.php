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

// Get agent's properties
$query = "SELECT DISTINCT p.*, s.Name as SellerName 
          FROM Properties p 
          LEFT JOIN Sellers s ON p.SellerID = s.SellerID 
          LEFT JOIN Transactions t ON p.PropertyID = t.PropertyID 
          WHERE t.AgentID = :agent_id";
$stmt = $db->prepare($query);
$stmt->execute([':agent_id' => $_SESSION['user_id']]);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all sellers for the dropdown
$query = "SELECT * FROM Sellers";
$stmt = $db->prepare($query);
$stmt->execute();
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Properties - Real Estate Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Page Content -->
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <div class="container-fluid">
                    <a class="navbar-brand fw-bold" href="#">Agent Portal</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="manage_properties.php"><i class="bi bi-houses"></i> Manage Properties</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="view_transactions.php"><i class="bi bi-receipt"></i> View Transactions</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Add Property Form -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Add New Property</h5>
                                <form action="add_property.php" method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <input type="text" class="form-control" id="address" name="address" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="city" class="form-label">City</label>
                                            <input type="text" class="form-control" id="city" name="city" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="size" class="form-label">Size (sq ft)</label>
                                            <input type="number" class="form-control" id="size" name="size" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="bedrooms" class="form-label">Bedrooms</label>
                                            <input type="number" class="form-control" id="bedrooms" name="bedrooms" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="year_built" class="form-label">Year Built</label>
                                            <input type="number" class="form-control" id="year_built" name="year_built" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="price" class="form-label">Price (Rs.)</label>
                                            <input type="number" class="form-control" id="price" name="price" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="rent" class="form-label">Rent (Rs.)</label>
                                            <input type="number" class="form-control" id="rent" name="rent" required>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label for="seller_id" class="form-label">Seller</label>
                                            <select class="form-select" id="seller_id" name="seller_id" required>
                                                <option value="">Select Seller</option>
                                                <?php foreach ($sellers as $seller): ?>
                                                    <option value="<?php echo $seller['SellerID']; ?>">
                                                        <?php echo htmlspecialchars($seller['Name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Add Property</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Properties Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Your Properties</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Address</th>
                                                <th>City</th>
                                                <th>Size</th>
                                                <th>Bedrooms</th>
                                                <th>Price</th>
                                                <th>Rent</th>
                                                <th>Seller</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($properties as $property): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($property['Address']); ?></td>
                                                <td><?php echo htmlspecialchars($property['City']); ?></td>
                                                <td><?php echo htmlspecialchars($property['Size_sqft']); ?> sq ft</td>
                                                <td><?php echo htmlspecialchars($property['Bedrooms']); ?></td>
                                                <td>Rs. <?php echo number_format($property['Price'], 2); ?></td>
                                                <td>Rs. <?php echo number_format($property['Rent'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($property['SellerName']); ?></td>
                                                <td>
                                                    <a href="edit_property.php?id=<?php echo htmlspecialchars($property['PropertyID']); ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form action="delete_property.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property['PropertyID']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this property?')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    .wrapper {
        display: block;
    }
    #content {
        margin: 0;
        width: 100%;
    }
    </style>
</body>
</html> 