<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    header("Location: ../login.php");
    exit;
}
require_once '../includes/db.php';
require_once '../admin/includes/pagination.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>OMADE Admin - Distribuciones Omade</title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon_io/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon_io/apple-touch-icon.png">
    <link rel="shortcut icon" href="../assets/favicon_io/favicon.ico">

    <!-- Preconnect para recursos externos (reduce latencia DNS+TCP) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- Google Fonts — carga no bloqueante -->
    <link rel="preload" as="style"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap"></noscript>

    <!-- Font Awesome — carga no bloqueante -->
    <link rel="preload" as="style"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>

    <!-- CSS local -->
    <link rel="stylesheet" href="../assets/css/style.css">


    <style>
        /* Admin Specific Overrides */
        body { display: flex; min-height: 100vh; background: #f4f7f6; font-family: 'Inter', sans-serif; margin: 0; }
        
        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background: #0f172a; /* Dark Navy similar to image */
            color: #ecf0f1;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 20000; /* Highest priority */
        }

        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .sidebar-brand h2 { margin: 0; color: #fff; font-size: 1.4rem; }
        .sidebar-brand p { margin: 5px 0 0; color: #bdc3c7; font-size: 0.9rem; }

        .sidebar-nav { padding: 0 15px; overflow-y: auto; flex: 1; }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .nav-item i { width: 25px; text-align: center; margin-right: 10px; font-size: 1.1rem; }
        
        .nav-item:hover, .nav-item.active {
            background: #1e293b;
            color: #38bdf8; /* Light Blue accent */
            transform: translateX(5px);
        }
        
        .nav-item.logout { margin-top: 20px; color: #ef4444; }
        .nav-item.logout:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* Main Content Styling */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 30px;
            width: calc(100% - 280px);
        }

        .page-header {
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 1.8rem;
            color: #1e293b;
            margin-bottom: 5px;
        }

        /* Shared Components */
        .card { 
            background: #fff; 
            border-radius: 10px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.05); 
            padding: 25px; 
            margin-bottom: 25px; 
        }
        
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 0; /* Let table fill */
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            overflow-x: auto; /* Enable horizontal scrolling for mobile */
        }
        
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #475569; font-weight: 600; text-align: left; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 15px 20px; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f8fafc; }

        .btn-action { padding: 6px 12px; border-radius: 4px; font-size: 0.85rem; font-weight: 500; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;}
        .btn-view { background: #3b82f6; color: white; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-success { background: #10b981; color: white; }
        
        /* Toast Notification Styles */
        #toast-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5); /* Semi-transparent dark overlay */
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(2px); /* Subtle blur effect */
        }
        
        #toast-box {
            background: white;
            padding: 30px 50px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            text-align: center;
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-width: 400px;
        }

        #toast-box.active {
            transform: scale(1);
            opacity: 1;
        }

        #toast-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }
        
        #toast-message {
            font-size: 1.1rem;
            color: #333;
            font-weight: 500;
        }
        /* Mobile Responsive adjustments */
        .mobile-toggle { display: none; font-size: 1.5rem; color: #1e293b; cursor: pointer; margin-bottom: 20px; }
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 19999; }

        /* Tablet */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .mobile-toggle {
                display: block;
            }
            .sidebar-overlay.active {
                display: block;
            }
            .page-header h1 { font-size: 1.4rem; }
            .card { padding: 18px; }
        }

        /* Phone */
        @media (max-width: 480px) {
            .main-content { padding: 12px; }
            .page-header { margin-bottom: 16px; }
            .page-header h1 { font-size: 1.2rem; }
            .card { padding: 14px; }
            .btn-action { padding: 5px 8px; font-size: 0.8rem; }
            td, th { padding: 10px 12px !important; font-size: 0.85rem; }
        }

        /* --- Boxed Pagination Styles --- */
        .pagination-container {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
            padding: 10px 5px 15px 5px; /* Added bottom padding for scrollbar */
            overflow-x: auto;
            white-space: nowrap;
            max-width: 100%;
            -webkit-overflow-scrolling: touch;
            
            /* Force Scrollbar Styling */
            scrollbar-width: auto; /* Standard width to ensure visibility */
            scrollbar-color: #64748b #e2e8f0; /* Darker thumb, visible track */
        }

        /* Webkit Scrollbar Styling (Chrome, Safari, Edge) */
        .pagination-container::-webkit-scrollbar {
            height: 12px; /* Thicker/Taller scrollbar */
        }
        
        .pagination-container::-webkit-scrollbar-track {
            background: #e2e8f0; /* Distinct track color */
            border-radius: 6px;
        }
        
        .pagination-container::-webkit-scrollbar-thumb {
            background-color: #64748b; /* Dark visible thumb */
            border-radius: 6px;
            border: 3px solid #e2e8f0; /* Padding around thumb */
        }

        .pagination-container::-webkit-scrollbar-thumb:hover {
            background-color: #475569;
        }

        .page-box {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-width: 40px;
            height: 40px;
            padding: 0 12px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .page-box:hover:not(.disabled):not(.active) {
            background: #f1f5f9;
            border-color: #94a3b8;
            color: #1e293b;
        }

        .page-box.active {
            background: #0f172a; 
            color: #ffffff;
            border-color: #0f172a;
        }

        .page-box.disabled {
            background: #f8fafc;
            color: #cbd5e1;
            cursor: not-allowed;
            border-color: #e2e8f0;
        }
        
        .page-box.prev-next {
            min-width: auto;
            padding: 0 15px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .page-dots {
            color: #94a3b8;
            font-weight: bold;
            padding: 0 5px;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>

<!-- Global Toast/Alert Component -->
<div id="toast-overlay">
    <div id="toast-box">
        <div id="toast-icon"></div>
        <div id="toast-message"></div>
    </div>
</div>

<!-- Mobile Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
        document.querySelector('.sidebar-overlay').classList.toggle('active');
    }

    // Global function to show notifications
    function showToast(message, type = 'success', duration = 2000) {
        const overlay = document.getElementById('toast-overlay');
        const box = document.getElementById('toast-box');
        const icon = document.getElementById('toast-icon');
        const msg = document.getElementById('toast-message');
        
        // Configure content
        msg.textContent = message;
        
        if(type === 'success') {
            icon.innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i>';
            box.style.borderTop = '5px solid #10b981';
        } else if (type === 'error') {
            icon.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i>';
            box.style.borderTop = '5px solid #ef4444';
        } else {
            icon.innerHTML = '<i class="fas fa-info-circle" style="color: #3b82f6;"></i>';
            box.style.borderTop = '5px solid #3b82f6';
        }
        
        // Show
        overlay.style.display = 'flex';
        // Small timeout to allow display:flex to apply before adding class for transition
        setTimeout(() => {
            box.classList.add('active');
        }, 10);
        
        // Hide after duration
        setTimeout(() => {
            box.classList.remove('active');
            setTimeout(() => {
                overlay.style.display = 'none';
            }, 300); // Wait for transition out
        }, duration);
    }
</script>

<?php
// Check for PHP session flash messages
if (isset($_SESSION['flash'])) {
    $msg = addslashes($_SESSION['flash']['message']); // Escape for JS
    $type = $_SESSION['flash']['type'] ?? 'success';
    echo "<script>document.addEventListener('DOMContentLoaded', () => showToast('$msg', '$type'));</script>";
    unset($_SESSION['flash']);
}
?>

<div class="sidebar">
    <div class="sidebar-brand">
        <!-- Placeholder for logo if user has one, otherwise text -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div style="flex: 1; text-align: center;">
                 <h2>OMADE Admin</h2>
                 <img src="logo_admin.png" alt="OMADE Logo" style="height: 50px; margin: 10px 0; display: block; margin-left: auto; margin-right: auto;">
                 <p>Panel de Control</p>
            </div>
            <!-- Close button for mobile -->
            <i class="fas fa-times" onclick="toggleSidebar()" style="cursor: pointer; font-size: 1.2rem; display: none;" id="mobile-close-btn"></i>
        </div>
        <style> @media(max-width: 768px) { #mobile-close-btn { display: block !important; } } </style>
    </div>
    
    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Inicio
        </a>
        
        <?php if(hasPermission($pdo, $_SESSION['user_id'], 'pos')): ?>
        <a href="pos.php" class="nav-item <?= $current_page == 'pos.php' ? 'active' : '' ?>">
            <i class="fas fa-cash-register"></i> Punto de Venta
        </a>
        <?php endif; ?>

        <?php if(hasPermission($pdo, $_SESSION['user_id'], 'orders')): ?>
        <a href="orders.php" class="nav-item <?= $current_page == 'orders.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i> Historial de Ventas
        </a>
        <?php endif; ?>

        <?php if(hasPermission($pdo, $_SESSION['user_id'], 'products')): ?>
        <a href="products.php" class="nav-item <?= $current_page == 'products.php' ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Productos
        </a>
        <?php endif; ?>

        <?php if(hasPermission($pdo, $_SESSION['user_id'], 'quotes')): ?>
        <a href="quotes.php" class="nav-item <?= $current_page == 'quotes.php' ? 'active' : '' ?>">
            <i class="fas fa-file-invoice-dollar"></i> Cotizaciones
        </a>
        <?php endif; ?>

        <?php if(hasPermission($pdo, $_SESSION['user_id'], 'clients')): ?>
        <a href="clients.php" class="nav-item <?= $current_page == 'clients.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Clientes
        </a>
        <?php endif; ?>

        <?php if(hasPermission($pdo, $_SESSION['user_id'], 'reports')): ?>
        <a href="reports.php" class="nav-item <?= $current_page == 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Reportes
        </a>
        <?php endif; ?>

        <?php if(hasPermission($pdo, $_SESSION['user_id'], 'support')): ?>
        <a href="support.php" class="nav-item <?= $current_page == 'support.php' ? 'active' : '' ?>">
            <i class="fas fa-comments"></i> Soporte
        </a>
        <?php endif; ?>
        
        <?php if($_SESSION['role'] === 'super_admin'): ?>
        <a href="admins.php" class="nav-item <?= $current_page == 'admins.php' ? 'active' : '' ?>">
            <i class="fas fa-user-shield"></i> Administradores
        </a>
        <a href="logs.php" class="nav-item <?= $current_page == 'logs.php' ? 'active' : '' ?>">
            <i class="fas fa-list-alt"></i> Log de Actividad
        </a>
        <?php endif; ?>

    </div>
    
    <div class="sidebar-footer" style="padding: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
        <a href="../api/logout.php" class="nav-item logout" style="margin: 0; justify-content: flex-start; color: #ef4444;">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </div>
</div>


<div class="main-content">
    <div class="mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>
    <style>
        .sidebar { height: 100dvh; } /* Ensure full height on mobile browsers */
        .sidebar-footer { padding-bottom: calc(env(safe-area-inset-bottom) + 20px) !important; }

        /* ---- Scroll-to-top button ---- */
        #scroll-top-btn {
            position: fixed; bottom: 28px; right: 24px; z-index: 9000;
            width: 44px; height: 44px; border-radius: 50%;
            background: #1e293b; color: white; border: none; cursor: pointer;
            display: none; align-items: center; justify-content: center;
            font-size: 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: background 0.2s, transform 0.2s, opacity 0.3s;
            opacity: 0;
        }
        #scroll-top-btn.visible { display: flex; opacity: 1; }
        #scroll-top-btn:hover { background: #3b82f6; transform: translateY(-3px); }

        /* ---- Page transition overlay ---- */
        #page-transition {
            position: fixed; inset: 0; background: #0f172a;
            z-index: 99990; pointer-events: none;
            opacity: 0; transition: opacity 0.18s ease;
        }
        #page-transition.fade-out { opacity: 0.25; }
    </style>

    <!-- Scroll to top button -->
    <button id="scroll-top-btn" title="Volver arriba" onclick="window.scrollTo({top:0,behavior:'smooth'})">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Page transition overlay -->
    <div id="page-transition"></div>

    <script>
        // Scroll-to-top visibility
        window.addEventListener('scroll', () => {
            const btn = document.getElementById('scroll-top-btn');
            if (window.scrollY > 300) btn.classList.add('visible');
            else btn.classList.remove('visible');
        }, { passive: true });

        // Keyboard shortcut: Ctrl+Shift+F → focus first search input
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'F') {
                e.preventDefault();
                const input = document.querySelector('input[type="text"], input[type="search"]');
                if (input) { input.focus(); input.select(); }
            }
        });

        // Subtle page transition on internal link clicks
        const overlay = document.getElementById('page-transition');
        document.querySelectorAll('a[href]').forEach(link => {
            if (link.hostname === location.hostname && !link.target && !link.href.startsWith('#') && !link.href.includes('javascript:')) {
                link.addEventListener('click', () => {
                    overlay.classList.add('fade-out');
                });
            }
        });
    </script>
    <!-- Top bar could go here if needed, but sidebar is clear enough -->

