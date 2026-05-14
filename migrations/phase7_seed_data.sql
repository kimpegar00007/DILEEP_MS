-- Phase 7: Seed Data for Testing
-- Date: May 11, 2026
-- Description: Insert 5 proponents and 5 beneficiaries for each of the three provinces

-- ========================================
-- PROPONENTS SEED DATA
-- ========================================

-- Negros Occidental Proponents (5)
INSERT INTO proponents (
    control_number, proponent_name, proponent_type, district, recipient_barangays, 
    project_title, category, amount, total_beneficiaries, male_beneficiaries, female_beneficiaries,
    province, latitude, longitude, status, date_received, date_approved, date_implemented,
    beneficiary_full_name, type_of_beneficiaries, type_of_workers
) VALUES
('DILP-NOCFO-2026-001', 'Barangay Mansilingan Livelihood Association', 'LGU-associated', '1st District', 'Mansilingan, Bacolod City',
 'Banana Cue and Turon Vending Project', 'Formation', 150000.00, 25, 8, 17,
 'Negros Occidental', 10.6767, 122.9500, 'implemented', '2026-01-15', '2026-02-10', '2026-03-05',
 'Maria Santos, Juan Dela Cruz, Rosa Garcia', 'Displaced Workers, Informal Sector Workers', 'Street Vendors, Ambulant Vendors'),

('DILP-NOCFO-2026-002', 'Silay City Farmers Cooperative', 'Non-LGU-associated', '2nd District', 'Balaring, Silay City',
 'Organic Vegetable Production Enhancement', 'Enhancement', 200000.00, 30, 15, 15,
 'Negros Occidental', 10.7964, 123.0003, 'approved', '2026-01-20', '2026-02-25', NULL,
 'Pedro Martinez, Ana Lopez, Carlos Reyes', 'Farmers, Rural Workers', 'Agricultural Workers, Vegetable Growers'),

('DILP-NOCFO-2026-003', 'Talisay City Women Entrepreneurs', 'LGU-associated', '3rd District', 'Zone 14, Talisay City',
 'Sari-Sari Store and Food Vending', 'Formation', 180000.00, 20, 3, 17,
 'Negros Occidental', 10.7333, 122.9833, 'liquidated', '2025-11-10', '2025-12-15', '2026-01-20',
 'Luisa Fernandez, Gloria Ramos, Teresa Cruz', 'Women, Single Parents', 'Store Owners, Food Vendors'),

('DILP-NOCFO-2026-004', 'Victorias City Fisherfolk Association', 'Non-LGU-associated', '4th District', 'Zone 3, Victorias City',
 'Fishing Equipment and Boat Restoration', 'Restoration', 250000.00, 35, 28, 7,
 'Negros Occidental', 10.9000, 123.0667, 'monitored', '2025-10-05', '2025-11-20', '2025-12-28',
 'Roberto Aquino, Manuel Torres, Jose Villanueva', 'Fisherfolk, Coastal Workers', 'Fishermen, Boat Operators'),

('DILP-NOCFO-2026-005', 'Sagay City Youth Livelihood Group', 'LGU-associated', '5th District', 'Poblacion 1, Sagay City',
 'Motorcycle Taxi and Delivery Services', 'Formation', 175000.00, 15, 12, 3,
 'Negros Occidental', 10.8961, 123.4189, 'pending', '2026-02-01', NULL, NULL,
 'Mark Johnson, Kevin Santos, Ryan Dela Cruz', 'Youth, Out-of-School Youth', 'Drivers, Delivery Riders');

-- Negros Oriental Proponents (5)
INSERT INTO proponents (
    control_number, proponent_name, proponent_type, district, recipient_barangays,
    project_title, category, amount, total_beneficiaries, male_beneficiaries, female_beneficiaries,
    province, latitude, longitude, status, date_received, date_approved, date_implemented,
    beneficiary_full_name, type_of_beneficiaries, type_of_workers
) VALUES
('DILP-NORO-2026-001', 'Dumaguete City Vendors Association', 'LGU-associated', '1st District', 'Poblacion 1, Dumaguete City',
 'Mobile Food Cart and Vending Project', 'Formation', 160000.00, 22, 9, 13,
 'Negros Oriental', 9.3068, 123.3054, 'implemented', '2026-01-10', '2026-02-05', '2026-03-01',
 'Elena Gomez, Ricardo Bautista, Alma Rivera', 'Displaced Workers, Urban Poor', 'Food Vendors, Street Vendors'),

('DILP-NORO-2026-002', 'Bais City Fishermen Cooperative', 'Non-LGU-associated', '2nd District', 'Okiot, Bais City',
 'Fishing Gear and Cold Storage Enhancement', 'Enhancement', 220000.00, 28, 22, 6,
 'Negros Oriental', 9.5900, 123.1217, 'approved', '2026-01-18', '2026-02-20', NULL,
 'Antonio Mendoza, Francisco Silva, Eduardo Castro', 'Fisherfolk, Coastal Communities', 'Fishermen, Fish Processors'),

('DILP-NORO-2026-003', 'Tanjay City Women Weavers', 'LGU-associated', '3rd District', 'Poblacion, Tanjay City',
 'Traditional Weaving and Handicraft Production', 'Formation', 140000.00, 18, 2, 16,
 'Negros Oriental', 9.5167, 123.1500, 'liquidated', '2025-11-15', '2025-12-20', '2026-01-25',
 'Rosario Morales, Beatriz Santos, Carmen Diaz', 'Women, Indigenous People', 'Weavers, Artisans'),

('DILP-NORO-2026-004', 'Canlaon City Farmers Group', 'Non-LGU-associated', '1st District', 'Linothangan, Canlaon City',
 'Coffee and Cacao Processing Equipment', 'Restoration', 190000.00, 24, 14, 10,
 'Negros Oriental', 10.3833, 123.2000, 'monitored', '2025-10-10', '2025-11-25', '2026-01-05',
 'Miguel Hernandez, Pablo Ramirez, Luis Gutierrez', 'Farmers, Highland Communities', 'Coffee Farmers, Cacao Growers'),

('DILP-NORO-2026-005', 'Guihulngan City Youth Entrepreneurs', 'LGU-associated', '2nd District', 'Poblacion, Guihulngan City',
 'Computer Repair and Internet Cafe Services', 'Formation', 155000.00, 12, 8, 4,
 'Negros Oriental', 10.1167, 123.2667, 'pending', '2026-02-05', NULL, NULL,
 'Jason Cruz, Daniel Reyes, Michael Torres', 'Youth, Tech-Savvy Workers', 'Technicians, Service Providers');

-- Siquijor Proponents (5)
INSERT INTO proponents (
    control_number, proponent_name, proponent_type, district, recipient_barangays,
    project_title, category, amount, total_beneficiaries, male_beneficiaries, female_beneficiaries,
    province, latitude, longitude, status, date_received, date_approved, date_implemented,
    beneficiary_full_name, type_of_beneficiaries, type_of_workers
) VALUES
('DILP-SIQ-2026-001', 'Siquijor Town Tourism Guides Association', 'LGU-associated', 'Siquijor District', 'Poblacion, Siquijor',
 'Tour Guide Training and Equipment', 'Formation', 130000.00, 16, 10, 6,
 'Siquijor', 9.2000, 123.5167, 'implemented', '2026-01-12', '2026-02-08', '2026-03-03',
 'Ferdinand Navarro, Angelica Perez, Benjamin Flores', 'Tourism Workers, Local Guides', 'Tour Guides, Hospitality Workers'),

('DILP-SIQ-2026-002', 'Larena Fisherfolk Cooperative', 'Non-LGU-associated', 'Siquijor District', 'Cangmangki, Larena',
 'Fishing Boat and Net Enhancement Project', 'Enhancement', 185000.00, 20, 16, 4,
 'Siquijor', 9.2667, 123.6167, 'approved', '2026-01-22', '2026-02-28', NULL,
 'Rodrigo Salazar, Ernesto Valdez, Alfredo Jimenez', 'Fisherfolk, Island Communities', 'Fishermen, Boat Operators'),

('DILP-SIQ-2026-003', 'Maria Women Handicraft Makers', 'LGU-associated', 'Siquijor District', 'Poblacion, Maria',
 'Souvenir and Handicraft Production', 'Formation', 125000.00, 14, 1, 13,
 'Siquijor', 9.3167, 123.5667, 'liquidated', '2025-11-20', '2025-12-25', '2026-01-30',
 'Margarita Ortiz, Cristina Romero, Isabel Medina', 'Women, Rural Artisans', 'Craftswomen, Souvenir Makers'),

('DILP-SIQ-2026-004', 'Enrique Villanueva Farmers Group', 'Non-LGU-associated', 'Siquijor District', 'Bino-ongan, Enrique Villanueva',
 'Organic Farming Tools and Equipment', 'Restoration', 170000.00, 19, 11, 8,
 'Siquijor', 9.1500, 123.4833, 'monitored', '2025-10-15', '2025-11-30', '2026-01-10',
 'Gregorio Pascual, Domingo Aguilar, Vicente Navarro', 'Farmers, Organic Growers', 'Agricultural Workers, Organic Farmers'),

('DILP-SIQ-2026-005', 'San Juan Youth Livelihood Cooperative', 'LGU-associated', 'Siquijor District', 'Cang-apa, San Juan',
 'Motorcycle Parts and Repair Shop', 'Formation', 145000.00, 10, 9, 1,
 'Siquijor', 9.1833, 123.5333, 'pending', '2026-02-08', NULL, NULL,
 'Angelo Castillo, Dennis Moreno, Francis Robles', 'Youth, Skilled Workers', 'Mechanics, Technicians');

-- ========================================
-- BENEFICIARIES SEED DATA
-- ========================================

-- Negros Occidental Beneficiaries (5)
INSERT INTO beneficiaries (
    last_name, first_name, middle_name, suffix, gender, province, municipality, barangay,
    contact_number, project_name, type_of_worker, amount_worth, source_of_funds,
    latitude, longitude, status, date_approved, date_turnover, date_monitoring
) VALUES
('Santos', 'Maria', 'Cruz', NULL, 'Female', 'Negros Occidental', 'Bacolod City', 'Mansilingan',
 '09171234567', 'Banana Cue Vending Stall', 'Street Vendor', 6000.00, 'GAA',
 10.6767, 122.9500, 'implemented', '2026-02-10', '2026-03-05', '2026-04-10'),

('Dela Cruz', 'Juan', 'Reyes', 'Jr.', 'Male', 'Negros Occidental', 'Silay City', 'Balaring',
 '09181234568', 'Vegetable Vending Cart', 'Ambulant Vendor', 5500.00, 'Centrally Managed Fund',
 10.7964, 123.0003, 'approved', '2026-02-25', NULL, NULL),

('Garcia', 'Rosa', 'Lopez', NULL, 'Female', 'Negros Occidental', 'Talisay City', 'Zone 14',
 '09191234569', 'Sari-Sari Store Goods', 'Store Owner', 7000.00, 'GAA',
 10.7333, 122.9833, 'monitored', '2025-12-15', '2026-01-20', '2026-02-25'),

('Aquino', 'Roberto', 'Martinez', NULL, 'Male', 'Negros Occidental', 'Victorias City', 'Zone 3',
 '09201234570', 'Fishing Net and Equipment', 'Fisherman', 7500.00, 'Other',
 10.9000, 123.0667, 'monitored', '2025-11-20', '2025-12-28', '2026-01-30'),

('Johnson', 'Mark', 'Santos', NULL, 'Male', 'Negros Occidental', 'Sagay City', 'Poblacion 1',
 '09211234571', 'Motorcycle for Delivery Service', 'Delivery Rider', 8000.00, 'GAA',
 10.8961, 123.4189, 'pending', NULL, NULL, NULL);

-- Negros Oriental Beneficiaries (5)
INSERT INTO beneficiaries (
    last_name, first_name, middle_name, suffix, gender, province, municipality, barangay,
    contact_number, project_name, type_of_worker, amount_worth, source_of_funds,
    latitude, longitude, status, date_approved, date_turnover, date_monitoring
) VALUES
('Gomez', 'Elena', 'Rivera', NULL, 'Female', 'Negros Oriental', 'Dumaguete City', 'Poblacion 1',
 '09221234572', 'Mobile Food Cart', 'Food Vendor', 6500.00, 'GAA',
 9.3068, 123.3054, 'implemented', '2026-02-05', '2026-03-01', '2026-04-05'),

('Mendoza', 'Antonio', 'Silva', NULL, 'Male', 'Negros Oriental', 'Bais City', 'Okiot',
 '09231234573', 'Fishing Gear Set', 'Fisherman', 7200.00, 'Centrally Managed Fund',
 9.5900, 123.1217, 'approved', '2026-02-20', NULL, NULL),

('Morales', 'Rosario', 'Santos', NULL, 'Female', 'Negros Oriental', 'Tanjay City', 'Poblacion',
 '09241234574', 'Weaving Loom and Materials', 'Weaver', 5800.00, 'GAA',
 9.5167, 123.1500, 'monitored', '2025-12-20', '2026-01-25', '2026-03-01'),

('Hernandez', 'Miguel', 'Ramirez', NULL, 'Male', 'Negros Oriental', 'Canlaon City', 'Linothangan',
 '09251234575', 'Coffee Processing Equipment', 'Coffee Farmer', 8200.00, 'Other',
 10.3833, 123.2000, 'monitored', '2025-11-25', '2026-01-05', '2026-02-10'),

('Cruz', 'Jason', 'Reyes', NULL, 'Male', 'Negros Oriental', 'Guihulngan City', 'Poblacion',
 '09261234576', 'Computer Repair Tools', 'Technician', 6800.00, 'GAA',
 10.1167, 123.2667, 'pending', NULL, NULL, NULL);

-- Siquijor Beneficiaries (5)
INSERT INTO beneficiaries (
    last_name, first_name, middle_name, suffix, gender, province, municipality, barangay,
    contact_number, project_name, type_of_worker, amount_worth, source_of_funds,
    latitude, longitude, status, date_approved, date_turnover, date_monitoring
) VALUES
('Navarro', 'Ferdinand', 'Perez', NULL, 'Male', 'Siquijor', 'Siquijor', 'Poblacion',
 '09271234577', 'Tour Guide Equipment', 'Tour Guide', 5200.00, 'GAA',
 9.2000, 123.5167, 'implemented', '2026-02-08', '2026-03-03', '2026-04-08'),

('Salazar', 'Rodrigo', 'Valdez', NULL, 'Male', 'Siquijor', 'Larena', 'Cangmangki',
 '09281234578', 'Fishing Net and Boat Repair', 'Fisherman', 7800.00, 'Centrally Managed Fund',
 9.2667, 123.6167, 'approved', '2026-02-28', NULL, NULL),

('Ortiz', 'Margarita', 'Romero', NULL, 'Female', 'Siquijor', 'Maria', 'Poblacion',
 '09291234579', 'Handicraft Materials and Tools', 'Craftswoman', 5500.00, 'GAA',
 9.3167, 123.5667, 'monitored', '2025-12-25', '2026-01-30', '2026-03-05'),

('Pascual', 'Gregorio', 'Aguilar', NULL, 'Male', 'Siquijor', 'Enrique Villanueva', 'Bino-ongan',
 '09301234580', 'Organic Farming Tools', 'Agricultural Worker', 6900.00, 'Other',
 9.1500, 123.4833, 'monitored', '2025-11-30', '2026-01-10', '2026-02-15'),

('Castillo', 'Angelo', 'Moreno', NULL, 'Male', 'Siquijor', 'San Juan', 'Cang-apa',
 '09311234581', 'Motorcycle Repair Tools', 'Mechanic', 6200.00, 'GAA',
 9.1833, 123.5333, 'pending', NULL, NULL, NULL);
