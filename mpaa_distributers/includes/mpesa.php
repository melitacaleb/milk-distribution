 <?php
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
require_once 'includes/mpesa.php'; // M-Pesa integration functions

requireAuth();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['process_payment'])) {
        $payment_id = $_POST['payment_id'];
        $phone = $_POST['phone'];
        $amount = $_POST['amount'];
        
        // Process M-Pesa payment
        $mpesa_response = processMpesaPayment($phone, $amount, $payment_id);
        
        if ($mpesa_response['success']) {
            // Update payment status in database
            if (updatePaymentStatus($conn, $payment_id, 'paid', $mpesa_response['transaction_id'])) {
                $success = "Payment processed successfully! Transaction ID: " . $mpesa_response['transaction_id'];
            } else {
                $error = "Payment processed but failed to update record. Transaction ID: " . $mpesa_response['transaction_id'];
            }
        } else {
            $error = "M-Pesa payment failed: " . $mpesa_response['message'];
        }
    } elseif (isset($_POST['generate_bulk_payments'])) {
        // Generate monthly payments for all farmers
        if (generateMonthlyPayments($conn)) {
            $success = "Bulk payments generated successfully!";
        } else {
            $error = "Failed to generate bulk payments.";
        }
    }
}

// Get pending and recent payments
$pending_payments = getPendingPaymentsList($conn);
$recent_payments = getRecentPayments($conn, 10);
$total_pending = array_sum(array_column($pending_payments, 'amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mpaa Distributers - Payments Management</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        .payment-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .payment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.1);
            color: #ffc107;
        }
        
        .status-paid {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .status-failed {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .mpesa-color {
            color: #00B900; /* M-Pesa green */
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
        
        .summary-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .progress-bar {
            height: 10px;
            border-radius: 5px;
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
                    <h4 class="mb-0">Payments Management</h4>
                </div>
                <div class="d-flex">
                    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#bulkPaymentsModal">
                        <i class="bi bi-cash-stack"></i> Generate Monthly Payments
                    </button>
                    <input type="month" class="form-control" id="paymentMonthFilter" 
                           value="<?= date('Y-m') ?>">
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid py-4">
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
            
            <!-- Payment Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="payment-card bg-white p-4 border-start border-5 border-warning">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Pending Payments</h6>
                                <h2 class="mb-0">KES <?= number_format($total_pending, 2) ?></h2>
                            </div>
                            <i class="bi bi-clock-history" style="font-size: 1.5rem; color: #ffc107;"></i>
                        </div>
                        <div class="mt-3">
                            <div class="progress">
                                <div class="progress-bar bg-warning" 
                                     style="width: <?= min(100, count($pending_payments) * 10) ?>%">
                                </div>
                            </div>
                            <small class="text-muted"><?= count($pending_payments) ?> pending transactions</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="payment-card bg-white p-4 border-start border-5 border-success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">This Month's Payments</h6>
                                <h2 class="mb-0">KES <?= number_format(getMonthlyPaymentsTotal($conn), 2) ?></h2>
                            </div>
                            <i class="bi bi-cash-coin" style="font-size: 1.5rem; color: #28a745;"></i>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <?= getMonthlyPaymentsCount($conn) ?> processed payments
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="payment-card bg-white p-4 border-start border-5 border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-muted small mb-2">Farmers Due</h6>
                                <h2 class="mb-0"><?= count($pending_payments) ?></h2>
                            </div>
                            <i class="bi bi-people" style="font-size: 1.5rem; color: #2a5a78;"></i>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                <?= count(array_unique(array_column($pending_payments, 'farmer_id'))) ?> unique farmers
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Pending Payments -->
            <div class="card payment-card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Pending Payments</h5>
                        <div class="badge bg-warning text-dark">
                            <?= count($pending_payments) ?> records
                        </div>
                    </div>
                    
                    <?php if (!empty($pending_payments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Farmer</th>
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Milk (L)</th>
                                    <th>Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_payments as $payment): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <?= strtoupper(substr($payment['farmer_name'], 0, 2)) ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= $payment['farmer_name'] ?></h6>
                                                <small class="text-muted"><?= $payment['farmer_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $payment['period'] ?></td>
                                    <td>KES <?= number_format($payment['amount'], 2) ?></td>
                                    <td><?= $payment['milk_quantity'] ?></td>
                                    <td><?= $payment['phone'] ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#processPaymentModal"
                                                data-payment-id="<?= $payment['payment_id'] ?>"
                                                data-amount="<?= $payment['amount'] ?>"
                                                data-phone="<?= $payment['phone'] ?>"
                                                data-farmer="<?= $payment['farmer_name'] ?>">
                                            <i class="bi bi-send"></i> Pay via M-Pesa
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-light">
                                    <td colspan="2" class="text-end"><strong>Totals:</strong></td>
                                    <td><strong>KES <?= number_format($total_pending, 2) ?></strong></td>
                                    <td><strong><?= array_sum(array_column($pending_payments, 'milk_quantity')) ?> L</strong></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-check-circle" style="font-size: 3rem; color: #28a745;"></i>
                        <h5 class="mt-3">No Pending Payments</h5>
                        <p class="text-muted">All payments are up to date</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Payments -->
            <div class="card payment-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Recent Payments</h5>
                        <a href="reports.php?type=payments" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-graph-up"></i> View Payment Reports
                        </a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Farmer</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Transaction ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($payment['processed_at'])) ?></td>
                                    <td><?= $payment['farmer_name'] ?></td>
                                    <td>KES <?= number_format($payment['amount'], 2) ?></td>
                                    <td>
                                        <?php if ($payment['payment_method'] === 'M-Pesa'): ?>
                                        <span class="mpesa-color">
                                            <i class="bi bi-phone"></i> M-Pesa
                                        </span>
                                        <?php else: ?>
                                        <?= $payment['payment_method'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= 
                                            $payment['status'] === 'paid' ? 'status-paid' : 
                                            ($payment['status'] === 'failed' ? 'status-failed' : 'status-pending') 
                                        ?>">
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $payment['transaction_id'] ?? 'N/A' ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Process Payment Modal -->
    <div class="modal fade" id="processPaymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process M-Pesa Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="processPaymentForm">
                    <input type="hidden" name="payment_id" id="modalPaymentId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Farmer</label>
                            <input type="text" class="form-control" id="modalFarmerName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (KES)</label>
                            <input type="number" name="amount" class="form-control" id="modalAmount" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">M-Pesa Phone Number</label>
                            <input type="tel" name="phone" class="form-control" id="modalPhone" required>
                            <small class="text-muted">Format: 2547XXXXXXXX</small>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> This will initiate an M-Pesa payment request to the farmer's phone
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="process_payment" class="btn btn-success">
                            <i class="bi bi-send"></i> Confirm Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Payments Modal -->
    <div class="modal fade" id="bulkPaymentsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Monthly Payments</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> This will generate payment records for all farmers with milk collections this month.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Month</label>
                            <input type="month" class="form-control" name="payment_month" 
                                   value="<?= date('Y-m') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" name="payment_date" 
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="generate_bulk_payments" class="btn btn-primary">
                            <i class="bi bi-cash-stack"></i> Generate Payments
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Script -->
    <script>
        // Process Payment Modal setup
        const processPaymentModal = document.getElementById('processPaymentModal');
        processPaymentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            document.getElementById('modalPaymentId').value = button.getAttribute('data-payment-id');
            document.getElementById('modalAmount').value = button.getAttribute('data-amount');
            document.getElementById('modalPhone').value = button.getAttribute('data-phone');
            document.getElementById('modalFarmerName').value = button.getAttribute('data-farmer');
        });
        
        // Month filter functionality
        document.getElementById('paymentMonthFilter').addEventListener('change', function() {
            window.location.href = 'payments.php?month=' + this.value;
        });
        
        // Format phone number input
        document.getElementById('modalPhone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 0 && this.value[0] !== '2') {
                this.value = '254' + this.value;
            }
        });
    </script>
</body>
</html>