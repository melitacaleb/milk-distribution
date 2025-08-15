<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and include required files
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

// Authenticate user
requireAuth();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add Collection
        if (isset($_POST['add_collection'])) {
            // Validate required fields
            $required = ['farmer_id', 'collection_date', 'collection_time', 'quantity', 'quality'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Please fill in all required fields");
                }
            }

            // Prepare collection data
            $collectionData = [
                'farmer_id' => trim($_POST['farmer_id']),
                'collection_date' => trim($_POST['collection_date']),
                'collection_time' => trim($_POST['collection_time']),
                'quantity' => (float)$_POST['quantity'],
                'quality' => strtoupper(trim($_POST['quality'])),
                'fat_content' => !empty($_POST['fat_content']) ? (float)$_POST['fat_content'] : null
            ];

            // Save to database
            if (addCollection($conn, $collectionData)) {
                $_SESSION['success'] = "Milk collection recorded successfully!";
                header("Location: collections.php");
                exit();
            } else {
                throw new Exception("Failed to save collection to database");
            }
        } 
        // Delete Collection
        elseif (isset($_POST['delete_collection'])) {
            if (!empty($_POST['collection_id'])) {
                if (deleteCollection($conn, $_POST['collection_id'])) {
                    $_SESSION['success'] = "Collection deleted successfully!";
                    header("Location: collections.php");
                    exit();
                } else {
                    throw new Exception("Failed to delete collection");
                }
            } else {
                throw new Exception("No collection ID provided");
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get collections data
$today = date('Y-m-d');
$today_collections = getCollectionsByDate($conn, $today);
$recent_collections = getRecentCollections($conn, 20);

// Calculate totals
$today_total_quantity = array_sum(array_column($today_collections, 'quantity'));
$today_total_amount = array_sum(array_column($today_collections, 'total_amount'));
$today_farmers_count = count($today_collections);

// Display session messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mpaa Distributers - Milk Collections</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        .collection-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border-left: 4px solid #2a5a78;
        }
        .collection-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        .quality-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        .quality-A { background-color: rgba(40, 167, 69, 0.1); color: #28a745; }
        .quality-B { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .quality-C { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
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
        .total-row { background-color: #f8f9fa; font-weight: bold; }
        .form-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
            }
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
                    <h4 class="mb-0">Milk Collections</h4>
                </div>
                <div class="d-flex">
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addCollectionModal">
                        <i class="bi bi-plus"></i> New Collection
                    </button>
                    <input type="date" class="form-control" id="collectionDateFilter" value="<?= $today ?>">
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid py-4">
            <!-- Alerts -->
            <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="collection-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Today's Collection</h6>
                                <h2 class="mb-0"><?= $today_total_quantity ?> L</h2>
                            </div>
                            <i class="bi bi-droplet" style="font-size: 1.5rem; color: #4b8bb8;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="collection-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Today's Farmers</h6>
                                <h2 class="mb-0"><?= $today_farmers_count ?></h2>
                            </div>
                            <i class="bi bi-people" style="font-size: 1.5rem; color: #2a5a78;"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="collection-card bg-white p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Today's Payments</h6>
                                <h2 class="mb-0">KES <?= number_format($today_total_amount, 2) ?></h2>
                            </div>
                            <i class="bi bi-cash-stack" style="font-size: 1.5rem; color: #f8a51b;"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add Collection Form -->
            <div class="form-section">
                <h5 class="mb-4"><i class="bi bi-plus-circle"></i> Record New Collection</h5>
                
                <form method="POST" id="collectionForm" onsubmit="return validateForm()">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Farmer <span class="text-danger">*</span></label>
                            <select name="farmer_id" class="form-select" required>
                                <option value="">Select Farmer</option>
                                <?php foreach (getAllFarmers($conn) as $farmer): ?>
                                <option value="<?= htmlspecialchars($farmer['farmer_id']) ?>">
                                    <?= htmlspecialchars($farmer['name']) ?> (<?= htmlspecialchars($farmer['farmer_id']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Collection Date <span class="text-danger">*</span></label>
                            <input type="date" name="collection_date" class="form-control" required 
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Collection Time <span class="text-danger">*</span></label>
                            <input type="time" name="collection_time" class="form-control" required 
                                   value="<?= date('H:i') ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Quantity (Liters) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" min="0.1" name="quantity" class="form-control" required 
                                   id="quantityInput" placeholder="e.g. 5.2">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Quality Grade <span class="text-danger">*</span></label>
                            <select name="quality" class="form-select" required id="qualitySelect">
                                <option value="A">Grade A</option>
                                <option value="B">Grade B</option>
                                <option value="C">Grade C</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Fat Content (%)</label>
                            <input type="number" step="0.1" min="0" max="10" name="fat_content" class="form-control" 
                                   id="fatContentInput" placeholder="e.g. 3.5">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Price per Liter</label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number" step="0.1" name="price_per_liter" class="form-control" 
                                       id="priceInput" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">KES</span>
                                <input type="number" step="0.1" name="total_amount" class="form-control" 
                                       id="totalAmountInput" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-8 d-flex align-items-end">
                            <button type="submit" name="add_collection" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Collection
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Today's Collections Table -->
            <div class="card collection-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Today's Collections (<?= date('F j, Y') ?>)</h5>
                        <div class="badge bg-primary"><?= $today_farmers_count ?> records</div>
                    </div>
                    
                    <?php if (!empty($today_collections)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Farmer</th>
                                    <th>Quantity (L)</th>
                                    <th>Quality</th>
                                    <th>Fat %</th>
                                    <th>Price/L</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_collections as $collection): ?>
                                <tr>
                                    <td><?= date('H:i', strtotime($collection['recorded_at'])) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <?= strtoupper(substr($collection['farmer_name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($collection['farmer_name']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($collection['farmer_id']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $collection['quantity'] ?></td>
                                    <td>
                                        <span class="badge <?= $collection['quality'] === 'A' ? 'quality-A' : ($collection['quality'] === 'B' ? 'quality-B' : 'quality-C') ?>">
                                            Grade <?= $collection['quality'] ?>
                                        </span>
                                    </td>
                                    <td><?= $collection['fat_content'] ?>%</td>
                                    <td>KES <?= $collection['price_per_liter'] ?></td>
                                    <td>KES <?= number_format($collection['total_amount'], 2) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete('<?= $collection['collection_id'] ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="2" class="text-end"><strong>Totals:</strong></td>
                                    <td><strong><?= $today_total_quantity ?> L</strong></td>
                                    <td colspan="3"></td>
                                    <td><strong>KES <?= number_format($today_total_amount, 2) ?></strong></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-droplet" style="font-size: 3rem; color: #6c757d;"></i>
                        <h5 class="mt-3">No Collections Today</h5>
                        <p class="text-muted">Record milk collections to see them here</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Collections Table -->
            <div class="card collection-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Collections</h5>
                        <a href="reports.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-graph-up"></i> View Reports
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Farmer</th>
                                    <th>Quantity (L)</th>
                                    <th>Quality</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_collections as $collection): ?>
                                <tr>
                                    <td><?= date('M j', strtotime($collection['collection_date'])) ?></td>
                                    <td><?= htmlspecialchars($collection['farmer_name']) ?></td>
                                    <td><?= $collection['quantity'] ?></td>
                                    <td>
                                        <span class="badge <?= $collection['quality'] === 'A' ? 'quality-A' : ($collection['quality'] === 'B' ? 'quality-B' : 'quality-C') ?>">
                                            Grade <?= $collection['quality'] ?>
                                        </span>
                                    </td>
                                    <td>KES <?= number_format($collection['total_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this collection record? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="collection_id" id="collectionIdToDelete">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_collection" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Script -->
    <script>
        // Automatic price calculation based on quality
        document.getElementById('qualitySelect').addEventListener('change', updatePrice);
        document.getElementById('quantityInput').addEventListener('input', calculateTotal);
        
        function updatePrice() {
            const quality = document.getElementById('qualitySelect').value;
            let price = 30; // Default for Grade C
            
            if (quality === 'A') price = 50;
            else if (quality === 'B') price = 40;
            
            document.getElementById('priceInput').value = price;
            calculateTotal();
        }
        
        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('quantityInput').value) || 0;
            const price = parseFloat(document.getElementById('priceInput').value) || 0;
            const total = quantity * price;
            document.getElementById('totalAmountInput').value = total.toFixed(2);
        }
        
        function confirmDelete(collectionId) {
            document.getElementById('collectionIdToDelete').value = collectionId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        // Form validation
        function validateForm() {
            const quantity = parseFloat(document.getElementById('quantityInput').value);
            if (isNaN(quantity) || quantity <= 0) {
                alert('Please enter a valid quantity (greater than 0)');
                return false;
            }
            return true;
        }
        
        // Initialize price on page load
        updatePrice();
        
        // Date filter functionality
        document.getElementById('collectionDateFilter').addEventListener('change', function() {
            window.location.href = 'collections.php?date=' + this.value;
        });
    </script>
</body>
</html>