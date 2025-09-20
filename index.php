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
            } elseif (!$vvoter['payment_source_1'] && $voter['payment_source_2']) {
                $error .= " (You have only paid in Source 2)";
            } else {
                $error .= " (No payments recorded)";
            }
            $_SESSION['voter_id'] = $voter_id;

        } elseif ($voter['has_voted']) {
            $_SESSION['voter_id'] = $voter_id;
        } else {
            $_SESSION['voter_id'] = $voter_id;
        }
    } else {
        $error = "Voter ID not found.";
    }
}

// Handle voting for both positions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote'])) {
    if (!$voter) {
        $error = "Please verify your voter ID first.";
    } else {
        // Get selected candidates for both positions
        $vote_president = $_POST['president'] ?? '';
        $vote_secretary = $_POST['secretary'] ?? '';
        $vote_treasurer = $_POST['treasurer'] ?? '';
        $vote_fin_secretary = $_POST['fin_secretary'] ?? '';
        $vote_pro = $_POST['pro'] ?? '';
        $vote_Ppresident = $_POST['Ppresident'] ?? '';
        // Validate that both positions are selected
        if (empty($vote_president) || empty($vote_secretary)) {
            $error = "Please select a candidate for both positions.";
        } else {
            $stmt = $pdo->prepare("UPDATE voters SET has_voted = TRUE, vote_president = ?, vote_secretary = ?,vote_Ppresident=?,vote_treasurer=?,vote_fin_secretary=?, vote_pro=? WHERE voter_id = ?");
            $stmt->execute([$vote_president, $vote_secretary,$vote_Ppresident,$vote_treasurer,$vote_fin_secretary,$vote_pro, $voter['voter_id']]);
            
            $success = "Your votes have been cast successfully!";
            $voter_name = $voter['full_name'];
            $president_vote = $vote_president;
            $secretary_vote = $vote_secretary;
            
            unset($_SESSION['voter_id']);
            $voter = null;
        }
    }
}

// Get candidates grouped by position
$stmt = $pdo->query("SELECT * FROM candidates ORDER BY position, name");
$candidates = $stmt->fetchAll();

// Group candidates by position
$candidates_by_position = [];
foreach ($candidates as $candidate) {
    $candidates_by_position[$candidate['position']][] = $candidate;
}
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
        .position-section {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            background-color: #f8f9fa;
        }
        .position-header {
            background-color: #0d6efd;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .candidate-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .candidate-card:hover {
            border-color: #0d6efd;
            background-color: #f0f8ff;
        }
        .candidate-card.selected {
            border-color: #198754;
            background-color: #d1e7dd;
        }
        .vote-summary {
            background-color: #d1e7dd;
            border: 2px solid #198754;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4">E-Ballot Voting System</h1>
            <p class="lead">Vote for Positions</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <h4>Voting Successful!</h4>
                <p><strong>Thank you, <?= htmlspecialchars($voter_name) ?>!</strong></p>
                <p>Your votes have been recorded:</p>
              
                <p class="mb-0">You have successfully completed the voting process.</p>
            </div>
        <?php endif; ?>
        
        <?php if (!$voter): ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="eligibility-info">
                        <h5>Voting Eligibility Requirements</h5>
                        <p>To be eligible to vote, you must have made payments in <strong>any</strong> payment sources.</p>
                        <p class="mb-0">You will be voting for <strong> positions</strong>: Vice President,General Secretary, PRO and treasurer.</p>
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
                </div>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4>Cast Your Votes</h4>
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
                            
                            <p class="lead text-center mb-4">Please select one candidate for each position:</p>
                            
                            <form method="POST" id="votingForm">


   <div class="position-section">
                                    <div class="position-header">
                                        <h3>President</h3>
                                        <p class="mb-0">Select one candidate for President</p>
                                    </div>
                                    
                                    <div class="row">
                                        <?php if (isset($candidates_by_position['President'])): ?>
                                            <?php foreach ($candidates_by_position['President'] as $candidate): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="candidate-card" onclick="selectCandidate('Ppresident', '<?= $candidate['name'] ?>')">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="Ppresident" 
                                                                   id="Ppresident_<?= $candidate['id'] ?>" 
                                                                   value="<?= htmlspecialchars($candidate['name']) ?>" required>
                                                            <label class="form-check-label w-100" for="Ppresident_<?= $candidate['id'] ?>">
                                                                <h5><?= htmlspecialchars($candidate['name']) ?></h5>
                                                                <p class="text-muted mb-0">Position: <?= htmlspecialchars($candidate['position']) ?></p>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning">No candidates available for  President position.</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>




                                <!-- President Position -->
                                <div class="position-section">
                                    <div class="position-header">
                                        <h3>Vice President</h3>
                                        <p class="mb-0">Select one candidate for Vice President</p>
                                    </div>
                                    
                                    <div class="row">
                                        <?php if (isset($candidates_by_position['Vice President'])): ?>
                                            <?php foreach ($candidates_by_position['Vice President'] as $candidate): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="candidate-card" onclick="selectCandidate('president', '<?= $candidate['name'] ?>')">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="president" 
                                                                   id="president_<?= $candidate['id'] ?>" 
                                                                   value="<?= htmlspecialchars($candidate['name']) ?>" required>
                                                            <label class="form-check-label w-100" for="president_<?= $candidate['id'] ?>">
                                                                <h5><?= htmlspecialchars($candidate['name']) ?></h5>
                                                                <p class="text-muted mb-0">Position: <?= htmlspecialchars($candidate['position']) ?></p>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning">No candidates available for Vice President position.</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                  <div class="position-section">
                                    <div class="position-header">
                                        <h3>Financial Secretary</h3>
                                        <p class="mb-0">Select one candidate for Financial Secretary</p>
                                    </div>
                                    
                                    <div class="row">
                                        <?php if (isset($candidates_by_position['Fin.Secrerary'])): ?>
                                            <?php foreach ($candidates_by_position['Fin.Secrerary'] as $candidate): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="candidate-card" onclick="selectCandidate('secretary', '<?= $candidate['name'] ?>')">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="fin_secretary" 
                                                                   id="fin_secretary_<?= $candidate['id'] ?>" 
                                                                   value="<?= htmlspecialchars($candidate['name']) ?>" required>
                                                            <label class="form-check-label w-100" for="fin_secretary_<?= $candidate['id'] ?>">
                                                                <h5><?= htmlspecialchars($candidate['name']) ?></h5>
                                                                <p class="text-muted mb-0">Position: <?= htmlspecialchars($candidate['position']) ?></p>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning">No candidates available for Financial Secretary position.</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>






                                  <div class="position-section">
                                    <div class="position-header">
                                        <h3>Treasurer</h3>
                                        <p class="mb-0">Select one candidate for Treasurer</p>
                                    </div>
                                    
                                    <div class="row">
                                        <?php if (isset($candidates_by_position['Treasurer'])): ?>
                                            <?php foreach ($candidates_by_position['Treasurer'] as $candidate): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="candidate-card" onclick="selectCandidate('treasurer', '<?= $candidate['name'] ?>')">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="treasurer" 
                                                                   id="treasurer_<?= $candidate['id'] ?>" 
                                                                   value="<?= htmlspecialchars($candidate['name']) ?>" required>
                                                            <label class="form-check-label w-100" for="treasurer_<?= $candidate['id'] ?>">
                                                                <h5><?= htmlspecialchars($candidate['name']) ?></h5>
                                                                <p class="text-muted mb-0">Position: <?= htmlspecialchars($candidate['position']) ?></p>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning">No candidates available for Treasurer position.</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>












                                  <div class="position-section">
                                    <div class="position-header">
                                        <h3>PRO</h3>
                                        <p class="mb-0">Select one candidate for PRO</p>
                                    </div>
                                    
                                    <div class="row">
                                        <?php if (isset($candidates_by_position['PRO'])): ?>
                                            <?php foreach ($candidates_by_position['PRO'] as $candidate): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="candidate-card" onclick="selectCandidate('pro', '<?= $candidate['name'] ?>')">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="pro" 
                                                                   id="pro_<?= $candidate['id'] ?>" 
                                                                   value="<?= htmlspecialchars($candidate['name']) ?>" required>
                                                            <label class="form-check-label w-100" for="pro_<?= $candidate['id'] ?>">
                                                                <h5><?= htmlspecialchars($candidate['name']) ?></h5>
                                                                <p class="text-muted mb-0">Position: <?= htmlspecialchars($candidate['position']) ?></p>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning">No candidates available for PRO position.</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>




















                                
                                <!-- Secretary Position -->
                                <div class="position-section">
                                    <div class="position-header">
                                        <h3>Secretary</h3>
                                        <p class="mb-0">Select one candidate for Secretary</p>
                                    </div>
                                    
                                    <div class="row">
                                        <?php if (isset($candidates_by_position['General Secretary'])): ?>
                                            <?php foreach ($candidates_by_position['General Secretary'] as $candidate): ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="candidate-card" onclick="selectCandidate('secretary', '<?= $candidate['name'] ?>')">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="secretary" 
                                                                   id="secretary_<?= $candidate['id'] ?>" 
                                                                   value="<?= htmlspecialchars($candidate['name']) ?>" required>
                                                            <label class="form-check-label w-100" for="secretary_<?= $candidate['id'] ?>">
                                                                <h5><?= htmlspecialchars($candidate['name']) ?></h5>
                                                                <p class="text-muted mb-0">Position: <?= htmlspecialchars($candidate['position']) ?></p>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning">No candidates available for Secretary position.</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Vote Summary -->
                                <div class="vote-summary" id="voteSummary" style="display: none;">
                                    <h5>Your Selected Candidates:</h5>
                                    <p><strong>President:</strong> <span id="selectedPPresident">Not selected</span></p>

                                    <p><strong>Vice President:</strong> <span id="selectedPresident">Not selected</span></p>
                                    <p><strong>General Secretary:</strong> <span id="selectedSecretary">Not selected</span></p>

                                    <p><strong>PRO:</strong> <span id="selectedPro">Not selected</span></p>
                                    <p><strong>Financial Secretary:</strong> <span id="selectedFinSecretary">Not selected</span></p>
                                    <p><strong> Treasurer:</strong> <span id="selectedtreasurer">Not selected</span></p>

                                    <div class="alert alert-info">
                                        <small>Please review your selections before submitting. You cannot change your votes after submission.</small>
                                    </div>
                                </div>
                                
                                <div class="mt-4 text-center">
                                    <button type="submit" name="vote" class="btn btn-success btn-lg px-5">Submit Votes</button>
                                    <button type="button" class="btn btn-outline-secondary btn-lg px-5 ms-2" onclick="resetForm()">Reset Selections</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5">
        <p>E-Ballot System &copy; <?= date('Y') ?> | Vote for Multiple Positions</p>
    </footer>

    <script>
        function selectCandidate(position, candidateName) {
            // Unselect all cards for this position
            document.querySelectorAll('.candidate-card').forEach(card => {
                if (card.querySelector(`input[name="${position}"]`)) {
                    card.classList.remove('selected');
                }
            });
            
            // Select the clicked card
            event.currentTarget.classList.add('selected');
            
            // Update the radio button
            const radio = event.currentTarget.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update summary
            updateVoteSummary();
        }
        
        function updateVoteSummary() {
            const presidentRadio = document.querySelector('input[name="president"]:checked');
            const PpresidentRadio = document.querySelector('input[name="Ppresident"]:checked');

            const secretaryRadio = document.querySelector('input[name="secretary"]:checked');
            const finsecretaryRadio = document.querySelector('input[name="fin_secretary"]:checked');
            const proRadio = document.querySelector('input[name="pro"]:checked');
            const treasurerRadio = document.querySelector('input[name="treasurer"]:checked');
            if (presidentRadio || secretaryRadio) {
                document.getElementById('voteSummary').style.display = 'block';
                 if (PpresidentRadio) {
                    document.getElementById('selectedPPresident').textContent = PpresidentRadio.value;
                }
                if (presidentRadio) {
                    document.getElementById('selectedPresident').textContent = presidentRadio.value;
                }
                
                if (secretaryRadio) {
                    document.getElementById('selectedSecretary').textContent = secretaryRadio.value;
                }
                  if (finsecretaryRadio) {
                    document.getElementById('selectedFinSecretary').textContent = finsecretaryRadio.value;
                }  if (proRadio) {
                    document.getElementById('selectedPro').textContent = proRadio.value;
                }  if (treasurerRadio) {
                    document.getElementById('selectedtreasurer').textContent = treasurerRadio.value;
                }
            }
        }
        
        function resetForm() {
            // Uncheck all radio buttons
            document.querySelectorAll('input[type="radio"]').forEach(radio => {
                radio.checked = false;
            });
            
            // Remove selected class from all cards
            document.querySelectorAll('.candidate-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Hide summary
            document.getElementById('voteSummary').style.display = 'none';
        }
        
        // Initialize form validation
        document.getElementById('votingForm').addEventListener('submit', function(e) {
            const presidentSelected = document.querySelector('input[name="president"]:checked');
            const secretarySelected = document.querySelector('input[name="secretary"]:checked');
            
            if (!presidentSelected || !secretarySelected) {
                e.preventDefault();
                alert('Please select a candidate for both positions before submitting your votes.');
            }
        });
        
        // Update summary when page loads if there are already selections
        document.addEventListener('DOMContentLoaded', function() {
            updateVoteSummary();
        });
    </script>
</body>
</html>
