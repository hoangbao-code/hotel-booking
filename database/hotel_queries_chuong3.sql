USE `hotel_db`;

SELECT 
    r.room_name,
    c.category_name,
    SUM(bi.quantity) AS total_quantity_booked,
    SUM(bi.subtotal) AS total_revenue
FROM booking_items bi
JOIN bookings b ON bi.booking_id = b.booking_id
JOIN rooms r ON bi.room_id = r.room_id
JOIN room_categories c ON r.category_id = c.category_id
WHERE b.status = 'completed'
GROUP BY r.room_id, r.room_name, c.category_name
ORDER BY total_quantity_booked DESC, total_revenue DESC
LIMIT 5;

SELECT 
    r.room_id,
    r.room_number,
    r.room_name,
    c.category_name,
    r.price,
    r.sale_price,
    r.short_description,
    r.thumbnail,
    inv.total_rooms,
    inv.reserved_rooms,
    (inv.total_rooms - inv.reserved_rooms) AS available_rooms
FROM rooms r
JOIN room_categories c ON r.category_id = c.category_id
JOIN room_inventory inv ON r.room_id = inv.room_id
WHERE r.room_id = 2;

SELECT 
    r.room_name,
    rs.spec_name,
    rs.spec_value,
    rs.sort_order
FROM rooms r
JOIN room_specifications rs ON r.room_id = rs.room_id
WHERE r.room_id = 1
ORDER BY rs.sort_order ASC;

SELECT 
    DATE(paid_at) AS payment_date,
    COUNT(payment_id) AS successful_transactions,
    SUM(amount) AS total_revenue
FROM payments
WHERE payment_status = 'paid' AND paid_at IS NOT NULL
GROUP BY DATE(paid_at)
ORDER BY payment_date DESC
LIMIT 5;

SELECT 
    r.room_name,
    c.category_name,
    r.price AS original_price,
    COALESCE(r.sale_price, r.price) AS actual_price,
    inv.total_rooms AS stock_rooms
FROM rooms r
JOIN room_categories c ON r.category_id = c.category_id
JOIN room_inventory inv ON r.room_id = inv.room_id
WHERE (c.slug = 'deluxe' OR c.parent_id = (SELECT category_id FROM room_categories WHERE slug = 'deluxe'))
  AND r.status = 1
  AND COALESCE(r.sale_price, r.price) BETWEEN 1500000 AND 2000000
ORDER BY actual_price ASC
LIMIT 2 OFFSET 1;
