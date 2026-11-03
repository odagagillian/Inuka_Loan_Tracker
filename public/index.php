<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Get the number of registered clients.
$clientCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM clients"
)->fetchColumn();

// Get the number of loans.
$loanCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM loans"
)->fetchColumn();

// Calculate total loan amount due.
$totalDue = $pdo->query(
    "SELECT COALESCE(SUM(total_due), 0.00) FROM loans"
)->fetchColumn();

// Calculate all repayments received.
$totalRepaid = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0.00) FROM repayments"
)->fetchColumn();

// Calculate outstanding balance.
$outstanding = (float) $totalDue - (float) $totalRepaid;

// Get recent repayments.
$recentStmt = $pdo->query(
    "SELECT
        repayments.amount,
        repayments.payment_reference,
        repayments.payment_date,
        loans.loan_reference,
        clients.full_name
     FROM repayments
     INNER JOIN loans ON repayments.loan_id = loans.id
     INNER JOIN clients ON loans.client_id = clients.id
     ORDER BY repayments.created_at DESC
     LIMIT 5"
);

$recentRepayments = $recentStmt->fetchAll();

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Inuka Loan Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container">

    <header>
        <h1>Inuka Loan App</h1>
        <p>Loan management and repayment monitoring system</p>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="clients.php">Clients</a>
            <a href="loans.php">Loans</a>
            <a href="repayments.php">Repayments</a>
        </nav>
    </header>

    <main>

        <section class="dashboard-heading">
            <h2>Dashboard Overview</h2>
            <p>Welcome to your loan management dashboard.</p>
        </section>

        <section class="stats-grid">

            <div class="stat-card">
                <h3>Registered Clients</h3>
                <p><?= $clientCount ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Loans</h3>
                <p><?= $loanCount ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Loan Amounts Due</h3>
                <p>KES <?= number_format((float) $totalDue, 2) ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Repayments</h3>
                <p>KES <?= number_format((float) $totalRepaid, 2) ?></p>
            </div>

            <div class="stat-card highlight">
                <h3>Outstanding Balances</h3>
                <p>KES <?= number_format($outstanding, 2) ?></p>
            </div>

        </section>

        <section class="card">
            <h2>Recent Repayments</h2>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Loan Reference</th>
                            <th>Payment Reference</th>
                            <th>Date</th>
                            <th>Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($recentRepayments as $repayment): ?>
                            <tr>
                                <td><?= escape($repayment['full_name']) ?></td>
                                <td><?= escape($repayment['loan_reference']) ?></td>
                                <td><?= escape($repayment['payment_reference']) ?></td>
                                <td><?= escape($repayment['payment_date']) ?></td>
                                <td>
                                    KES <?= number_format((float) $repayment['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($recentRepayments) === 0): ?>
                            <tr>
                                <td colspan="5">No repayments recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p>
                <a href="repayments.php">View all repayments</a>
            </p>
        </section>

        <section class="quick-links">
            <h2>Quick Actions</h2>

            <div class="quick-link-grid">
                <a href="clients.php" class="action-card">
                    <h3>Register Client</h3>
                    <p>Add a new client to the system.</p>
                </a>

                <a href="loans.php" class="action-card">
                    <h3>Create Loan</h3>
                    <p>Register a new client loan.</p>
                </a>

                <a href="repayments.php" class="action-card">
                    <h3>Record Repayment</h3>
                    <p>Enter and track loan repayments.</p>
                </a>
            </div>
        </section>

    </main>

    <footer>
        <p>Inuka Loan Tracker | Loan Management System</p>
    </footer>

</div>

</body>
</html>
