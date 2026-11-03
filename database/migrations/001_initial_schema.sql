CREATE TABLE clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_number VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE loans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_reference VARCHAR(50) NOT NULL UNIQUE,
    client_id BIGINT UNSIGNED NOT NULL,
    principal_amount DECIMAL(12, 2) NOT NULL,
    total_due DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id)
        REFERENCES clients(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CHECK (principal_amount > 0),
    CHECK (total_due > 0)
) ENGINE=InnoDB;


CREATE TABLE repayments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loan_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    payment_reference VARCHAR(100) NOT NULL UNIQUE,
    payment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (loan_id)
        REFERENCES loans(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CHECK (amount > 0),

    INDEX idx_repayments_loan (loan_id)
) ENGINE=InnoDB;