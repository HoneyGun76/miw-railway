<?php
require_once 'safe_config.php';

function getPackageSecure($pakId) {
    global $conn;
    
    if (!$conn || empty($pakId)) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM data_paket WHERE pak_id = ?");
        $stmt->execute([$pakId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Package query error: " . $e->getMessage());
        return false;
    }
}

function getAllPackagesSecure() {
    global $conn;
    
    if (!$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM data_paket ORDER BY tanggal_keberangkatan ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Packages query error: " . $e->getMessage());
        return [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_package':
            $pakId = $_POST['pak_id'] ?? '';
            echo json_encode(getPackageSecure($pakId));
            break;
        case 'get_all_packages':
            echo json_encode(getAllPackagesSecure());
            break;
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}
?>