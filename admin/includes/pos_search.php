
<!-- Search Bar & Actions Component -->
<div class="pos-search-bar-container">
    <div class="pos-search-bar">

        <!-- Row 1: Text Search (always full width on mobile) -->
        <div class="search-input-group">
            <i class="fas fa-search" id="search-icon"></i>
            <input type="text" id="prod-search" placeholder="Buscar producto por nombre..." autocomplete="off">
            <div id="search-results" class="search-results-dropdown"></div>
        </div>

        <!-- Row 2 on mobile: Barcode + Camera -->
        <div class="scan-row">
            <div class="scan-input-group">
                <i class="fas fa-barcode"></i>
                <input type="text" id="scan-input" placeholder="Código de barras" autocomplete="off" inputmode="text">
            </div>
            <button onclick="openScanner()" class="btn-scan-mobile" title="Abrir Cámara">
                <i class="fas fa-camera"></i>
                <span class="cam-label">Cámara</span>
            </button>
        </div>

    </div>
</div>

<!-- Scanner Modal -->
<div id="scanner-modal" class="scanner-modal">
    <div class="scanner-content">
        <h3>Escanear Código</h3>
        <div id="reader"></div>
        <button onclick="closeScanner()" class="btn-close-cam">Cerrar</button>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<style>
    /* ---- POS Search Container ---- */
    .pos-search-bar-container {
        position: relative;
        z-index: 100;
        margin-bottom: 20px;
    }

    .pos-search-bar {
        background: white;
        padding: 14px 16px;
        border-radius: 16px;
        display: flex;
        gap: 12px;
        flex-wrap: nowrap;       /* Desktop: single row */
        align-items: center;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        border: 1px solid #e2e8f0;
    }

    /* ---- Search group (text) ---- */
    .search-input-group {
        position: relative;
        flex: 1;                 /* Takes remaining space */
        min-width: 0;
    }
    .search-input-group i {
        position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
        color: #94a3b8; font-size: 1rem; pointer-events: none;
    }
    .search-input-group input {
        width: 100%;
        padding: 11px 12px 11px 38px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #f8fafc;
        color: #0f172a;
        box-sizing: border-box;
    }
    .search-input-group input:focus {
        border-color: #3b82f6;
        background: white;
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }

    /* ---- Scan row (barcode input + camera btn) ---- */
    .scan-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        width: 220px;            /* Fixed desktop width */
    }
    .scan-input-group {
        position: relative;
        flex: 1;
        min-width: 0;
    }
    .scan-input-group i {
        position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
        color: #94a3b8; font-size: 1rem; pointer-events: none;
    }
    .scan-input-group input {
        width: 100%;
        padding: 11px 10px 11px 36px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.2s;
        background: #f8fafc;
        box-sizing: border-box;
    }
    .scan-input-group input:focus {
        border-color: #6366f1;
        background: white;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
    }

    /* ---- Camera button ---- */
    .btn-scan-mobile {
        flex-shrink: 0;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 10px;
        padding: 11px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
        font-weight: 600;
        white-space: nowrap;
        transition: background 0.2s, transform 0.15s;
    }
    .btn-scan-mobile:hover { background: #2563eb; transform: translateY(-1px); }
    .btn-scan-mobile:active { background: #1d4ed8; transform: scale(0.97); }
    .cam-label { display: none; } /* Hidden by default, shown on tablet */

    /* ---- Search Results Dropdown ---- */
    .search-results-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        left: 0; right: 0;
        background: white;
        border: 2px solid #3b82f6;
        border-radius: 12px;
        box-shadow: 0 20px 30px rgba(0,0,0,0.15);
        z-index: 99999;
        display: none;
        max-height: 380px;
        overflow-y: auto;
    }
    .search-result-item {
        padding: 13px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.1s;
    }
    .search-result-item:hover { background: #eff6ff; }
    .search-result-item:last-child { border-bottom: none; }
    .search-loading-state {
        padding: 20px; text-align: center; color: #64748b; font-weight: 500;
    }

    /* ---- Scanner Modal ---- */
    .scanner-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 99999; align-items: center; justify-content: center; }
    .scanner-content { background: white; padding: 20px; border-radius: 16px; width: 90%; max-width: 400px; text-align: center; }
    .btn-close-cam { margin-top: 15px; width: 100%; padding: 12px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }

    /* ---- TABLET (768-1024px): scan row wraps below, camera shows label ---- */
    @media (max-width: 1024px) {
        .scan-row { width: auto; flex-shrink: 0; }
        .cam-label { display: inline; }
    }

    /* ---- DESKTOP: hide camera button (barcode typed or physical scanner used) ---- */
    @media (min-width: 1025px) {
        .btn-scan-mobile { display: none; }
    }

    /* ---- MOBILE (< 640px): stack into 2 rows ---- */
    @media (max-width: 640px) {
        .pos-search-bar {
            flex-direction: column;
            gap: 10px;
            padding: 12px;
        }
        .search-input-group { width: 100%; }
        .search-input-group input { font-size: 1rem; padding: 13px 12px 13px 40px; }
        .scan-row { width: 100%; }
        .scan-input-group input { font-size: 0.95rem; }
        .btn-scan-mobile { padding: 13px 18px; font-size: 0.95rem; }
        .cam-label { display: inline; }
    }
</style>


<script>
// --- Autocomplete & Search Logic ---
(function() {
    const searchInput = document.getElementById('prod-search');
    const resultsBox = document.getElementById('search-results');
    const searchIcon = document.getElementById('search-icon');
    let searchTimeout = null;

    // Show box immediately with loading state
    function showLoading() {
        resultsBox.innerHTML = '<div class="search-loading-state"><i class="fas fa-spinner fa-spin"></i> Buscando coincidencias...</div>';
        resultsBox.style.display = 'block';
    }

    async function performSearch(query) {
        if (!query) {
            resultsBox.style.display = 'none';
            return;
        }
        
        showLoading(); // FORCE DISPLAY on start

        try {
            console.log('Searching for:', query); // Debug log
            const res = await fetch(`../api/pos_search.php?q=${encodeURIComponent(query)}`);
            if (!res.ok) throw new Error('Network response was not ok');
            
            const products = await res.json();
            console.log('Results:', products); // Debug log
            
            resultsBox.innerHTML = ''; // Clear loading
            
            if (products.length > 0) {
                resultsBox.style.display = 'block'; // Ensure visible
                products.slice(0, 10).forEach(p => { 
                    const div = document.createElement('div');
                    div.className = 'search-result-item';
                    div.innerHTML = `
                        <div style="flex:1;">
                            <div style="font-weight: 600; color: #0f172a; font-size: 1rem;">${p.name}</div>
                            <div style="font-size: 0.8rem; color: #64748b;">CODE: ${p.product_code || 'N/A'}</div>
                        </div>
                        <div style="font-weight: 700; color: #16a34a; font-size: 1.1rem;">$${parseFloat(p.price).toFixed(2)}</div>
                    `;
                    div.onclick = function() {
                        if(typeof addToCart === 'function') {
                            addToCart(p.id, p.name, parseFloat(p.price));
                        }
                    };
                    resultsBox.appendChild(div);
                });
            } else {
                 resultsBox.style.display = 'block';
                 resultsBox.innerHTML = '<div class="search-result-item" style="justify-content:center; color:#94a3b8;">No se encontraron productos</div>';
            }
        } catch (err) {
            console.error("Error buscando productos:", err);
            resultsBox.innerHTML = `<div class="search-result-item" style="color:red;">Error: ${err.message}</div>`;
        }
    }

    if(searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const val = e.target.value.trim();
            if(val.length > 0) {
                searchTimeout = setTimeout(() => {
                    performSearch(val);
                }, 100); // Fast debounce
            } else {
                resultsBox.style.display = 'none';
            }
        });

        // Focus Handlers to keep box visible
        searchInput.addEventListener('focus', () => {
             if(searchInput.value.trim().length > 0) resultsBox.style.display = 'block';
        });

        // Delay hide on blur to allow clicking items
        searchInput.addEventListener('blur', () => {
            setTimeout(() => {
                // Check if we didn't just click inside the box (handled by click listener mostly)
                // resultsBox.style.display = 'none'; 
            }, 200);
        });
    }

    // Global click listener to close
    document.addEventListener('click', (e) => {
        if (searchInput && resultsBox && !searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });

    // Scanner Globals
    window.openScanner = function() {
        document.getElementById('scanner-modal').style.display = 'flex';
        if(!window.html5QrcodeScanner) window.html5QrcodeScanner = new Html5Qrcode("reader");
        window.html5QrcodeScanner.start({ facingMode: "environment" }, { fps: 10, qrbox: {width:250, height:250} }, onScanSuccess);
    };

    window.closeScanner = function() {
        document.getElementById('scanner-modal').style.display = 'none';
        if(window.html5QrcodeScanner) window.html5QrcodeScanner.stop().catch(()=>{});
    };

    function onScanSuccess(txt) {
        document.getElementById('scan-input').value = txt;
        window.closeScanner();
        const e = new KeyboardEvent('keypress', {key: 'Enter'});
        document.getElementById('scan-input').dispatchEvent(e);
    }
    
    const scanInput = document.getElementById('scan-input');
    if(scanInput) {
        scanInput.addEventListener('keypress', async (e) => {
            if(e.key === 'Enter') {
                e.preventDefault();
                const code = e.target.value.trim();
                if(!code) return;
                try {
                    const res = await fetch(`../api/pos_scan.php?code=${encodeURIComponent(code)}`);
                    const data = await res.json();
                    if(data.found) {
                        if(typeof addToCart === 'function') {
                            addToCart(data.product.id, data.product.name, parseFloat(data.product.price));
                            e.target.value = '';
                        }
                    } else {
                        if(confirm('Producto no encontrado. ¿Registrar?')) {
                            window.location.href = `products.php?new_code=${encodeURIComponent(code)}#addProductForm`;
                        } else {
                            e.target.value = '';
                        }
                    }
                } catch(err) { console.error(err); }
            }
        });
    }
})();
</script>
