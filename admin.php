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
    require_once 'vendor/autoload.php';
    
    try {
        $file = $_FILES['excel_file']['tmp_name'];
        $payment_source = $_POST['payment_source']; // 1 or 2
        
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        
        // Process the specific format of your Excel file
        $payments = [];
        $startProcessing = false;
        unset($data[0]);
        unset($data[1]);
        
        foreach ($data as $row) {
            // Skip empty rows and header rows
            if (count($row) < 3 || empty($row[1])) continue;
            
            // Look for the start of payment records (after header rows)
    /*        if (strpos($row[1], 'Adventist Grammar School') !== false) {
          } */
            
            
                $full_name = trim($row[1]);
                $amount = trim($row[2]);
                $amount= floatval(str_replace(",","",$amount));
                
                if ($amount > 0) {
                    // Generate voter_id from name (you might want a better method)
                    $voter_id = 'V' . substr(md5($full_name), 0, 8);
                    
                    if (!isset($payments[$voter_id])) {
                        $payments[$voter_id] = [];
                    }
                    
                    $payments[$voter_id][] = [
                        'voter_id' => $voter_id,
                        'full_name' => $full_name,
                        'amount' => $amount
                    ];
                }
            
        }
        
        // Process payments and update database
        $validVoters = [];
        
        foreach ($payments as $voter_id => $paymentRecords) {
            // For each payment source, we take the first payment (or sum if needed)
            if (count($paymentRecords) >= 1) {
                $payment = $paymentRecords[0]; // Take first payment
                $validVoters[] = [
                    'voter_id' => $voter_id,
                    'full_name' => $payment['full_name'],
                    'amount' => $payment['amount']
                ];
            }
        }
        
        // Update database based on payment source
        if ($payment_source == 1) {
/*             $stmt = $pdo->prepare("INSERT INTO voters (voter_id, full_name, payment_source_1, payment_amount_1) 
                                   VALUES (?, ?, TRUE, ?)
                                   ON CONFLICT (voter_id) DO UPDATE SET payment_source_1 = TRUE, payment_amount_1 = ?");
*/
$stmt = $pdo->prepare("INSERT INTO voters (voter_id, full_name, payment_source_1, payment_amount_1) 
                                   VALUES (?, ?, TRUE, ?)
                                   ON DUPLICATE KEY UPDATE payment_source_1 = TRUE, payment_amount_1 = ?");

        } else {
/*            $stmt = $pdo->prepare("INSERT INTO voters (voter_id, full_name, payment_source_2, payment_amount_2) 
                                   VALUES (?, ?, TRUE, ?)
                                   ON CONFLICT(voter_id) DO UPDATE SET payment_source_2 = TRUE, payment_amount_2 = ?");

*/
$stmt = $pdo->prepare("INSERT INTO voters (voter_id, full_name, payment_source_1, payment_amount_1) 
                                   VALUES (?, ?, TRUE, ?)
                                   ON DUPLICATE KEY UPDATE payment_source_2 = TRUE, payment_amount_2 = ?");



        }
        
        foreach ($validVoters as $voter) {
            $stmt->execute([
                $voter['voter_id'],
                $voter['full_name'],
                $voter['amount'],
                $voter['amount']
            ]);
        }
        
        // Update has_paid status for voters who paid in both sources
        $pdo->exec("UPDATE voters 
                   SET has_paid = TRUE, 
                       total_payment_amount = (payment_amount_1 + payment_amount_2)
                   WHERE payment_source_1 = TRUE AND payment_source_2 = TRUE");
        
        $message = "Excel file processed successfully. " . count($validVoters) . " payments recorded from source $payment_source.";
        
    } catch (Exception $e) {
        $message = "Error processing file: " . $e->getMessage();
    }
}

// Get voting statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_voters FROM voters WHERE has_paid = TRUE");
$total_voters = $stmt->fetch()['total_voters'];

$stmt = $pdo->query("SELECT COUNT(*) as voted FROM voters WHERE has_voted = TRUE");
$voted = $stmt->fetch()['voted'];

$stmt = $pdo->query("SELECT COUNT(*) as source1_only FROM voters WHERE payment_source_1 = TRUE AND payment_source_2 = FALSE");
$source1_only = $stmt->fetch()['source1_only'];

$stmt = $pdo->query("SELECT COUNT(*) as source2_only FROM voters WHERE payment_source_1 = FALSE AND payment_source_2 = TRUE");
$source2_only = $stmt->fetch()['source2_only'];

$stmt = $pdo->query("SELECT COUNT(*) as both_sources FROM voters WHERE payment_source_1 = TRUE AND payment_source_2 = TRUE");
$both_sources = $stmt->fetch()['both_sources'];

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
            <a class="navbar-brand" href="#"><img src="logo.jpg" class="img-rounded img-thumbnail" width="30" height="30" />Adventist Grammar School E-Ballot Admin</a>
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
                        <h5>Upload Payment Records</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="payment_source" class="form-label">Payment Source</label>
                                <select class="form-select" id="payment_source" name="payment_source" required>
                                    <option value="1">Payment Source 1 (First Excel Sheet)</option>
                                    <option value="2">Payment Source 2 (Second Excel Sheet)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Excel File</label>
                                <input class="form-control" type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                                <div class="form-text">Upload Excel file in the specified format</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Upload and Process</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Payment Statistics</h5>
                    </div>
                    <div class="card-body">
                        <p>Paid in Source 1 Only: <strong><?= $source1_only ?></strong></p>
                        <p>Paid in Source 2 Only: <strong><?= $source2_only ?></strong></p>
                        <p>Paid in Both Sources: <strong><?= $both_sources ?></strong></p>
                        <p class="fw-bold text-success">Eligible to Vote: <strong><?= $both_sources ?></strong></p>
                    </div>
                </div>
                
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
        
        <div class="card mb-4">
            <div class="card-header">
                <h5>Voter Payment Details</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Voter ID</th>
                                <th>Full Name</th>
                                <th>Source 1 Paid</th>
                                <th>Source 2 Paid</th>
                                <th>Total Amount</th>
                                <th>Eligible</th>
                                <th>Voted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT * FROM voters ORDER BY full_name");
                            $voters = $stmt->fetchAll();
                            foreach ($voters as $voter): ?>
                                <tr>
                                    <td><?= htmlspecialchars($voter['voter_id']) ?></td>
                                    <td><?= htmlspecialchars($voter['full_name']) ?></td>
                                    <td><?= $voter['payment_source_1'] ? 'Yes (₦' . $voter['payment_amount_1'] . ')' : 'No' ?></td>
                                    <td><?= $voter['payment_source_2'] ? 'Yes (₦' . $voter['payment_amount_2'] . ')' : 'No' ?></td>
                                    <td>₦<?= $voter['total_payment_amount'] ?></td>
                                    <td>
                                        <?php if ($voter['has_paid']): ?>
                                            <span class="badge bg-success">Eligible</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Not Eligible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($voter['has_voted']): ?>
                                            <span class="badge bg-info">Voted</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Not Voted</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
