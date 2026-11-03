<?php

declare(strict_types=1);

class LoanService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a new loan for an existing client.
     */
    public function createLoan(
        int $clientId,
        string $principalAmount,
        string $totalDue
    ): int {

        // Validate client ID
        if ($clientId <= 0) {
            throw new InvalidArgumentException(
                'Please select a valid client.'
            );
        }
if ((float) $totalDue < (float) $principalAmount) {
    throw new InvalidArgumentException(
        'Total amount due cannot be less than the principal amount.'
    );
}
        // Validate monetary amounts
        if (
            !is_numeric($principalAmount) ||
            !is_numeric($totalDue) ||
            (float) $principalAmount <= 0 ||
            (float) $totalDue <= 0
        ) {
            throw new InvalidArgumentException(
                'Principal amount and total due must be greater than zero.'
            );
        }

        // Check that the client exists
        $clientCheck = $this->pdo->prepare(
            "SELECT id FROM clients WHERE id = :id"
        );

        $clientCheck->execute([
            ':id' => $clientId
        ]);

        if (!$clientCheck->fetch()) {
            throw new InvalidArgumentException(
                'The selected client does not exist.'
            );
        }

        // Generate a unique loan reference
        $loanReference = 'LN-' . date('Ymd') . '-' .
            strtoupper(bin2hex(random_bytes(3)));

        // Save the loan
        $sql = "INSERT INTO loans (
                    loan_reference,
                    client_id,
                    principal_amount,
                    total_due
                )
                VALUES (
                    :loan_reference,
                    :client_id,
                    :principal_amount,
                    :total_due
                )";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':loan_reference' => $loanReference,
            ':client_id' => $clientId,
            ':principal_amount' => $principalAmount,
            ':total_due' => $totalDue
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Retrieve all loans with their associated clients.
     */
    public function getAllLoans(): array
    {
        $sql = "SELECT
                    loans.id,
                    loans.loan_reference,
                    loans.principal_amount,
                    loans.total_due,
                    loans.created_at,
                    clients.client_number,
                    clients.full_name
                FROM loans
                INNER JOIN clients
                    ON loans.client_id = clients.id
                ORDER BY loans.created_at DESC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Retrieve a loan using its database ID.
     */
    public function getLoanById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = "SELECT *
                FROM loans
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $loan = $stmt->fetch();

        return $loan ?: null;
    }
}