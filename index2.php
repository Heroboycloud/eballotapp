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
            $error = "You are not eligible to vote. Payment not verified.";
            $voter = null;
        } elseif ($voter['has_voted']) {
            $error = "You have already voted.";
            $voter = null;
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
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4">E-Ballot Voting System</h1>
            <p class="lead">Secure online voting for your organization</p>
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
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4>Verify Your Eligibility</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="voter_id" class="form-label">Voter ID</label>
                                    <input type="text" class="form-control" id="voter_id" name="voter_id" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Check Eligibility</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Cast Your Vote</h4>
                        </div>
                        <div class="card-body">
                            <p>Welcome, <strong><?= htmlspecialchars($voter['full_name']) ?></strong>!</p>
                            <p>Please select your preferred candidate:</p>
                            
                            <form method="POST">
                                <?php foreach ($candidates as $candidate): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="candidate" 
                                               id="candidate<?= $candidate['id'] ?>" value="<?= htmlspecialchars($candidate['name']) ?>" required>
                                        <label class="form-check-label" for="candidate<?= $candidate['id'] ?>">
                                            <strong><?= htmlspecialchars($candidate['name']) ?></strong> - <?= htmlspecialchars($candidate['position']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                
                                <button type="submit" name="vote" class="btn btn-success btn-lg w-100 mt-3">Cast Vote</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5">
        <p>E-Ballot System &copy; <?= date('Y') ?></p>
    </footer>
</body>
</html>
