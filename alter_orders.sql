ALTER TABLE orders ADD COLUMN reservation_fee DECIMAL(10, 2) DEFAULT 0 AFTER discount;
ALTER TABLE orders ADD COLUMN payment_percentage INT DEFAULT 100 AFTER reservation_fee;
ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'reserved', 'picked_up', 'cancelled') DEFAULT 'pending';
ALTER TABLE orders DROP COLUMN shipping_cost;
