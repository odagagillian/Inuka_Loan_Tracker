<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ClientService.php';

$clientService = new ClientService($pdo);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $clientNumber = $_POST['client_number'] ?? '';
    $fullName = $_POST['full_name'] ?? '';

    try {

        $clientService->createClient(
            $clientNumber,
            $fullName
        );

        $message = 'Client registered successfully!';
        $messageType = 'success';

    } catch (InvalidArgumentException $e) {

        $message = $e->getMessage();
        $messageType = 'error';

    } catch (PDOException $e) {

        error_log($e->getMessage());

        $message = 'Something went wrong. Please try again.';
        $messageType = 'error';
    }
}

$clients = $clientService->getAllClients();

?>

<!DOCTYPE html>
<html lang="en">
<link rel="stylesheet" href="css/style.css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Client Registration | Inuka Loan App</title>

</head>

<body>

    <h1>Client Registration</h1>

    <p>Register and manage loan clients.</p>

    <?php if ($message !== ''): ?>

        <p style="color: <?= $messageType === 'success' ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>

    <h2>Register New Client</h2>

    <form method="POST">

        <label for="client_number">Client Number</label>

        <input
            type="text"
            id="client_number"
            name="client_number"
            placeholder="e.g. CL001"
            required
        >

        <br><br>

        <label for="full_name">Full Name</label>

        <input
            type="text"
            id="full_name"
            name="full_name"
            placeholder="Enter client's full name"
            required
        >

        <br><br>

        <button type="submit">
            Register Client
        </button>

    </form> <br>
<div class="page-navigation">

    <a href="index.php" class="back-button">
        ← Dashboard
    </a>

    <a href="index.php" class="dashboard-button">
    </a>

</div>
    <hr>

    <h2>Registered Clients</h2>

    <?php if (count($clients) > 0): ?>

        <table border="1" cellpadding="10">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Number</th>
                    <th>Full Name</th>
                    <th>Date Registered</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($clients as $client): ?>

                    <tr>
                        <td><?= htmlspecialchars((string) $client['id']) ?></td>

                        <td><?= htmlspecialchars($client['client_number']) ?></td>

                        <td><?= htmlspecialchars($client['full_name']) ?></td>

                        <td><?= htmlspecialchars($client['created_at']) ?></td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <p>No clients have been registered yet.</p>

    <?php endif; ?>

</body>
</html>