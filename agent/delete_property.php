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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['property_id'])) {
    try {
        $db->beginTransaction();

        // First check if the property belongs to the agent through transactions
        $query = "SELECT DISTINCT p.PropertyID 
                  FROM Properties p 
                  LEFT JOIN Transactions t ON p.PropertyID = t.PropertyID 
                  WHERE p.PropertyID = :property_id AND t.AgentID = :agent_id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':property_id' => $_POST['property_id'],
            ':agent_id' => $_SESSION['user_id']
        ]);
        
        if (!$stmt->fetch()) {
            throw new Exception("Property not found or you don't have permission to delete this property.");
        }

        // Delete any associated transactions first
        $query = "DELETE FROM Transactions WHERE PropertyID = :property_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':property_id' => $_POST['property_id']]);

        // Then delete the property
        $query = "DELETE FROM Properties WHERE PropertyID = :property_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':property_id' => $_POST['property_id']]);

        $db->commit();
        $_SESSION['success_message'] = "Property deleted successfully!";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error_message'] = "Error deleting property: " . $e->getMessage();
    }
}

header("Location: manage_properties.php");
exit(); 