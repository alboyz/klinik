-- Pet Shop Clinic Database Schema
-- Created for PHP Native Desktop Application

CREATE DATABASE IF NOT EXISTS pet_clinic;
USE pet_clinic;

-- Admin/Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin', 'doctor', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Doctors table
CREATE TABLE IF NOT EXISTS doctors (
    doctor_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    doctor_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Animal owners table
CREATE TABLE IF NOT EXISTS animal_owners (
    owner_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_code VARCHAR(20) UNIQUE NOT NULL, -- ph001 format
    owner_name VARCHAR(100) NOT NULL,
    address TEXT,
    telephone_number VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Animals table
CREATE TABLE IF NOT EXISTS animals (
    animal_id INT AUTO_INCREMENT PRIMARY KEY,
    animal_code VARCHAR(20) UNIQUE NOT NULL,
    animal_name VARCHAR(100) NOT NULL,
    owner_id INT NOT NULL,
    identifying_signs TEXT,
    species ENUM('cat', 'dog', 'bird', 'rabbit', 'other') NOT NULL,
    race VARCHAR(50),
    age INT,
    gender ENUM('male', 'female') NOT NULL,
    date_of_birth DATE,
    weight DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES animal_owners(owner_id) ON DELETE CASCADE
);

-- Medicine table
CREATE TABLE IF NOT EXISTS medicines (
    medicine_id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_code VARCHAR(20) UNIQUE NOT NULL, -- ob_001 format
    medicine_name VARCHAR(100) NOT NULL,
    description TEXT,
    dosage_form VARCHAR(50), -- tablet, liquid, injection, etc.
    strength VARCHAR(50),
    manufacturer VARCHAR(100),
    stock_quantity INT DEFAULT 0,
    unit_price DECIMAL(10,2),
    expiry_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Examinations table
CREATE TABLE IF NOT EXISTS examinations (
    examination_id INT AUTO_INCREMENT PRIMARY KEY,
    animal_id INT NOT NULL,
    doctor_id INT NOT NULL,
    examination_date DATE NOT NULL,
    important_disease_history TEXT,
    allergies TEXT,
    diagnosis TEXT,
    routine_drugs TEXT,
    action_taken TEXT,
    notes TEXT,
    status ENUM('pending', 'completed', 'follow_up') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (animal_id) REFERENCES animals(animal_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id) ON DELETE CASCADE
);

-- Medical records table (based on examinations)
CREATE TABLE IF NOT EXISTS medical_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    examination_id INT NOT NULL,
    animal_id INT NOT NULL,
    record_date DATE NOT NULL,
    important_disease_history TEXT,
    allergies TEXT,
    diagnosis TEXT,
    routine_drugs TEXT,
    action_taken TEXT,
    doctor_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (examination_id) REFERENCES examinations(examination_id) ON DELETE CASCADE,
    FOREIGN KEY (animal_id) REFERENCES animals(animal_id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    examination_id INT,
    owner_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'card', 'transfer', 'other') DEFAULT 'cash',
    description TEXT,
    status ENUM('pending', 'paid', 'partial', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (examination_id) REFERENCES examinations(examination_id) ON DELETE SET NULL,
    FOREIGN KEY (owner_id) REFERENCES animal_owners(owner_id) ON DELETE CASCADE
);

-- Prescription medicines (junction table for examinations and medicines)
CREATE TABLE IF NOT EXISTS prescription_medicines (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    examination_id INT NOT NULL,
    medicine_id INT NOT NULL,
    quantity INT NOT NULL,
    dosage_instructions TEXT,
    duration_days INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (examination_id) REFERENCES examinations(examination_id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@petclinic.com', 'admin');

-- Insert sample data
INSERT INTO doctors (doctor_name, specialization, license_number, phone, email) VALUES 
('Dr. Sarah Johnson', 'Small Animal Medicine', 'VET001', '+1234567890', 'sarah.johnson@petclinic.com'),
('Dr. Michael Chen', 'Surgery', 'VET002', '+1234567891', 'michael.chen@petclinic.com');

INSERT INTO animal_owners (owner_code, owner_name, address, telephone_number, email) VALUES 
('PH001', 'John Smith', '123 Main St, City', '+1234567892', 'john.smith@email.com'),
('PH002', 'Mary Johnson', '456 Oak Ave, City', '+1234567893', 'mary.johnson@email.com');

INSERT INTO medicines (medicine_code, medicine_name, description, dosage_form, strength, stock_quantity, unit_price) VALUES 
('OB001', 'Amoxicillin', 'Antibiotic for bacterial infections', 'Tablet', '250mg', 100, 5.50),
('OB002', 'Metacam', 'Anti-inflammatory pain relief', 'Liquid', '1.5mg/ml', 50, 25.00),
('OB003', 'Frontline', 'Flea and tick prevention', 'Spot-on', 'Standard', 75, 15.00);

