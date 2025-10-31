-- Swissturn Tool Management System
-- Seed Data for Testing
-- Created: 2025-10-31

-- Note: Run schema.sql first before executing this file

-- =====================================================
-- Test Users
-- =====================================================
-- Password for all test users: password123
INSERT INTO users (username, password_hash, full_name, email, role, is_active) VALUES
('worker1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', 'john.smith@swissturn.com', 'worker', TRUE),
('worker2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', 'sarah.johnson@swissturn.com', 'worker', TRUE),
('worker3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Brown', 'michael.brown@swissturn.com', 'worker', TRUE),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Wilson', 'david.wilson@swissturn.com', 'admin', TRUE);

-- =====================================================
-- Test Suppliers
-- =====================================================
INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, is_active) VALUES
('PrecisionTools Inc.', 'Robert Anderson', 'sales@precisiontools.com', '+1-555-0101', '123 Industrial Ave, Manufacturing City, MC 12345', TRUE),
('SwissCut Solutions', 'Maria Schmidt', 'info@swisscut.ch', '+41-44-555-0202', 'Industriestrasse 45, 8005 Zürich, Switzerland', TRUE),
('MetalMaster Supply', 'James Lee', 'orders@metalmaster.com', '+1-555-0303', '456 Factory Road, Tool Town, TT 67890', TRUE),
('GlobalTools GmbH', 'Hans Mueller', 'contact@globaltools.de', '+49-89-555-0404', 'Werkzeugstraße 12, 80333 München, Germany', TRUE),
('ProGear Equipment', 'Lisa Chen', 'sales@progear.com', '+1-555-0505', '789 Equipment Blvd, Gear City, GC 13579', TRUE);

-- =====================================================
-- Test Tools
-- =====================================================

-- Cutting Tools (Category 1)
INSERT INTO tools (category_id, tool_name, tool_description, tool_size, supplier_id, supplier_model_number, current_stock, minimum_stock, usage_count, first_use_date, lifespan_type, lifespan_limit, status) VALUES
(1, 'End Mill Cutter 10mm', 'High-speed steel end mill for precision cutting', '10mm', 1, 'EM-HSS-10', 25, 10, 45, '2025-10-01', 'usage', 100, 'active'),
(1, 'End Mill Cutter 12mm', 'Carbide end mill for hardened steel', '12mm', 2, 'SC-CARB-12', 15, 8, 82, '2025-09-15', 'usage', 100, 'near_expiry'),
(1, 'Drill Bit Set 1-10mm', 'HSS drill bit set with titanium coating', '1-10mm', 1, 'DBS-TI-110', 30, 12, 5, '2025-10-20', 'time', 180, 'active'),
(1, 'Threading Tap M6', 'High-precision threading tap', 'M6', 3, 'TT-M6-PRO', 8, 15, 0, NULL, 'usage', 50, 'needs_reorder'),
(1, 'Face Mill 50mm', 'Indexable face mill cutter', '50mm', 2, 'FM-IDX-50', 12, 5, 28, '2025-09-20', 'usage', 150, 'active');

-- Measuring Tools (Category 2)
INSERT INTO tools (category_id, tool_name, tool_description, tool_size, supplier_id, supplier_model_number, current_stock, minimum_stock, usage_count, first_use_date, lifespan_type, lifespan_limit, status) VALUES
(2, 'Digital Caliper 0-150mm', 'Digital vernier caliper with LCD display', '0-150mm', 4, 'DC-150-LCD', 20, 8, 150, '2025-08-01', 'time', 365, 'active'),
(2, 'Micrometer 0-25mm', 'Precision outside micrometer', '0-25mm', 4, 'MIC-025-PRE', 15, 6, 95, '2025-07-15', 'time', 365, 'active'),
(2, 'Dial Indicator 0-10mm', 'Magnetic base dial indicator', '0-10mm', 1, 'DI-MAG-010', 10, 5, 45, '2025-09-10', 'time', 180, 'active'),
(2, 'Height Gauge 300mm', 'Digital height gauge with fine adjustment', '300mm', 4, 'HG-300-DIG', 5, 3, 22, '2025-10-05', 'time', 365, 'active');

-- Hand Tools (Category 3)
INSERT INTO tools (category_id, tool_name, tool_description, tool_size, supplier_id, supplier_model_number, current_stock, minimum_stock, usage_count, first_use_date, lifespan_type, lifespan_limit, status) VALUES
(3, 'Hex Key Set Metric', 'Professional hex key set 1.5-10mm', '1.5-10mm', 5, 'HKS-MET-PRO', 35, 15, 12, '2025-10-15', 'time', 730, 'active'),
(3, 'Torque Wrench 10-100Nm', 'Click-type torque wrench', '10-100Nm', 5, 'TW-100-CLK', 8, 4, 67, '2025-08-20', 'time', 365, 'active'),
(3, 'Pliers Set', 'Professional pliers set (5 pieces)', 'Various', 3, 'PS-5PC-PRO', 22, 10, 8, '2025-10-10', 'time', 730, 'active'),
(3, 'Adjustable Wrench 12"', 'Chrome vanadium adjustable wrench', '12 inch', 5, 'AW-12-CV', 18, 8, 34, '2025-09-25', 'time', 365, 'active');

-- Power Tools (Category 4)
INSERT INTO tools (category_id, tool_name, tool_description, tool_size, supplier_id, supplier_model_number, current_stock, minimum_stock, usage_count, first_use_date, lifespan_type, lifespan_limit, status) VALUES
(4, 'Cordless Drill 18V', 'Professional cordless drill with battery', '18V', 5, 'CD-18V-PRO', 10, 4, 145, '2025-07-01', 'usage', 500, 'active'),
(4, 'Angle Grinder 125mm', 'Electric angle grinder 1200W', '125mm', 3, 'AG-125-1200', 7, 3, 89, '2025-08-10', 'usage', 300, 'active'),
(4, 'Impact Wrench Pneumatic', 'Air impact wrench 1/2 inch drive', '1/2"', 5, 'IW-AIR-12', 5, 3, 234, '2025-06-15', 'usage', 1000, 'active');

-- Safety Equipment (Category 5)
INSERT INTO tools (category_id, tool_name, tool_description, tool_size, supplier_id, supplier_model_number, current_stock, minimum_stock, usage_count, first_use_date, lifespan_type, lifespan_limit, status) VALUES
(5, 'Safety Glasses Clear', 'Anti-fog safety glasses with UV protection', 'Universal', 3, 'SG-CLR-UV', 50, 25, 0, NULL, 'time', 365, 'active'),
(5, 'Work Gloves Heavy Duty', 'Cut-resistant work gloves', 'L', 3, 'WG-L-CUT', 45, 30, 0, NULL, 'time', 90, 'active'),
(5, 'Ear Protection Muffs', 'Noise-canceling ear muffs 32dB', 'Universal', 5, 'EP-32DB-MUF', 20, 10, 0, NULL, 'time', 730, 'active'),
(5, 'Face Shield', 'Full-face protection shield', 'Universal', 3, 'FS-FULL-CLR', 15, 8, 0, NULL, 'time', 365, 'active');

-- Fixtures (Category 6)
INSERT INTO tools (category_id, tool_name, tool_description, tool_size, supplier_id, supplier_model_number, current_stock, minimum_stock, usage_count, first_use_date, lifespan_type, lifespan_limit, status) VALUES
(6, 'Machine Vise 6"', 'Heavy-duty machine vise', '6 inch', 1, 'MV-6-HD', 8, 3, 42, '2025-09-01', 'time', 1825, 'active'),
(6, 'Collet Chuck Set ER32', 'ER32 collet chuck with collets', 'ER32', 2, 'CC-ER32-SET', 6, 2, 78, '2025-08-05', 'usage', 500, 'active'),
(6, 'Parallel Set 10 Pairs', 'Precision parallel set hardened', '1-10mm', 1, 'PS-10P-HRD', 12, 5, 15, '2025-10-01', 'time', 1825, 'active');

-- =====================================================
-- Test Transactions
-- =====================================================
INSERT INTO transactions (tool_id, user_id, transaction_type, job_id, task_description, quantity, transaction_date, notes) VALUES
-- Recent transactions
(1, 2, 'checkout', 'JOB-2025-1001', 'Milling aluminum housing parts', 2, '2025-10-30 08:15:00', 'Parts for customer order #5543'),
(2, 3, 'checkout', 'JOB-2025-1002', 'Cutting steel components', 1, '2025-10-30 09:30:00', 'High-priority order'),
(7, 2, 'checkout', 'JOB-2025-1001', 'Quality inspection of milled parts', 1, '2025-10-30 10:45:00', 'Final inspection'),
(1, 2, 'checkin', 'JOB-2025-1001', 'Completed milling operation', 2, '2025-10-30 14:30:00', 'Tools in good condition'),
(15, 4, 'checkout', 'JOB-2025-1003', 'Drilling mounting holes', 1, '2025-10-30 13:20:00', 'Prototype assembly'),
-- Older transactions
(3, 2, 'checkout', 'JOB-2025-0998', 'Drilling operations', 1, '2025-10-28 07:45:00', NULL),
(3, 2, 'checkin', 'JOB-2025-0998', 'Completed', 1, '2025-10-28 16:30:00', NULL),
(11, 3, 'checkout', 'JOB-2025-0995', 'Torque specification job', 1, '2025-10-25 09:00:00', 'Torque: 85Nm'),
(11, 3, 'checkin', 'JOB-2025-0995', 'Job completed', 1, '2025-10-25 15:45:00', NULL),
(16, 4, 'checkout', 'JOB-2025-0990', 'Assembly work', 1, '2025-10-22 08:30:00', NULL),
(16, 4, 'checkin', 'JOB-2025-0990', 'Assembly completed', 1, '2025-10-22 17:00:00', 'Battery needs charging');

-- =====================================================
-- Test Purchase Orders
-- =====================================================
INSERT INTO purchase_orders (tool_id, supplier_id, quantity, status, priority, requested_by, requested_date, notes) VALUES
(4, 3, 20, 'pending', 'high', 1, '2025-10-29 10:00:00', 'Stock below minimum threshold'),
(2, 2, 15, 'approved', 'medium', 1, '2025-10-28 14:30:00', 'Tools approaching end of life'),
(15, 5, 5, 'ordered', 'medium', 5, '2025-10-25 09:15:00', 'Additional units for new project'),
(8, 4, 10, 'received', 'low', 5, '2025-10-20 11:00:00', 'Regular restock completed');

-- =====================================================
-- Test Audit Logs
-- =====================================================
INSERT INTO audit_logs (user_id, action, table_name, record_id, new_values, ip_address, created_at) VALUES
(1, 'CREATE_TOOL', 'tools', 1, '{"tool_name":"End Mill Cutter 10mm","category_id":1}', '192.168.1.100', '2025-10-01 08:00:00'),
(2, 'CHECKOUT_TOOL', 'transactions', 1, '{"tool_id":1,"quantity":2,"job_id":"JOB-2025-1001"}', '192.168.1.105', '2025-10-30 08:15:00'),
(1, 'APPROVE_ORDER', 'purchase_orders', 2, '{"order_id":2,"status":"approved"}', '192.168.1.100', '2025-10-28 15:00:00'),
(5, 'CREATE_SUPPLIER', 'suppliers', 5, '{"supplier_name":"ProGear Equipment"}', '192.168.1.100', '2025-10-15 10:30:00'),
(2, 'CHECKIN_TOOL', 'transactions', 4, '{"tool_id":1,"quantity":2}', '192.168.1.105', '2025-10-30 14:30:00');

-- Seed data insertion completed successfully
