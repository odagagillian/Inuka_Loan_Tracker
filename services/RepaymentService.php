<?php
declare(strict_types=1);

class RepaymentService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Convert a monetary amount into cents without using floats.
     */
    private function toCents(string $amount): int
    {
        if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException(
                'Enter a valid amount with no more than two decimal places.'
            );
        }

        $parts = explode('.', $amount);
        $whole = (int) $parts[0];
        $fraction = isset($parts[1])
            ? (int) str_pad($parts[1], 2, '0')
            : 0;

        return ($whole * 100) + $fraction;
    }

    /**
     * Record a repayment after checking the outstanding balance.
     */
    public function recordRepayment(
        int $loanId,
        string $amount,
        string $paymentReference,
        string $paymentDate
    ): int {
        $paymentReference = trim($paymentReference);
        $amount = trim($amount);

        if ($loanId <= 0) {
            throw new InvalidArgumentException('Please select a valid loan.');
        }

        if ($paymentReference === '') {
            throw new InvalidArgumentException('Payment reference is required.');
        }

        if (strlen($paymentReference) > 100) {
            throw new InvalidArgumentException('Payment reference is too long.');
        }

        $amountCents = $this->toCents($amount);

        if ($amountCents <= 0) {
            throw new InvalidArgumentException('Repayment amount must be greater than zero.');
        }

        $date = DateTime::createFromFormat('!Y-m-d', $paymentDate);

        if (!$date || $date->format('Y-m-d') !== $paymentDate) {
            throw new InvalidArgumentException('Please enter a valid payment date.');
        }

        try {
            $this->pdo->beginTransaction();

            // Lock the loan while checking and recording its repayment.
            $loanStmt = $this->pdo->prepare(
                "SELECT id, total_due
                 FROM loans
                 WHERE id = :loan_id
                 FOR UPDATE"
            );

            $loanStmt->execute([':loan_id' => $loanId]);
            $loan = $loanStmt->fetch();

            if (!$loan) {
                throw new InvalidArgumentException('The selected loan does not exist.');
            }

            // Calculate the total already repaid.
            $sumStmt = $this->pdo->prepare(
                "SELECT COALESCE(SUM(amount), 0.00)
                 FROM repayments
                 WHERE loan_id = :loan_id"
            );

            $sumStmt->execute([':loan_id' => $loanId]);
            $totalRepaid = (string) $sumStmt->fetchColumn();

            $totalDueCents = $this->toCents((string) $loan['total_due']);
            $totalRepaidCents = $this->toCents($totalRepaid);
            $outstandingCents = $totalDueCents - $totalRepaidCents;

            if ($amountCents > $outstandingCents) {
                throw new InvalidArgumentException(
                    'Repayment exceeds the outstanding balance of KES ' .
                    number_format($outstandingCents / 100, 2) . '.'
                );
            }

            // The database UNIQUE constraint prevents duplicate references.
            $insertStmt = $this->pdo->prepare(
                "INSERT INTO repayments
                    (loan_id, amount, payment_reference, payment_date)
                 VALUES
                    (:loan_id, :amount, :payment_reference, :payment_date)"
            );

            $insertStmt->execute([
                ':loan_id' => $loanId,
                ':amount' => $amount,
                ':payment_reference' => $paymentReference,
                ':payment_date' => $paymentDate
            ]);

            $repaymentId = (int) $this->pdo->lastInsertId();

            $this->pdo->commit();

            return $repaymentId;

        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new InvalidArgumentException(
                    'This payment reference has already been used.'
                );
            }

            throw $e;

        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Get every loan with its total repaid and outstanding balance.
     */
    public function getLoanBalances(): array
    {
        $sql = "
            SELECT
                loans.id,
                loans.loan_reference,
                loans.total_due,
                clients.client_number,
                clients.full_name,
                COALESCE(SUM(repayments.amount), 0.00) AS total_repaid,
                loans.total_due - COALESCE(SUM(repayments.amount), 0.00)
                    AS outstanding_balance
            FROM loans
            INNER JOIN clients ON loans.client_id = clients.id
            LEFT JOIN repayments ON loans.id = repayments.loan_id
            GROUP BY
                loans.id,
                loans.loan_reference,
                loans.total_due,
                clients.client_number,
                clients.full_name
            ORDER BY loans.created_at DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Display repayment history, including the related loan and client.
     */
    public function getRepaymentHistory(): array
    {
        $sql = "
            SELECT
                repayments.id,
                repayments.amount,
                repayments.payment_reference,
                repayments.payment_date,
                loans.loan_reference,
                clients.client_number,
                clients.full_name
            FROM repayments
            INNER JOIN loans ON repayments.loan_id = loans.id
            INNER JOIN clients ON loans.client_id = clients.id
            ORDER BY repayments.payment_date DESC, repayments.id DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }
}
