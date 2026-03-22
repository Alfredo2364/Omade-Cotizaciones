<?php
require_once '../includes/db.php';

// Template Download (Must be before any output)
if(isset($_GET['download_template'])) {
    // Check permission logic here if needed, or rely on authentication check inside a separate include if reused
    // For now, minimal check since it's just a template
    session_start();
    if (!isset($_SESSION['user_id'])) die("Acceso denegado");

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="productos_plantilla.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Codigo', 'Nombre', 'Descripcion', 'Precio', 'Stock', 'Categoria']);
    fputcsv($output, ['FIL-001', 'Filtro de Aceite', 'Filtro sintetico premium', '150.00', '50', 'General']);
    fclose($output);
    exit();
}

require_once '../includes/admin_header.php';

if (!hasPermission($pdo, $_SESSION['user_id'], 'products')) die("Acceso Denegado");

$message = "";
$type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $fileName = $_FILES['csv_file']['tmp_name'];
    
    if ($_FILES['csv_file']['size'] > 0) {
        $file = fopen($fileName, "r");
        
        // Skip header row
        fgetcsv($file);
        
        $count = 0;
        $errors = 0;
        
        // Update SQL to include category
        $stmt = $pdo->prepare("INSERT INTO products (product_code, name, description, price, stock, category, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
            // column[0] = Code, [1] = Name, [2] = Desc, [3] = Price, [4] = Stock, [5] = Category
            
            $code = trim($column[0] ?? '');
            $name = trim($column[1] ?? '');
            $desc = trim($column[2] ?? '');
            $price = floatval($column[3] ?? 0);
            $stock = intval($column[4] ?? 0);
            $category = trim($column[5] ?? 'General'); // Default to 'General' if empty
            if (empty($category)) $category = 'General';
            
            // Strict Validation: Name MUST be present.
            if (!empty($name) && $price > 0) {
                try {
                    $stmt->execute([$code, $name, $desc, $price, $stock, $category]);
                    $count++;
                } catch (Exception $e) {
                    $errors++;
                }
            } else {
                // Skip if name is empty (even if price exists)
                $errors++;
            }
        }
        
        $message = "Importación completada: $count productos agregados. $errors errores/saltados.";
        $type = "success";
        
        logActivity($pdo, $_SESSION['user_id'], 'IMPORT_PRODUCTS', "Importó $count productos vía CSV");
    }
}
?>


<div class="page-header">
    <h1><i class="fas fa-file-import" style="color: var(--secondary);"></i> Importación Masiva</h1>
</div>

<div class="card premium-card" style="max-width: 700px; margin: 0 auto; padding: 40px;">
    
    <div style="text-align: center; margin-bottom: 30px;">
        <div style="background: #eff6ff; width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="fas fa-file-csv" style="font-size: 2.5rem; color: var(--secondary);"></i>
        </div>
        <h2 style="margin: 0; color: var(--primary); font-size: 1.5rem;">Cargar Productos desde CSV</h2>
        <p style="color: #64748b; margin-top: 10px;">
            Agrega o actualiza tu inventario rápidamente subiendo un archivo CSV.<br>
            Asegúrate de seguir el formato correcto.
        </p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-<?= $type == 'success' ? 'success' : 'danger' ?>" style="display: flex; align-items: center; gap: 15px; padding: 16px; border-radius: 12px; margin-bottom: 25px;">
            <i class="fas fa-<?= $type == 'success' ? 'check-circle' : 'exclamation-triangle' ?>" style="font-size: 1.5rem;"></i>
            <div>
                <strong><?= $type == 'success' ? '¡Éxito!' : 'Atención' ?></strong><br>
                <?= $message ?>
            </div>
        </div>
    <?php endif; ?>

    <div style="background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid var(--border); margin-bottom: 30px;">
        <h4 style="margin-top: 0; display: flex; align-items: center; gap: 10px; color: var(--primary);">
            <i class="fas fa-info-circle" style="color: var(--secondary);"></i> Instrucciones
        </h4>
        <ul style="margin: 0; padding-left: 20px; color: #475569; line-height: 1.6;">
            <li>El archivo debe ser formato <b>.CSV</b></li>
            <li>Las columnas requeridas son: <b>Código, Nombre, Descripción, Precio, Stock, Categoría</b>.</li>
            <li>Si el código ya existe, se creará un duplicado (o error según config).</li>
        </ul>
        <div style="margin-top: 15px;">
            <a href="?download_template=1" class="btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 0.9rem;">
                <i class="fas fa-download"></i> Descargar Plantilla de Ejemplo
            </a>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="importForm">
        <label class="file-upload-zone" style="display: block; border: 2px dashed #cbd5e1; border-radius: 16px; padding: 40px 20px; text-align: center; cursor: pointer; transition: all 0.2s; background: white;">
            <input type="file" name="csv_file" accept=".csv" required style="display: none;" onchange="updateFileName(this)">
            <div id="uploadIcon">
                <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
            </div>
            <h4 style="margin: 0; color: #64748b;" id="fileNameText">Haz clic o arrastra tu archivo aquí</h4>
            <span style="display: block; font-size: 0.85rem; color: #94a3b8; margin-top: 5px;">Máximo 5MB (Formato .csv)</span>
        </label>
        
        <button type="submit" class="premium-btn" style="margin-top: 25px; width: 100%; justify-content: center;">
            <i class="fas fa-file-import"></i> Iniciar Importación
        </button>
    </form>
    
    <div style="margin-top: 25px; text-align: center;">
        <a href="products.php" class="btn-back-professional">
            <i class="fas fa-arrow-left"></i> Volver al Catálogo
        </a>
    </div>
</div>


<style>
    :root {
        --btn-primary: #2563eb;
        --btn-hover: #3b82f6; /* Lighter */
    }

    .file-upload-zone:hover {
        border-color: var(--secondary);
        background: #eff6ff;
    }
    .btn-secondary {
        background: white; border: 1px solid var(--border); color: var(--text-main);
        padding: 8px 14px; border-radius: 8px; font-weight: 500; transition: all 0.2s;
        box-shadow: var(--shadow-xs);
        text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-secondary:hover {
        background: #f1f5f9; border-color: #cbd5e1; color: var(--primary);
    }
    .premium-btn {
        background: var(--btn-primary); /* Defined explicit fallback color or var */
        color: white; border: none; padding: 14px;
        border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer;
        transition: all 0.3s ease; box-shadow: var(--shadow-sm); display: flex; align-items: center;
    }
    .premium-btn:hover {
        background: var(--btn-hover); /* Lighter on hover */
        transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
    }

    .btn-back-professional {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px;
        color: #64748b; text-decoration: none; font-weight: 600; font-size: 0.95rem;
        border: 1px solid transparent; border-radius: 99px;
        transition: all 0.2s;
        background: transparent;
    }
    .btn-back-professional:hover {
        background: #f1f5f9;
        color: var(--primary);
        border-color: #cbd5e1;
    }
</style>

<script>
    function updateFileName(input) {
        if (input.files && input.files[0]) {
            const fileName = input.files[0].name;
            document.getElementById('fileNameText').textContent = fileName;
            document.getElementById('fileNameText').style.color = 'var(--primary)';
            document.getElementById('uploadIcon').innerHTML = '<i class="fas fa-file-csv" style="font-size: 3rem; color: var(--secondary);"></i>';
            document.querySelector('.file-upload-zone').style.borderColor = 'var(--secondary)';
            document.querySelector('.file-upload-zone').style.background = '#eff6ff';
        }
    }
</script>
