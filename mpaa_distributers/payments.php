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
    } elseif (isset($_POST['send_direct_payment'])) {
        // Handle direct M-Pesa payment to farmer
        $farmer_id = $_POST['farmer_id'];
        $amount = $_POST['amount'];
        $phone = $_POST['phone'];
        $reference = $_POST['reference'];
        
        // Process direct M-Pesa payment
        $response = sendDirectMpesaPayment($conn, $farmer_id, $amount, $phone, $reference);
        
        if ($response['success']) {
            $success = $response['message'];
        } else {
            $error = $response['message'];
        }
    }
}

// Get pending and recent payments
$pending_payments = getPendingPaymentsList($conn);
$recent_payments = getRecentPayments($conn, 10);
$total_pending = array_sum(array_column($pending_payments, 'amount'));
$farmers = getAllFarmers($conn); // Get all farmers for direct payment dropdown
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- [Previous head content remains exactly the same] -->
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <!-- [Previous navigation and alerts remain exactly the same] -->
        
        <!-- Direct Payment Section -->
        <div class="card payment-card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4">Direct M-Pesa Payment to Farmer</h5>
                
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label for="farmer_id" class="form-label">Select Farmer</label>
                        <select class="form-select" id="farmer_id" name="farmer_id" required>
                            <option value="">-- Select Farmer --</option>
                            <?php foreach ($farmers as $farmer): ?>
                            <option value="<?= $farmer['farmer_id'] ?>" 
                                    data-phone="<?= $farmer['phone'] ?>">
                                <?= $farmer['name'] ?> (<?= $farmer['farmer_id'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="amount" class="form-label">Amount (KES)</label>
                        <input type="number" class="form-control" id="amount" name="amount" min="10" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="phone" class="form-label">M-Pesa Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                        <small class="text-muted">Format: 2547XXXXXXXX</small>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="reference" class="form-label">Reference</label>
                        <input type="text" class="form-control" id="reference" name="reference" 
                               value="MPF<?= date('Ymd') ?>" required>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" name="send_direct_payment" class="btn btn-success">
                            <i class="bi bi-send"></i> Send Payment via M-Pesa
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- [Rest of your existing content remains exactly the same] -->
    </div>

    <!-- [Previous modals remain exactly the same] -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Script -->
    <script>
        // [Previous JavaScript remains exactly the same]
        
        // Auto-fill phone number when farmer is selected
        document.getElementById('farmer_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('phone').value = selectedOption.getAttribute('data-phone');
            }
        });
        
        // Format phone number input for direct payment
        document.getElementById('phone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 0 && this.value[0] !== '2') {
                this.value = '254' + this.value;
            }
        });
    </script>
</body>
</html>
