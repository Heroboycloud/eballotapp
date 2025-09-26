<?php
session_start();
require_once 'config.php';

// Check if voter is logged in
$voter = null;
if (isset($_SESSION['voter_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM voters WHERE voter_id = ?");
    $stmt->execute([$_SESSION['voter_id']]);
    $voter = $stmt->fetch();
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['voter_id'])) {
    $voter_id = $_POST['voter_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM voters WHERE voter_id = ?");
    $stmt->execute([$voter_id]);
    $voter = $stmt->fetch();
    
    if ($voter) {
        if (!$voter['has_paid']) {
            $error = "You are not eligible to vote. You need to have paid in both payment sources.";
            if ($voter['payment_source_1'] && !$voter['payment_source_2']) {
                $error .= " (You have only paid in Source 1)";
            } elseif (!$voter['payment_source_1'] && $voter['payment_source_2']) {
                $error .= " (You have only paid in Source 2)";
            } else {
                $error .= " (No payments recorded)";
            }
//            $voter = null;
            $_SESSION['voter_id'] = $voter_id;

        } elseif ($voter['has_voted']) {
//            $error = "You have already voted.";
//           $voter = null;
            $_SESSION['voter_id'] = $voter_id;
        } else {
            $_SESSION['voter_id'] = $voter_id;
        }
    } else {
        $error = "Voter ID not found.";
    }
}

// Handle voting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote'])) {
    if (!$voter) {
        $error = "Please verify your voter ID first.";
    } else {
        $candidate = $_POST['candidate'];
        
        $stmt = $pdo->prepare("UPDATE voters SET has_voted = TRUE, vote_candidate = ? WHERE voter_id = ?");
        $stmt->execute([$candidate, $voter['voter_id']]);
        
        $success = "Your vote has been cast successfully!";
        unset($_SESSION['voter_id']);
        $voter = null;
    }
}

// Get candidates
$stmt = $pdo->query("SELECT * FROM candidates");
$candidates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ballot Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero-section { 
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1557683316-973673baf926?ixlib=rb-4.0.3');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 40px;
        }
        .eligibility-info {
            background-color: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container text-center">
	    <img src="logo.jpg" class="img-thumbnail" width="50" height="50" />
            <h1 class="display-4">E-Ballot Voting System</h1>
            <p class="lead">Secure online voting - Payment verification required</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (!$voter): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="eligibility-info">
                        <h5>Voting Eligibility Requirements</h5>
                        <p>To be eligible to vote, you must have made payments in <strong>both</strong> payment sources.</p>
                        <p class="mb-0">If you have only paid in one source, please contact the administrator.</p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h4>Verify Your Eligibility</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="voter_id" class="form-label">Voter ID</label>
                                    <input type="text" class="form-control" id="voter_id" name="voter_id" required 
                                           placeholder="Enter your assigned Voter ID">
                                    <div class="form-text">Your Voter ID should have been provided to you.</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Check Eligibility</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Don't know your Voter ID?</h5>
                        </div>
                        <div class="card-body">
                            <p>If you don't know your Voter ID, please contact the election administrator with your full name.</p>
                            <p class="mb-0">Your Voter ID is typically generated from your name and payment records.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4>Cast Your Vote</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <h5>Eligibility Confirmed!</h5>
                                <p>Welcome, <strong><?= htmlspecialchars($voter['full_name']) ?></strong>!</p>
                                <p class="mb-0">Payment Status: 
                                    <span class="badge bg-success">Source 1: ₦<?= $voter['payment_amount_1'] ?></span>
                                    <span class="badge bg-success">Source 2: ₦<?= $voter['payment_amount_2'] ?></span>
                                    <span class="badge bg-info">Total: ₦<?= $voter['total_payment_amount'] ?></span>
                                </p>
                            </div>
                            
                            <p>Please select your preferred candidate:</p>
                            
                            <form method="POST">
                                <div class="row">
                                    <?php foreach ($candidates as $candidate): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check card">
                                                <div class="card-body">
                                                    <input class="form-check-input" type="radio" name="candidate" 
                                                           id="candidate<?= $candidate['id'] ?>" 
                                                           value="<?= htmlspecialchars($candidate['name']) ?>" required>
                                                    <label class="form-check-label w-100" for="candidate<?= $candidate['id'] ?>">
                                                        <strong><?= htmlspecialchars($candidate['name']) ?></strong><br>
                                                        <small class="text-muted">Position: <?= htmlspecialchars($candidate['position']) ?></small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" name="vote" class="btn btn-success btn-lg w-100">Cast My Vote</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5">
        <p>E-Ballot System &copy; <?= date('Y') ?> | Payment Verification Required</p>
    </footer>
</body>
</html>
