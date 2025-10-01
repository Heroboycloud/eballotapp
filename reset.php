<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';

$message = '';
$error = '';

// Handle reset confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['confirm_reset']) && $_POST['confirm_reset'] === 'yes') {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Reset all voter records (keep candidates)
/*
            $pdo->exec("UPDATE voters SET 
                has_paid = FALSE, 
                has_voted = FALSE, 
                vote_candidate = NULL,
                payment_source_1 = FALSE,
                payment_source_2 = FALSE,
                payment_amount_1 = 0,
                payment_amount_2 = 0,
                total_payment_amount = 0");
            */
            // Or if you want to completely delete all voter records:
             $pdo->exec("DELETE FROM voters");
            
            $pdo->commit();
            
            $message = "All voter records have been removed successfully!";
            
            // Redirect to admin page after 2 seconds
            header("Refresh: 2; URL=admin.php");
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error resetting records: " . $e->getMessage();
        }
    } elseif (isset($_POST['cancel_reset'])) {
        // Redirect immediately if cancel is clicked
        header("Location: admin.php");
        exit();
    }
}

// Get current statistics for confirmation message
$stmt = $pdo->query("SELECT COUNT(*) as total_voters FROM voters");
$total_voters = $stmt->fetch()['total_voters'];

$stmt = $pdo->query("SELECT COUNT(*) as voted FROM voters WHERE has_voted = TRUE");
$voted = $stmt->fetch()['voted'];

$stmt = $pdo->query("SELECT COUNT(*) as eligible FROM voters WHERE has_paid = TRUE");
$eligible = $stmt->fetch()['eligible'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset System - E-Ballot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .reset-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .warning-card {
            border-left: 6px solid #dc3545;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .stats-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .btn-reset {
            background: linear-gradient(45deg, #dc3545, #c82333);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
        .btn-cancel {
            background: linear-gradient(45deg, #6c757d, #5a6268);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <?php if ($message): ?>
                <div class="alert alert-success text-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <div class="mt-2">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        Redirecting to admin panel...
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center mb-4">
                    <h1 class="display-4 text-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </h1>
                    <h2 class="text-danger">System Reset</h2>
                    <p class="lead">Proceed with caution</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="card warning-card mb-4">
                    <div class="card-body">
                        <h4 class="card-title text-danger">
                            <i class="bi bi-shield-exclamation me-2"></i>
                            Warning: Destructive Action
                        </h4>
                        <p class="card-text">
                            You are about to reset all voter records. This action will:
                        </p>
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item text-danger">
                                <i class="bi bi-x-circle-fill me-2"></i>
                                Remove all payment records
                            </li>
                            <li class="list-group-item text-danger">
                                <i class="bi bi-x-circle-fill me-2"></i>
                                Clear all voting records
                            </li>
                            <li class="list-group-item text-danger">
                                <i class="bi bi-x-circle-fill me-2"></i>
                                Reset all voter eligibility status
                            </li>
                            <li class="list-group-item text-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                Preserve candidate information
                            </li>
                        </ul>
                        <div class="alert alert-warning mb-0">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <strong>Note:</strong> This action cannot be undone. Make sure you have backups if needed.
                        </div>
                    </div>
                </div>

                <div class="card stats-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up me-2"></i>
                            Current System Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="border rounded p-3 bg-white">
                                    <h3 class="text-primary"><?= $total_voters ?></h3>
                                    <small class="text-muted">Total Voters</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 bg-white">
                                    <h3 class="text-success"><?= $eligible ?></h3>
                                    <small class="text-muted">Eligible Voters</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-3 bg-white">
                                    <h3 class="text-info"><?= $voted ?></h3>
                                    <small class="text-muted">Votes Cast</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" id="resetForm">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="confirmation" class="form-label text-danger fw-bold">
                                    Type "Aggset94" to proceed:
                                </label>
                                <input type="text" class="form-control form-control-lg" 
                                       id="confirmation" name="confirmation" 
                                       placeholder="Type Aggset94 here" required
                                       oninput="validateConfirmation()">
                                <div class="form-text">
                                    This is case-sensitive. You must type exactly as shown.
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="cancel_reset" class="btn btn-secondary btn-cancel me-md-2">
                                    <i class="bi bi-arrow-left-circle me-2"></i>
                                    Cancel & Return
                                </button>
                                <button type="submit" name="confirm_reset" value="yes" 
                                        id="resetButton" class="btn btn-danger btn-reset" disabled>
                                    <i class="bi bi-trash-fill me-2"></i>
                                    Reset All Records
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function validateConfirmation() {
            const confirmationInput = document.getElementById('confirmation');
            const resetButton = document.getElementById('resetButton');
            const isConfirmed = confirmationInput.value === 'Aggset94';
            
            resetButton.disabled = !isConfirmed;
            
            if (isConfirmed) {
                confirmationInput.classList.remove('is-invalid');
                confirmationInput.classList.add('is-valid');
            } else {
                confirmationInput.classList.remove('is-valid');
                confirmationInput.classList.add('is-invalid');
            }
        }

        // Prevent form submission if not confirmed
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const confirmationInput = document.getElementById('confirmation');
            if (confirmationInput.value !== 'Aggset94') {
                e.preventDefault();
                alert('Please type "Aggset94" to proceed with the reset.');
                confirmationInput.focus();
            } else if (!confirm('Are you absolutely sure you want to reset ALL voter records? This action cannot be undone!')) {
                e.preventDefault();
            }
        });

        // Focus on confirmation input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const confirmationInput = document.getElementById('confirmation');
            if (confirmationInput) {
                confirmationInput.focus();
            }
        });
    </script>
</body>
</html>
