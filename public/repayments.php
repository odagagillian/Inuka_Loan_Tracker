<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/RepaymentService.php';

$repaymentService = new RepaymentService($pdo);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);

        if (!$loanId) {
            throw new InvalidArgumentException('Please select a valid loan.');
        }

        $repaymentService->recordRepayment(
            $loanId,
            $_POST['amount'] ?? '',
            $_POST['payment_reference'] ?? '',
            $_POST['payment_date'] ?? ''
        );

        $message = 'Repayment recorded successfully.';
        $messageType = 'success';

    } catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
        $messageType = 'error';

    } catch (PDOException $e) {
        error_log($e->getMessage());
        $message = 'A database error occurred. Please try again.';
        $messageType = 'error';
    }
}

$loans = $repaymentService->getLoanBalances();
$history = $repaymentService->getRepaymentHistory();

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
    <title>Loan Repayments | Inuka Loan Tracker</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container">

    <header>
        <h1>Loan Repayments</h1>
        <p>Record and monitor client loan repayments.</p>

        <nav>
            <a href="index.php">Dashboard</a>
            <a href="clients.php">Clients</a>
            <a href="loans.php">Loans</a>
            <a href="repayments.php">Repayments</a>
        </nav>
    </header>

    <main>

        <?php if ($message !== ''): ?>
            <div class="message <?= escape($messageType) ?>">
                <?= escape($message) ?>
            </div>
        <?php endif; ?>

        <section class="card">
            <h2>Record a Repayment</h2>

            <form method="POST" action="repayments.php">

                <label for="loan_id">Select Loan</label>
                <select name="loan_id" id="loan_id" required>
                    <option value="">Choose a loan</option>

                    <?php foreach ($loans as $loan): ?>
                        <?php if ((float) $loan['outstanding_balance'] > 0): ?>
                            <option
                                value="<?= (int) $loan['id'] ?>"
                                data-balance="<?= escape((string) $loan['outstanding_balance']) ?>"
                            >
                                <?= escape($loan['loan_reference']) ?>
                                -
                                <?= escape($loan['client_number']) ?>
                                -
                                Outstanding: KES
                                <?= number_format((float) $loan['outstanding_balance'], 2) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>

                </select>

                <p id="balance-preview">
                    Select a loan to see its outstanding balance.
                </p>

                <label for="amount">Repayment Amount (KES)</label>
                <input
                    type="number"
                    name="amount"
                    id="amount"
                    min="0.01"
                    step="0.01"
                    required
                >

                <label for="payment_reference">Payment Reference</label>
                <input
                    type="text"
                    name="payment_reference"
                    id="payment_reference"
                    maxlength="100"
                    placeholder="e.g. PAY123456"
                    required
                >

                <label for="payment_date">Payment Date</label>
                <input
                    type="date"
                    name="payment_date"
                    id="payment_date"
                    value="<?= date('Y-m-d') ?>"
                    required
                >

                <button type="submit">Record Repayment</button>

            </form>
        </section> <br> 
<div class="page-navigation">

    <a href="loans.php" class="back-button">
        ← Previous
    </a>

    <a href="index.php" class="dashboard-button">
        Dashboard
    </a>

</div>
        <section class="card">
            <h2>Loan Balances</h2>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Loan Reference</th>
                            <th>Client</th>
                            <th>Total Due</th>
                            <th>Total Repaid</th>
                            <th>Outstanding</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td><?= escape($loan['loan_reference']) ?></td>
                                <td>
                                    <?= escape($loan['full_name']) ?>
                                    (<?= escape($loan['client_number']) ?>)
                                </td>
                                <td>
                                    KES <?= number_format((float) $loan['total_due'], 2) ?>
                                </td>
                                <td>
                                    KES <?= number_format((float) $loan['total_repaid'], 2) ?>
                                </td>
                                <td>
                                    KES <?= number_format((float) $loan['outstanding_balance'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($loans) === 0): ?>
                            <tr>
                                <td colspan="5">No loans have been registered yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2>Repayment History</h2>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Payment Reference</th>
                            <th>Loan Reference</th>
                            <th>Client</th>
                            <th>Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($history as $repayment): ?>
                            <tr>
                                <td><?= escape($repayment['payment_date']) ?></td>
                                <td><?= escape($repayment['payment_reference']) ?></td>
                                <td><?= escape($repayment['loan_reference']) ?></td>
                                <td><?= escape($repayment['full_name']) ?></td>
                                <td>
                                    KES <?= number_format((float) $repayment['amount'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($history) === 0): ?>
                            <tr>
                                <td colspan="5">No repayments have been recorded yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

</div>

<script src="js/app.js"></script>

</body>
</html>
