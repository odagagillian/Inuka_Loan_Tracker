<?php

declare(strict_types=1);

class ClientService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Register a new client.
     */
    public function createClient(string $clientNumber, string $fullName): int
    {
        // Remove unnecessary spaces
        $clientNumber = trim($clientNumber);
        $fullName = trim($fullName);

        // Validate client number
        if ($clientNumber === '') {
            throw new InvalidArgumentException(
                'Client number is required.'
            );
        }

        // Validate full name
        if ($fullName === '') {
            throw new InvalidArgumentException(
                'Client full name is required.'
            );
        }

        try {
            $sql = "INSERT INTO clients (client_number, full_name)
                    VALUES (:client_number, :full_name)";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':client_number' => $clientNumber,
                ':full_name' => $fullName
            ]);

            return (int) $this->pdo->lastInsertId();

        } catch (PDOException $e) {

            // MySQL error 1062 means duplicate entry
            if (($e->errorInfo[1] ?? null) === 1062) {
                throw new InvalidArgumentException(
                    'This client number is already registered.'
                );
            }

            throw $e;
        }
    }

    /**
     * Retrieve all registered clients.
     */
    public function getAllClients(): array
    {
        $sql = "SELECT id, client_number, full_name, created_at
                FROM clients
                ORDER BY created_at DESC";

        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Retrieve one client using their database ID.
     */
    public function getClientById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = "SELECT id, client_number, full_name, created_at
                FROM clients
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $client = $stmt->fetch();

        return $client ?: null;
    }
}