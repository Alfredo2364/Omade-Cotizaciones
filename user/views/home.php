<?php
// user/views/home.php
?>
            <!-- HOME TAB -->
            
            <!-- Welcome Hero -->
            <div class="welcome-hero">
                <div class="hero-content">
                    <h1>Bienvenido a Distribuciones Omade</h1>
                    <p>Gestiona tus pedidos, consulta nuestro catálogo de refacciones y solicita cotizaciones personalizadas en un solo lugar.</p>
                    <div style="display: flex; gap: 15px;">
                        <a href="?tab=products" class="btn-cta"><i class="fas fa-search"></i> Ver Catálogo</a>
                        <a href="?tab=quotes&action=new" class="btn-cta" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(8px); box-shadow: none; border: 1px solid rgba(255,255,255,0.1);">Nueva Cotización</a>
                    </div>
                </div>
                <div class="hero-decoration">
                    <i class="fas fa-cubes"></i>
                </div>
            </div>

            <!-- Carousel Premium -->
            <div class="section-title">
                <i class="fas fa-star" style="color: #f59e0b;"></i> Ofertas Destacadas
            </div>
            
            <div class="carousel-container" style="background: transparent; box-shadow: none; height: auto; margin-bottom: 40px;">
                <div class="carousel-window" style="overflow: hidden; width: 100%; position: relative;">
                    <div class="carousel-track" id="track" style="display: flex; transition: transform 0.5s ease-in-out; gap: 20px;">
                        <?php
                        $carousel_stmt = $pdo->query("SELECT * FROM products WHERE in_carousel = 1");
                        $slides = $carousel_stmt->fetchAll();
                        
                        if (empty($slides)) {
                             for($i=1; $i<=3; $i++) {
                                 echo '<div class="carousel-card-premium" style="text-align: center; border-style: dashed; border-color: var(--border); background: var(--surface-3);">
                                         <div class="premium-img-container" style="background: var(--surface-3); opacity: 0.6;">
                                            <i class="fas fa-rocket" style="font-size: 3.5rem; color: var(--text-muted);"></i>
                                        </div>
                                        <div class="premium-card-body">
                                            <h4 style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 8px;">¡Próximamente!</h4>
                                            <p style="font-size: 0.9rem; color: var(--text-muted);">Estamos preparando ofertas increíbles para ti.</p>
                                        </div>
                                       </div>';
                             }
                        } else {
                            // Display valid slides
                            foreach ($slides as $slide) {
                                $imgSrc = !empty($slide['image']) ? '../' . $slide['image'] : 'https://placehold.co/400x300/f1f5f9/94a3b8?text=' . urlencode('Sin Imagen');
                                echo "<div class='carousel-card-premium'>
                                        <div class='premium-img-container'>
                                            <img src='{$imgSrc}' loading='lazy' onerror=\"this.onerror=null;this.src='https://placehold.co/400x300/f1f5f9/94a3b8?text=Sin+Imagen';\">
                                        </div>
                                        <div class='premium-card-body'>
                                            <h4>" . htmlspecialchars($slide['name']) . "</h4>
                                            <p>" . htmlspecialchars(substr($slide['description'], 0, 60)) . "...</p>
                                            <div class='premium-price'>
                                                <span>$" . number_format($slide['price'], 2) . "</span>
                                                <a href='?tab=products' style='font-size: 0.9rem; color: #3b82f6; text-decoration: none;'>Ver más <i class='fas fa-arrow-right'></i></a>
                                            </div>
                                        </div>
                                      </div>";
                            }
                            
                            // Fill remaining slots if less than 5
                            $remaining = 5 - count($slides);
                            for($i=0; $i<$remaining; $i++) {
                                 echo '<div class="carousel-card-premium" style="text-align: center; border-style: dashed; border-color: var(--border); background: var(--surface-3);">
                                        <div class="premium-img-container" style="background: transparent;">
                                            <i class="fas fa-rocket" style="font-size: 3.5rem; color: var(--text-muted); opacity: 0.5;"></i>
                                        </div>
                                        <div class="premium-card-body">
                                            <h4 style="color: var(--text-muted); font-size: 1.2rem; margin-bottom: 10px;">¡Próximamente!</h4>
                                            <p style="font-size: 0.95rem; color: var(--text-muted);">Estamos preparando más ofertas. Mantente al tanto.</p>
                                        </div>
                                       </div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Stats Grid Premium -->
            <div class="stats-grid-premium">
                <a href="?tab=quotes" class="stat-card-premium" style="text-decoration: none; color: inherit; cursor: pointer;">
                    <div class="stat-icon-wrapper icon-blue">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Mis Cotizaciones</h3>
                        <div class="value"><?= $quotes_count ?></div>
                    </div>
                </a>
                <a href="?tab=orders" class="stat-card-premium" style="text-decoration: none; color: inherit; cursor: pointer;">
                    <div class="stat-icon-wrapper icon-green">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Mis Pedidos</h3>
                        <div class="value"><?= $orders_count ?></div>
                    </div>
                </a>
                <a href="?tab=support" class="stat-card-premium" style="text-decoration: none; color: inherit; cursor: pointer;">
                    <div class="stat-icon-wrapper icon-purple">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Soporte</h3>
                        <div class="value"><?= $msgs_count ?></div>
                    </div>
                </a>
            </div>

            <!-- Action Grid -->
            <div class="action-grid">
                <div class="action-box">
                    <h3>Buscar Producto</h3>
                    <p>Encuentra refacciones o servicios en nuestro catálogo actualizado.</p>
                    <a href="?tab=products" class="btn-action-primary">Ver Catálogo</a>
                </div>
                <div class="action-box">
                    <h3>Cotización Personalizada</h3>
                    <p>Solicita una cotización especial para tus necesidades.</p>
                    <a href="?tab=quotes&action=new" class="btn-action-primary">Crear cotización</a>
                </div>
            </div>
