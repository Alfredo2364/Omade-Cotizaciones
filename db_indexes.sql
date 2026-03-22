-- ============================================================
-- OMADE DB — Índices de rendimiento (versión corregida)
-- Ejecutar en phpMyAdmin: selecciona omade_db → pestaña SQL
-- Solo necesitas ejecutar esto UNA VEZ
-- ============================================================

-- products: búsquedas por nombre, código, favoritos y stock
ALTER TABLE products
    ADD INDEX IF NOT EXISTS idx_products_name     (name(50)),
    ADD INDEX IF NOT EXISTS idx_products_code     (product_code),
    ADD INDEX IF NOT EXISTS idx_products_favorite (pos_favorite),
    ADD INDEX IF NOT EXISTS idx_products_stock    (stock);

-- orders: filtros por fecha y usuario
ALTER TABLE orders
    ADD INDEX IF NOT EXISTS idx_orders_created_at (created_at),
    ADD INDEX IF NOT EXISTS idx_orders_user_id    (user_id);

-- order_items: JOINs con orders y products
ALTER TABLE order_items
    ADD INDEX IF NOT EXISTS idx_order_items_order   (order_id),
    ADD INDEX IF NOT EXISTS idx_order_items_product (product_id);

-- users: búsquedas por rol y email
ALTER TABLE users
    ADD INDEX IF NOT EXISTS idx_users_role  (role),
    ADD INDEX IF NOT EXISTS idx_users_email (email);

-- messages: soporte — lecturas y JOINs por sender/receiver
ALTER TABLE messages
    ADD INDEX IF NOT EXISTS idx_messages_sender   (sender_id),
    ADD INDEX IF NOT EXISTS idx_messages_receiver (receiver_id),
    ADD INDEX IF NOT EXISTS idx_messages_is_read  (is_read);

-- activity_logs: filtros por usuario y fecha
ALTER TABLE activity_logs
    ADD INDEX IF NOT EXISTS idx_logs_user_id    (user_id),
    ADD INDEX IF NOT EXISTS idx_logs_created_at (created_at);

-- quotes: filtros por estado, cliente y fecha
-- NOTA: la tabla quotes NO tiene user_id, usa client_email/status/created_at
ALTER TABLE quotes
    ADD INDEX IF NOT EXISTS idx_quotes_status      (status),
    ADD INDEX IF NOT EXISTS idx_quotes_created_at  (created_at),
    ADD INDEX IF NOT EXISTS idx_quotes_email       (client_email(100));
