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

// Get filter parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build the query
$query = "SELECT t.*, p.Address, b.Name as BuyerName 
          FROM Transactions t 
          LEFT JOIN Properties p ON t.PropertyID = p.PropertyID 
          LEFT JOIN Buyers b ON t.BuyerID = b.BuyerID 
          WHERE t.AgentID = :agent_id";

$params = [':agent_id' => $_SESSION['user_id']];

if ($type) {
    $query .= " AND t.TransactionType = :type";
    $params[':type'] = $type;
}

if ($start_date) {
    $query .= " AND t.TransactionDate >= :start_date";
    $params[':start_date'] = $start_date;
}

if ($end_date) {
    $query .= " AND t.TransactionDate <= :end_date";
    $params[':end_date'] = $end_date;
}

$query .= " ORDER BY t.TransactionDate DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$stats = [
    'total_sales' => 0,
    'total_rentals' => 0,
    'total_amount' => 0
];

foreach ($transactions as $transaction) {
    if ($transaction['TransactionType'] === 'Sale') {
        $stats['total_sales']++;
    } else {
        $stats['total_rentals']++;
    }
    $stats['total_amount'] += $transaction['FinalPrice'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions - Real Estate Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <div class="container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h1>View Transactions</h1>
                <nav>
                    <a href="dashboard.php" class="btn">Back to Dashboard</a>
                    <a href="../logout.php" class="btn">Logout</a>
                </nav>
            </div>

            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="bi bi-handshake"></i>
                    <h3>Total Sales</h3>
                    <p><?php echo $stats['total_sales']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="bi bi-key"></i>
                    <h3>Total Rentals</h3>
                    <p><?php echo $stats['total_rentals']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="bi bi-currency-dollar"></i>
                    <h3>Total Amount</h3>
                    <p>Rs.<?php echo number_format($stats['total_amount'], 2); ?></p>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Filter Transactions</h2>
                </div>
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="type">Transaction Type</label>
                        <select name="type" id="type">
                            <option value="">All Types</option>
                            <option value="Sale" <?php echo $type === 'Sale' ? 'selected' : ''; ?>>Sales</option>
                            <option value="Rent" <?php echo $type === 'Rent' ? 'selected' : ''; ?>>Rentals</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                    </div>

                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="view_transactions.php" class="btn">Clear Filters</a>
                </form>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Transaction History</h2>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Property</th>
                                <th>Buyer</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['TransactionDate']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $transaction['TransactionType'] === 'Sale' ? 'status-sold' : 'status-rented'; ?>">
                                            <?php echo htmlspecialchars($transaction['TransactionType']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['Address']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['BuyerName']); ?></td>
                                    <td>Rs.<?php echo number_format($transaction['FinalPrice'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 