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

// Get filter parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$agent_id = isset($_GET['agent_id']) ? $_GET['agent_id'] : '';

// Build the query based on filters
$query = "SELECT 
            t.TransactionID,
            t.TransactionType,
            t.TransactionDate,
            t.FinalPrice,
            a.Name as AgentName,
            p.Address,
            p.City,
            b.Name as BuyerName
          FROM Transactions t
          LEFT JOIN Agents a ON t.AgentID = a.AgentID
          LEFT JOIN Properties p ON t.PropertyID = p.PropertyID
          LEFT JOIN Buyers b ON t.BuyerID = b.BuyerID
          WHERE 1=1";

$params = [];

if ($type !== 'all') {
    $query .= " AND t.TransactionType = ?";
    $params[] = $type;
}

if ($start_date) {
    $query .= " AND t.TransactionDate >= ?";
    $params[] = $start_date;
}

if ($end_date) {
    $query .= " AND t.TransactionDate <= ?";
    $params[] = $end_date;
}

if ($agent_id) {
    $query .= " AND t.AgentID = ?";
    $params[] = $agent_id;
}

$query .= " ORDER BY t.TransactionDate DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all agents for the filter dropdown
$agents_query = "SELECT AgentID, Name FROM Agents ORDER BY Name";
$agents_stmt = $db->query($agents_query);
$agents = $agents_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$summary = [
    'total_sales' => 0,
    'total_rentals' => 0,
    'total_sales_amount' => 0,
    'total_rental_amount' => 0
];

foreach ($transactions as $transaction) {
    if ($transaction['TransactionType'] === 'Sale') {
        $summary['total_sales']++;
        $summary['total_sales_amount'] += $transaction['FinalPrice'];
    } else {
        $summary['total_rentals']++;
        $summary['total_rental_amount'] += $transaction['FinalPrice'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Transactions - Real Estate Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>View Transactions</h1>
            <nav>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
                <a href="../logout.php" class="btn">Logout</a>
            </nav>
        </header>

        <section class="transaction-filters">
            <h2>Filter Transactions</h2>
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="type">Transaction Type</label>
                    <select name="type" id="type">
                        <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All</option>
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
                <div class="form-group">
                    <label for="agent_id">Agent</label>
                    <select name="agent_id" id="agent_id">
                        <option value="">All Agents</option>
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent['AgentID']; ?>" <?php echo $agent_id == $agent['AgentID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agent['Name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </form>
        </section>

        <section class="transaction-summary">
            <h2>Summary</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Sales</h3>
                    <p><?php echo $summary['total_sales']; ?></p>
                    <p class="sub-text">Amount: Rs.<?php echo number_format($summary['total_sales_amount'], 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Rentals</h3>
                    <p><?php echo $summary['total_rentals']; ?></p>
                    <p class="sub-text">Amount: Rs.<?php echo number_format($summary['total_rental_amount'], 2); ?></p>
                </div>
            </div>
        </section>

        <section class="transactions-list">
            <h2>Transaction Details</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Property Address</th>
                            <th>City</th>
                            <th>Agent</th>
                            <th>Buyer</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['TransactionID']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['TransactionDate']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['TransactionType']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['Address']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['City']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['AgentName']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['BuyerName']); ?></td>
                                <td>Rs.<?php echo number_format($transaction['FinalPrice'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html> 