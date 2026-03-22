-- Script para limpiar y resetear la base de datos (Versión Segura FK + Logs)
-- ESTO BORRARÁ TODOS LOS DATOS DE PRUEBA, EL CATÁLOGO Y EL HISTORIAL DE ACTIVIDAD.
-- Mantiene: Solo el Admin Maestro.

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Limpiar historial de actividad (Logs)
DELETE FROM activity_logs;
ALTER TABLE activity_logs AUTO_INCREMENT = 1;

-- 2. Limpiar tablas transaccionales (Usando DELETE para evitar error 1701)
DELETE FROM order_items;
ALTER TABLE order_items AUTO_INCREMENT = 1;

DELETE FROM orders;
ALTER TABLE orders AUTO_INCREMENT = 1;

DELETE FROM quotes;
ALTER TABLE quotes AUTO_INCREMENT = 1;

DELETE FROM messages;
ALTER TABLE messages AUTO_INCREMENT = 1;

-- 3. Limpiar Usuarios (EXCEPTO ADMIN)
-- Asumiendo que el Admin tiene ID 1 y/o role 'admin'.
DELETE FROM users WHERE role != 'admin' AND id != 1;
ALTER TABLE users AUTO_INCREMENT = 1;

-- 4. Limpiar Catálogo de Productos (SOLICITADO)
DELETE FROM products;
ALTER TABLE products AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- Confirmación
SELECT 'Limpieza COMPLETA (incluyendo Logs). Solo Admin conservado.' as Status;
