<?php
require_once 'db_connect.php';

/**
 * DASHBOARD FUNCTIONS
 */

function getTodayCollection($conn, $date) {
    try {
        $stmt = $conn->prepare("SELECT SUM(quantity) FROM collections WHERE collection_date = ?");
        $stmt->execute([$date]);
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getTodayCollection: " . $e->getMessage());
        return 0;
    }
}

function getWeeklyCollection($conn) {
    try {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        $stmt = $conn->prepare("SELECT SUM(quantity) FROM collections WHERE collection_date BETWEEN ? AND ?");
        $stmt->execute([$startOfWeek, $endOfWeek]);
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getWeeklyCollection: " . $e->getMessage());
        return 0;
    }
}

function getActiveFarmersCount($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM farmers WHERE status = 'active'");
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error in getActiveFarmersCount: " . $e->getMessage());
        return 0;
    }
}

function getPendingPayments($conn) {
    try {
        $stmt = $conn->prepare("SELECT SUM(amount) FROM payments WHERE status = 'pending'");
        $stmt->execute();
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getPendingPayments: " . $e->getMessage());
        return 0;
    }
}

function getMonthlySales($conn) {
    try {
        $currentMonth = date('Y-m');
        $stmt = $conn->prepare("SELECT SUM(total_amount) FROM collections WHERE DATE_FORMAT(collection_date, '%Y-%m') = ?");
        $stmt->execute([$currentMonth]);
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getMonthlySales: " . $e->getMessage());
        return 0;
    }
}

function getTopFarmers($conn, $limit = 5) {
    try {
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 5;
        
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        
        $sql = "SELECT f.farmer_id, f.name, f.location, 
                SUM(c.quantity) as total_quantity,
                (
                    SELECT quality FROM collections 
                    WHERE farmer_id = f.farmer_id 
                    ORDER BY collection_date DESC 
                    LIMIT 1
                ) as avg_quality
                FROM farmers f
                JOIN collections c ON f.farmer_id = c.farmer_id
                WHERE c.collection_date BETWEEN ? AND ?
                GROUP BY f.farmer_id
                ORDER BY total_quantity DESC
                LIMIT " . $limit;
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$startOfWeek, $endOfWeek]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getTopFarmers: " . $e->getMessage());
        return [];
    }
}

function getRecentCollections($conn, $limit = 5) {
    try {
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 5;
        
        $stmt = $conn->prepare("
            SELECT c.*, f.name as farmer_name, 
                   DATE_FORMAT(c.collection_date, '%Y-%m-%d') as collection_date,
                   DATE_FORMAT(c.recorded_at, '%H:%i') as collection_time
            FROM collections c
            JOIN farmers f ON c.farmer_id = f.farmer_id
            ORDER BY c.collection_date DESC, c.recorded_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getRecentCollections: " . $e->getMessage());
        return [];
    }
}

/**
 * FARMER FUNCTIONS
 */

function getAllFarmers($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM farmers ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllFarmers: " . $e->getMessage());
        return [];
    }
}

function getAllFarmersWithTodayData($conn) {
    try {
        $today = date('Y-m-d');
        
        $stmt = $conn->prepare("
            SELECT f.*, 
                   COALESCE(c.quantity, 0) as today_quantity,
                   COALESCE(c.total_amount, 0) as today_amount
            FROM farmers f
            LEFT JOIN (
                SELECT farmer_id, SUM(quantity) as quantity, SUM(total_amount) as total_amount
                FROM collections
                WHERE collection_date = ?
                GROUP BY farmer_id
            ) c ON f.farmer_id = c.farmer_id
            ORDER BY f.name
        ");
        
        $stmt->execute([$today]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllFarmersWithTodayData: " . $e->getMessage());
        return [];
    }
}

function addFarmer($conn, $data) {
    try {
        // Validate required fields
        $required = ['name', 'phone', 'location'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $farmer_id = 'MPF' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("
            INSERT INTO farmers 
            (farmer_id, name, phone, location, cows, status, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $farmer_id,
            $data['name'],
            $data['phone'],
            $data['location'],
            $data['cows'] ?? 1,
            $data['status'] ?? 'active',
            $data['notes'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Error in addFarmer: " . $e->getMessage());
        return false;
    }
}

function getFarmerDetails($conn, $farmer_id) {
    try {
        $stmt = $conn->prepare("
            SELECT f.*,
                   (SELECT SUM(quantity) FROM collections WHERE farmer_id = f.farmer_id) as total_collection,
                   (SELECT SUM(total_amount) FROM collections WHERE farmer_id = f.farmer_id) as total_payment
            FROM farmers f
            WHERE f.farmer_id = ?
        ");
        $stmt->execute([$farmer_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getFarmerDetails: " . $e->getMessage());
        return false;
    }
}

/**
 * COLLECTION FUNCTIONS
 */

function getCollectionsByDate($conn, $date) {
    try {
        $stmt = $conn->prepare("
            SELECT c.*, f.name as farmer_name 
            FROM collections c
            JOIN farmers f ON c.farmer_id = f.farmer_id
            WHERE c.collection_date = ?
            ORDER BY c.recorded_at DESC
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getCollectionsByDate: " . $e->getMessage());
        return [];
    }
}

function deleteCollection($conn, $collection_id) {
    try {
        $stmt = $conn->prepare("DELETE FROM collections WHERE collection_id = ?");
        return $stmt->execute([$collection_id]);
    } catch (PDOException $e) {
        error_log("Error in deleteCollection: " . $e->getMessage());
        return false;
    }
}

function addCollection($conn, $data) {
    try {
        // Validate required fields
        $required = ['farmer_id', 'collection_date', 'collection_time', 'quantity', 'quality'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Calculate price and total amount
        $price_per_liter = getPriceByQuality($data['quality']);
        $total_amount = $data['quantity'] * $price_per_liter;
        
        // Generate collection ID
        $collection_id = 'MPC' . time();
        
        $stmt = $conn->prepare("
            INSERT INTO collections 
            (collection_id, farmer_id, collection_date, collection_time, quantity, quality, fat_content, price_per_liter, total_amount) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $collection_id,
            $data['farmer_id'],
            $data['collection_date'],
            $data['collection_time'],
            $data['quantity'],
            $data['quality'],
            $data['fat_content'] ?? null,
            $price_per_liter,
            $total_amount
        ]);
    } catch (PDOException $e) {
        error_log("Error in addCollection: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Validation error in addCollection: " . $e->getMessage());
        return false;
    }
}

function getPriceByQuality($quality) {
    $quality = strtoupper($quality);
    switch($quality) {
        case 'A': return 50;
        case 'B': return 40;
        case 'C': return 30;
        default: return 30;
    }
}

/**
 * PAYMENT FUNCTIONS
 */

function processPayment($conn, $payment_id) {
    try {
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'paid', processed_at = NOW() 
            WHERE payment_id = ?
        ");
        return $stmt->execute([$payment_id]);
    } catch (PDOException $e) {
        error_log("Error in processPayment: " . $e->getMessage());
        return false;
    }
}

function getPendingPaymentsList($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, f.name as farmer_name 
            FROM payments p
            JOIN farmers f ON p.farmer_id = f.farmer_id
            WHERE p.status = 'pending'
            ORDER BY p.period DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getPendingPaymentsList: " . $e->getMessage());
        return [];
    }
}

function getRecentPayments($conn, $limit = 10) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, f.name as farmer_name 
            FROM payments p
            JOIN farmers f ON p.farmer_id = f.farmer_id
            ORDER BY p.processed_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getRecentPayments: " . $e->getMessage());
        return [];
    }
}

function getMonthlyPaymentsTotal($conn) {
    try {
        $current_month = date('Y-m');
        $stmt = $conn->prepare("
            SELECT SUM(amount) 
            FROM payments 
            WHERE status = 'paid' AND period = ?
        ");
        $stmt->execute([$current_month]);
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getMonthlyPaymentsTotal: " . $e->getMessage());
        return 0;
    }
}

function getMonthlyPaymentsCount($conn) {
    try {
        $current_month = date('Y-m');
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM payments 
            WHERE status = 'paid' AND period = ?
        ");
        $stmt->execute([$current_month]);
        return $stmt->fetchColumn() ?? 0;
    } catch (PDOException $e) {
        error_log("Error in getMonthlyPaymentsCount: " . $e->getMessage());
        return 0;
    }
}

/**
 * REPORT FUNCTIONS
 */

function generateCollectionReport($conn, $start_date, $end_date) {
    try {
        $stmt = $conn->prepare("
            SELECT c.*, f.name as farmer_name, f.location
            FROM collections c
            JOIN farmers f ON c.farmer_id = f.farmer_id
            WHERE c.collection_date BETWEEN ? AND ?
            ORDER BY c.collection_date DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in generateCollectionReport: " . $e->getMessage());
        return [];
    }
}

function generatePaymentReport($conn, $start_date, $end_date) {
    try {
        $stmt = $conn->prepare("
            SELECT p.*, f.name as farmer_name, f.location
            FROM payments p
            JOIN farmers f ON p.farmer_id = f.farmer_id
            WHERE p.processed_at BETWEEN ? AND ?
            ORDER BY p.processed_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in generatePaymentReport: " . $e->getMessage());
        return [];
    }
}

/**
 * UTILITY FUNCTIONS
 */

function getCurrentMonthDates() {
    return [
        'start' => date('Y-m-01'),
        'end' => date('Y-m-t')
    ];
}

function formatCurrency($amount) {
    return 'KES ' . number_format($amount, 2);
}