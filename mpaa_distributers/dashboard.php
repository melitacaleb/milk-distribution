<?php
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

requireAuth();

// Get dashboard stats
$stats = [
    'today_collection' => getTodayCollection($conn, date('Y-m-d')),
    'weekly_collection' => getWeeklyCollection($conn),
    'active_farmers' => getActiveFarmersCount($conn),
    'pending_payments' => getPendingPayments($conn),
    'monthly_sales' => getMonthlySales($conn),
    'top_farmers' => getTopFarmers($conn, 5),
    'recent_collections' => getRecentCollections($conn, 5)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mpaa Distributers - Professional Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --mpaa-primary: #2a5a78;
            --mpaa-secondary: #4b8bb8;
            --mpaa-accent: #f8a51b;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--mpaa-primary) 0%, var(--mpaa-secondary) 100%);
            color: white;
            height: 100vh;
            position: fixed;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 5px;
            margin: 3px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
            border-left: 4px solid var(--mpaa-accent);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card .bi {
            font-size: 1.5rem;
            color: var(--mpaa-accent);
        }
        
        .recent-activity-item {
            border-left: 3px solid var(--mpaa-secondary);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .quality-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .quality-A {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .quality-B {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .quality-C {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .sidebar .nav-text {
                display: none;
            }
            .main-content {
                margin-left: 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar col-md-3 col-lg-2 d-md-block p-3">
        <div class="text-center mb-4">
            <img src="assets/img/logo.png" alt="Mpaa Distributers" class="img-fluid mb-3" style="max-height: 60px;">
            <h5 class="mb-0">Mpaa Distributers</h5>
            <small class="text-white-50">Milk Management System</small>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="collections.php">
                    <i class="bi bi-droplet"></i>
                    <span class="nav-text">Collections</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="farmers.php">
                    <i class="bi bi-people"></i>
                    <span class="nav-text">Farmers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="payments.php">
                    <i class="bi bi-cash-stack"></i>
                    <span class="nav-text">Payments</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="reports.php">
                    <i class="bi bi-graph-up"></i>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link" href="settings.php">
                    <i class="bi bi-gear"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                        <i class="bi bi-list"></i>
                    </button>
                    <h4 class="mb-0 ms-3">Dashboard Overview</h4>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <a href="#" class="dropdown-toggle text-dark" id="notificationsDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-bell fs-5 position-relative">
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    5
                                </span>
                            </i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><h6 class="dropdown-header">Notifications</h6></li>
                            <li><a class="dropdown-item" href="#">New collection recorded</a></li>
                            <li><a class="dropdown-item" href="#">Payment processed</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">View all</a></li>
                        </ul>
                    </div>
                    
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle d-flex align-items-center text-decoration-none" id="userDropdown" data-bs-toggle="dropdown">
                            <div class="bg-primary text-white rounded-circle p-2 me-2">
                                <i class="bi bi-person"></i>
                            </div>
                            <span>System Admin</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid py-4">
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Today's Collection</h6>
                                <h2 class="mb-0"><?= $stats['today_collection'] ?> L</h2>
                            </div>
                            <i class="bi bi-droplet"></i>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-success bg-opacity-10 text-success me-2">
                                <i class="bi bi-arrow-up"></i> 12%
                            </span>
                            <span class="text-muted small">vs yesterday</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Active Farmers</h6>
                                <h2 class="mb-0"><?= $stats['active_farmers'] ?></h2>
                            </div>
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-success bg-opacity-10 text-success me-2">
                                <i class="bi bi-arrow-up"></i> 3
                            </span>
                            <span class="text-muted small">new this month</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Pending Payments</h6>
                                <h2 class="mb-0">KES <?= number_format($stats['pending_payments'], 2) ?></h2>
                            </div>
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div class="mt-3">
                            <span class="text-muted small">to be processed</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Monthly Sales</h6>
                                <h2 class="mb-0">KES <?= number_format($stats['monthly_sales'], 2) ?></h2>
                            </div>
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-success bg-opacity-10 text-success me-2">
                                <i class="bi bi-arrow-up"></i> 8.5%
                            </span>
                            <span class="text-muted small">vs last month</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Weekly Collection Trend</h5>
                                <div>
                                    <select class="form-select form-select-sm" style="width: 150px;">
                                        <option>This Week</option>
                                        <option>Last Week</option>
                                        <option>Last Month</option>
                                    </select>
                                </div>
                            </div>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="collectionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Quality Distribution</h5>
                            <div class="chart-container" style="height: 300px;">
                                <canvas id="qualityChart"></canvas>
                            </div>
                            <div class="mt-3 text-center">
                                <span class="badge quality-A me-2">Grade A: 65%</span>
                                <span class="badge quality-B me-2">Grade B: 25%</span>
                                <span class="badge quality-C">Grade C: 10%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Row -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Top Farmers</h5>
                                <a href="farmers.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Farmer</th>
                                            <th>Location</th>
                                            <th>This Week (L)</th>
                                            <th>Avg. Quality</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['top_farmers'] as $farmer): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?= $farmer['name'] ?></h6>
                                                        <small class="text-muted"><?= $farmer['farmer_id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= $farmer['location'] ?></td>
                                            <td><?= $farmer['total_quantity'] ?></td>
                                            <td>
                                                <span class="badge <?= $farmer['avg_quality'] == 'A' ? 'quality-A' : ($farmer['avg_quality'] == 'B' ? 'quality-B' : 'quality-C') ?>">
                                                    Grade <?= $farmer['avg_quality'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0">Recent Activities</h5>
                                <a href="collections.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="recent-activities">
                                <?php foreach ($stats['recent_collections'] as $collection): ?>
                                <div class="recent-activity-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1"><?= $collection['farmer_name'] ?></h6>
                                            <small class="text-muted"><?= $collection['collection_date'] ?> • <?= $collection['collection_time'] ?></small>
                                        </div>
                                        <div class="text-end">
                                            <strong><?= $collection['quantity'] ?> L</strong>
                                            <div>
                                                <span class="badge <?= $collection['quality'] == 'A' ? 'quality-A' : ($collection['quality'] == 'B' ? 'quality-B' : 'quality-C') ?>">
                                                    Grade <?= $collection['quality'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Collection Chart
            const collectionCtx = document.getElementById('collectionChart').getContext('2d');
            new Chart(collectionCtx, {
                type: 'bar',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Milk Collected (L)',
                        data: [120, 190, 170, 210, 180, 240, 195],
                        backgroundColor: 'rgba(42, 90, 120, 0.7)',
                        borderColor: 'rgba(42, 90, 120, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // Quality Chart
            const qualityCtx = document.getElementById('qualityChart').getContext('2d');
            new Chart(qualityCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Grade A', 'Grade B', 'Grade C'],
                    datasets: [{
                        data: [65, 25, 10],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>