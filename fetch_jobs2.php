<?php
require_once("config/session.php");
require_once("config/helper.php");
require_once("config/database.php");
require_once("config/constant.php");
confirm_logged_in();

// Enable error logging (development only)
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
ini_set('display_errors', 1); // Remove in production
error_reporting(E_ALL);

// Check database connection
if (!$conn) {
    $error_message = "Database connection failed: " . mysqli_connect_error();
    error_log($error_message);
    $response = [
        "draw" => 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $error_message
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Get DataTables parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search_value = isset($_POST['search_value']) ? $conn->real_escape_string($_POST['search_value']) : '';
$status_filter = isset($_POST['status_filter']) ? $conn->real_escape_string($_POST['status_filter']) : 'all_jobs';
$starred_filter = isset($_POST['starred_filter']) ? intval($_POST['starred_filter']) : 0;

// Build the WHERE clause for job_codes
$where = [];
if ($status_filter === 'closed') {
    $where[] = "status = 'Closed'";
} else {
    $where[] = "status != 'Closed'";
}
if ($starred_filter == 1) {
    $where[] = "starred = 1";
}
if (!empty($search_value)) {
    $where[] = "(job_code LIKE '%$search_value%' OR job_title LIKE '%$search_value%')";
}
$where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total records (for pagination)
$total_query = "SELECT COUNT(*) AS total FROM job_codes $where_clause";
$total_result = $conn->query($total_query);
if (!$total_result) {
    $error_message = "Database error (total query): " . $conn->error;
    error_log($error_message);
    $response = [
        "draw" => $draw,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $error_message
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
$total_records = $total_result->fetch_assoc()['total'];

// Get filtered records
$order_column = isset($_POST['order'][0]['column']) ? $_POST['order'][0]['column'] : 1;
$order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'asc';
$orderable_columns = [
    1 => 'job_code'
];
$order_by = isset($orderable_columns[$order_column]) ? $orderable_columns[$order_column] : 'job_code';
$order_clause = "ORDER BY $order_by $order_dir";

$query = "
    SELECT job_code, job_title
    FROM job_codes
    $where_clause
    $order_clause
    LIMIT $start, $length
";
$result = $conn->query($query);
if (!$result) {
    $error_message = "Database error (main query): " . $conn->error;
    error_log($error_message);
    $response = [
        "draw" => $draw,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $error_message
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Count "New Candidates" (status = 'Active')
        $new_count_query = "SELECT COUNT(*) AS total FROM bot WHERE jobcode = '{$row['job_code']}' AND status = 'Active'";
        $new_count_result = $conn->query($new_count_query);
        $new_count = $new_count_result ? $new_count_result->fetch_assoc()['total'] : 0;

        // Count "Reviewed" candidates (status = 'Reviewed')
        $reviewed_count_query = "SELECT COUNT(*) AS total FROM bot WHERE jobcode = '{$row['job_code']}' AND status = 'Reviewed'";
        $reviewed_count_result = $conn->query($reviewed_count_query);
        $reviewed_count = $reviewed_count_result ? $reviewed_count_result->fetch_assoc()['total'] : 0;

        $data[] = [
            'id' => $row['job_code'], // Using job_code as a unique identifier
            'job_code' => $row['job_code'],
            'job_title' => $row['job_title'],
            'candidates_all' => $new_count, // New Candidates
            'candidates_new' => $reviewed_count // Reviewed Candidates
        ];
    }
}

// Prepare response for DataTables
$response = [
    "draw" => $draw,
    "recordsTotal" => $total_records,
    "recordsFiltered" => $total_records,
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>