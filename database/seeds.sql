-- ============================================
-- E-COMMERCE SAMPLE DATA (SEEDS)
-- Phones, Tablets, and Electronics
-- WITH ADMIN USERS
-- ============================================

USE ecommerce_security;

-- ============================================
-- 1. INSERT BRANDS
-- ============================================
INSERT INTO brands (name, logo) VALUES
('Apple', 'apple-logo.png'),
('Samsung', 'samsung-logo.png'),
('Huawei', 'huawei-logo.png'),
('Xiaomi', 'xiaomi-logo.png'),
('OnePlus', 'oneplus-logo.png'),
('Google', 'google-logo.png'),
('Oppo', 'oppo-logo.png'),
('Realme', 'realme-logo.png');

-- ============================================
-- 2. INSERT CATEGORIES
-- ============================================
INSERT INTO categories (name, description) VALUES
('Phones', 'Smartphones and mobile phones'),
('Tablets', 'Tablets and iPads'),
('Laptops', 'Laptops and notebooks'),
('Smartwatches', 'Smart watches and fitness trackers'),
('Headphones', 'Headphones, earbuds, and audio devices');

-- ============================================
-- 3. INSERT USERS (UPDATED - With Roles)
-- ============================================
-- V1: Plain text passwords (intentionally vulnerable!)
-- V2: Will use password_hash()

-- ADMIN USERS
INSERT INTO users (name, email, password, phone, address, role, is_active) VALUES
(
    'Admin User',
    'admin@bugsy.com',
    'admin123',  -- V1: Plain text (VULNERABLE!)
    '+201000000001',
    'Admin Office, Cairo, Egypt',
    'admin',
    TRUE
),
(
    'Super Admin',
    'superadmin@bugsy.com',
    'super123',  -- V1: Plain text (VULNERABLE!)
    '+201000000002',
    'Admin Office, Cairo, Egypt',
    'admin',
    TRUE
);

-- CUSTOMER USERS
INSERT INTO users (name, email, password, phone, address, role, is_active) VALUES
(
    'Ahmed Mohamed',
    'ahmed@example.com',
    '123456',  -- V1: Plain text (VULNERABLE!)
    '+201234567890',
    '123 Tahrir Square, Cairo, Egypt',
    'customer',
    TRUE
),
(
    'Sara Ali',
    'sara@example.com',
    'password123',  -- V1: Plain text (VULNERABLE!)
    '+201098765432',
    '456 Nile Street, Giza, Egypt',
    'customer',
    TRUE
),
(
    'Mohamed Hassan',
    'mohamed@example.com',
    'test123',  -- V1: Plain text (VULNERABLE!)
    '+201555444333',
    '789 Alexandria Road, Alex, Egypt',
    'customer',
    TRUE
),
(
    'Fatima Ibrahim',
    'fatima@example.com',
    'user123',  -- V1: Plain text (VULNERABLE!)
    '+201777888999',
    '321 Pyramids Ave, Giza, Egypt',
    'customer',
    TRUE
),
(
    'Omar Khaled',
    'omar@example.com',
    'pass123',  -- V1: Plain text (VULNERABLE!)
    '+201666555444',
    '555 Sphinx Street, Giza, Egypt',
    'customer',
    TRUE
);

-- ============================================
-- 4. INSERT PRODUCTS - PHONES
-- ============================================
INSERT INTO products (name, description, price, stock, brand_id, category_id, specifications, main_image, rating, is_available) VALUES
-- Apple Phones
(
    'iPhone 15 Pro Max', 
    'The ultimate iPhone with titanium design, A17 Pro chip, and advanced camera system. Features include Action button, always-on display, and Dynamic Island.',
    52999.00,
    50,
    1, -- Apple
    1, -- Phones
    '{"ram": "8GB", "storage": "256GB", "screen": "6.7 inch Super Retina XDR", "camera": "48MP + 12MP + 12MP", "battery": "4422mAh", "processor": "A17 Pro", "color": "Natural Titanium"}',
    'iphone-15-pro-max.jpg',
    4.85,
    TRUE
),
(
    'iPhone 15', 
    'All-new iPhone 15 with Dynamic Island, 48MP main camera, and USB-C. Available in stunning colors.',
    38999.00,
    75,
    1, -- Apple
    1, -- Phones
    '{"ram": "6GB", "storage": "128GB", "screen": "6.1 inch Super Retina XDR", "camera": "48MP + 12MP", "battery": "3349mAh", "processor": "A16 Bionic", "color": "Blue"}',
    'iphone-15.jpg',
    4.70,
    TRUE
),
(
    'iPhone 14', 
    'Previous generation iPhone with excellent performance, great camera, and all-day battery life.',
    32999.00,
    40,
    1, -- Apple
    1, -- Phones
    '{"ram": "6GB", "storage": "128GB", "screen": "6.1 inch Super Retina XDR", "camera": "12MP + 12MP", "battery": "3279mAh", "processor": "A15 Bionic", "color": "Midnight"}',
    'iphone-14.jpg',
    4.60,
    TRUE
),

-- Samsung Phones
(
    'Samsung Galaxy S24 Ultra', 
    'Samsung flagship with S Pen, 200MP camera, and AI features. Powerful Snapdragon 8 Gen 3 processor.',
    48999.00,
    60,
    2, -- Samsung
    1, -- Phones
    '{"ram": "12GB", "storage": "256GB", "screen": "6.8 inch Dynamic AMOLED 2X", "camera": "200MP + 50MP + 12MP + 10MP", "battery": "5000mAh", "processor": "Snapdragon 8 Gen 3", "color": "Titanium Gray"}',
    'samsung-s24-ultra.jpg',
    4.80,
    TRUE
),
(
    'Samsung Galaxy S23', 
    'Compact flagship with powerful performance, excellent camera, and beautiful display.',
    35999.00,
    55,
    2, -- Samsung
    1, -- Phones
    '{"ram": "8GB", "storage": "128GB", "screen": "6.1 inch Dynamic AMOLED 2X", "camera": "50MP + 12MP + 10MP", "battery": "3900mAh", "processor": "Snapdragon 8 Gen 2", "color": "Phantom Black"}',
    'samsung-s23.jpg',
    4.65,
    TRUE
),
(
    'Samsung Galaxy A54', 
    'Mid-range phone with excellent value, great camera, and long battery life. Perfect for everyday use.',
    16999.00,
    100,
    2, -- Samsung
    1, -- Phones
    '{"ram": "8GB", "storage": "128GB", "screen": "6.4 inch Super AMOLED", "camera": "50MP + 12MP + 5MP", "battery": "5000mAh", "processor": "Exynos 1380", "color": "Awesome Violet"}',
    'samsung-a54.jpg',
    4.50,
    TRUE
),

-- Xiaomi Phones
(
    'Xiaomi 14 Pro', 
    'Flagship phone with Leica cameras, Snapdragon 8 Gen 3, and stunning display. Professional photography features.',
    29999.00,
    45,
    4, -- Xiaomi
    1, -- Phones
    '{"ram": "12GB", "storage": "256GB", "screen": "6.73 inch AMOLED", "camera": "50MP + 50MP + 50MP (Leica)", "battery": "4880mAh", "processor": "Snapdragon 8 Gen 3", "color": "Titanium Black"}',
    'xiaomi-14-pro.jpg',
    4.70,
    TRUE
),
(
    'Xiaomi Redmi Note 13 Pro', 
    'Best-selling mid-range phone with excellent camera, fast charging, and great value for money.',
    11999.00,
    120,
    4, -- Xiaomi
    1, -- Phones
    '{"ram": "8GB", "storage": "128GB", "screen": "6.67 inch AMOLED", "camera": "200MP + 8MP + 2MP", "battery": "5000mAh", "processor": "Snapdragon 7s Gen 2", "color": "Midnight Black"}',
    'redmi-note-13-pro.jpg',
    4.55,
    TRUE
),

-- OnePlus Phones
(
    'OnePlus 12', 
    'Flagship killer with Hasselblad camera, blazing fast performance, and rapid charging.',
    32999.00,
    35,
    5, -- OnePlus
    1, -- Phones
    '{"ram": "12GB", "storage": "256GB", "screen": "6.82 inch AMOLED", "camera": "50MP + 64MP + 48MP (Hasselblad)", "battery": "5400mAh", "processor": "Snapdragon 8 Gen 3", "color": "Flowy Emerald"}',
    'oneplus-12.jpg',
    4.75,
    TRUE
),

-- Google Phones
(
    'Google Pixel 8 Pro', 
    'Pure Android experience with best-in-class AI features, amazing camera, and clean software.',
    39999.00,
    30,
    6, -- Google
    1, -- Phones
    '{"ram": "12GB", "storage": "128GB", "screen": "6.7 inch LTPO OLED", "camera": "50MP + 48MP + 48MP", "battery": "5050mAh", "processor": "Google Tensor G3", "color": "Obsidian"}',
    'pixel-8-pro.jpg',
    4.80,
    TRUE
);

-- ============================================
-- 5. INSERT PRODUCTS - TABLETS
-- ============================================
INSERT INTO products (name, description, price, stock, brand_id, category_id, specifications, main_image, rating, is_available) VALUES
-- Apple Tablets
(
    'iPad Pro 12.9" M2', 
    'Most powerful iPad with M2 chip, Liquid Retina XDR display, and Apple Pencil support. Perfect for professionals.',
    45999.00,
    25,
    1, -- Apple
    2, -- Tablets
    '{"ram": "8GB", "storage": "128GB", "screen": "12.9 inch Liquid Retina XDR", "camera": "12MP + 10MP", "battery": "10758mAh", "processor": "Apple M2", "color": "Space Gray"}',
    'ipad-pro-12.jpg',
    4.90,
    TRUE
),
(
    'iPad Air M1', 
    'Powerful and versatile iPad with M1 chip, beautiful design, and support for Magic Keyboard.',
    24999.00,
    40,
    1, -- Apple
    2, -- Tablets
    '{"ram": "8GB", "storage": "64GB", "screen": "10.9 inch Liquid Retina", "camera": "12MP + 12MP", "battery": "7606mAh", "processor": "Apple M1", "color": "Blue"}',
    'ipad-air.jpg',
    4.75,
    TRUE
),
(
    'iPad 10th Gen', 
    'All-screen design, A14 Bionic chip, and great for everyday tasks, entertainment, and learning.',
    17999.00,
    60,
    1, -- Apple
    2, -- Tablets
    '{"ram": "4GB", "storage": "64GB", "screen": "10.9 inch Liquid Retina", "camera": "12MP + 12MP", "battery": "7606mAh", "processor": "A14 Bionic", "color": "Yellow"}',
    'ipad-10.jpg',
    4.65,
    TRUE
),

-- Samsung Tablets
(
    'Samsung Galaxy Tab S9 Ultra', 
    'Massive 14.6" display, S Pen included, perfect for productivity and entertainment. Premium flagship tablet.',
    42999.00,
    20,
    2, -- Samsung
    2, -- Tablets
    '{"ram": "12GB", "storage": "256GB", "screen": "14.6 inch Dynamic AMOLED 2X", "camera": "13MP + 8MP", "battery": "11200mAh", "processor": "Snapdragon 8 Gen 2", "color": "Graphite"}',
    'tab-s9-ultra.jpg',
    4.85,
    TRUE
),
(
    'Samsung Galaxy Tab S9', 
    'Compact flagship tablet with beautiful display, S Pen, and powerful performance.',
    28999.00,
    35,
    2, -- Samsung
    2, -- Tablets
    '{"ram": "8GB", "storage": "128GB", "screen": "11 inch Dynamic AMOLED 2X", "camera": "13MP + 8MP", "battery": "8400mAh", "processor": "Snapdragon 8 Gen 2", "color": "Beige"}',
    'tab-s9.jpg',
    4.70,
    TRUE
),
(
    'Samsung Galaxy Tab A9+', 
    'Affordable tablet for entertainment and basic tasks. Large display and long battery life.',
    9999.00,
    70,
    2, -- Samsung
    2, -- Tablets
    '{"ram": "4GB", "storage": "64GB", "screen": "11 inch LCD", "camera": "8MP + 5MP", "battery": "7040mAh", "processor": "Snapdragon 695", "color": "Navy"}',
    'tab-a9-plus.jpg',
    4.40,
    TRUE
);

-- ============================================
-- 6. INSERT PRODUCTS - SMARTWATCHES
-- ============================================
INSERT INTO products (name, description, price, stock, brand_id, category_id, specifications, main_image, rating, is_available) VALUES
(
    'Apple Watch Series 9', 
    'Most advanced Apple Watch with always-on Retina display, health tracking, and fitness features.',
    18999.00,
    50,
    1, -- Apple
    4, -- Smartwatches
    '{"size": "45mm", "display": "Always-On Retina", "battery": "Up to 18 hours", "features": "ECG, Blood Oxygen, Crash Detection", "color": "Midnight Aluminum"}',
    'apple-watch-9.jpg',
    4.80,
    TRUE
),
(
    'Samsung Galaxy Watch 6', 
    'Feature-packed smartwatch with comprehensive health tracking, beautiful display, and Wear OS.',
    12999.00,
    45,
    2, -- Samsung
    4, -- Smartwatches
    '{"size": "44mm", "display": "Super AMOLED", "battery": "Up to 40 hours", "features": "Body Composition, Sleep Tracking, GPS", "color": "Graphite"}',
    'galaxy-watch-6.jpg',
    4.65,
    TRUE
),
(
    'Xiaomi Watch 2 Pro', 
    'Premium smartwatch with Wear OS, excellent battery life, and comprehensive fitness tracking.',
    6999.00,
    60,
    4, -- Xiaomi
    4, -- Smartwatches
    '{"size": "46mm", "display": "AMOLED", "battery": "Up to 14 days", "features": "117 Sport Modes, GPS, 5ATM Water Resistant", "color": "Black"}',
    'xiaomi-watch-2-pro.jpg',
    4.50,
    TRUE
);

-- ============================================
-- 7. INSERT PRODUCTS - HEADPHONES
-- ============================================
INSERT INTO products (name, description, price, stock, brand_id, category_id, specifications, main_image, rating, is_available) VALUES
(
    'Apple AirPods Pro 2nd Gen', 
    'Premium wireless earbuds with active noise cancellation, spatial audio, and MagSafe charging.',
    9999.00,
    80,
    1, -- Apple
    5, -- Headphones
    '{"type": "In-Ear", "noise_cancellation": "Active", "battery": "Up to 6 hours (30 with case)", "features": "Spatial Audio, Adaptive Audio, Transparency Mode", "color": "White"}',
    'airpods-pro-2.jpg',
    4.85,
    TRUE
),
(
    'Samsung Galaxy Buds2 Pro', 
    'High-quality wireless earbuds with intelligent ANC, 360 audio, and comfortable fit.',
    7999.00,
    70,
    2, -- Samsung
    5, -- Headphones
    '{"type": "In-Ear", "noise_cancellation": "Intelligent ANC", "battery": "Up to 8 hours (29 with case)", "features": "360 Audio, Voice Detect, IPX7 Water Resistant", "color": "Graphite"}',
    'buds2-pro.jpg',
    4.70,
    TRUE
),
(
    'OnePlus Buds Pro 2', 
    'Premium earbuds with adaptive noise cancellation, spatial audio, and fast charging.',
    4999.00,
    65,
    5, -- OnePlus
    5, -- Headphones
    '{"type": "In-Ear", "noise_cancellation": "Adaptive ANC", "battery": "Up to 9 hours (39 with case)", "features": "Spatial Audio, Golden Sound, 10 min = 3 hours charge", "color": "Obsidian Black"}',
    'oneplus-buds-pro-2.jpg',
    4.60,
    TRUE
);

-- ============================================
-- 8. INSERT PRODUCT IMAGES
-- ============================================
INSERT INTO product_images (product_id, image_url) VALUES
-- iPhone 15 Pro Max images
(1, 'iphone-15-pro-max-1.jpg'),
(1, 'iphone-15-pro-max-2.jpg'),
(1, 'iphone-15-pro-max-3.jpg'),
(1, 'iphone-15-pro-max-4.jpg'),

-- iPhone 15 images
(2, 'iphone-15-front.jpg'),
(2, 'iphone-15-back.jpg'),
(2, 'iphone-15-side.jpg'),

-- Samsung S24 Ultra images
(4, 'samsung-s24-ultra-1.jpg'),
(4, 'samsung-s24-ultra-2.jpg'),
(4, 'samsung-s24-ultra-3.jpg'),

-- iPad Pro images
(11, 'ipad-pro-front.jpg'),
(11, 'ipad-pro-back.jpg'),
(11, 'ipad-pro-pencil.jpg'),

-- Galaxy Tab S9 Ultra images
(14, 'tab-s9-ultra-1.jpg'),
(14, 'tab-s9-ultra-2.jpg');

-- ============================================
-- 9. INSERT CARTS (for test users - CUSTOMERS ONLY)
-- ============================================
INSERT INTO carts (user_id) VALUES
(3), -- Ahmed's cart (user_id 3)
(4), -- Sara's cart
(5); -- Mohamed's cart

-- ============================================
-- 10. INSERT CART ITEMS (sample cart data)
-- ============================================
INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES
-- Ahmed's cart (cart_id: 1)
(1, 1, 1, 52999.00), -- iPhone 15 Pro Max
(1, 20, 1, 9999.00),  -- AirPods Pro 2

-- Sara's cart (cart_id: 2)
(2, 11, 1, 45999.00), -- iPad Pro
(2, 12, 1, 24999.00), -- iPad Air

-- Mohamed's cart (cart_id: 3)
(3, 4, 1, 48999.00),  -- Samsung S24 Ultra
(3, 21, 2, 7999.00);  -- Galaxy Buds2 Pro x2

-- ============================================
-- 11. INSERT ORDERS (sample order history)
-- ============================================
INSERT INTO orders (order_number, user_id, total, status, payment_method, payment_status, shipping_address, notes) VALUES
(
    'ORD-20251001-0001',
    3, -- Ahmed (customer)
    38999.00,
    'delivered',
    'credit_card',
    'paid',
    '123 Tahrir Square, Cairo, Egypt',
    'Please deliver before 5 PM'
),
(
    'ORD-20251005-0002',
    4, -- Sara (customer)
    32999.00,
    'shipped',
    'vodafone_cash',
    'paid',
    '456 Nile Street, Giza, Egypt',
    NULL
),
(
    'ORD-20251008-0003',
    3, -- Ahmed (second order)
    62998.00,
    'processing',
    'credit_card',
    'paid',
    '123 Tahrir Square, Cairo, Egypt',
    NULL
),
(
    'ORD-20251010-0004',
    5, -- Mohamed
    28999.00,
    'pending',
    'cash_on_delivery',
    'pending',
    '789 Alexandria Road, Alex, Egypt',
    'Call before delivery'
),
(
    'ORD-20251012-0005',
    6, -- Fatima
    52999.00,
    'delivered',
    'credit_card',
    'paid',
    '321 Pyramids Ave, Giza, Egypt',
    NULL
);

-- ============================================
-- 12. INSERT ORDER ITEMS
-- ============================================
INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) VALUES
-- Order 1 items (Ahmed's first order)
(1, 2, 'iPhone 15', 1, 38999.00, 38999.00),

-- Order 2 items (Sara's order)
(2, 9, 'OnePlus 12', 1, 32999.00, 32999.00),

-- Order 3 items (Ahmed's second order)
(3, 4, 'Samsung Galaxy S24 Ultra', 1, 48999.00, 48999.00),
(3, 6, 'Samsung Galaxy A54', 1, 13999.00, 13999.00),

-- Order 4 items (Mohamed's order)
(4, 15, 'Samsung Galaxy Tab S9', 1, 28999.00, 28999.00),

-- Order 5 items (Fatima's order)
(5, 1, 'iPhone 15 Pro Max', 1, 52999.00, 52999.00);

-- ============================================
-- 13. INSERT SAMPLE AUDIT LOGS (For V2/V3 testing)
-- ============================================
-- These demonstrate what audit logs will look like in V2/V3
INSERT INTO audit_logs (user_id, action, resource_type, resource_id, ip_address, user_agent, request_method, request_url, status_code, details) VALUES
-- Successful login
(
    3,
    'login_success',
    'user',
    3,
    '192.168.1.100',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'POST',
    '/api/v1/login',
    200,
    '{"email": "ahmed@example.com", "login_time": "2025-10-01 10:30:00"}'
),

-- Failed login attempt (wrong password)
(
    NULL,
    'login_failed',
    'user',
    NULL,
    '192.168.1.105',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
    'POST',
    '/api/v1/login',
    401,
    '{"email": "sara@example.com", "reason": "invalid_password", "attempt_time": "2025-10-02 14:15:00"}'
),

-- Product created (admin action)
(
    1,
    'product_created',
    'product',
    1,
    '192.168.1.50',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
    'POST',
    '/api/v2/products',
    201,
    '{"product_name": "iPhone 15 Pro Max", "price": 52999.00, "admin_id": 1}'
),

-- Order placed
(
    3,
    'order_placed',
    'order',
    1,
    '192.168.1.100',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    'POST',
    '/api/v1/checkout',
    201,
    '{"order_number": "ORD-20251001-0001", "total": 38999.00, "items": 1}'
),

-- IDOR attempt detected (V2/V3 feature)
(
    5,
    'unauthorized_access_attempt',
    'order',
    1,
    '192.168.1.200',
    'curl/7.68.0',
    'GET',
    '/api/v1/orders/1',
    403,
    '{"attempted_by": 5, "target_user": 3, "reason": "Tried to access another users order"}'
),

-- Multiple failed login attempts (potential brute force)
(
    NULL,
    'login_failed',
    'user',
    NULL,
    '10.0.0.99',
    'Python-requests/2.28.0',
    'POST',
    '/api/v1/login',
    401,
    '{"email": "admin@bugsy.com", "reason": "invalid_password", "attempt_number": 5}'
);

-- ============================================
-- DATABASE SEEDED SUCCESSFULLY!
-- ============================================

-- Quick stats
SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'admin') as admin_users,
    (SELECT COUNT(*) FROM users WHERE role = 'customer') as customer_users,
    (SELECT COUNT(*) FROM brands) as total_brands,
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM orders) as total_orders,
    (SELECT COUNT(*) FROM audit_logs) as audit_log_entries;

-- ============================================
-- TEST ACCOUNTS REFERENCE
-- ============================================
-- 
-- ADMIN ACCOUNTS (For V2/V3 testing):
-- Email: admin@bugsy.com        | Password: admin123
-- Email: superadmin@bugsy.com   | Password: super123
--
-- CUSTOMER ACCOUNTS:
-- Email: ahmed@example.com      | Password: 123456
-- Email: sara@example.com       | Password: password123
-- Email: mohamed@example.com    | Password: test123
-- Email: fatima@example.com     | Password: user123
-- Email: omar@example.com       | Password: pass123
--
-- ⚠️  V1 WARNING: All passwords in PLAIN TEXT (intentionally vulnerable!)
-- ✅  V2 FIX: Will use password_hash() and password_verify()
-- ===