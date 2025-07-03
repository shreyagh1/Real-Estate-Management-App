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

// Get statistics
$stats = [
    'total_agents' => 0,
    'total_properties' => 0,
    'total_sales' => 0,
    'total_rentals' => 0
];

// Get total agents
$query = "SELECT COUNT(*) as count FROM Agents";
$stmt = $db->query($query);
$stats['total_agents'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total properties
$query = "SELECT COUNT(*) as count FROM Properties";
$stmt = $db->query($query);
$stats['total_properties'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total sales and rentals
$query = "SELECT TransactionType, COUNT(*) as count FROM Transactions GROUP BY TransactionType";
$stmt = $db->query($query);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($transactions as $transaction) {
    if ($transaction['TransactionType'] === 'Sale') {
        $stats['total_sales'] = $transaction['count'];
    } else {
        $stats['total_rentals'] = $transaction['count'];
    }
}

// Get recent transactions
$query = "SELECT t.*, a.Name as AgentName, p.Address, b.Name as BuyerName 
          FROM Transactions t 
          LEFT JOIN Agents a ON t.AgentID = a.AgentID 
          LEFT JOIN Properties p ON t.PropertyID = p.PropertyID 
          LEFT JOIN Buyers b ON t.BuyerID = b.BuyerID 
          ORDER BY t.TransactionDate DESC LIMIT 5";
$stmt = $db->query($query);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Real Estate Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .welcome-section {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            flex: 1;
            margin-right: 20px;
        }

        .welcome-section h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .welcome-section p {
            margin: 15px 0 0;
            font-size: 1.1em;
            opacity: 0.9;
        }

        .logout-btn {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            color: #f8f9fa;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #3498db;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 1.4em;
            color: #2c3e50;
            font-weight: 600;
        }

        .stat-card p {
            margin: 10px 0 0;
            font-size: 1.8em;
            color: #34495e;
            font-weight: 700;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .quick-action-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-decoration: none;
            color: inherit;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }

        .quick-action-card i {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #3498db;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .quick-action-card h3 {
            margin: 0;
            font-size: 1.3em;
            color: #2c3e50;
            font-weight: 600;
        }

        .quick-action-card p {
            margin: 10px 0 0;
            font-size: 1em;
            color: #7f8c8d;
        }

        .dashboard-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.8em;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-sold {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-rented {
            background-color: #fff3e0;
            color: #f57c00;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }

            .dashboard-header {
                flex-direction: column;
                gap: 20px;
            }

            .welcome-section {
                margin-right: 0;
                text-align: center;
            }

            .dashboard-stats {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1>Welcome, Admin!</h1>
                <p>Here's an overview of the real estate portal</p>
            </div>
            <a href="../logout.php" class="logout-btn">
                <i class="bi bi-sign-turn-right"></i>
                Logout
            </a>
        </div>
        <div class="dashboard">
            <div class="dashboard-stats">
                <div class="stat-card">
                    <i class="bi bi-people"></i>
                    <h3>Total Agents</h3>
                    <p><?php echo $stats['total_agents']; ?></p>
                </div>
                <div class="stat-card">
                    <i class="bi bi-house"></i>
                    <h3>Total Properties</h3>
                    <p><?php echo $stats['total_properties']; ?></p>
                </div>
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
            </div>

            <div class="quick-actions">
                <a href="manage_agents.php" class="quick-action-card">
                    <i class="bi bi-person-gear"></i>
                    <h3>Manage Agents</h3>
                    <p>Add, edit, or remove agents</p>
                </a>
                <a href="manage_properties.php" class="quick-action-card">
                    <i class="bi bi-building"></i>
                    <h3>Manage Properties</h3>
                    <p>View and manage all properties</p>
                </a>
                <a href="view_transactions.php" class="quick-action-card">
                    <i class="bi bi-graph-up"></i>
                    <h3>View Transactions</h3>
                    <p>Monitor all transactions</p>
                </a>
                <a href="reports.php" class="quick-action-card">
                    <i class="bi bi-file-earmark-text"></i>
                    <h3>View Reports</h3>
                    <p>Access detailed reports</p>
                </a>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Transactions</h2>
                    <a href="view_transactions.php" class="btn btn-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Property</th>
                                <th>Agent</th>
                                <th>Buyer</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['TransactionDate']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $transaction['TransactionType'] === 'Sale' ? 'status-sold' : 'status-rented'; ?>">
                                            <?php echo htmlspecialchars($transaction['TransactionType']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['Address']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['AgentName']); ?></td>
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