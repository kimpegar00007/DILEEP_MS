-- DOLE DILP Monitoring System Database Schema
-- Database: dilp_monitoring

-- Create Users table (for authentication and role management)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'encoder', 'user') DEFAULT 'user',
    full_name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Beneficiaries table (Individual recipients)
CREATE TABLE beneficiaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    suffix VARCHAR(20),
    gender ENUM('Male', 'Female') NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    municipality VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20),
    project_name VARCHAR(255) NOT NULL,
    type_of_worker VARCHAR(100),
    amount_worth DECIMAL(15,2) NOT NULL,
    noted_findings TEXT,
    date_complied_by_proponent DATE,
    date_forwarded_to_ro6 DATE,
    rpmt_findings TEXT,
    date_approved DATE,
    date_forwarded_to_nofo DATE,
    date_turnover DATE,
    date_monitoring DATE,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('pending', 'approved', 'implemented', 'monitored') DEFAULT 'pending',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Create Proponents table (Group recipients)
CREATE TABLE proponents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    proponent_type ENUM('LGU-associated', 'Non-LGU-associated') NOT NULL,
    date_received DATE,
    noted_findings TEXT,
    control_number VARCHAR(50) UNIQUE,
    number_of_copies INT,
    date_copies_received DATE,
    district VARCHAR(100),
    proponent_name VARCHAR(255) NOT NULL,
    project_title VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    number_of_associations INT,
    total_beneficiaries INT NOT NULL,
    male_beneficiaries INT DEFAULT 0,
    female_beneficiaries INT DEFAULT 0,
    type_of_beneficiaries VARCHAR(255),
    category ENUM('Formation', 'Enhancement', 'Restoration') NOT NULL,
    recipient_barangays TEXT,
    letter_of_intent_date DATE,
    date_forwarded_to_ro6 DATE,
    rpmt_findings TEXT,
    date_complied_by_proponent DATE,
    date_complied_by_proponent_nofo DATE,
    date_forwarded_to_nofo DATE,
    date_approved DATE,
    date_check_release DATE,
    check_number VARCHAR(50),
    check_date_issued DATE,
    or_number VARCHAR(50),
    or_date_issued DATE,
    date_turnover DATE,
    date_implemented DATE,
    date_liquidated DATE,
    liquidation_deadline DATE,
    date_monitoring DATE,
    source_of_funds VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('pending', 'approved', 'implemented', 'liquidated', 'monitored') DEFAULT 'pending',
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Create Activity Log table (for audit trail)
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create Proponent Associations table (stores association details per proponent)
CREATE TABLE proponent_associations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    proponent_id INT NOT NULL,
    association_name VARCHAR(255) NOT NULL,
    association_address VARCHAR(500) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proponent_id) REFERENCES proponents(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_beneficiaries_municipality ON beneficiaries(municipality);
CREATE INDEX idx_beneficiaries_barangay ON beneficiaries(barangay);
CREATE INDEX idx_beneficiaries_status ON beneficiaries(status);
CREATE INDEX idx_beneficiaries_date_approved ON beneficiaries(date_approved);

CREATE INDEX idx_proponents_type ON proponents(proponent_type);
CREATE INDEX idx_proponents_district ON proponents(district);
CREATE INDEX idx_proponents_status ON proponents(status);
CREATE INDEX idx_proponents_control_number ON proponents(control_number);
CREATE INDEX idx_proponents_date_approved ON proponents(date_approved);

CREATE INDEX idx_proponent_associations_proponent ON proponent_associations(proponent_id);

CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_table ON activity_logs(table_name, record_id);

-- Create Fieldwork Schedule table (for Schedule of Activities / Fieldwork module)
CREATE TABLE IF NOT EXISTS fieldwork_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    location VARCHAR(500),
    assigned_user_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('pending', 'ongoing', 'completed', 'missed') DEFAULT 'pending',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE INDEX idx_fieldwork_status ON fieldwork_schedule(status);
CREATE INDEX idx_fieldwork_start_date ON fieldwork_schedule(start_date);
CREATE INDEX idx_fieldwork_end_date ON fieldwork_schedule(end_date);
CREATE INDEX idx_fieldwork_assigned_user ON fieldwork_schedule(assigned_user_id);
CREATE INDEX idx_fieldwork_created_by ON fieldwork_schedule(created_by);

-- Insert default admin user (password: admin123 - hashed with bcrypt)
-- Note: Change this password immediately after first login
INSERT INTO users (username, email, password, role, full_name) VALUES 
('admin', 'admin@dilp.gov.ph', '$2y$12$cxubeCJxgDoHaci9zO4Ud.b7uJ7PQQpWfOafrfLY2efdUQGNuRDLi', 'admin', 'System Administrator');

