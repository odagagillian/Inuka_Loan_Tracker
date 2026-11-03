# Inuka Loan Repayment Tracker

## 1. Project Overview

The Inuka Loan Repayment Tracker is a simple web-based application designed to help a microfinance institution manage clients, loans and loan repayments.

The system allows users to register clients, create loans, record repayments, view repayment history and monitor outstanding loan balances.

## 2. Features

- Register clients using a unique client number and full name.
- Create loans linked to registered clients.
- Generate unique loan references.
- Record repayments with payment references and dates.
- View repayment history.
- Calculate total repayments and outstanding balances.
- Prevent duplicate payment references.
- Reject invalid repayments and overpayments.
- Display a dashboard with loan and repayment summaries.

## 3. Technologies Used

- PHP
- MySQL
- HTML
- CSS
- PDO for database access
- PHPUnit for automated testing
- Composer for dependency management

## 4. Installation and Setup

### Requirements

- XAMPP (Apache, PHP and MySQL)
- Composer
- Git

### Setup Instructions

1. Clone or download this repository into the XAMPP `htdocs` directory.
2. Start Apache and MySQL using the XAMPP Control Panel.
3. Open phpMyAdmin.
4. Create a database named `inuka_loan_app`.
5. Import `database/migrations/001_initial_schema.sql` into the database.
6. Open `config/database.php` and update the database credentials if necessary.
7. Open the project directory in a terminal.
8. Install the project dependencies:

```bash
composer install
```

9. Open the application in your browser:

```text
http://localhost/Inuka_loan_app/public/
```

### Running Automated Tests

1. Create a separate MySQL database named `inuka_loan_app_test`.
2. Import the same SQL schema into the test database.
3. Check `config/test_database.php` and ensure the test database connection settings are correct.
4. Run the tests from the project root:

```bash
vendor/bin/phpunit
```

The test database is separate from the application database. The automated tests clear and create fictional test records, so they should only be run against the test database.

## 5. Database Design

The application uses three main tables:

### Clients

Stores registered client information.

- `id` – Primary key.
- `client_number` – Unique client identification number.
- `full_name` – Client's full name.
- `created_at` – Registration timestamp.

### Loans

Stores loan information and links each loan to a client.

- `id` – Primary key.
- `loan_reference` – Unique loan reference.
- `client_id` – Foreign key referencing the clients table.
- `principal_amount` – Original loan amount.
- `total_due` – Total amount payable.
- `created_at` – Loan creation timestamp.

### Repayments

Stores repayment transactions.

- `id` – Primary key.
- `loan_id` – Foreign key referencing the loans table.
- `amount` – Repayment amount.
- `payment_reference` – Unique payment reference.
- `payment_date` – Date the repayment was made.
- `created_at` – Record creation timestamp.

## 6. Business Rules and Data Integrity

- Each client number must be unique.
- Each loan reference must be unique.
- Every loan must belong to an existing client.
- Every repayment must belong to an existing loan.
- Repayment amounts must be greater than zero.
- A repayment cannot exceed the outstanding balance.
- A payment reference cannot be reused.
- Outstanding balance is calculated as:

`Outstanding Balance = Total Amount Due - Total Repayments`

- The outstanding balance is calculated from repayment records rather than stored separately, reducing the risk of inconsistent balances.
- Database transactions and row locking are used when recording repayments to help prevent concurrent payments from exceeding the outstanding balance.
- Foreign keys and unique constraints help maintain database integrity.

## 7. Automated Tests

The application includes three automated tests using PHPUnit:

1. **Successful repayment:** Verifies that a KES 3,000 repayment against a KES 10,000 outstanding balance leaves KES 7,000.
2. **Duplicate payment reference:** Verifies that the same payment reference cannot be recorded twice.
3. **Overpayment prevention:** Verifies that an attempted KES 1,500 repayment against a KES 1,000 outstanding balance is rejected without changing the balance.

## 8. AI Tools Disclosure

AI tools were used to assist with planning, code drafting, debugging and documentation. The application was tested manually and with PHPUnit. The developer reviewed and tested the implementation and can explain the database design, business rules and application logic.

## 9. Test Data

The application can be tested using fictional client and loan information. No real customer information or payment provider integrations are included.
