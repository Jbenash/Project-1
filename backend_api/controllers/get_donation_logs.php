<?php
session_start();
$allowedOrigins = ['http://localhost:5173', 'http://localhost:5174'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

class DonationLogsHandler
{
    private $pdo;
    private $hospital_id;

    public function __construct($pdo, $hospital_id)
    {
        $this->pdo = $pdo;
        $this->hospital_id = $hospital_id;
    }

    public function handle()
    {
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }

        // Only allow GET requests
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit();
        }

        try {
            // Prepare SQL statement to get donation logs for this hospital
            $sql = "SELECT d.donation_id, d.donor_id, u.name AS full_name, d.blood_type, d.units_donated, d.donation_date
                    FROM donations d
                    INNER JOIN donors dn ON d.donor_id = dn.donor_id
                    INNER JOIN users u ON dn.user_id = u.user_id
                    WHERE dn.preferred_hospital_id = :hospital_id
                    ORDER BY d.donation_date DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['hospital_id' => $this->hospital_id]);

            $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Return success response with donation data
            echo json_encode([
                'success' => true,
                'donations' => $donations
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }
}

// Database connection
$host = 'localhost';
$dbname = 'liveon_db';
$username = 'root';
$password = '';

// Only allow logged-in MROs
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mro') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $user_id = $_SESSION['user_id'];
    // Get hospital_id for this MRO
    $stmt = $pdo->prepare('SELECT hospital_id FROM mro_officers WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !$row['hospital_id']) {
        echo json_encode(['success' => false, 'error' => 'Hospital not found for this MRO']);
        exit();
    }
    $hospital_id = $row['hospital_id'];
    $handler = new DonationLogsHandler($pdo, $hospital_id);
    $handler->handle();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection error: ' . $e->getMessage()
    ]);
}
