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

// Handle agent deletion
if (isset($_POST['delete_agent'])) {
    $agent_id = $_POST['agent_id'];
    $query = "DELETE FROM Agents WHERE AgentID = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$agent_id]);
}

// Handle agent addition/update
if (isset($_POST['submit_agent'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    if (isset($_POST['agent_id'])) {
        // Update existing agent
        $query = "UPDATE Agents SET Name = ?, Email = ?, Phone = ? WHERE AgentID = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $email, $phone, $_POST['agent_id']]);
    } else {
        // Add new agent
        $query = "INSERT INTO Agents (Name, Email, Phone) VALUES (?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $email, $phone]);
    }
}

// Get all agents
$query = "SELECT * FROM Agents ORDER BY Name";
$stmt = $db->query($query);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agents - Real Estate Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Manage Agents</h1>
            <nav>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
                <a href="../logout.php" class="btn">Logout</a>
            </nav>
        </header>

        <section class="agent-form">
            <h2>Add/Edit Agent</h2>
            <form method="POST" class="form">
                <input type="hidden" name="agent_id" id="agent_id">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <button type="submit" name="submit_agent" class="btn btn-primary">Save Agent</button>
            </form>
        </section>

        <section class="agents-list">
            <h2>Current Agents</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agents as $agent): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($agent['Name']); ?></td>
                                <td><?php echo htmlspecialchars($agent['Email']); ?></td>
                                <td><?php echo htmlspecialchars($agent['Phone']); ?></td>
                                <td>
                                    <button onclick="editAgent(<?php echo htmlspecialchars(json_encode($agent)); ?>)" class="btn btn-secondary">Edit</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="agent_id" value="<?php echo $agent['AgentID']; ?>">
                                        <button type="submit" name="delete_agent" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this agent?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        function editAgent(agent) {
            document.getElementById('agent_id').value = agent.AgentID;
            document.getElementById('name').value = agent.Name;
            document.getElementById('email').value = agent.Email;
            document.getElementById('phone').value = agent.Phone;
        }
    </script>
</body>
</html> 