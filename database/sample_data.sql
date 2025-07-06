-- Sample Data for Pet Clinic Application
-- Insert sample data for testing purposes

-- Insert sample doctors
INSERT INTO doctors (doctor_code, doctor_name, specialization, license_number, phone, email, address) VALUES
('DOC001', 'Dr. Sarah Johnson', 'Small Animal Medicine', 'VET12345', '+1-555-0101', 'sarah.johnson@petclinic.com', '123 Veterinary Street, City, State 12345'),
('DOC002', 'Dr. Michael Chen', 'Surgery', 'VET12346', '+1-555-0102', 'michael.chen@petclinic.com', '456 Animal Care Ave, City, State 12345'),
('DOC003', 'Dr. Emily Rodriguez', 'Emergency Medicine', 'VET12347', '+1-555-0103', 'emily.rodriguez@petclinic.com', '789 Pet Health Blvd, City, State 12345');

-- Insert sample animal owners
INSERT INTO animal_owners (owner_code, owner_name, address, telephone_number, email) VALUES
('PH001', 'John Smith', '123 Main Street, Anytown, State 12345', '+1-555-1001', 'john.smith@email.com'),
('PH002', 'Maria Garcia', '456 Oak Avenue, Somewhere, State 12345', '+1-555-1002', 'maria.garcia@email.com'),
('PH003', 'David Wilson', '789 Pine Road, Elsewhere, State 12345', '+1-555-1003', 'david.wilson@email.com'),
('PH004', 'Lisa Brown', '321 Elm Street, Nowhere, State 12345', '+1-555-1004', 'lisa.brown@email.com'),
('PH005', 'Robert Taylor', '654 Maple Drive, Anywhere, State 12345', '+1-555-1005', 'robert.taylor@email.com');

-- Insert sample animals
INSERT INTO animals (animal_code, animal_name, owner_id, identifying_signs, species, race, age, gender, date_of_birth, weight) VALUES
('ANI001', 'Buddy', 1, 'Brown spot on forehead', 'dog', 'Golden Retriever', 3, 'male', '2021-03-15', 28.5),
('ANI002', 'Whiskers', 1, 'White paws', 'cat', 'Persian', 2, 'female', '2022-01-20', 4.2),
('ANI003', 'Max', 2, 'Black collar', 'dog', 'German Shepherd', 5, 'male', '2019-07-10', 35.0),
('ANI004', 'Luna', 2, 'Blue eyes', 'cat', 'Siamese', 1, 'female', '2023-05-12', 3.8),
('ANI005', 'Charlie', 3, 'Floppy ears', 'dog', 'Beagle', 4, 'male', '2020-09-08', 12.3),
('ANI006', 'Bella', 4, 'Long tail', 'cat', 'Maine Coon', 6, 'female', '2018-11-25', 6.1),
('ANI007', 'Rocky', 5, 'Scar on left leg', 'dog', 'Bulldog', 7, 'male', '2017-04-03', 22.8);

-- Insert sample medicines
INSERT INTO medicines (medicine_code, medicine_name, description, dosage_form, strength, manufacturer, stock_quantity, unit_price, expiry_date) VALUES
('OB001', 'Amoxicillin', 'Antibiotic for bacterial infections', 'tablet', '250mg', 'VetPharm Inc.', 50, 2.50, '2025-12-31'),
('OB002', 'Metacam', 'Anti-inflammatory pain relief', 'liquid', '1.5mg/ml', 'Boehringer Ingelheim', 25, 15.75, '2025-08-15'),
('OB003', 'Frontline Plus', 'Flea and tick prevention', 'drops', '0.67ml', 'Merial', 30, 12.99, '2025-10-20'),
('OB004', 'Heartgard Plus', 'Heartworm prevention', 'tablet', '68mcg', 'Merial', 40, 8.50, '2025-06-30'),
('OB005', 'Prednisolone', 'Corticosteroid for inflammation', 'tablet', '5mg', 'VetMed Corp.', 60, 1.25, '2025-11-15'),
('OB006', 'Cerenia', 'Anti-nausea medication', 'tablet', '16mg', 'Zoetis', 20, 4.75, '2025-09-10'),
('OB007', 'Revolution', 'Parasite prevention', 'drops', '60mg', 'Zoetis', 35, 18.99, '2025-07-25');

-- Insert sample examinations
INSERT INTO examinations (animal_id, doctor_id, examination_date, important_disease_history, allergies, diagnosis, routine_drugs, action_taken, notes, status) VALUES
(1, 1, '2024-01-15', 'None', 'None known', 'Routine health check - all normal', 'Heartgard Plus monthly', 'Vaccinations updated, general examination performed', 'Healthy dog, continue current care routine', 'completed'),
(2, 2, '2024-01-20', 'Upper respiratory infection (2023)', 'Penicillin allergy', 'Mild conjunctivitis', 'Eye drops twice daily', 'Prescribed antibiotic eye drops, follow-up in 1 week', 'Monitor for improvement, avoid penicillin-based medications', 'completed'),
(3, 1, '2024-02-05', 'Hip dysplasia', 'None known', 'Hip dysplasia monitoring', 'Metacam as needed for pain', 'X-rays taken, pain management discussed', 'Continue monitoring, consider surgery if condition worsens', 'completed'),
(4, 3, '2024-02-10', 'None', 'Fish allergy', 'Routine kitten examination', 'Deworming medication', 'First vaccination series, deworming treatment', 'Healthy kitten, schedule next vaccination in 3 weeks', 'completed'),
(5, 2, '2024-02-15', 'Ear infections (recurring)', 'None known', 'Chronic ear infection', 'Ear cleaning solution, antibiotic drops', 'Ear cleaning performed, medication prescribed', 'Schedule follow-up in 2 weeks, consider allergy testing', 'pending'),
(6, 1, '2024-02-20', 'Kidney disease (early stage)', 'None known', 'Chronic kidney disease management', 'Special diet, kidney support supplements', 'Blood work performed, diet adjustment recommended', 'Monitor kidney function every 3 months', 'completed'),
(7, 3, '2024-02-25', 'Breathing difficulties', 'None known', 'Brachycephalic airway syndrome', 'Anti-inflammatory medication', 'Breathing assessment, weight management discussed', 'Monitor breathing, avoid overexertion and heat', 'pending');

-- Insert sample payments
INSERT INTO payments (examination_id, owner_id, amount, payment_date, payment_method, description, status) VALUES
(1, 1, 85.00, '2024-01-15', 'card', 'Routine examination and vaccinations for Buddy', 'paid'),
(2, 1, 45.50, '2024-01-20', 'cash', 'Eye treatment for Whiskers', 'paid'),
(3, 2, 120.00, '2024-02-05', 'card', 'Hip dysplasia examination and X-rays for Max', 'paid'),
(4, 2, 65.00, '2024-02-10', 'transfer', 'Kitten examination and first vaccinations for Luna', 'paid'),
(5, 3, 75.00, '2024-02-15', 'card', 'Ear infection treatment for Charlie', 'pending'),
(6, 4, 95.00, '2024-02-20', 'cash', 'Kidney disease management consultation for Bella', 'paid'),
(7, 5, 110.00, '2024-02-25', 'card', 'Breathing assessment for Rocky', 'pending');

-- Insert sample prescription medicines
INSERT INTO prescription_medicines (examination_id, medicine_id, quantity, dosage_instructions, duration_days) VALUES
(2, 1, 14, 'Apply 2 drops to affected eye twice daily', 7),
(3, 2, 30, 'Give 1ml once daily with food', 30),
(4, 5, 10, 'Give 1 tablet once daily for 5 days', 5),
(5, 1, 21, 'Give 1 tablet twice daily with food', 10),
(6, 5, 15, 'Give half tablet once daily', 15),
(7, 2, 15, 'Give 0.5ml once daily as needed for breathing difficulty', 15);

-- Insert admin user (password: admin123)
INSERT INTO users (username, password_hash, full_name, email, role, status) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@petclinic.com', 'admin', 'active');

-- Insert doctor users (password: doctor123)
INSERT INTO users (username, password_hash, full_name, email, role, status) VALUES
('dr.johnson', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sarah Johnson', 'sarah.johnson@petclinic.com', 'doctor', 'active'),
('dr.chen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Michael Chen', 'michael.chen@petclinic.com', 'doctor', 'active'),
('dr.rodriguez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Emily Rodriguez', 'emily.rodriguez@petclinic.com', 'doctor', 'active');

-- Insert staff user (password: staff123)
INSERT INTO users (username, password_hash, full_name, email, role, status) VALUES
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Clinic Staff', 'staff@petclinic.com', 'staff', 'active');

