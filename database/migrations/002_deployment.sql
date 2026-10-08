ALTER TABLE invoices ADD COLUMN document_snapshot JSON NULL;
ALTER TABLE job_financials ADD COLUMN calculation_policy JSON NULL;

CREATE TABLE auth_attempts (
    attempt_key CHAR(64) NOT NULL PRIMARY KEY,
    failures INT UNSIGNED NOT NULL DEFAULT 0,
    window_start DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE integration_events (
    event_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    source VARCHAR(30) NOT NULL,
    external_id VARCHAR(160) NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    payload JSON NOT NULL,
    payload_hash CHAR(64) NOT NULL,
    status ENUM('pending','processed','failed') NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_error VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME NULL,
    UNIQUE KEY uq_event_source_id (source, external_id),
    KEY idx_event_work (status, attempts, event_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE voice_calls (
    voice_call_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(30) NOT NULL,
    external_id VARCHAR(120) NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    agent_id VARCHAR(120) NOT NULL,
    from_number VARCHAR(40) NOT NULL DEFAULT '',
    to_number VARCHAR(40) NOT NULL DEFAULT '',
    event_rank TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status VARCHAR(30) NOT NULL,
    summary TEXT NULL,
    transcript MEDIUMTEXT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_voice_provider_call (provider, external_id),
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inquiries (
    inquiry_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_id BIGINT UNSIGNED NOT NULL UNIQUE,
    company_id INT UNSIGNED NOT NULL,
    source VARCHAR(30) NOT NULL,
    contact JSON NOT NULL,
    priority_hint ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    status ENUM('new','promoted') NOT NULL DEFAULT 'new',
    job_id INT UNSIGNED NULL,
    promoted_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES integration_events(event_id) ON DELETE RESTRICT,
    FOREIGN KEY (company_id) REFERENCES companies(company_id) ON DELETE RESTRICT,
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing invoices can only be frozen as they look at migration time.
-- Their original issuance details cannot be reconstructed without historical records.
UPDATE invoices i
JOIN jobs j ON j.job_id=i.job_id
JOIN customers c ON c.customer_id=j.customer_id
LEFT JOIN companies co ON co.company_id=j.company_id
SET i.document_snapshot=JSON_OBJECT(
    'company_name',COALESCE(co.name,'Field Service CRM'),
    'company_phone',COALESCE(co.phone,''),'company_email',COALESCE(co.email,''),
    'company_address',COALESCE(co.address,''),'company_logo',NULL,
    'customer_name',c.full_name,'customer_phone',c.phone,'customer_email',c.email,
    'address_line1',c.address_line1,'address_line2',c.address_line2,
    'city',c.city,'state',c.state,'zip',c.zip,
    'job_type',j.job_type,'job_description',j.description
)
WHERE i.document_snapshot IS NULL;
