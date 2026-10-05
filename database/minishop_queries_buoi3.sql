USE `minishop`;

SELECT 
    p.product_name,
    c.category_name,
    b.brand_name,
    SUM(oi.quantity) AS total_quantity_sold,
    SUM(oi.subtotal) AS total_revenue
FROM order_items oi
JOIN orders o ON oi.order_id = o.order_id
JOIN products p ON oi.product_id = p.product_id
JOIN categories c ON p.category_id = c.category_id
JOIN brands b ON p.brand_id = b.brand_id
WHERE o.status = 'completed'
GROUP BY p.product_id, p.product_name, c.category_name, b.brand_name
ORDER BY total_quantity_sold DESC, total_revenue DESC
LIMIT 5;

SELECT 
    p.product_id,
    p.sku,
    p.product_name,
    c.category_name,
    b.brand_name,
    s.supplier_name,
    p.price,
    p.sale_price,
    p.short_description,
    p.thumbnail,
    i.quantity AS stock_quantity,
    i.reserved_quantity,
    (i.quantity - i.reserved_quantity) AS available_quantity
FROM products p
JOIN categories c ON p.category_id = c.category_id
JOIN brands b ON p.brand_id = b.brand_id
JOIN suppliers s ON p.supplier_id = s.supplier_id
JOIN inventory i ON p.product_id = i.product_id
WHERE p.product_id = 2;

SELECT 
    p.product_name,
    b.brand_name,
    ps.spec_name,
    ps.spec_value,
    ps.sort_order
FROM products p
JOIN brands b ON p.brand_id = b.brand_id
JOIN product_specifications ps ON p.product_id = ps.product_id
WHERE p.product_id = 1
ORDER BY ps.sort_order ASC;

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
    p.product_name,
    c.category_name,
    b.brand_name,
    p.price AS original_price,
    COALESCE(p.sale_price, p.price) AS actual_price,
    i.quantity AS stock_quantity
FROM products p
JOIN categories c ON p.category_id = c.category_id
JOIN brands b ON p.brand_id = b.brand_id
JOIN inventory i ON p.product_id = i.product_id
WHERE (c.slug = 'laptop' OR c.parent_id = (SELECT category_id FROM categories WHERE slug = 'laptop'))
  AND p.status = 1
  AND COALESCE(p.sale_price, p.price) BETWEEN 15000000 AND 30000000
ORDER BY actual_price ASC
LIMIT 2 OFFSET 2;
