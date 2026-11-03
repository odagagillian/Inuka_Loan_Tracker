<?php

require_once __DIR__ . '/../config/database.php';

$loanServiceFile = null;
$clientServiceFile = null;

$servicePaths = [
    __DIR__ . '/../services/LoanService.php',
    __DIR__ . '/../services/loanService.php',
    __DIR__ . '/../Services/LoanService.php',
    __DIR__ . '/../Services/loanService.php',
];

$clientPaths = [
    __DIR__ . '/../services/ClientService.php',
    __DIR__ . '/../services/clientService.php',
    __DIR__ . '/../Services/ClientService.php',
    __DIR__ . '/../Services/clientService.php',
];

foreach ($servicePaths as $path) {
    if (file_exists($path)) {
        $loanServiceFile = $path;
        break;
    }
}

foreach ($clientPaths as $path) {
    if (file_exists($path)) {
        $clientServiceFile = $path;
        break;
    }
}

if ($loanServiceFile !== null) {
    require_once $loanServiceFile;
}

if ($clientServiceFile !== null) {
    require_once $clientServiceFile;
}

if (!class_exists('LoanService')) {
    throw new RuntimeException('LoanService class could not be loaded.');
}

if (!class_exists('ClientService')) {
    throw new RuntimeException('ClientService class could not be loaded.');
}

$loanService = new LoanService($pdo);
$clientService = new ClientService($pdo);

$message = '';
$messageType = '';

// Retrieve registered clients
$clients = $clientService->getAllClients();

// Process loan registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $clientId = (int) ($_POST['client_id'] ?? 0);
    $principalAmount = trim($_POST['principal_amount'] ?? '');
    $totalDue = trim($_POST['total_due'] ?? '');

    try {

        $loanService->createLoan(
            $clientId,
            $principalAmount,
            $totalDue
        );

        $message = 'Loan created successfully!';
        $messageType = 'success';

    } catch (InvalidArgumentException $e) {

        $message = $e->getMessage();
        $messageType = 'error';

    } catch (PDOException $e) {

        error_log($e->getMessage());

        $message = 'Unable to create loan. Please try again.';
        $messageType = 'error';
    }
}

// Retrieve all loans
$loans = $loanService->getAllLoans();

?>

<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" href="css/style.css">
<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Loan Management | Inuka Loan Tracker</title>

</head>

<body>

    <h1>Loan Management</h1>

    <p>Create and manage client loans.</p>

    <?php if ($message !== ''): ?>

        <p style="color: <?= $messageType === 'success' ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>


    <h2>Create New Loan</h2>

    <?php if (count($clients) > 0): ?>

        <form method="POST">

            <label for="client_id">Select Client</label>

            <select id="client_id" name="client_id" required>

                <option value="">-- Select a client --</option>

                <?php foreach ($clients as $client): ?>

                    <option value="<?= (int) $client['id'] ?>">

                        <?= htmlspecialchars(
                            $client['client_number'] . ' - ' . $client['full_name']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <br><br>


            <label for="principal_amount">Principal Amount (KES)</label>

            <input
                type="number"
                id="principal_amount"
                name="principal_amount"
                min="0.01"
                step="0.01"
                placeholder="e.g. 10000"
                required
            >

            <br><br>


            <label for="total_due">Total Amount Due (KES)</label>

            <input
                type="number"
                id="total_due"
                name="total_due"
                min="0.01"
                step="0.01"
                placeholder="e.g. 12000"
                required
            >

            <br><br>


            <button type="submit">
                Create Loan
            </button>

        </form>

    <?php else: ?>

        <p>
            No clients are registered yet.
            Please register a client before creating a loan.
        </p>

    <?php endif; ?>


    <hr>
<div class="page-navigation">

    <a href="clients.php" class="back-button">
        ←Clients
    </a> <br>

    <a href="index.php" class="dashboard-button">
        Dashboard
    </a>

</div>

    <h2>Registered Loans</h2>

    <?php if (count($loans) > 0): ?>

        <table border="1" cellpadding="10">

            <thead>

                <tr>
                    <th>Loan Reference</th>
                    <th>Client Number</th>
                    <th>Client Name</th>
                    <th>Principal (KES)</th>
                    <th>Total Due (KES)</th>
                    <th>Date Created</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($loans as $loan): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($loan['loan_reference']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($loan['client_number']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($loan['full_name']) ?>
                        </td>

                        <td>
                            <?= number_format((float) $loan['principal_amount'], 2) ?>
                        </td>

                        <td>
                            <?= number_format((float) $loan['total_due'], 2) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($loan['created_at']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <p>No loans have been registered yet.</p>

    <?php endif; ?>

</body>

</html>