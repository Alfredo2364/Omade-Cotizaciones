<?php require_once '../includes/admin_header.php'; ?>
<?php if (!hasPermission($pdo, $_SESSION['user_id'], 'pos')) die("Acceso Denegado"); ?>

<div class="page-header">
    <h1><i class="fas fa-receipt" style="color: #94a3b8;"></i> Punto de Venta</h1>
</div>

<div class="pos-wrapper">
    <!-- Left Column: Product Search & Grid -->
    <div class="pos-products-section">
        
        <!-- Search Bar & Actions Component -->
        <?php include 'includes/pos_search.php'; ?>

        <!-- Quick Favorites Grid -->
        <div class="products-grid-container" style="z-index: 1;">
            <h4 style="margin: 0 0 15px 0; color: var(--muted); font-weight: 600;">Favoritos Rápidos</h4>
            <div class="products-grid" id="products-grid">
                <?php
                // Fetch Favorites
                try {
                    $favs = $pdo->query(
                        "SELECT id, name, price, image, stock FROM products WHERE pos_favorite = 1 AND stock > 0 LIMIT 20"
                    )->fetchAll();
                } catch (Exception $e) {
                    $favs = [];
                }
                
                function renderProductCard($p) {
                    // Safe handling of nulls to prevent warnings
                    $id = $p['id'] ?? 0;
                    $name = $p['name'] ?? 'Producto';
                    $price = $p['price'] ?? 0;
                    $image = $p['image'] ?? '';
                    
                    $img = !empty($image) ? '../' . $image : 'https://via.placeholder.com/100?text=Sin+Img';
                    $cleanName = addslashes($name);
                    
                    // htmlspecialchars needs non-null
                    $displayName = htmlspecialchars($name);
                    $displayPrice = number_format((float)$price, 2);
                    
                    return "
                    <div class='product-card' onclick=\"addToCart({$id}, '{$cleanName}', {$price})\">
                        <div class='product-img'>
                            <img src='{$img}' loading='lazy' decoding='async' onerror=\"this.src='https://via.placeholder.com/100?text=Sin+Img'\">
                        </div>
                        <div class='product-info'>
                            <div class='product-name'>" . $displayName . "</div>
                            <div class='product-price'>$" . $displayPrice . "</div>
                        </div>
                    </div>";
                }

                if (!empty($favs)) {
                    foreach($favs as $p) {
                        echo renderProductCard($p);
                    }
                } else {
                    echo "<p style='color:var(--muted); padding:10px;'>No hay favoritos configurados</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Cart Panel -->
    <div class="pos-cart-section">
        <div class="cart-card">
            <div class="cart-header">
                <h3><i class="fas fa-shopping-cart"></i> Carrito</h3>
                <span class="badge-items" id="cart-count">0 items</span>
            </div>
            
            <div class="cart-items-container" id="cart-items">
                <div class="empty-cart-state" id="empty-cart-msg">
                    <i class="fas fa-shopping-basket"></i>
                    <p>Carrito vacío</p>
                </div>
                <table class="cart-table" id="cart-table">
                    <tbody id="cart-table-body"></tbody>
                </table>
            </div>

            <div class="cart-footer">

                <!-- Seller + Client Info -->
                <div class="cart-meta-row">
                    <div class="cart-meta-item">
                        <i class="fas fa-user-tie"></i>
                        <span>Vendedor: <strong><?= htmlspecialchars($_SESSION['name'] . ' ' . ($_SESSION['paternal_surname'] ?? '')) ?></strong></span>
                    </div>
                    <div class="cart-client-input">
                        <i class="fas fa-user"></i>
                        <input type="text" id="client-name-input" placeholder="Nombre del cliente (opcional)" autocomplete="off" maxlength="100">
                    </div>
                </div>

                <div class="cart-total-row">
                    <span>Total</span>
                    <span class="total-amount" id="cart-total">$0.00</span>
                </div>
                
                <div class="cart-actions">
                    <button class="btn-checkout" onclick="checkout()">
                        <i class="fas fa-check-circle"></i> Cobrar
                    </button>
                    <button id="btn-cancel" class="btn-cancel-sale" onclick="cancelSale()" disabled>
                        <i class="fas fa-trash"></i> Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

// Cart Functions
function addToCart(id, name, price) {
    const existing = cart.find(item => item.id === id);
    if (existing) {
        existing.qty++;
    } else {
        cart.push({ id, name, price, qty: 1 });
    }
    renderCart();

    // Reset UI and focus back for rapid entry
    const searchInput = document.getElementById('prod-search');
    if(searchInput) {
        searchInput.value = '';
        const resultsBox = document.getElementById('search-results');
        if(resultsBox) resultsBox.style.display = 'none';
        searchInput.focus();
    }
}

function removeFromCart(index) {
    cart.splice(index, 1);
    renderCart();
}

function updateQty(index, newQty) {
    if(newQty < 1) {
        if(confirm('¿Eliminar producto?')) {
            removeFromCart(index);
        } else {
            renderCart(); 
        }
        return;
    }
    cart[index].qty = parseInt(newQty);
    renderCart();
}

function renderCart() {
    const tbody = document.getElementById('cart-table-body');
    const emptyMsg = document.getElementById('empty-cart-msg');
    const cartTable = document.getElementById('cart-table');
    
    tbody.innerHTML = '';
    let total = 0;
    let count = 0;
    
    if(cart.length === 0) {
        emptyMsg.style.display = 'flex';
        cartTable.style.display = 'none';
        document.getElementById('btn-cancel').disabled = true;
        document.getElementById('btn-cancel').style.opacity = '0.5';
    } else {
        emptyMsg.style.display = 'none';
        cartTable.style.display = 'table';
        document.getElementById('btn-cancel').disabled = false;
        document.getElementById('btn-cancel').style.opacity = '1';
        
        cart.forEach((item, index) => {
            count += item.qty;
            const itemTotal = item.price * item.qty;
            total += itemTotal;
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="col-name">
                    <div class="c-name">${item.name}</div>
                    <div class="c-price">$${item.price.toFixed(2)}</div>
                </td>
                <td class="col-qty">
                    <input type="number" value="${item.qty}" min="1" onchange="updateQty(${index}, this.value)">
                </td>
                <td class="col-total">$${itemTotal.toFixed(2)}</td>
                <td class="col-action"><button onclick="removeFromCart(${index})">&times;</button></td>
            `;
            tbody.appendChild(row);
        });
    }

    document.getElementById('cart-total').innerText = '$' + total.toFixed(2);
    document.getElementById('cart-count').innerText = count + ' items';
}

function cancelSale() {
    if (cart.length === 0) return;
    if (confirm('¿Vaciar carrito?')) {
        cart = [];
        renderCart();
    }
}

async function checkout() {
    if (cart.length === 0) return;
    if(!confirm('¿Procesar venta por ' + document.getElementById('cart-total').innerText + '?')) return;

    const btn = document.querySelector('.btn-checkout');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...';
    btn.disabled = true;

    try {
        const response = await fetch('../api/pos_checkout.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                cart: cart,
                client_name: document.getElementById('client-name-input').value.trim()
            })
        });
        const result = await response.json();
        
        if (result.success) {
            // Check if showToast exists, otherwise alert
            if(typeof showToast === 'function') {
                showToast('¡Venta registrada!', 'success');
            } else {
                alert('¡Venta registrada!');
            }
            
            cart = [];
            renderCart();
            document.getElementById('client-name-input').value = '';
            if(result.order_id) {
                window.open('print_receipt.php?id=' + result.order_id, '_blank');
            }
        } else {
            if(typeof showToast === 'function') {
                showToast(result.message, 'error');
            } else {
                alert(result.message);
            }
        }
    } catch (error) {
        alert('Error de conexión');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}
</script>

<style>
    /* Fixed Layout */
    .pos-wrapper {
        display: flex; gap: 20px; height: calc(100vh - 120px); align-items: flex-start;
    }
    .pos-products-section {
        flex: 1; display: flex; flex-direction: column; gap: 15px; height: 100%;
        position: relative; overflow: visible !important;
    }
    .pos-cart-section {
        width: 350px; height: 100%; display: flex; flex-direction: column;
    }

    /* Seller + Client meta row */
    .cart-meta-row {
        padding: 10px 15px;
        border-bottom: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .cart-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.82rem;
        color: var(--text-muted);
    }
    .cart-meta-item i { color: var(--muted); font-size: 0.9rem; flex-shrink: 0; }
    .cart-meta-item strong { color: var(--text-color); }
    .cart-client-input {
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--surface-3);
        border: 1px solid rgba(255,255,255,0.12); /* More visible border */
        border-radius: 8px;
        padding: 8px 12px;
        transition: all 0.2s ease;
        margin-top: 5px;
    }
    .cart-client-input:focus-within {
        border-color: #3b82f6;
        background: var(--surface-2);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    .cart-client-input i { color: #94a3b8; font-size: 0.9rem; flex-shrink: 0; }
    .cart-client-input input {
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.85rem;
        color: var(--text-color);
        width: 100%;
        padding: 0;
    }
    .cart-client-input input::placeholder { color: var(--muted); }
    .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; padding-bottom: 20px; }
    .product-card {
        background: var(--surface-2); border-radius: 10px; cursor: pointer;
        border: 1px solid var(--border); display: flex; flex-direction: column;
        overflow: hidden; height: 150px; transition: transform 0.2s;
    }
    .product-card:hover { transform: translateY(-3px); border-color: #3b82f6; }
    .product-img { height: 80px; display: flex; align-items: center; justify-content: center; background: var(--surface-3); }
    .product-img img { max-width: 70%; max-height: 80%; object-fit: contain; }
    .product-info { padding: 8px; text-align: center; flex: 1; display:flex; flex-direction:column; justify-content:center;}
    .product-name { font-size: 0.8rem; font-weight: 600; color: var(--text-color); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .product-price { font-size: 0.9rem; color: var(--secondary-color); font-weight: 700; }

    /* Cart */
    .cart-card { background: var(--surface-2); border-radius: 12px; box-shadow: var(--card-shadow); display: flex; flex-direction: column; height: 100%; border: 1px solid var(--border); }
    .cart-header { background: var(--surface-3); color: var(--text-color); padding: 15px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); }
    .cart-items-container { flex: 1; overflow-y: auto; position: relative; }
    .empty-cart-state { position: absolute; top:0; left:0; width:100%; height:100%; display:flex; flex-direction:column; align-items:center; justify-content:center; color: var(--text-muted); }
    .empty-cart-state i { font-size: 2.5rem; margin-bottom: 10px; opacity: 0.4; }
    
    .cart-table { width: 100%; border-collapse: collapse; }
    .cart-table td { padding: 10px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    .c-name { font-weight: 500; font-size: 0.85rem; color: var(--text-color); }
    .c-price { font-size: 0.75rem; color: var(--text-muted); }
    .col-qty input { width: 35px; padding: 4px; text-align: center; background: var(--surface-3); color: var(--text-color); border: 1px solid var(--border); border-radius: 4px; }
    .col-total { font-weight: 600; color: var(--text-color); text-align: right; font-size: 0.9rem; }
    .col-action button { background: none; border: none; color: #ef4444; cursor: pointer; }

    .cart-footer { padding: 15px; background: var(--surface-3); border-top: 1px solid var(--border); }
    .cart-total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-weight: 700; color: var(--text-color); font-size: 1.1rem; }
    .cart-actions { display: flex; flex-direction: column; gap: 8px; }
    .btn-checkout { background: #16a34a; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
    .btn-cancel-sale { background: white; color: #ef4444; border: 1px solid #ef4444; padding: 8px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }

    /* ---- POS TABLET (≤1024px): vertical stack ---- */
    @media (max-width: 1024px) {
        .pos-wrapper { flex-direction: column; height: auto; gap: 15px; }
        .pos-products-section { width: 100%; height: auto; }
        .pos-cart-section { width: 100%; height: auto; }
        .products-grid-container { max-height: 45vh; overflow-y: auto; }
        .products-grid { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); }
        .cart-card { height: auto; min-height: 300px; }
        .cart-items-container { max-height: 35vh; overflow-y: auto; }
        .btn-checkout, .btn-cancel-sale { padding: 14px; font-size: 1rem; min-height: 48px; }
        .col-qty input { width: 44px; height: 36px; font-size: 1rem; }
    }

    /* ---- POS PHONE (≤640px): ultra compact ---- */
    @media (max-width: 640px) {
        .pos-wrapper { padding: 0; gap: 12px; }
        .products-grid { grid-template-columns: repeat(auto-fill, minmax(90px, 1fr)); gap: 8px; }
        .product-card { height: 130px; }
        .product-img { height: 65px; }
        .product-name { font-size: 0.75rem; }
        .product-price { font-size: 0.82rem; }
        .cart-table td { padding: 8px 6px; }
        .c-name { font-size: 0.8rem; }
        .cart-total-row { font-size: 1rem; }
        .cart-header { padding: 12px 15px; }
        .cart-header h3 { font-size: 1rem; }
        .products-grid-container { max-height: 38vh; }
        .cart-items-container { max-height: 30vh; }
    }
</style>

</body>
</html>
