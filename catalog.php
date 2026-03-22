<?php
require_once 'includes/db.php';
session_start(); // Start session to check login state

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Productos - Distribuciones Omade</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="assets/favicon_io/site.webmanifest">
    <style>
        .catalog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 40px 0;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05); /* Softer shadow */
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 100%;
            height: 250px;
            object-fit: contain; /* Ensure whole product is visible */
            background: #fff;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }

        .product-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0b1e3b;
            margin-bottom: 10px;
        }

        .product-desc {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 15px;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            line-clamp: 3; /* Standard property */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-meta {
            margin-top: auto;
            border-top: 1px solid #f0f0f0;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1abc9c;
        }

        .stock-badge {
            background: #e8f8f5;
            color: #1abc9c;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .stock-badge.low {
            background: #fdedec;
            color: #e74c3c;
        }

        /* Hero override for Catalog */
        .hero-catalog {
            background: linear-gradient(135deg, #0b1e3b 0%, #162a4a 100%);
            padding: 120px 20px 60px;
            text-align: center;
            color: white;
            position: relative;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div style="display: flex; flex-direction: row; align-items: center; gap: 15px;">
            <div class="logo">Distribuciones Omade</div>
            <img src="assets/images/logo/Logo.png" alt="OMADE Logo" style="height: 55px;">
        </div>
        
        <!-- Simplified 'Volver' Navigation -->
        <div class="nav-links" style="display: flex; gap: 20px;">
            <?php 
                $backLink = 'index.html';
                if(isset($_SESSION['user_id'])) {
                    $backLink = ($_SESSION['role'] === 'admin') ? 'admin/dashboard.php' : 'user/dashboard.php';
                }
            ?>
            <a href="<?php echo $backLink; ?>" style="font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 1.4rem;">&larr;</span> Volver
            </a>
        </div>
    </nav>

    <header class="hero-catalog">
        <div class="hero-content">
            <h1>Nuestro Catálogo</h1>
            <p>Explora nuestra selección de productos de calidad premium.</p>
        </div>
    </header>

    <section class="section">
        <div style="max-width: 1200px; margin: 0 auto;">
            
            <?php if (count($products) > 0): ?>
                <div class="catalog-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo !empty($product['image']) ? $product['image'] : 'assets/images/placeholder.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-image">
                            
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-desc" title="<?= htmlspecialchars($product['description']) ?>"><?php echo htmlspecialchars($product['description']); ?></p>
                                
                                <div class="product-meta">
                                    <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                                    
                                    <?php if($product['stock'] > 5): ?>
                                        <span class="stock-badge">Existencias: <?php echo $product['stock']; ?></span>
                                    <?php elseif($product['stock'] > 0): ?>
                                        <span class="stock-badge low">¡Solo quedan <?php echo $product['stock']; ?>!</span>
                                    <?php else: ?>
                                        <span class="stock-badge" style="background:#ddd; color:#666;">Agotado</span>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #eee;">
                                    <a href="login.php" style="display: block; text-align: center; background: #f8fafc; color: #0b1e3b; text-decoration: none; padding: 10px; border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; border: 1px solid #e2e8f0;">
                                        Inicia sesión para cotizar
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px;">
                    <h3 style="color: #999;">No hay productos disponibles en este momento.</h3>
                </div>
            <?php endif; ?>

        </div>
    </section>

    <!-- Matches Index Footer -->
    <footer style="background: #0b1e3b; color: white; padding: 40px 20px; margin-top: 50px;">
        <div style="max-width: 1200px; margin: 0 auto; text-align: center; opacity: 0.8;">
            &copy; <?php echo date('Y'); ?> Distribuciones Omade.
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
