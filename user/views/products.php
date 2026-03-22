<?php
// user/views/products.php
?>
            <!-- PRODUCTS TAB -->
            <div class="page-header" style="margin-bottom: 30px;">
                <h1 style="font-size: 1.8rem; color: var(--primary);">Catálogo de Productos</h1>
                <p style="color: var(--text-light);">Explora nuestro inventario y solicita cotizaciones.</p>
            </div>

            <!-- Search Bar -->
            <div class="premium-card" style="margin-bottom: 30px; padding: 20px;">
                <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                    <input type="hidden" name="tab" value="products">
                    <div class="input-wrapper" style="flex: 1;">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" class="premium-input" placeholder="Buscar productos por nombre o descripción..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    </div>
                    <button type="submit" class="premium-btn" style="width: auto; padding: 12px 25px;">
                        Buscar
                    </button>
                    <?php if(isset($_GET['q']) && $_GET['q'] !== ''): ?>
                        <a href="?tab=products" class="premium-btn" style="width: auto; background: #94a3b8; text-decoration: none; padding: 12px 20px;">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px;">
                <?php
                $search = $_GET['q'] ?? '';
                $sql = "SELECT * FROM products WHERE (name LIKE ? OR description LIKE ?)";
                $params = ["%$search%", "%$search%"];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $products = $stmt->fetchAll();

                if (empty($products)) {
                    echo '<div style="grid-column: 1/-1; text-align: center; padding: 60px; color: var(--text-muted); background: var(--surface-2); border-radius: 16px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);"><i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.5;"></i><p>No se encontraron productos disponibles.</p></div>';
                }

                foreach ($products as $product):
                    $img = !empty($product['image']) ? '../' . $product['image'] : 'https://placehold.co/400x300/f1f5f9/94a3b8?text=' . urlencode('Sin Imagen');
                ?>
                <div class="premium-card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s; border: none;">
                    <div style="height: 200px; background: var(--surface-3); display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                        <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">
                        <div style="position: absolute; top: 10px; right: 10px; background: var(--surface-2); border: 1px solid var(--border); padding: 5px 12px; border-radius: 20px; font-weight: 700; color: var(--secondary); font-size: 0.95rem; box-shadow: var(--shadow-sm);">
                            $<?= number_format($product['price'], 2) ?>
                        </div>
                    </div>
                    <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
                        <h3 style="margin: 0 0 10px; color: var(--primary); font-size: 1.15rem; font-weight: 700;"><?= htmlspecialchars($product['name']) ?></h3>
                        <p title="<?= htmlspecialchars($product['description']) ?>" style="color: var(--text-light); font-size: 0.9rem; flex: 1; margin-bottom: 20px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($product['description']) ?>
                        </p>
                        
                        <a href="?tab=products&add_to_quote=<?= urlencode($product['name']) ?>" class="premium-btn" style="text-decoration: none; justify-content: center;">
                            <i class="fas fa-cart-plus"></i> Agregar a Cotización
                        </a>
                    </div>
                </div>
                <!-- Hover Effect Style for Product Cards -->
                <style>
                    .premium-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-md); }
                </style>
                <?php endforeach; ?>
            </div>

            <!-- Floating Quote Cart Summary (Visible if items exist) -->
            <?php if(!empty($_SESSION['quote_cart'])): ?>
            <div style="position: fixed; bottom: 30px; right: 30px; background: var(--secondary); color: white; padding: 15px 25px; border-radius: 50px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 15px; z-index: 1000; animation: bounceIn 0.5s;">
                <div style="font-weight: 600;">
                    <i class="fas fa-clipboard-list"></i> <?= count($_SESSION['quote_cart']) ?> Productos en borrador
                </div>
                <a href="?tab=quotes" style="background: white; color: var(--secondary); padding: 5px 15px; border-radius: 20px; text-decoration: none; font-weight: bold; font-size: 0.9rem;">
                    Finalizar Cotización &rarr;
                </a>
            </div>
            <style>
                @keyframes bounceIn {
                    0% { transform: scale(0.8); opacity: 0; }
                    100% { transform: scale(1); opacity: 1; }
                }
            </style>
            <?php endif; ?>
