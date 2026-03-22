<?php require_once '../includes/admin_header.php'; ?>
<?php if (!hasPermission($pdo, $_SESSION['user_id'], 'products')) die("Acceso Denegado"); ?>

<?php
// --- Handle Form Submissions (Add/Update/Delete) ---

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $product_code = $_POST['product_code'] ?? null;
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category'] ?? 'General';
        
        $min_qty = $_POST['discount_min_qty'] ?? 0;
        $disc_percent = $_POST['discount_percent'] ?? 0;
        $in_carousel = isset($_POST['in_carousel']) ? 1 : 0;
        $pos_favorite = isset($_POST['pos_favorite']) ? 1 : 0;

        // Image Handling — with file type validation
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext  = ['jpg','jpeg','png','gif','webp'];
            $allowed_mime = ['image/jpeg','image/png','image/gif','image/webp'];
            $fileExt  = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $fileMime = mime_content_type($_FILES['image']['tmp_name']);

            if (!in_array($fileExt, $allowed_ext) || !in_array($fileMime, $allowed_mime)) {
                $_SESSION['flash'] = ['message' => 'Tipo de archivo no permitido. Solo imágenes (jpg, png, gif, webp).', 'type' => 'error'];
            } else {
                $uploadDir = '../uploads/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $fileName   = uniqid('img_') . '.' . $fileExt;
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imagePath = 'uploads/products/' . $fileName;
                }
            }
        }

        if(!empty($name) && is_numeric($price)) {
            $stmt = $pdo->prepare("INSERT INTO products (name, product_code, description, price, stock, category, discount_min_qty, discount_percent, image, in_carousel, pos_favorite) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if($stmt->execute([$name, $product_code, $description, $price, $stock, $category, $min_qty, $disc_percent, $imagePath, $in_carousel, $pos_favorite])) {
                logActivity($pdo, $_SESSION['user_id'], 'ADD_PRODUCT', "Añadió producto: $name");
                $_SESSION['flash'] = ['message' => 'Producto agregado correctamente', 'type' => 'success'];
                echo "<script>window.location.href='products.php';</script>";
            } else {
                 $_SESSION['flash'] = ['message' => 'Error al agregar producto', 'type' => 'error'];
            }
        }
    }

    // Handle Update Product
    if (isset($_POST['update_product'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $product_code = $_POST['product_code'] ?? null;
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category'] ?? 'General';
        
        $min_qty = $_POST['discount_min_qty'] ?? 0;
        $disc_percent = $_POST['discount_percent'] ?? 0;
        $in_carousel = isset($_POST['in_carousel']) ? 1 : 0;
        $pos_favorite = isset($_POST['pos_favorite']) ? 1 : 0;

        $imageSql = "";
        $params = [$name, $product_code, $description, $price, $stock, $category, $min_qty, $disc_percent, $in_carousel, $pos_favorite];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '.' . $fileExt;
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = 'uploads/products/' . $fileName;
                $imageSql = ", image = ?";
                $params[] = $imagePath;
            }
        }
        
        $params[] = $id;

        $stmt = $pdo->prepare("UPDATE products SET name = ?, product_code = ?, description = ?, price = ?, stock = ?, category = ?, discount_min_qty = ?, discount_percent = ?, in_carousel = ?, pos_favorite = ? $imageSql WHERE id = ?");
        
        if($stmt->execute($params)) {
            logActivity($pdo, $_SESSION['user_id'], 'UPDATE_PRODUCT', "Actualizó producto ID: $id");
            $_SESSION['flash'] = ['message' => 'Producto actualizado correctamente', 'type' => 'success'];
            echo "<script>window.location.href='products.php';</script>";
        } else {
            $_SESSION['flash'] = ['message' => 'Error al actualizar producto', 'type' => 'error'];
        }
    }
}

// Delete logic — requires POST with CSRF token to prevent CSRF via img/link attacks
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_csrf']) && $_POST['_csrf'] === ($_SESSION['csrf_token'] ?? '')) {
    $delId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delId]);
    logActivity($pdo, $_SESSION['user_id'], 'DELETE_PRODUCT', "Eliminó producto ID: $delId");
    $_SESSION['flash'] = ['message' => 'Producto eliminado correctamente', 'type' => 'success'];
    header('Location: products.php');
    exit;
}

// Fetch Product for Editing
$productToEdit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $productToEdit = $stmt->fetch();
}
?>

<!-- Action Bar & Search -->
<div class="card" style="padding: 20px; background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 25px; border: 1px solid #e2e8f0;">
    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
        
        <!-- Search Input with POS Style -->
        <div style="position: relative; flex: 2; min-width: 300px;">
            <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;"></i>
            <input type="text" id="productSearch" placeholder="Buscar producto por nombre, código o categoría..." 
                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                   style="width: 100%; padding: 14px 14px 14px 45px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 1rem; outline: none; transition: all 0.2s;">
            <!-- Instant Results Dropdown (Optional improvement for later, for now we search standardly or via JS filter if we want) -->
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 10px;">
             <a href="import_products.php" class="btn-import">
                <i class="fas fa-file-excel"></i> Importar
            </a>
            <button onclick="toggleProductForm()" class="btn-new-product">
                <i class="fas fa-plus-circle"></i> <span>Nuevo Producto</span>
            </button>
        </div>
    </div>
</div>

<!-- Add/Edit Form Container -->
<div id="productFormContainer" class="form-container <?= ($productToEdit || isset($_GET['new_code'])) ? 'open' : '' ?>">
    <div class="form-card">
        <div class="form-header">
            <h3><?= $productToEdit ? 'Editar Producto: ' . htmlspecialchars($productToEdit['name']) : 'Registrar Nuevo Producto' ?></h3>
            <button onclick="toggleProductForm()" class="btn-icon"><i class="fas fa-times"></i></button>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <?php if($productToEdit): ?>
                <input type="hidden" name="id" value="<?= $productToEdit['id'] ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <!-- Basic Info -->
                <div>
                    <label>Nombre del Producto</label>
                    <input type="text" name="name" class="form-control" required placeholder="Ej: Filtro de Aceite" value="<?= $productToEdit['name'] ?? '' ?>">
                </div>
                <div>
                    <label>Código (SKU/Barras)</label>
                    <input type="text" name="product_code" class="form-control" placeholder="Ej: FIL-001" value="<?= $productToEdit['product_code'] ?? htmlspecialchars($_GET['new_code'] ?? '') ?>">
                </div>
                <div>
                    <label>Precio ($)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required placeholder="0.00" value="<?= $productToEdit['price'] ?? '' ?>">
                </div>
                <div>
                    <label>Stock Actual</label>
                    <input type="number" name="stock" class="form-control" required placeholder="0" value="<?= $productToEdit['stock'] ?? '' ?>">
                </div>
                <div>
                     <label>Categoría</label>
                     <input type="text" name="category" class="form-control" placeholder="Ej: General" value="<?= $productToEdit['category'] ?? 'General' ?>">
                </div>
                <div>
                    <label>Imagen</label>
                    <?php if($productToEdit && $productToEdit['image']): ?>
                        <div style="font-size: 0.8rem; margin-bottom: 5px;"><a href="../<?= $productToEdit['image'] ?>" target="_blank">Ver actual</a></div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                
                <!-- Expanded Description -->
                <div style="grid-column: 1 / -1;">
                    <label>Descripción</label>
                    <textarea name="description" class="form-control" placeholder="Detalles del producto..." rows="2"><?= $productToEdit['description'] ?? '' ?></textarea>
                </div>

                <!-- Volume Discounts Panel -->
                <div class="discount-panel" style="grid-column: 1 / -1;">
                    <label class="panel-label">Descuento por Volumen (Opcional)</label>
                    <div class="discount-grid">
                        <div>
                            <label>Cant. Mínima</label>
                            <input type="number" name="discount_min_qty" class="form-control" placeholder="Ej: 5" value="<?= $productToEdit['discount_min_qty'] ?? 0 ?>">
                        </div>
                        <div>
                            <label>% Descuento</label>
                            <input type="number" step="0.5" name="discount_percent" class="form-control" placeholder="Ej: 10" value="<?= $productToEdit['discount_percent'] ?? 0 ?>">
                        </div>
                    </div>
                </div>

                <!-- Flags -->
                <div class="checkbox-group">
                    <input type="checkbox" name="in_carousel" id="in_carousel" value="1" <?= ($productToEdit && isset($productToEdit['in_carousel']) && $productToEdit['in_carousel']) ? 'checked' : '' ?>>
                    <label for="in_carousel">Mostrar en Carrusel de Ofertas</label>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="pos_favorite" id="pos_favorite" value="1" <?= ($productToEdit && isset($productToEdit['pos_favorite']) && $productToEdit['pos_favorite']) ? 'checked' : '' ?>>
                    <label for="pos_favorite">Mostrar en Botonera POS</label>
                </div>
            </div>

            <div class="form-actions">
                <?php if($productToEdit): ?>
                    <a href="products.php" class="btn-cancel">Cancelar</a>
                <?php endif; ?>
                <button type="submit" name="<?= $productToEdit ? 'update_product' : 'add_product' ?>" class="btn-submit">
                    <?= $productToEdit ? 'Actualizar' : 'Guardar' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table Container -->
<div class="card" style="padding: 0; overflow: hidden; border: none; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);">
     <div class="card-header-styled" style="background: white; padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; display: flex; align-items: center; gap: 10px; color: #1e293b;">
            <i class="fas fa-boxes" style="color: #3b82f6; background: #eff6ff; padding: 8px; border-radius: 8px;"></i> 
            Inventario de Productos
        </h3>
    </div>

    <div class="table-container" style="box-shadow: none; border-radius: 0;">
        <table>
            <thead>
                <tr>
                    <th style="padding-left: 20px;">Producto</th>
                    <th>Código</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Pagination & Search Logic
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $perPage = 10;
                // If searching via JS, we might want to load more, but for now stick to server logic
                // Or if we want true "POS Style", we can rely on JS for instant filter of loaded items... 
                // BUT given the pagination, server-side search is better.
                // Let's implement Server Search via GET param for now, triggerable by the input.
                
                $offset = ($page - 1) * $perPage;
                $search = $_GET['search'] ?? '';

                $where = "";
                $params = [];
                if (!empty($search)) {
                    $where = "WHERE name LIKE ? OR product_code LIKE ? OR category LIKE ?";
                    $params = ["%$search%", "%$search%", "%$search%"];
                }

                // Count total
                $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM products $where");
                $totalStmt->execute($params);
                $total = $totalStmt->fetchColumn();
                $totalPages = ceil($total / $perPage);

                // Fetch Data
                $sql = "SELECT * FROM products $where ORDER BY id DESC LIMIT $perPage OFFSET $offset";
                $stmt = $pdo->prepare($sql);
                foreach ($params as $k => $v) { $stmt->bindValue($k+1, $v); } // Bind if params exist
                if(!empty($params)) $stmt->execute(); else $stmt->execute();
                
                $products = $stmt->fetchAll();

                if (count($products) == 0) {
                    echo "<tr><td colspan='7' style='text-align:center; padding: 40px; color: #64748b;'>
                            <i class='far fa-folder-open fa-2x' style='margin-bottom: 10px; opacity: 0.5;'></i><br>
                            No se encontraron productos.
                          </td></tr>";
                }

                foreach ($products as $p): 
                    $img = !empty($p['image']) ? '../' . $p['image'] : '../assets/images/no-image.png'; // Fallback
                    // Only show placeholder if file exists check? No, simple fallback is fine.
                ?>
                <tr class="product-row-anim">
                    <td style="padding-left: 20px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; background: #f8fafc; display: flex; align-items: center; justify-content: center;">
                                <?php if(!empty($p['image'])): ?>
                                    <img src="../<?= $p['image'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-box" style="color: #cbd5e1; font-size: 1.5rem;"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem;"><?= htmlspecialchars($p['name']) ?></div>
                                <?php if($p['description']): ?>
                                    <div style="font-size: 0.8rem; color: #94a3b8; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars($p['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="color: #64748b; font-family: monospace; font-size: 0.9rem;"><?= htmlspecialchars($p['product_code'] ?? '-') ?></td>
                    <td style="font-weight: 700; color: #15803d; font-size: 1rem;">$<?= number_format($p['price'], 2) ?></td>
                    <td>
                        <?php if($p['stock'] < 5): ?>
                            <span style="color: #ef4444; font-weight: 700; background: #fee2e2; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;">
                                <?= $p['stock'] ?> (Bajo)
                            </span>
                        <?php else: ?>
                             <span style="color: #334155; font-weight: 600;">
                                <?= $p['stock'] ?> u.
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                            <?= htmlspecialchars($p['category'] ?? 'General') ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <?php if($p['in_carousel']): ?>
                                <span class="badge" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;">Oferta</span>
                            <?php endif; ?>
                            <?php if($p['pos_favorite']): ?>
                                <span class="badge" style="background: #fef3c7; color: #d97706; border: 1px solid #fde68a;">POS ★</span>
                            <?php endif; ?>
                            <?php if(!$p['in_carousel'] && !$p['pos_favorite']): ?>
                                <span class="badge" style="background: white; color: #94a3b8; border: 1px solid #e2e8f0;">Normal</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="text-align: center;">
                        <div class="action-buttons" style="justify-content: center; display: flex; gap: 5px;">
                            <a href="?edit=<?= $p['id'] ?>" class="btn-icon btn-edit" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                            <form method="POST" action="?delete=<?= $p['id'] ?>" style="display: inline; margin: 0;">
                                <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="btn-icon btn-delete" title="Eliminar" style="border:none; cursor:pointer;" onclick="return confirm('¿Seguro que deseas eliminar este producto?');">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Improved Pagination -->
    <?php if ($totalPages > 1): 
        $queryParams = ['search' => $search];
        echo renderPagination($page, $totalPages, $queryParams);
    endif; ?>
</div>

<script>
// Toggle Form
function toggleProductForm() {
    const container = document.getElementById('productFormContainer');
    if(container.classList.contains('open')) {
        container.classList.remove('open');
        if (window.location.search.includes('edit=')) {
            window.history.pushState({}, document.title, window.location.pathname);
        }
    } else {
        container.classList.add('open');
        setTimeout(() => document.querySelector('input[name="name"]').focus(), 300);
    }
}

// Search Logic (Debounced Redirect)
let searchTimeout;
const searchInput = document.getElementById('productSearch');
searchInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        window.location.href = '?search=' + encodeURIComponent(e.target.value);
    }, 600); // 600ms debounce before reloading with search
});

searchInput.addEventListener('focus', () => searchInput.parentElement.style.transform = 'scale(1.01)');
searchInput.addEventListener('blur', () => searchInput.parentElement.style.transform = 'scale(1)');
</script>

<style>
    /* Button Styles */
    .btn-new-product {
        background: #1e293b; color: white; border: none; padding: 12px 25px; border-radius: 10px;
        cursor: pointer; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.3);
        display: flex; align-items: center; gap: 8px; transition: all 0.2s;
    }
    .btn-new-product:hover { background: #334155; transform: translateY(-2px); }
    
    .btn-import {
        background: #fff; color: #10b981; border: 1px solid #10b981; padding: 12px 20px; border-radius: 10px;
        text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: all 0.2s;
    }
    .btn-import:hover { background: #ecfdf5; }

    /* Search Input Focus */
    #productSearch:focus { border-color: #3b82f6 !important; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }

    /* Form Container */
    .form-container {
        max-height: 0; opacity: 0; overflow: hidden;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 0;
    }
    .form-container.open { max-height: 1200px; opacity: 1; margin-bottom: 30px; }

    .form-card {
        background: white; padding: 30px; border-radius: 12px;
        border: 1px solid #e2e8f0; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        position: relative; overflow: hidden;
    }
    .form-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, #10b981, #3b82f6);
    }
    
    .form-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px; }
    .form-header h3 { margin: 0; color: #0f172a; font-size: 1.25rem; }

    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
    .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; outline: none; transition: 0.2s; }
    .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    
    label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 0.9rem; }

    /* Discount Panel */
    .discount-panel { background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px dashed #cbd5e1; }
    .panel-label { color: #2563eb; font-weight: 700; margin-bottom: 15px; }
    .discount-grid { display: flex; gap: 20px; }

    /* Checkboxes */
    .checkbox-group { display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 12px; border-radius: 8px; }
    .checkbox-group input { width: 18px; height: 18px; cursor: pointer; }
    .checkbox-group label { margin: 0; cursor: pointer; }

    /* Actions */
    .form-actions { display: flex; justify-content: flex-end; gap: 15px; margin-top: 30px; }
    .btn-submit { background: #0f172a; color: white; padding: 12px 30px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: 0.2s; }
    .btn-submit:hover { background: #1e293b; transform: translateY(-2px); }
    .btn-cancel { background: white; border: 1px solid #cbd5e1; color: #64748b; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 500; }
    .btn-cancel:hover { background: #f8fafc; color: #475569; }

    /* Table Override */
    table { width: 100%; border-collapse: separate; border-spacing: 0; }
    table th { 
        background: #f8fafc !important; color: #64748b !important; 
        text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; 
        padding: 15px; border-bottom: 1px solid #e2e8f0; 
    }
    table td { border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .product-row-anim:hover { background: #f8fafc; }
    
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
    
    .btn-icon { width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none; }
    .btn-edit { background: #fffbeb; color: #f59e0b; }
    .btn-edit:hover { background: #fef3c7; }
    .btn-delete { background: #fef2f2; color: #ef4444; }
    .btn-delete:hover { background: #fee2e2; }

    /* ---- Mobile Responsive ---- */
    @media (max-width: 1024px) {
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .form-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .page-header { flex-wrap: wrap; gap: 10px; }
    }
    @media (max-width: 480px) {
        .form-actions { flex-direction: column; }
        .btn-submit, .btn-cancel { width: 100%; text-align: center; justify-content: center; }
        table th, table td { font-size: 0.8rem; padding: 8px 10px; }
    }
</style>

</body>
</html>
