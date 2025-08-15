<?php
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

requireAuth();

// Handle report generation
$report_data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-t');
    $report_type = $_POST['report_type'] ?? 'collections';
    
    if ($report_type === 'collections') {
        $report_data = generateCollectionReport($conn, $start_date, $end_date);
    } else {
        $report_data = generatePaymentReport($conn, $start_date, $end_date);
    }
}

// Calculate summary statistics
$total_milk = array_sum(array_column($report_data, 'quantity'));
$total_amount = array_sum(array_column($report_data, 'total_amount'));
$average_price = $total_milk > 0 ? $total_amount / $total_milk : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mpaa Distributers - Reports</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        .report-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border-left: 4px solid #2a5a78;
        }
        
        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .summary-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
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
        
        .action-btns .btn {
            margin-right: 5px;
            margin-bottom: 5px;
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
                    <h4 class="mb-0">Reports & Analytics</h4>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid py-4">
            <!-- Report Generator Card -->
            <div class="card report-card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Generate Custom Report</h5>
                    
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Report Type</label>
                            <select name="report_type" class="form-select">
                                <option value="collections" selected>Milk Collections</option>
                                <option value="payments">Farmer Payments</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?= date('Y-m-01') ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="<?= date('Y-m-t') ?>">
                        </div>
                        
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> Generate Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Summary Statistics -->
            <?php if (!empty($report_data)): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="summary-card">
                        <div class="row">
                            <div class="col-md-3">
                                <h6>Total Records</h6>
                                <h3><?= count($report_data) ?></h3>
                            </div>
                            <div class="col-md-3">
                                <h6>Total Milk (L)</h6>
                                <h3><?= number_format($total_milk, 2) ?> L</h3>
                            </div>
                            <div class="col-md-3">
                                <h6>Total Amount</h6>
                                <h3>KES <?= number_format($total_amount, 2) ?></h3>
                            </div>
                            <div class="col-md-3">
                                <h6>Avg Price/L</h6>
                                <h3>KES <?= number_format($average_price, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Report Data Table -->
            <div class="card report-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <?= $_POST['report_type'] === 'collections' ? 'Milk Collections' : 'Payment Records' ?>
                        </h5>
                        <div class="action-btns">
                            <button class="btn btn-success" onclick="exportToPDF()">
                                <i class="bi bi-file-earmark-pdf"></i> Export PDF
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                                <i class="bi bi-plus"></i> Add Record
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <?php if ($_POST['report_type'] === 'collections'): ?>
                                    <th>Date</th>
                                    <th>Farmer</th>
                                    <th>Quantity (L)</th>
                                    <th>Quality</th>
                                    <th>Fat %</th>
                                    <th>Price/L</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                    <?php else: ?>
                                    <th>Date</th>
                                    <th>Farmer</th>
                                    <th>Period</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $record): ?>
                                <tr>
                                    <?php if ($_POST['report_type'] === 'collections'): ?>
                                    <td><?= $record['collection_date'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= $record['farmer_name'] ?></h6>
                                                <small class="text-muted"><?= $record['farmer_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $record['quantity'] ?></td>
                                    <td>
                                        <span class="badge <?= $record['quality'] === 'A' ? 'quality-A' : ($record['quality'] === 'B' ? 'quality-B' : 'quality-C') ?>">
                                            Grade <?= $record['quality'] ?>
                                        </span>
                                    </td>
                                    <td><?= $record['fat_content'] ?>%</td>
                                    <td>KES <?= $record['price_per_liter'] ?></td>
                                    <td>KES <?= number_format($record['total_amount'], 2) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editRecord(<?= $record['id'] ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord(<?= $record['id'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                    <?php else: ?>
                                    <td><?= $record['processed_at'] ?></td>
                                    <td><?= $record['farmer_name'] ?></td>
                                    <td><?= $record['period'] ?></td>
                                    <td>KES <?= number_format($record['amount'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $record['status'] === 'paid' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= ucfirst($record['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-receipt"></i> Receipt
                                        </button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card report-card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-graph-up" style="font-size: 3rem; color: #6c757d;"></i>
                    <h5 class="mt-3">No Report Generated</h5>
                    <p class="text-muted">Select date range and generate a report to view data</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1" aria-labelledby="addRecordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRecordModalLabel">Add New Collection Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addRecordForm" method="POST" action="process_collection.php">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Farmer</label>
                                <select name="farmer_id" class="form-select" required>
                                    <option value="">Select Farmer</option>
                                    <?php foreach (getAllFarmers($conn) as $farmer): ?>
                                    <option value="<?= $farmer['farmer_id'] ?>">
                                        <?= $farmer['name'] ?> (<?= $farmer['farmer_id'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Collection Date</label>
                                <input type="date" name="collection_date" class="form-control" required 
                                       value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quantity (Liters)</label>
                                <input type="number" step="0.1" name="quantity" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quality Grade</label>
                                <select name="quality" class="form-select" required>
                                    <option value="A">Grade A</option>
                                    <option value="B">Grade B</option>
                                    <option value="C">Grade C</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Fat Content (%)</label>
                                <input type="number" step="0.1" name="fat_content" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Price per Liter (KES)</label>
                                <input type="number" step="0.1" name="price_per_liter" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Amount (KES)</label>
                                <input type="number" step="0.1" name="total_amount" class="form-control" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    
    <!-- Custom Script -->
    <script>
        // Calculate total amount automatically
        document.querySelector('input[name="quantity"]').addEventListener('input', calculateTotal);
        document.querySelector('input[name="price_per_liter"]').addEventListener('input', calculateTotal);
        
        function calculateTotal() {
            const quantity = parseFloat(document.querySelector('input[name="quantity"]').value) || 0;
            const price = parseFloat(document.querySelector('input[name="price_per_liter"]').value) || 0;
            const total = quantity * price;
            document.querySelector('input[name="total_amount"]').value = total.toFixed(2);
        }
        
        // Export to PDF function
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Title
            doc.setFontSize(18);
            doc.text('Mpaa Distributers - Milk Collection Report', 15, 15);
            
            // Date range
            doc.setFontSize(12);
            doc.text(`Date Range: ${document.querySelector('input[name="start_date"]').value} to ${document.querySelector('input[name="end_date"]').value}`, 15, 25);
            
            // Summary
            doc.setFontSize(12);
            doc.text('Summary Statistics', 15, 35);
            doc.text(`Total Milk: ${<?= $total_milk ?>} L`, 15, 45);
            doc.text(`Total Amount: KES ${<?= $total_amount ?>}`, 15, 55);
            
            // Table data
            const headers = [
                'Date', 
                'Farmer', 
                'Quantity (L)', 
                'Quality', 
                'Price/L', 
                'Total'
            ];
            
            const data = [
                <?php foreach($report_data as $row): ?>
                [
                    '<?= $row['collection_date'] ?>',
                    '<?= $row['farmer_name'] ?>',
                    <?= $row['quantity'] ?>,
                    'Grade <?= $row['quality'] ?>',
                    <?= $row['price_per_liter'] ?>,
                    <?= $row['total_amount'] ?>
                ],
                <?php endforeach; ?>
            ];
            
            // Add table
            doc.autoTable({
                head: [headers],
                body: data,
                startY: 65,
                styles: {
                    fontSize: 10,
                    cellPadding: 2
                },
                headStyles: {
                    fillColor: [42, 90, 120],
                    textColor: 255
                }
            });
            
            // Save the PDF
            doc.save(`Mpaa_Report_${new Date().toISOString().slice(0,10)}.pdf`);
        }
        
        function editRecord(id) {
            // Implementation for editing a record
            alert('Edit record with ID: ' + id);
            // In a real implementation, you would fetch the record data and populate a form
        }
        
        function deleteRecord(id) {
            if (confirm('Are you sure you want to delete this record?')) {
                // Implementation for deleting a record
                alert('Delete record with ID: ' + id);
                // In a real implementation, you would make an AJAX call to delete the record
            }
        }
    </script>
</body>
</html>