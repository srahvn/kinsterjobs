<?php
require_once("config/session.php");
require_once("config/helper.php");
require_once("config/database.php");
require_once("config/constant.php");
confirm_logged_in();

// Helper function to safely handle htmlspecialchars with null values
function safe_htmlspecialchars($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Fetch counts for tabs using job_codes table
$all_jobs_count_query = "SELECT COUNT(*) AS total FROM bot";
$all_jobs_result = $conn->query($all_jobs_count_query);
$all_jobs_count = $all_jobs_result->fetch_assoc()['total'];

$closed_count_query = "SELECT COUNT(*) AS total FROM job_codes WHERE status = 'Closed'";
$closed_result = $conn->query($closed_count_query);
$closed_count = $closed_result->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Job Vacancies - <?= PROJECT_MODULE ?></title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="dist/css/AdminLTE.min.css">
  <link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap.min.css">
  <style>
    .job-grid {
      font-family: 'Arial', sans-serif;
    }
    /* Tabs Styles */
    .job-tabs {
      display: flex;
      border-bottom: 2px solid #e0e0e0;
      margin-bottom: 20px;
    }
    .job-tab {
      padding: 10px 20px;
      font-size: 16px;
      font-weight: bold;
      color: #666;
      cursor: pointer;
      position: relative;
    }
    .job-tab.active {
      color: #1a1a1a;
      border-bottom: 2px solid #1a1a1a;
    }
    .job-tab span.badge {
      background-color: #e0e0e0;
      color: #666;
      font-size: 12px;
      margin-left: 5px;
      padding: 2px 6px;
      border-radius: 10px;
    }
    .job-tab.active span.badge {
      background-color: #1a1a1a;
      color: #fff;
    }
    /* Filter and Search */
    .filter-search {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .filter-search .input-group {
      width: 300px;
    }
    .filter-search .form-control {
      border-radius: 20px 0 0 20px;
      border: 1px solid #e0e0e0;
      font-size: 14px;
    }
    .filter-search .input-group-btn .btn {
      border-radius: 0 20px 20px 0;
      background-color: #e0e0e0;
      border: 1px solid #e0e0e0;
      border-left: none;
      color: #666;
    }
    .filter-search .starred-filter {
      font-size: 14px;
      color: #666;
      cursor: pointer;
      padding: 5px 10px;
      border: 1px solid #e0e0e0;
      border-radius: 20px;
      background-color: #fff;
    }
    .filter-search .starred-filter.active {
      background-color: #e0e0e0;
    }
    .filter-search .starred-filter i {
      margin-right: 5px;
    }
    /* Job Table Styles */
    .job-table thead th {
      font-size: 14px;
      font-weight: bold;
      color: #666;
      border-bottom: 2px solid #e0e0e0;
      padding: 10px;
      background-color: #fff;
    }
    .job-table tbody tr {
      background-color: #fff;
      border-bottom: 1px solid #e0e0e0;
      transition: background-color 0.3s;
      cursor: pointer;
    }
    .job-table tbody tr:hover {
      background-color: #f9f9f9;
    }
    .job-table tbody td {
      padding: 15px 10px;
      font-size: 14px;
      vertical-align: middle;
    }
    .job-table .job-title {
      font-weight: bold;
      color: #1a1a1a;
      display: flex;
      align-items: center;
    }
    .job-table .job-title i {
      margin-right: 10px;
      color: #666;
    }
    .job-table .location {
      font-size: 12px;
      color: #666;
    }
    .job-table .count-box {
      display: inline-block;
      margin-right: 20px;
      text-align: center;
    }
    .job-table .count-box i {
      margin-right: 5px;
    }
    .job-table .count-box span {
      font-size: 14px;
      font-weight: bold;
    }
    .job-table .count-box.clickable {
      cursor: pointer;
      color: #007bff;
    }
    .job-table .count-box.clickable:hover {
      text-decoration: underline;
    }
    /* Ensure icons in count-box are black */
    .job-table .count-box i {
      color: #000 !important; /* Black color for icons */
    }
    /* Override the clickable count-box icon color */
    .job-table .count-box.clickable i {
      color: #000 !important; /* Black color for clickable icons */
    }
    /* Ensure the clickable count-box text remains black as well */
    .job-table .count-box.clickable span {
      color: #000 !important; /* Black color for the number next to the icon */
    }
    .job-table .count-label {
      font-size: 10px;
      color: #666;
      display: block;
    }
    .job-table .action-btn {
      float: right;
      font-size: 14px;
      color: #666;
      cursor: pointer;
    }
    .job-table .action-btn i {
      margin-right: 5px;
    }
    /* DataTables Length Menu (Show X entries) */
    .dataTables_length {
      margin-bottom: 20px;
    }
    .dataTables_length label {
      font-size: 14px;
      color: #666;
    }
    .dataTables_length select {
      border: 1px solid #e0e0e0;
      padding: 5px;
      font-size: 14px;
      color: #666;
    }
    .box.box-info {
      border-top-color: #2d333600;
    }
    /* Error message styling */
    .error-message {
      color: red;
      font-size: 14px;
      text-align: center;
      margin-top: 10px;
    }
  </style>

<style>
    #loader {
      border: 16px solid #f3f3f3;
      border-radius: 50%;
      border-top: 16px solid #3498db;
      width: 120px;
      height: 120px;
      -webkit-animation: spin 2s linear infinite;
      animation: spin 2s linear infinite;
      margin-left: 250px;
      margin-top: 250px;
    }   
    @-webkit-keyframes spin {
      0% { -webkit-transform: rotate(0deg); }
      100% { -webkit-transform: rotate(360deg); }
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .dataTables_processing {
      background: transparent !important;
      border: none !important;
      box-shadow: none !important;
      z-index: 9999;
    }
    .custom-spinner {
      text-align: center;
      padding: 20px;
      font-size: 22px;
      color: #007bff;
    }
  </style>
</head>
<body class="skin-black sidebar-mini">
<div class="wrapper">

  <header class="main-header">
    <a href="home.php" class="logo">
      <span class="logo-mini"><b>A</b>DM</span>
      <span class="logo-lg"><b>Kinster</b> <?= PROJECT_MODULE ?></span>
    </a>
    <nav class="navbar navbar-static-top">
      <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">Toggle navigation</span> 
        &nbsp;<b> Welcome , <?php $email=$_SESSION['EMAIL'];  echo get_user_name($conn,$email); ?> </b>
      </a>
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
          <li class="dropdown user user-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <img src="dist/img/user2-160x160.jpg" class="user-image" alt="User Image">
              <span class="hidden-xs"><?php $email=$_SESSION['EMAIL'];  echo get_user_name($conn,$email); ?></span>
            </a>
            <ul class="dropdown-menu">
              <li class="user-header">
                <img src="dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
                <p><?php $email=$_SESSION['EMAIL'];  echo get_user_name($conn,$email); ?></p>
              </li>
              <li class="user-footer">
                <div class="pull-left">
                  <a href="#" class="btn btn-default btn-flat">Profile</a>
                </div>
                <div class="pull-right">
                  <a href="logout.php" class="btn btn-default btn-flat">Sign out</a>
                </div>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </nav>
  </header>
  <?php require_once("aside.php");?>
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-md-12">
          <div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title" style="font-weight: 600;font-size: 21px;">Jobs</h3>
            </div>
            <div class="box-body">
              <!-- Tabs -->
              <div class="job-tabs">
                <div class="job-tab active" data-status="all_jobs">
                  All Jobs <span class="badge"><?php echo $all_jobs_count; ?></span>
                </div>
                <div class="job-tab" data-status="closed">
                  Closed <span class="badge"><?php echo $closed_count; ?></span>
                </div>
              </div>

              <!-- Filter and Search -->
              <div class="filter-search">
                <div class="input-group">
                  <input type="text" class="form-control" id="jobSearch" placeholder="Filter and search jobs">
                  <span class="input-group-btn">
                    <button class="btn btn-default" type="button"><i class="fa fa-chevron-down"></i></button>
                  </span>
                </div>
              </div>

              <!-- Job Table -->
              <div class="table-responsive">
                <table class="table table-hover job-table" id="jobTable">
                  <thead>
                    <tr>
                      <th><input type="checkbox" id="selectAll"></th>
                      <th>Job Title</th>
                      <th>Candidates</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Data will be loaded via AJAX -->
                  </tbody>
                </table>
              </div>
              <div id="error-message" class="error-message"></div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <div class="pull-right hidden-xs">
      <b>Version</b> 1.0
    </div>
    <strong>Copyright © Kinster.</strong> All rights reserved.
  </footer>

</div>

<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="dist/js/app.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap.min.js"></script>
<script>
$(document).ready(function() {
  let currentStatusFilter = 'all_jobs'; // Default to "All Jobs"
  let starredFilter = 0; // Default to show all jobs (not just starred)

  // Initialize DataTable
  var table = $('#jobTable').DataTable({
    "processing": true,
    "serverSide": true,
    "language": {
      "processing": '<div class="custom-spinner"><i style="color:#1a1a1a !important;" class="fa fa-hourglass-half fa-spin"></i></div>'
    },
    "ajax": {
      "url": "fetch_jobs2.php",
      "type": "POST",
      "data": function(d) {
        d.status_filter = currentStatusFilter;
        d.starred_filter = starredFilter;
        d.search_value = $('#jobSearch').val();
      },
      "dataSrc": function(json) {
        if (json.error) {
          console.error('DataTable Error:', json.error);
          $('#error-message').text('Error loading data: ' + json.error);
          return [];
        }
        $('#error-message').text(''); // Clear any previous error message
        return json.data;
      },
      "error": function(xhr, error, thrown) {
        console.error('AJAX Error:', error, thrown);
        $('#error-message').text('Error loading data: ' + (xhr.statusText || 'Unknown error'));
      }
    },
    "columns": [
      {
        "data": null,
        "render": function(data, type, row) {
          return `<input type="checkbox" class="job-checkbox" data-id="${row.id}">`;
        },
        "orderable": false
      },
      {
        "data": null,
        "render": function(data, type, row) {
          return `
            <div class="job-title">
              ${row.job_code} - ${row.job_title}
            </div>
          `;
        }
      },
      {
        "data": null,
        "render": function(data, type, row) {
          // Fallback to 0 if candidates_all or candidates_new is undefined or null
          const candidatesAll = row.candidates_all !== undefined && row.candidates_all !== null ? row.candidates_all : 0;
          const candidatesNew = row.candidates_new !== undefined && row.candidates_new !== null ? row.candidates_new : 0;
          // Log the row data for debugging
          console.log('Row Data:', row);
          return `
            <div class="count-box clickable" data-jobcode="${row.job_code}">
              <i class="fa fa-users"></i>
              <span>${candidatesAll}</span>
              <div class="count-label">New</div>
            </div>
            <div class="count-box clickable" data-jobcode="${row.job_code}">
              <i class="fa fa-file-text-o"></i>
              <span>${candidatesNew}</span>
              <div class="count-label">Reviewed</div>
            </div>
          `;
        },
        "orderable": false
      }
    ],
    "pageLength": 10,
    "searching": false,
    "ordering": true,
    "order": [[1, "asc"]],
    "lengthMenu": [10, 25, 50, 100],
    "dom": '<"dataTables_length"l>rtip'
  });

  // Tab click handler
  $('.job-tab').on('click', function() {
    $('.job-tab').removeClass('active');
    $(this).addClass('active');
    currentStatusFilter = $(this).data('status');
    table.ajax.reload();
  });

  // Click handler for the "New Candidates" icon
  $('#jobTable').on('click', '.count-box.clickable:first-child', function(e) {
    e.stopPropagation(); // Prevent the row click event from firing
    var jobCode = $(this).data('jobcode');
    window.location.href = 'candidate_list.php?jobcode=' + encodeURIComponent(jobCode) + '&status=Active';
  });

  // Click handler for the "Reviewed" icon
  $('#jobTable').on('click', '.count-box.clickable:last-child', function(e) {
    e.stopPropagation(); // Prevent the row click event from firing
    var jobCode = $(this).data('jobcode');
    window.location.href = 'candidate_list.php?jobcode=' + encodeURIComponent(jobCode) + '&status=Reviewed';
  });

  // Row click handler to redirect to vacancy_detail.php
  $('#jobTable tbody').on('click', 'tr', function(e) {
    // Prevent redirection if the click is on the checkbox or the count-box
    if ($(e.target).is('.job-checkbox') || $(e.target).closest('.count-box.clickable').length) {
      return;
    }
    var data = table.row(this).data();
    var jobCode = data.job_code;
    window.location.href = 'vacancy_detail.php?jobcode=' + encodeURIComponent(jobCode);
  });

  // Search input handler
  $('#jobSearch').on('keyup', function() {
    table.ajax.reload();
  });

  // Select all checkbox handler
  $('#selectAll').on('change', function() {
    $('.job-checkbox').prop('checked', this.checked);
  });
});
</script>
</body>
</html>

<?php
$conn->close();
?>
 