<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
require_once "../database/db.php";

// Get export parameters
$type = $_GET['type'] ?? 'summary';
$start_date = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end'] ?? date('Y-m-d');

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="smart_bus_report_' . $type . '_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Function to format data for Excel
function formatForExcel($data) {
    $formatted = [];
    foreach ($data as $row) {
        $formatted_row = [];
        foreach ($row as $value) {
            $formatted_row[] = is_numeric($value) ? $value : '"' . str_replace('"', '""', $value) . '"';
        }
        $formatted[] = implode("\t", $formatted_row);
    }
    return $formatted;
}

// Generate report based on type
switch ($type) {
    case 'summary':
        // Summary Report
        $stmt = $conn->prepare("
            SELECT 
                'Total Events' as metric,
                COUNT(*) as value
            FROM bus_logs 
            WHERE DATE(created_at) BETWEEN ? AND ?
            UNION ALL
            SELECT 
                'Overloading Events',
                COUNT(CASE WHEN status = 'overloading' THEN 1 END)
            FROM bus_logs 
            WHERE DATE(created_at) BETWEEN ? AND ?
            UNION ALL
            SELECT 
                'Average Passengers',
                ROUND(AVG(passenger_count), 1)
            FROM bus_logs 
            WHERE DATE(created_at) BETWEEN ? AND ?
            UNION ALL
            SELECT 
                'Max Passengers',
                MAX(passenger_count)
            FROM bus_logs 
            WHERE DATE(created_at) BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
        $summary_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "SMART BUS SYSTEM - SUMMARY REPORT\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "Date Range: " . $start_date . " to " . $end_date . "\n\n";
        echo "Metric\tValue\n";
        foreach ($summary_data as $row) {
            echo $row['metric'] . "\t" . $row['value'] . "\n";
        }
        break;
        
    case 'detailed':
        // Detailed Logs Report
        $stmt = $conn->prepare("
            SELECT 
                bl.id,
                b.plate_number,
                bl.event,
                bl.passenger_count,
                bl.status,
                bl.created_at,
                bl.comment
            FROM bus_logs bl
            JOIN buses b ON bl.bus_id = b.bus_id
            WHERE DATE(bl.created_at) BETWEEN ? AND ?
            ORDER BY bl.created_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $detailed_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "SMART BUS SYSTEM - DETAILED LOGS REPORT\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "Date Range: " . $start_date . " to " . $end_date . "\n\n";
        echo "ID\tPlate Number\tEvent\tPassenger Count\tStatus\tCreated At\tComment\n";
        foreach ($detailed_data as $row) {
            echo $row['id'] . "\t" . 
                 $row['plate_number'] . "\t" . 
                 $row['event'] . "\t" . 
                 $row['passenger_count'] . "\t" . 
                 $row['status'] . "\t" . 
                 $row['created_at'] . "\t" . 
                 ($row['comment'] ?? '') . "\n";
        }
        break;
        
    case 'performance':
        // Bus Performance Report
        $stmt = $conn->prepare("
            SELECT 
                b.plate_number,
                COUNT(bl.id) as total_events,
                COUNT(CASE WHEN bl.status = 'overloading' THEN 1 END) as overloading_events,
                ROUND(AVG(bl.passenger_count), 1) as avg_passengers,
                MAX(bl.passenger_count) as max_passengers,
                ROUND((COUNT(CASE WHEN bl.status = 'overloading' THEN 1 END) / COUNT(bl.id)) * 100, 1) as overloading_rate
            FROM buses b
            LEFT JOIN bus_logs bl ON b.bus_id = bl.bus_id 
                AND DATE(bl.created_at) BETWEEN ? AND ?
            GROUP BY b.bus_id, b.plate_number
            ORDER BY overloading_events DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $performance_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "SMART BUS SYSTEM - BUS PERFORMANCE REPORT\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "Date Range: " . $start_date . " to " . $end_date . "\n\n";
        echo "Plate Number\tTotal Events\tOverloading Events\tAvg Passengers\tMax Passengers\tOverloading Rate (%)\n";
        foreach ($performance_data as $row) {
            echo $row['plate_number'] . "\t" . 
                 $row['total_events'] . "\t" . 
                 $row['overloading_events'] . "\t" . 
                 $row['avg_passengers'] . "\t" . 
                 $row['max_passengers'] . "\t" . 
                 $row['overloading_rate'] . "\n";
        }
        break;
        
    case 'notifications':
        // Notifications Report
        $stmt = $conn->prepare("
            SELECT 
                n.id,
                n.message,
                n.status,
                n.sent_at,
                n.comment,
                b.plate_number
            FROM notifications n
            LEFT JOIN bus_logs bl ON n.bus_log_id = bl.id
            LEFT JOIN buses b ON bl.bus_id = b.bus_id
            WHERE n.message LIKE '%overloading%'
            AND DATE(n.sent_at) BETWEEN ? AND ?
            ORDER BY n.sent_at DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $notifications_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "SMART BUS SYSTEM - NOTIFICATIONS REPORT\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "Date Range: " . $start_date . " to " . $end_date . "\n\n";
        echo "ID\tMessage\tStatus\tSent At\tComment\tPlate Number\n";
        foreach ($notifications_data as $row) {
            echo $row['id'] . "\t" . 
                 $row['message'] . "\t" . 
                 $row['status'] . "\t" . 
                 $row['sent_at'] . "\t" . 
                 ($row['comment'] ?? '') . "\t" . 
                 ($row['plate_number'] ?? 'N/A') . "\n";
        }
        break;
        
    case 'users':
        // Users Report
        $stmt = $conn->query("
            SELECT 
                username,
                role,
                created_at,
                last_login
            FROM users
            ORDER BY created_at DESC
        ");
        $users_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "SMART BUS SYSTEM - USERS REPORT\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        echo "Username\tRole\tCreated At\tLast Login\n";
        foreach ($users_data as $row) {
            echo $row['username'] . "\t" . 
                 $row['role'] . "\t" . 
                 $row['created_at'] . "\t" . 
                 ($row['last_login'] ?? 'Never') . "\n";
        }
        break;
        
    case 'buses':
        // Buses Report
        $stmt = $conn->query("
            SELECT 
                plate_number,
                capacity,
                created_at
            FROM buses
            ORDER BY created_at DESC
        ");
        $buses_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "SMART BUS SYSTEM - BUSES REPORT\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        echo "Plate Number\tCapacity\tCreated At\n";
        foreach ($buses_data as $row) {
            echo $row['plate_number'] . "\t" . 
                 $row['capacity'] . "\t" . 
                 $row['created_at'] . "\n";
        }
        break;
        
    case 'daily':
        // Daily Statistics Report
        $stmt = $conn->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_events,
                COUNT(CASE WHEN status = 'overloading' THEN 1 END) as overloading_events,
                COUNT(CASE WHEN status = 'full' THEN 1 END) as full_events,
                COUNT(CASE WHEN status = 'normal' THEN 1 END) as normal_events,
                ROUND(AVG(passenger_count), 1) as avg_passengers,
                MAX(passenger_count) as max_passengers
            FROM bus_logs 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "SMART BUS SYSTEM - DAILY STATISTICS REPORT\n";
        echo "Generated on: " . date('Y-m-d H:i:s') . "\n";
        echo "Date Range: " . $start_date . " to " . $end_date . "\n\n";
        echo "Date\tTotal Events\tOverloading Events\tFull Events\tNormal Events\tAvg Passengers\tMax Passengers\n";
        foreach ($daily_data as $row) {
            echo $row['date'] . "\t" . 
                 $row['total_events'] . "\t" . 
                 $row['overloading_events'] . "\t" . 
                 $row['full_events'] . "\t" . 
                 $row['normal_events'] . "\t" . 
                 $row['avg_passengers'] . "\t" . 
                 $row['max_passengers'] . "\n";
        }
        break;
        
    default:
        echo "Invalid report type specified.";
        exit;
}
?> 