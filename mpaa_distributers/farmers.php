<?php
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

requireAuth();

// Handle farmer addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_farmer'])) {
    if (addFarmer($conn, $_POST)) {
        $success = "Farmer added successfully!";
    } else {
        $error = "Failed to add farmer. Please try again.";
    }
}

// Get all farmers with their today's collection and payment info
$farmers = getAllFarmersWithTodayData($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mpaa Distributers - Farmers Management</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        .farmer-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border-left: 4px solid #2a5a78;
        }
        
        .farmer-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .progress-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .status-active {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .status-inactive {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #2a5a78;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <div class="d-flex align-items-center">
                    <h4 class="mb-0">Farmers Management</h4>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid py-4">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="farmer-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Total Farmers</h6>
                                <h2 class="mb-0"><?= count($farmers) ?></h2>
                            </div>
                            <i class="bi bi-people" style="font-size: 1.5rem; color: #2a5a78;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="farmer-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Active Today</h6>
                                <h2 class="mb-0">
                                    <?= count(array_filter($farmers, fn($f) => $f['today_quantity'] > 0)) ?>
                                </h2>
                            </div>
                            <i class="bi bi-check-circle" style="font-size: 1.5rem; color: #28a745;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="farmer-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Today's Milk</h6>
                                <h2 class="mb-0">
                                    <?= array_sum(array_column($farmers, 'today_quantity')) ?> L
                                </h2>
                            </div>
                            <i class="bi bi-droplet" style="font-size: 1.5rem; color: #4b8bb8;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="farmer-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Today's Payments</h6>
                                <h2 class="mb-0">
                                    KES <?= number_format(array_sum(array_column($farmers, 'today_amount')), 2) ?>
                                </h2>
                            </div>
                            <i class="bi bi-cash-stack" style="font-size: 1.5rem; color: #f8a51b;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Farmers Table -->
            <div class="card farmer-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Farmers List</h5>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFarmerModal">
                            <i class="bi bi-plus"></i> Add Farmer
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Farmer</th>
                                    <th>Contact</th>
                                    <th>Cows</th>
                                    <th>Today's Milk (L)</th>
                                    <th>Today's Payment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($farmers as $farmer): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <?= strtoupper(substr($farmer['name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= $farmer['name'] ?></h6>
                                                <small class="text-muted"><?= $farmer['location'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $farmer['phone'] ?></td>
                                    <td><?= $farmer['cows'] ?></td>
                                    <td><?= $farmer['today_quantity'] > 0 ? $farmer['today_quantity'] : '-' ?></td>
                                    <td>
                                        <?= $farmer['today_amount'] > 0 ? 'KES ' . number_format($farmer['today_amount'], 2) : '-' ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $farmer['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                            <?= ucfirst($farmer['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                onclick="viewFarmer('<?= $farmer['farmer_id'] ?>')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary me-1" 
                                                onclick="editFarmer('<?= $farmer['farmer_id'] ?>')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete('<?= $farmer['farmer_id'] ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <!-- Total Row -->
                                <tr class="total-row">
                                    <td colspan="3" class="text-end"><strong>Totals:</strong></td>
                                    <td><strong><?= array_sum(array_column($farmers, 'today_quantity')) ?> L</strong></td>
                                    <td><strong>KES <?= number_format(array_sum(array_column($farmers, 'today_amount')), 2) ?></strong></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Add Farmer Form -->
            <div class="card farmer-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Register New Farmer</h5>
                    
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Number of Cows</label>
                                <input type="number" name="cows" class="form-control" min="1" value="1">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Additional Notes</label>
                                <input type="text" name="notes" class="form-control">
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" name="add_farmer" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Farmer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Script -->
    <script>
        function viewFarmer(farmerId) {
            // Implementation for viewing farmer details
            alert('View farmer: ' + farmerId);
            // In a real implementation, you would show a modal with farmer details
        }
        
        function editFarmer(farmerId) {
            // Implementation for editing farmer
            alert('Edit farmer: ' + farmerId);
            // In a real implementation, you would populate a form with farmer data
        }
        
        function confirmDelete(farmerId) {
            if (confirm('Are you sure you want to delete this farmer?')) {
                // Implementation for deleting farmer
                alert('Delete farmer: ' + farmerId);
                // In a real implementation, you would make an AJAX call to delete the farmer
            }
        }
    </script>
</body>
</html>