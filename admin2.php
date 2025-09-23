<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

require_once 'config.php';

// Handle Excel upload
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    require_once 'vendor/autoload.php'; // Composer autoload for PhpSpreadsheet
    
    try {
        $file = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        
        // Remove header row
        array_shift($data);
        
        // Track payments by voter_id
        $payments = [];
        $validVoters = [];
        
        foreach ($data as $row) {
            if (count($row) >= 5) {
                $voter_id = $row[0];
                $amount = floatval($row[4]);
                
                // Check if payment is valid (assuming > 0 is valid)
                if ($amount > 0) {
                    if (!isset($payments[$voter_id])) {
                        $payments[$voter_id] = [];
                    }
                    $payments[$voter_id][] = [
                        'full_name' => $row[1],
                        'email' => $row[2],
                        'payment_date' => $row[3],
                        'payment_amount' => $amount
                    ];
                }
            }
        }
        
        // Determine valid voters (those with exactly one valid payment)
        foreach ($payments as $voter_id => $paymentRecords) {
            if (count($paymentRecords) === 1) {
                $payment = $paymentRecords[0];
                $validVoters[] = [
                    'voter_id' => $voter_id,
                    'full_name' => $payment['full_name'],
                    'email' => $payment['email'],
                    'payment_date' => $payment['payment_date'],
                    'payment_amount' => $payment['payment_amount']
                ];
            }
        }
        
        // Update database with valid voters
/*        $stmt = $pdo->prepare("INSERT INTO voters (voter_id, full_name, email, has_paid, payment_amount, payment_date) 
                               VALUES (?, ?, ?, TRUE, ?, ?)
                               ON DUPLICATE KEY UPDATE has_paid = TRUE, payment_amount = ?, payment_date = ?");
       */
$stmt = $pdo->prepare("INSERT INTO voters (voter_id, full_name, email, has_paid, payment_amount, payment_date) VALUES (?, ?, ?, TRUE, ?, ?) ON CONFLICT (voter_id) DO UPDATE SET has_paid = TRUE,payment_amount = ?,payment_date = ?");
        foreach ($validVoters as $voter) {
            $stmt->execute([
                $voter['voter_id'],
                $voter['full_name'],
                $voter['email'],
                $voter['payment_amount'],
                $voter['payment_date'],
                $voter['payment_amount'],
                $voter['payment_date']
            ]);
        }
        
        $message = "Excel file processed successfully. " . count($validVoters) . " valid voters found.";
        
    } catch (Exception $e) {
        $message = "Error processing file: " . $e->getMessage();
    }
}

// Get voting statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_voters FROM voters WHERE has_paid = TRUE");
$total_voters = $stmt->fetch()['total_voters'];

$stmt = $pdo->query("SELECT COUNT(*) as voted FROM voters WHERE has_voted = TRUE");
$voted = $stmt->fetch()['voted'];

$stmt = $pdo->query("SELECT c.name, c.position, COUNT(v.vote_candidate) as votes 
                     FROM candidates c 
                     LEFT JOIN voters v ON c.name = v.vote_candidate 
                     GROUP BY c.name, c.position");
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - E-Ballot System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">E-Ballot Admin</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Admin Dashboard</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Upload Voter Payments</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Excel File</label>
                                <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">Excel format: Voter ID, Full Name, Email, Payment Date, Amount</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload and Process</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Voting Statistics</h5>
                    </div>
                    <div class="card-body">
                        <p>Total Eligible Voters: <strong><?= $total_voters ?></strong></p>
                        <p>Voters Who Have Voted: <strong><?= $voted ?></strong></p>
                        <p>Voting Percentage: <strong><?= $total_voters ? round(($voted / $total_voters) * 100, 2) : 0 ?>%</strong></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Election Results</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Position</th>
                            <th>Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td><?= htmlspecialchars($result['name']) ?></td>
                                <td><?= htmlspecialchars($result['position']) ?></td>
                                <td><?= $result['votes'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
