<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../services/RepaymentService.php';

final class RepaymentServiceTest extends TestCase
{
    private PDO $pdo;
    private RepaymentService $repaymentService;
    private int $loanId;

    protected function setUp(): void
    {
        require __DIR__ . '/../config/test_database.php';

        $this->pdo = $testPdo;
        $this->repaymentService = new RepaymentService($this->pdo);

        // Clear previous test records.
        $this->pdo->exec('DELETE FROM repayments');
        $this->pdo->exec('DELETE FROM loans');
        $this->pdo->exec('DELETE FROM clients');

        // Create a fictional test client.
        $clientStmt = $this->pdo->prepare(
            "INSERT INTO clients (client_number, full_name)
             VALUES (:number, :name)"
        );

        $clientStmt->execute([
            ':number' => 'TEST001',
            ':name' => 'Test Client'
        ]);

        $clientId = (int) $this->pdo->lastInsertId();

        // Create a test loan.
        $loanStmt = $this->pdo->prepare(
            "INSERT INTO loans
                (loan_reference, client_id, principal_amount, total_due)
             VALUES
                (:reference, :client_id, :principal, :total_due)"
        );

        $loanStmt->execute([
            ':reference' => 'TEST-LOAN-001',
            ':client_id' => $clientId,
            ':principal' => '10000.00',
            ':total_due' => '10000.00'
        ]);

        $this->loanId = (int) $this->pdo->lastInsertId();
    }

    public function testSuccessfulRepaymentUpdatesOutstandingBalance(): void
    {
        $this->repaymentService->recordRepayment(
            $this->loanId,
            '3000.00',
            'TEST-PAY-001',
            date('Y-m-d')
        );

        $stmt = $this->pdo->prepare(
            "SELECT total_due - (
                SELECT COALESCE(SUM(amount), 0)
                FROM repayments
                WHERE loan_id = loans.id
             ) AS outstanding
             FROM loans
             WHERE id = :loan_id"
        );

        $stmt->execute([':loan_id' => $this->loanId]);

        $this->assertSame('7000.00', $stmt->fetchColumn());
    }

    public function testDuplicatePaymentReferenceIsRejected(): void
    {
        $this->repaymentService->recordRepayment(
            $this->loanId,
            '3000.00',
            'TEST-PAY-DUPLICATE',
            date('Y-m-d')
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'This payment reference has already been used.'
        );

        $this->repaymentService->recordRepayment(
            $this->loanId,
            '1000.00',
            'TEST-PAY-DUPLICATE',
            date('Y-m-d')
        );
    }

    public function testOverpaymentIsRejectedWithoutChangingBalance(): void
    {
        // Change the loan's total due to KES 1,000.
        $stmt = $this->pdo->prepare(
            "UPDATE loans SET total_due = '1000.00' WHERE id = :id"
        );

        $stmt->execute([':id' => $this->loanId]);

        try {
            $this->repaymentService->recordRepayment(
                $this->loanId,
                '1500.00',
                'TEST-PAY-OVERPAY',
                date('Y-m-d')
            );

            $this->fail('An overpayment should have been rejected.');

        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString(
                'exceeds the outstanding balance',
                $e->getMessage()
            );
        }

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM repayments WHERE loan_id = :loan_id"
        );

        $countStmt->execute([':loan_id' => $this->loanId]);

        $this->assertSame(0, (int) $countStmt->fetchColumn());

        $balanceStmt = $this->pdo->prepare(
            "SELECT total_due FROM loans WHERE id = :loan_id"
        );

        $balanceStmt->execute([':loan_id' => $this->loanId]);

        $this->assertSame('1000.00', $balanceStmt->fetchColumn());
    }
}
