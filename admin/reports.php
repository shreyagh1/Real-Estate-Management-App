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

// Get all agents
$query = "SELECT * FROM Agents ORDER BY Name";
$stmt = $db->query($query);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sales report for each agent
$sales_query = "SELECT 
                    a.Name as AgentName,
                    t.TransactionDate,
                    p.Address,
                    p.City,
                    p.Size_sqft,
                    p.Bedrooms,
                    t.FinalPrice,
                    b.Name as BuyerName
                FROM Transactions t
                JOIN Agents a ON t.AgentID = a.AgentID
                JOIN Properties p ON t.PropertyID = p.PropertyID
                JOIN Buyers b ON t.BuyerID = b.BuyerID
                WHERE t.TransactionType = 'Sale'
                ORDER BY t.TransactionDate DESC";
$sales_stmt = $db->query($sales_query);
$sales_report = $sales_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get rental report for each agent
$rental_query = "SELECT 
                    a.Name as AgentName,
                    r.RentStartDate,
                    r.RentEndDate,
                    p.Address,
                    p.City,
                    p.Size_sqft,
                    p.Bedrooms,
                    r.MonthlyRent,
                    b.Name as RenterName
                FROM Rental r
                JOIN Agents a ON r.AgentID = a.AgentID
                JOIN Properties p ON r.PropertyID = p.PropertyID
                JOIN Buyers b ON r.BuyerID = b.BuyerID
                ORDER BY r.RentStartDate DESC";
$rental_stmt = $db->query($rental_query);
$rental_report = $rental_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate summary statistics
$summary = [
    'total_sales' => count($sales_report),
    'total_rentals' => count($rental_report),
    'total_sales_amount' => array_sum(array_column($sales_report, 'FinalPrice')),
    'total_rental_amount' => array_sum(array_column($rental_report, 'MonthlyRent')),
    'agents_summary' => []
];

// Group sales and rentals by agent
foreach ($agents as $agent) {
    $agent_sales = array_filter($sales_report, function($sale) use ($agent) {
        return $sale['AgentName'] === $agent['Name'];
    });
    
    $agent_rentals = array_filter($rental_report, function($rental) use ($agent) {
        return $rental['AgentName'] === $agent['Name'];
    });

    $summary['agents_summary'][$agent['Name']] = [
        'sales_count' => count($agent_sales),
        'rentals_count' => count($agent_rentals),
        'total_sales_amount' => array_sum(array_column($agent_sales, 'FinalPrice')),
        'total_rental_amount' => array_sum(array_column($agent_rentals, 'MonthlyRent'))
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Real Estate Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Reports Dashboard</h1>
            <nav>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
                <a href="../logout.php" class="btn">Logout</a>
            </nav>
        </header>

        <section class="summary-stats">
            <h2>Summary Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Sales</h3>
                    <p><?php echo $summary['total_sales']; ?></p>
                    <p class="sub-text">Total Amount: Rs.<?php echo number_format($summary['total_sales_amount'], 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Rentals</h3>
                    <p><?php echo $summary['total_rentals']; ?></p>
                    <p class="sub-text">Total Monthly Rent: Rs.<?php echo number_format($summary['total_rental_amount'], 2); ?></p>
                </div>
            </div>
        </section>

        <section class="agent-summary">
            <h2>Agent Performance Summary</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Agent Name</th>
                            <th>Total Sales</th>
                            <th>Sales Amount</th>
                            <th>Total Rentals</th>
                            <th>Rental Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($summary['agents_summary'] as $agent_name => $stats): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agent_name); ?></td>
                                <td><?php echo $stats['sales_count']; ?></td>
                                <td>Rs.<?php echo number_format($stats['total_sales_amount'], 2); ?></td>
                                <td><?php echo $stats['rentals_count']; ?></td>
                                <td>Rs.<?php echo number_format($stats['total_rental_amount'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="sales-report">
            <h2>Detailed Sales Report</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Date</th>
                            <th>Property</th>
                            <th>City</th>
                            <th>Size</th>
                            <th>Bedrooms</th>
                            <th>Buyer</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales_report as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['AgentName']); ?></td>
                                <td><?php echo htmlspecialchars($sale['TransactionDate']); ?></td>
                                <td><?php echo htmlspecialchars($sale['Address']); ?></td>
                                <td><?php echo htmlspecialchars($sale['City']); ?></td>
                                <td><?php echo htmlspecialchars($sale['Size_sqft']); ?> sq ft</td>
                                <td><?php echo htmlspecialchars($sale['Bedrooms']); ?></td>
                                <td><?php echo htmlspecialchars($sale['BuyerName']); ?></td>
                                <td>Rs.<?php echo number_format($sale['FinalPrice'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rental-report">
            <h2>Detailed Rental Report</h2>
            
            <!-- Add rental summary by agent -->
            <div class="rental-summary">
                <h3>Rentals by Agent</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Agent Name</th>
                                <th>Number of Rentals</th>
                                <th>Total Monthly Rent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rentals_by_agent = [];
                            foreach ($rental_report as $rental) {
                                if (!isset($rentals_by_agent[$rental['AgentName']])) {
                                    $rentals_by_agent[$rental['AgentName']] = [
                                        'count' => 0,
                                        'total_rent' => 0
                                    ];
                                }
                                $rentals_by_agent[$rental['AgentName']]['count']++;
                                $rentals_by_agent[$rental['AgentName']]['total_rent'] += $rental['MonthlyRent'];
                            }
                            foreach ($rentals_by_agent as $agent_name => $data):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($agent_name); ?></td>
                                    <td><?php echo $data['count']; ?></td>
                                    <td>Rs.<?php echo number_format($data['total_rent'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <h3>Detailed Rental Information</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Property</th>
                            <th>City</th>
                            <th>Size</th>
                            <th>Bedrooms</th>
                            <th>Renter</th>
                            <th>Monthly Rent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rental_report as $rental): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rental['AgentName']); ?></td>
                                <td><?php echo htmlspecialchars($rental['RentStartDate']); ?></td>
                                <td><?php echo htmlspecialchars($rental['RentEndDate']); ?></td>
                                <td><?php echo htmlspecialchars($rental['Address']); ?></td>
                                <td><?php echo htmlspecialchars($rental['City']); ?></td>
                                <td><?php echo htmlspecialchars($rental['Size_sqft']); ?> sq ft</td>
                                <td><?php echo htmlspecialchars($rental['Bedrooms']); ?></td>
                                <td><?php echo htmlspecialchars($rental['RenterName']); ?></td>
                                <td>Rs.<?php echo number_format($rental['MonthlyRent'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html> 