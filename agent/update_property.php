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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['property_id']) && isset($_POST['status'])) {
    try {
        $db->beginTransaction();

        // Debug information
        error_log("Updating property status: PropertyID=" . $_POST['property_id'] . ", Status=" . $_POST['status']);

        if ($_POST['status'] === 'Available') {
            // Delete any existing transactions for this property
            $query = "DELETE FROM Transactions WHERE PropertyID = :property_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':property_id' => $_POST['property_id']]);
            error_log("Deleted existing transactions for property " . $_POST['property_id']);
        } else {
            // First delete any existing transactions
            $query = "DELETE FROM Transactions WHERE PropertyID = :property_id";
            $stmt = $db->prepare($query);
            $stmt->execute([':property_id' => $_POST['property_id']]);
            error_log("Deleted existing transactions for property " . $_POST['property_id']);

            // Insert buyer record
            $query = "INSERT INTO Buyers (Name, Email, Phone) VALUES (:name, :email, :phone)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':name' => $_POST['buyer_name'],
                ':email' => $_POST['buyer_email'],
                ':phone' => $_POST['buyer_phone']
            ]);
            $buyer_id = $db->lastInsertId();
            error_log("Created new buyer with ID: " . $buyer_id);

            // Insert transaction record
            $query = "INSERT INTO Transactions (PropertyID, BuyerID, AgentID, TransactionType, TransactionDate, FinalPrice) 
                      VALUES (:property_id, :buyer_id, :agent_id, :type, NOW(), :price)";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':property_id' => $_POST['property_id'],
                ':buyer_id' => $buyer_id,
                ':agent_id' => $_SESSION['user_id'],
                ':type' => $_POST['status'] === 'Sold' ? 'Sale' : 'Rental',
                ':price' => $_POST['status'] === 'Sold' ? $_POST['price'] : $_POST['rent']
            ]);
            error_log("Created new transaction for property " . $_POST['property_id']);
        }

        $db->commit();
        $_SESSION['success_message'] = "Property status updated successfully!";
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error updating property status: " . $e->getMessage());
        $_SESSION['error_message'] = "Error updating property status: " . $e->getMessage();
    }
}

// Get property details
$property_id = isset($_GET['id']) ? $_GET['id'] : null;

// If no property ID is provided, get all properties for the agent
if (!$property_id) {
    $query = "SELECT DISTINCT p.*, s.Name as SellerName 
              FROM Properties p 
              LEFT JOIN Sellers s ON p.SellerID = s.SellerID 
              LEFT JOIN Transactions t ON p.PropertyID = t.PropertyID 
              WHERE t.AgentID = :agent_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':agent_id' => $_SESSION['user_id']]);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Get specific property details
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
        $_SESSION['error_message'] = "Property not found or you don't have permission to update this property.";
        header("Location: dashboard.php");
        exit();
    }
}

// Check if property is already sold or rented
if ($property_id) {
    $query = "SELECT TransactionType FROM Transactions WHERE PropertyID = :property_id ORDER BY TransactionDate DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':property_id' => $property_id]);
    $last_transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_status = $last_transaction ? ($last_transaction['TransactionType'] === 'Sale' ? 'Sold' : 'Rented') : 'Available';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Property Status - Real Estate Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h1>Update Property Status</h1>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!$property_id): ?>
                <!-- Property Selection Form -->
                <div class="property-selection">
                    <h2>Select a Property</h2>
                    <form method="GET" class="select-form">
                        <div class="form-group">
                            <label for="property_id">Choose Property:</label>
                            <select name="id" id="property_id" required>
                                <option value="">Select a property...</option>
                                <?php foreach ($properties as $prop): ?>
                                    <option value="<?php echo $prop['PropertyID']; ?>">
                                        <?php echo htmlspecialchars($prop['Address'] . ' - ' . $prop['City']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Continue</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <!-- Property Status Update Form -->
                <div class="property-details">
                    <h2>Property Details</h2>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($property['Address']); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($property['City']); ?></p>
                    <p><strong>Size:</strong> <?php echo htmlspecialchars($property['Size']); ?> sq ft</p>
                    <p><strong>Bedrooms:</strong> <?php echo htmlspecialchars($property['Bedrooms']); ?></p>
                    <p><strong>Price:</strong> Rs.<?php echo number_format($property['Price'], 2); ?></p>
                    <p><strong>Rent:</strong> Rs.<?php echo number_format($property['Rent'], 2); ?></p>
                    <p><strong>Seller:</strong> <?php echo htmlspecialchars($property['SellerName']); ?></p>
                    <p><strong>Current Status:</strong> <span class="status-badge status-<?php echo strtolower($current_status); ?>"><?php echo $current_status; ?></span></p>
                </div>

                <form method="POST" class="update-form">
                    <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                    
                    <div class="form-group">
                        <label>Update Status:</label>
                            <label class="status-option">
                                <input type="radio" name="status" value="Sold" <?php echo $current_status === 'Sold' ? 'checked' : ''; ?>>
                                <span class="status-badge status-sold">Sold</span>
                            </label>
                            <label class="status-option">
                                <input type="radio" name="status" value="Rented" <?php echo $current_status === 'Rented' ? 'checked' : ''; ?>>
                                <span class="status-badge status-rented">Rented</span>
                            </label>
                        </div>
                    </div>

                    <div id="transaction-details" style="display: none;">
                        <div class="form-group">
                            <label for="buyer_name">Buyer Name:</label>
                            <input type="text" name="buyer_name" id="buyer_name" required>
                        </div>

                        <div class="form-group">
                            <label for="buyer_email">Buyer Email:</label>
                            <input type="email" name="buyer_email" id="buyer_email" required>
                        </div>

                        <div class="form-group">
                            <label for="buyer_phone">Buyer Phone:</label>
                            <input type="tel" name="buyer_phone" id="buyer_phone" required>
                        </div>

                        <div class="form-group" id="price-group" style="display: none;">
                            <label for="price">Sale Price:</label>
                            <input type="number" name="price" id="price" value="<?php echo $property['Price']; ?>" min="0" step="0.01">
                        </div>

                        <div class="form-group" id="rent-group" style="display: none;">
                            <label for="rent">Rent Amount:</label>
                            <input type="number" name="rent" id="rent" value="<?php echo $property['Rent']; ?>" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('input[name="status"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const transactionDetails = document.getElementById('transaction-details');
                const priceGroup = document.getElementById('price-group');
                const rentGroup = document.getElementById('rent-group');
                
                if (this.value === 'Available') {
                    transactionDetails.style.display = 'none';
                } else {
                    transactionDetails.style.display = 'block';
                    if (this.value === 'Sold') {
                        priceGroup.style.display = 'block';
                        rentGroup.style.display = 'none';
                    } else if (this.value === 'Rented') {
                        priceGroup.style.display = 'none';
                        rentGroup.style.display = 'block';
                    }
                }
            });
        });
    </script>
</body>
</html> 