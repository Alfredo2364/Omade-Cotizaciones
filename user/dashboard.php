<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: ../login.php");
    exit;
}
require_once '../includes/db.php';

// Mock active tab logic (simple one-page dashboard for now, or links to sections)
$active_tab = $_GET['tab'] ?? 'home';

// Fetch stats for the cards
$my_quotes_count = $pdo->prepare("SELECT COUNT(*) FROM quotes WHERE client_email = ?"); // associating by email for now
$my_quotes_count->execute([$_SESSION['email'] ?? '']); // Assuming email is stored in session or we fetch it
$quotes_count = $my_quotes_count->fetchColumn();

// Fetch orders (mock connection as we don't have full user-order link established nicely in previous steps for 'client' role specifically without login flow fully tight, but assuming user_id works)
$my_orders_count = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$my_orders_count->execute([$_SESSION['user_id']]);
$orders_count = $my_orders_count->fetchColumn();

// Quote Cart Logic
if (!isset($_SESSION['quote_cart'])) {
    $_SESSION['quote_cart'] = [];
}

// Add Item
if (isset($_GET['add_to_quote'])) {
    $item = $_GET['add_to_quote'];
    if (!in_array($item, $_SESSION['quote_cart'])) {
        $_SESSION['quote_cart'][] = $item;
        $_SESSION['flash'] = "Bi-Check: Producto agregado al borrador de cotización."; // Simple flash msg or just rely on reload
    }
    // Redirect to remove query param and avoid re-add on refresh
    header("Location: dashboard.php?tab=products&added=1");
    exit;
}

// Clear Cart
if (isset($_GET['clear_quote_cart'])) {
    $_SESSION['quote_cart'] = [];
    header("Location: dashboard.php?tab=quotes");
    exit;
}

// Fetch unread messages
$my_msgs = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$my_msgs->execute([$_SESSION['user_id']]);
$msgs_count = $my_msgs->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel del Cliente - Distribuciones Omade</title>
    <!-- Preconnect -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Non-blocking fonts -->
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap"></noscript>
    <link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon_io/site.webmanifest">
    <style>
        /* Premium Dashboard Styles */
        :root {
            --primary: #0f172a;
            --secondary: #2563eb;
            --accent: #6366f1;
            --background: #f1f5f9;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body { background-color: var(--background); color: var(--text-main); font-family: 'Inter', sans-serif; }
        
        /* Navbar Premium */
        .client-navbar {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 40px;
            color: var(--text-main);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); /* Subtle shadow */
            position: sticky; top: 0; z-index: 1000;
            border-bottom: 1px solid var(--border);
        }

        .client-logo {
            display: flex; align-items: center; gap: 12px;
        }
        .client-logo h2 {
            margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--primary); letter-spacing: -0.5px;
        }
        .client-logo img { 
            height: 40px; width: auto; 
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        
        .nav-right { display: flex; align-items: center; gap: 25px; }

        .greeting {
            font-weight: 600; font-size: 0.95rem; color: var(--text-light);
            display: flex; align-items: center; gap: 8px;
            background: #f1f5f9; padding: 6px 12px; border-radius: 20px;
        }

        .nav-link {
            text-decoration: none; color: var(--text-light); font-weight: 500; font-size: 0.95rem;
            transition: all 0.2s; position: relative;
        }
        .nav-link:hover, .nav-link.active { color: var(--secondary); }
        .nav-link.active::after {
            content: ''; position: absolute; bottom: -24px; left: 0; width: 100%; height: 3px;
            background: var(--secondary); border-radius: 3px 3px 0 0;
        }
        
        .btn-logout {
            background: #fee2e2; color: #ef4444; padding: 8px 16px; border-radius: 8px;
            font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: 0.2s;
        }
        .btn-logout:hover { background: #fecaca; transform: translateY(-1px); }

        .main-container { max-width: 1280px; margin: 40px auto; padding: 0 20px; }

        /* Premium Cards */
        .premium-card {
            background: white; border-radius: 16px; padding: 24px;
            border: 1px solid var(--border); box-shadow: var(--shadow-sm);
            transition: box-shadow 0.2s, transform 0.2s;
        }
        
        /* Premium Forms */
        .premium-form-group { margin-bottom: 24px; }
        .premium-label {
            display: block; font-weight: 600; margin-bottom: 8px; color: var(--text-main); font-size: 0.9rem;
        }
        
        .input-wrapper {
            position: relative; display: flex; align-items: center;
        }
        .input-wrapper i {
            position: absolute; left: 16px; color: var(--text-light); z-index: 10; font-size: 1rem;
            pointer-events: none; transition: color 0.2s;
        }
        .premium-input, .premium-select, .premium-textarea {
            width: 100%; padding: 12px 16px 12px 45px; /* Added left padding for icon */
            border: 1px solid var(--border); border-radius: 12px;
            font-family: 'Inter', sans-serif; font-size: 0.95rem; color: var(--text-main);
            background: white; transition: all 0.2s ease-in-out; box-sizing: border-box;
            box-shadow: var(--shadow-xs);
        }
        .premium-input:focus, .premium-select:focus, .premium-textarea:focus {
            outline: none; border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        /* Focus state for icon */
        .premium-input:focus + i, .premium-input:focus ~ i { color: var(--secondary); }

        .premium-btn {
            background: linear-gradient(135deg, var(--secondary) 0%, #1d4ed8 100%);
            color: white; border: none; padding: 14px;
            border-radius: 12px; font-weight: 600; font-size: 1rem; cursor: pointer;
            width: 100%; transition: all 0.2s; display: flex; justify-content: center; align-items: center; gap: 10px;
            box-shadow: var(--shadow-sm);
        }
        .premium-btn:hover {
            transform: translateY(-2px); box-shadow: var(--shadow-md);
        }
        .premium-btn:disabled {
            background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none;
        }

        /* Premium Tables */
        .premium-table-container {
            background: white; border-radius: 16px; overflow: hidden;
            border: 1px solid var(--border); box-shadow: var(--shadow-sm);
        }
        .premium-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .premium-table th {
            background: #f8fafc; padding: 16px 24px; text-align: left;
            font-weight: 700; color: var(--text-light); font-size: 0.8rem; 
            text-transform: uppercase; letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }
        .premium-table td {
            padding: 16px 24px; border-bottom: 1px solid var(--border);
            color: var(--text-main); font-size: 0.95rem; vertical-align: middle;
            transition: background 0.2s;
        }
        .premium-table tr:last-child td { border-bottom: none; }
        .premium-table tr:hover td { background: #f8fafc; }
        
        .status-badge {
            padding: 6px 12px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; 
            display: inline-flex; align-items: center; gap: 6px; letter-spacing: 0.025em;
            text-transform: uppercase;
        }
        .status-badge::before {
            content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor;
        }
        .status-pending { background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; }
        .status-approved { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; }
        .status-rejected { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }

        /* Premium Stats Grid */
        .stats-grid-premium {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px; margin-bottom: 40px;
        }
        .stat-card-premium {
            background: white; border-radius: 16px; padding: 24px;
            border: 1px solid var(--border); box-shadow: var(--shadow-sm);
            display: flex; align-items: center; gap: 20px; transition: transform 0.2s;
        }
        .stat-card-premium:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .stat-icon-wrapper {
            width: 56px; height: 56px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;
        }
        .icon-blue { background: #eff6ff; color: #3b82f6; }
        .icon-green { background: #f0fdf4; color: #22c55e; }
        .icon-purple { background: #f5f3ff; color: #8b5cf6; }
        .stat-info h3 { margin: 0 0 4px; font-size: 0.9rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        .stat-info .value { font-size: 1.75rem; font-weight: 700; color: var(--primary); line-height: 1; }

        /* Premium Carousel */
        .section-title {
            font-size: 1.5rem; font-weight: 700; color: var(--primary);
            margin-bottom: 24px; display: flex; align-items: center; gap: 12px;
        }
        .carousel-card-premium {
            min-width: 300px; max-width: 320px; flex-shrink: 0;
            background: white; border-radius: 16px; overflow: hidden;
            border: 1px solid var(--border); box-shadow: var(--shadow-sm);
            display: flex; flex-direction: column; transition: transform 0.2s;
        }
        .carousel-card-premium:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .premium-img-container {
            height: 180px; background: #f8fafc; display: flex;
            align-items: center; justify-content: center; overflow: hidden;
        }
        .premium-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .premium-card-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
        .premium-card-body h4 { margin: 0 0 8px; color: var(--primary); font-size: 1.1rem; }
        .premium-card-body p { margin: 0 0 16px; color: var(--text-light); font-size: 0.9rem; line-height: 1.5; flex: 1; }
        .premium-price {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: auto; font-weight: 700; color: var(--secondary); font-size: 1.1rem;
        }

        /* Action Grid */
        .action-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px; margin-top: 40px;
        }
        .action-box {
            background: white; border-radius: 16px; padding: 32px;
            border: 1px solid var(--border); box-shadow: var(--shadow-sm);
            text-align: center; transition: transform 0.2s;
        }
        .action-box:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
        .action-box h3 { margin: 0 0 12px; color: var(--primary); font-size: 1.25rem; }
        .action-box p { color: var(--text-light); margin-bottom: 24px; line-height: 1.6; }
        
        .btn-cta, .btn-action-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: linear-gradient(135deg, var(--secondary) 0%, #1d4ed8 100%);
            color: white; padding: 12px 24px; border-radius: 9999px;
            font-weight: 600; text-decoration: none; transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2); border: none; font-size: 0.95rem;
        }
        .btn-cta:hover, .btn-action-primary:hover {
            transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
        }
        /* Hero Section */
        .welcome-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 24px;
            padding: 60px 40px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            margin-bottom: 40px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .hero-content { max-width: 600px; z-index: 2; position: relative; }
        .hero-content h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 15px; letter-spacing: -1px; }
        .hero-content p { font-size: 1.1rem; color: #cbd5e1; margin-bottom: 25px; line-height: 1.6; }
        .hero-decoration {
            position: absolute; right: -50px; bottom: -50px; opacity: 0.1;
            font-size: 15rem; color: white; transform: rotate(-15deg);
        }



        /* Mobile Sidebar (Restored) */
        .mobile-sidebar {
            position: fixed; top: 0; left: 0; width: 280px; height: 100%;
            background: var(--primary); z-index: 2000;
            transform: translateX(-100%); transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 25px; display: flex; flex-direction: column;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        .mobile-sidebar.active { transform: translateX(0); }
        .sidebar-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(2px); z-index: 1999;
        }
        .sidebar-overlay.active { display: block; }
        
        .mobile-nav-link {
            display: block; padding: 12px 0; color: rgba(255,255,255,0.8);
            text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1);
            font-size: 1rem; transition: 0.2s;
        }
        .mobile-nav-link:hover, .mobile-nav-link.active { color: white; padding-left: 10px; }
        
        .mobile-menu-btn { display: none; font-size: 1.5rem; cursor: pointer; color: var(--text-main); }

        /* Responsive */
        @media (max-width: 1400px) {
            .carousel-card-premium { min-width: calc(25% - 15px); } /* 4 items */
        }
        @media (max-width: 1100px) {
             .carousel-card-premium { min-width: calc(33.33% - 14px); } /* 3 items */
            .stats-grid-premium { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 850px) {
            .carousel-card-premium { min-width: calc(50% - 10px); } /* 2 items */
            .stats-grid-premium { grid-template-columns: repeat(2, 1fr); }
        }
        /* ---- Mobile Responsive ---- */
        @media (max-width: 1024px) {
            .main-container { margin: 20px auto; padding: 0 15px; }
            .action-grid { grid-template-columns: 1fr; }
            .premium-table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        }
        @media (max-width: 768px) {
            .client-navbar { padding: 12px 20px; }
            .client-logo h2 { font-size: 1rem; }
            .main-container { margin: 15px auto; padding: 0 10px; }
            .welcome-hero { padding: 35px 20px; }
            .hero-content h1 { font-size: 1.6rem; }
            .stats-grid-premium { grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px; }
            .action-grid { gap: 16px; }
            .section-title { font-size: 1.2rem; }
            .premium-table th, .premium-table td { padding: 12px 15px; font-size: 0.9rem; }
            /* quotes-layout: stack 2-col grid to single column on tablet/phone */
            .quotes-layout { grid-template-columns: 1fr !important; gap: 20px !important; }
            /* Ensure tables scroll horizontally */
            .premium-table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        }
        @media (max-width: 600px) {
            .stats-grid-premium { grid-template-columns: 1fr; }
            .carousel-card-premium { min-width: 100%; }
            .welcome-hero { flex-direction: column; text-align: center; padding: 30px 15px; }
            .hero-decoration { display: none; }
            .hero-content h1 { font-size: 1.4rem; }
            .hero-content p { font-size: 0.95rem; }
            .nav-right { display: none; }
            .client-navbar { padding: 12px 15px; }
            .mobile-menu-btn { display: block; }
            .main-container { padding-bottom: 80px; } /* Space for bottom nav */
            /* Hero CTA buttons stack vertically */
            .hero-content > div[style*='display: flex'] { flex-direction: column !important; gap: 10px !important; }
            .btn-cta { width: 100%; justify-content: center; }
        }

        /* Bottom navigation bar for mobile */
        .bottom-nav {
            display: none;
            position: fixed; bottom: 0; left: 0; right: 0;
            background: white; border-top: 1px solid #e2e8f0;
            padding: 8px 0 max(8px, env(safe-area-inset-bottom));
            z-index: 5000; justify-content: space-around; align-items: center;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.06);
        }
        .bottom-nav a {
            display: flex; flex-direction: column; align-items: center; gap: 3px;
            text-decoration: none; color: #94a3b8; font-size: 0.65rem; font-weight: 600;
            padding: 4px 8px; border-radius: 8px; transition: 0.2s; min-width: 52px;
        }
        .bottom-nav a i { font-size: 1.2rem; }
        .bottom-nav a.active, .bottom-nav a:hover { color: var(--secondary); background: #eff6ff; }
        @media (max-width: 600px) { .bottom-nav { display: flex; } }
    </style>
</head>
<body>

<!-- Mobile Sidebar & Overlay -->
<div class="sidebar-overlay" onclick="toggleMobileMenu()"></div>
<div class="mobile-sidebar">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 15px;">
        <h3 style="margin: 0; color: white;">Menú</h3>
        <i class="fas fa-times" style="color: white; font-size: 1.4rem; cursor: pointer;" onclick="toggleMobileMenu()"></i>
    </div>
    
    <div style="margin-bottom: 20px; color: rgba(255,255,255,0.8); text-align: center;">
        Hola, <?= htmlspecialchars($_SESSION['name']) ?>
    </div>

    <a href="?tab=profile" class="nav-btn">Mi Perfil</a>
    <a href="?tab=quotes" class="nav-btn">Cotizaciones</a>
    <a href="?tab=orders" class="nav-btn">Pedidos</a>
    <a href="support.php" class="nav-btn" style="position: relative;">
        Soporte
        <?php if($msgs_count > 0): ?>
            <span style="position: absolute; top: 5px; right: 10px; background: #ef4444; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;"><?= $msgs_count ?></span>
        <?php endif; ?>
    </a>
    <a href="../api/logout.php" class="nav-btn logout" style="margin-top: auto;">Salir</a>
</div>

<script>
    function toggleMobileMenu() {
        document.querySelector('.mobile-sidebar').classList.toggle('active');
        document.querySelector('.sidebar-overlay').classList.toggle('active');
    }
</script>

    <!-- Navbar -->
    <nav class="client-navbar">
        <div class="client-logo">
            <img src="../assets/images/logo/Logo.png" alt="OMADE Logo">
            <h2>Distribuciones Omade</h2>
        </div>
        
        <div class="nav-right">
            <div class="greeting">
                <i class="fas fa-user-circle" style="color: #64748b;"></i>
                Hola, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>
            </div>
            <a href="?tab=home" class="nav-link <?= $active_tab == 'home' ? 'active' : '' ?>">Inicio</a>
            <a href="?tab=quotes" class="nav-link <?= $active_tab == 'quotes' ? 'active' : '' ?>">Cotizaciones</a>
            <a href="?tab=orders" class="nav-link <?= $active_tab == 'orders' ? 'active' : '' ?>">Pedidos</a>
            <a href="?tab=profile" class="nav-link <?= $active_tab == 'profile' ? 'active' : '' ?>">Mi Perfil</a>
            
            <a href="support.php" class="nav-link" style="position: relative;">
                <i class="far fa-comment-dots" style="font-size: 1.1rem;"></i>
                <?php if($msgs_count > 0): ?>
                    <span style="position: absolute; top: -5px; right: -8px; background: #ef4444; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: bold;"><?= $msgs_count ?></span>
                <?php endif; ?>
            </a>
            
            <a href="../api/logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
        
        <div class="mobile-menu-btn" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <div class="main-container">

        <?php
        // Dynamic View Loading
        $valid_tabs = ['home', 'orders', 'quotes', 'profile', 'products', 'support'];
        
        // Default to home
        if (!in_array($active_tab, $valid_tabs)) {
            $active_tab = 'home';
        }
        
        $view_file = __DIR__ . "/views/$active_tab.php";
        
        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo "<div class='alert alert-danger'>Error: La vista para '$active_tab' no se encuentra.</div>";
        }
        ?>
    
    </div>

    <!-- Bottom Mobile Navigation Bar -->
    <nav class="bottom-nav">
        <a href="?tab=home" class="<?= $active_tab == 'home' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> Inicio
        </a>
        <a href="?tab=quotes" class="<?= $active_tab == 'quotes' ? 'active' : '' ?>">
            <i class="fas fa-file-invoice-dollar"></i> Cot.
        </a>
        <a href="?tab=orders" class="<?= $active_tab == 'orders' ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Pedidos
        </a>
        <a href="?tab=profile" class="<?= $active_tab == 'profile' ? 'active' : '' ?>">
            <i class="fas fa-user"></i> Perfil
        </a>
        <a href="../api/logout.php">
            <i class="fas fa-sign-out-alt"></i> Salir
        </a>
    </nav>

    <script>
        // Professional Multi-Item Carousel Logic
        const track = document.getElementById('track');
        if (track) {
            let index = 0;
            const items = document.querySelectorAll('.carousel-card-premium');
            const totalItems = items.length;
            
            function getVisibleItems() {
                const width = window.innerWidth;
                if (width <= 600) return 1;
                if (width <= 850) return 2;
                if (width <= 1100) return 3;
                if (width <= 1400) return 4;
                return 5;
            }

            function autoSlide() {
                const visibleItems = getVisibleItems();
                // If we have enough items to scroll
                if (totalItems > visibleItems) {
                    index++;
                    const maxIndex = totalItems - visibleItems;
                    
                    if (index > maxIndex) {
                        index = 0;
                    }

                    // Calculate move percentage based on item width + gap
                    // Improved: Move by item width relative to track
                    // Since specific percentages are used in CSS with calc, we can approximate or calculate.
                    // Easiest: item[0].offsetWidth + gap
                    
                    const itemWidth = items[0].offsetWidth;
                    // Adding 20px gap (from CSS .carousel-track gap: 20px)
                    const moveAmount = (itemWidth + 20) * index;
                    
                    track.style.transform = `translateX(-${moveAmount}px)`;
                } else {
                    // Reset if everything fits
                    track.style.transform = `translateX(0)`;
                    index = 0;
                }
            }

            // Only start interval if scrolling is possible or window resizes
            let interval = setInterval(autoSlide, 3500);
            
            // Reset on resize to recalculate widths
            window.addEventListener('resize', () => {
                clearInterval(interval);
                index = 0;
                track.style.transform = `translateX(0)`;
                interval = setInterval(autoSlide, 3500);
            });
        }
    </script>
    <!-- Global Toast/Alert Component -->
    <div id="toast-overlay">
        <div id="toast-box">
            <div id="toast-icon"></div>
            <div id="toast-message"></div>
        </div>
    </div>

    <script>
        // Global function to show notifications (Inline for dashboard)
        function showToast(message, type = 'success', duration = 2000) {
            const overlay = document.getElementById('toast-overlay');
            const box = document.getElementById('toast-box');
            const icon = document.getElementById('toast-icon');
            const msg = document.getElementById('toast-message');
            
            // Configure content
            msg.textContent = message;
            
            if(type === 'success') {
                icon.innerHTML = '<span style="color: #10b981; font-size: 3rem;">&#10004;</span>'; 
                box.style.borderTop = '5px solid #10b981';
            } else if (type === 'error') {
                icon.innerHTML = '<span style="color: #ef4444; font-size: 3rem;">&#10006;</span>';
                box.style.borderTop = '5px solid #ef4444';
            } else {
                icon.innerHTML = '<span style="color: #3b82f6; font-size: 3rem;">&#8505;</span>';
                box.style.borderTop = '5px solid #3b82f6';
            }
            
            // Show
            overlay.style.display = 'flex';
            setTimeout(() => { box.classList.add('active'); }, 10);
            
            // Hide
            setTimeout(() => {
                box.classList.remove('active');
                setTimeout(() => { overlay.style.display = 'none'; }, 300);
            }, duration);
        }
    </script>
</body>
</html>
