<?php
require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

requireAuth();

// Helper: fetch setting
function getSetting($key, $conn) {
    $stmt = $conn->prepare("SELECT value FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    return $stmt->fetchColumn();
}

// Helper: update setting
function updateSetting($key, $value, $conn) {
    $stmt = $conn->prepare("REPLACE INTO settings (`key`, `value`) VALUES (?, ?)");
    $stmt->execute([$key, $value]);
}

// Update settings if form submitted
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'grade_a_price',
        'grade_b_price',
        'grade_c_price',
        'payment_day',
        'company_name',
        'contact_email',
        'contact_phone',
        'physical_address'
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            updateSetting($field, $_POST[$field], $conn);
        }
    }
    $success = "✅ Settings updated successfully.";
}

// Load current settings
$settings = [
    'grade_a_price' => getSetting('grade_a_price', $conn),
    'grade_b_price' => getSetting('grade_b_price', $conn),
    'grade_c_price' => getSetting('grade_c_price', $conn),
    'payment_day' => getSetting('payment_day', $conn),
    'company_name' => getSetting('company_name', $conn),
    'contact_email' => getSetting('contact_email', $conn),
    'contact_phone' => getSetting('contact_phone', $conn),
    'physical_address' => getSetting('physical_address', $conn)
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Mpaa Distributers - Settings</title>
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="page-header">
                <h2 class="mb-0">System Settings</h2>
                <p class="text-muted mb-0">Configure system parameters and preferences</p>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success mt-3"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="dashboard-chart">
                            <h5 class="mb-3">Pricing Settings</h5>
                            <div class="mb-3">
                                <label class="form-label">Grade A Price (KES/L)</label>
                                <input type="number" name="grade_a_price" class="form-control" value="<?= $settings['grade_a_price'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Grade B Price (KES/L)</label>
                                <input type="number" name="grade_b_price" class="form-control" value="<?= $settings['grade_b_price'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Grade C Price (KES/L)</label>
                                <input type="number" name="grade_c_price" class="form-control" value="<?= $settings['grade_c_price'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Day of Month</label>
                                <select class="form-select" name="payment_day">
                                    <?php for ($i = 1; $i <= 28; $i++): ?>
                                    <option value="<?= $i ?>" <?= $i == $settings['payment_day'] ? 'selected' : '' ?>>
                                        <?= $i ?><?= $i == 1 ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th')) ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-chart">
                            <h5 class="mb-3">Company Information</h5>
                            <div class="mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" name="company_name" class="form-control" value="<?= $settings['company_name'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="contact_email" class="form-control" value="<?= $settings['contact_email'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" name="contact_phone" class="form-control" value="<?= $settings['contact_phone'] ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Physical Address</label>
                                <textarea class="form-control" name="physical_address" rows="3"><?= $settings['physical_address'] ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-primary">Save All Settings</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
