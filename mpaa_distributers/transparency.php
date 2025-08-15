<?php
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

requireAuth();

// Get system activities
$stmt = $conn->prepare("SELECT * FROM system_activities ORDER BY activity_date DESC LIMIT 10");
$stmt->execute();
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mpaa Distributers - Transparency</title>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="page-header">
                <h2 class="mb-0">Transparency Dashboard</h2>
                <p class="text-muted mb-0">Full visibility into milk collection and payments</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="transparency-metric">
                        <div class="transparency-icon mb-3 mx-auto">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h5>Transparency Score</h5>
                        <div class="transparency-metric-value">98%</div>
                        <small class="text-muted">System transparency rating</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="transparency-metric">
                        <div class="transparency-icon mb-3 mx-auto">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h5>Verified Data</h5>
                        <div class="transparency-metric-value">100%</div>
                        <small class="text-muted">Of all records are verified</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="transparency-metric">
                        <div class="transparency-icon mb-3 mx-auto">
                            <i class="fas fa-history"></i>
                        </div>
                        <h5>Audit Logs</h5>
                        <div class="transparency-metric-value">1,245</div>
                        <small class="text-muted">System activities tracked</small>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="dashboard-chart">
                        <h5 class="mb-3">Price Transparency</h5>
                        <div class="transparency-panel">
                            <div class="transparency-item">
                                <div class="transparency-icon">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Current Milk Prices</h6>
                                    <small class="text-muted">Per liter based on quality</small>
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-success me-2">Grade A: KES 50</span>
                                    <span class="badge bg-warning text-dark me-2">Grade B: KES 40</span>
                                    <span class="badge bg-danger">Grade C: KES 30</span>
                                </div>
                            </div>
                            <!-- More transparency items -->
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="dashboard-chart">
                        <h5 class="mb-3">Recent System Activities</h5>
                        <div class="transparency-panel">
                            <?php foreach ($activities as $activity): ?>
                            <div class="transparency-item">
                                <div class="transparency-icon">
                                    <i class="fas <?= 
                                        strpos($activity['activity_type'], 'payment') !== false ? 'fa-money-bill-wave' : 
                                        (strpos($activity['activity_type'], 'collection') !== false ? 'fa-fill-drip' : 'fa-info-circle')
                                    ?>"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?= $activity['activity_description'] ?></h6>
                                    <small class="text-muted"><?= $activity['user'] ?></small>
                                    <div class="text-muted small"><?= $activity['activity_date'] ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>