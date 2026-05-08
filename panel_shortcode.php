<?php
/**
 * MB Vajilla — Panel de Administración
 * Shortcode: [mbv_panel]
 * Activar en: Code Snippets → scope "Front-end only"
 */

function mbv_panel_shortcode() {

    // --- Auth check ---
    if ( ! is_user_logged_in() ) {
        wp_redirect( wp_login_url( get_permalink() ) );
        exit;
    }
    if ( ! current_user_can( 'edit_posts' ) ) {
        return '<p style="padding:40px;text-align:center;font-family:sans-serif">No tenés permisos para acceder a este panel.</p>';
    }

    $nonce    = wp_create_nonce( 'wp_rest' );
    $api_base = rest_url();   // e.g. https://www.mbvajilla.com.ar/wp-json/

    ob_start();
    ?>
    <!-- Hide WP theme chrome on this page -->
    <style>
    /* Flatsome-specific + generic WP theme selectors */
    .mbvp-active #header,
    .mbvp-active #header-outer,
    .mbvp-active #header-section,
    .mbvp-active .header-wrapper,
    .mbvp-active header.header,
    .mbvp-active .site-header,
    .mbvp-active #top-bar,
    .mbvp-active .nav-bar,
    .mbvp-active .header-nav,
    .mbvp-active #footer,
    .mbvp-active #footer-outer,
    .mbvp-active footer.footer,
    .mbvp-active .site-footer,
    .mbvp-active #breadcrumbs,
    .mbvp-active .breadcrumbs,
    .mbvp-active .flatsome-breadcrumbs,
    .mbvp-active .entry-header,
    .mbvp-active .page-title,
    .mbvp-active #wpadminbar { display: none !important; }

    /* Remove WP admin bar offset */
    html.mbvp-html { margin-top: 0 !important; }
    html.mbvp-html body { margin-top: 0 !important; }

    .mbvp-active #main,
    .mbvp-active .content-area,
    .mbvp-active .entry-content,
    .mbvp-active article,
    .mbvp-active .post-content,
    .mbvp-active .container { margin: 0 !important; padding: 0 !important; max-width: 100% !important; width: 100% !important; }

    /* === MB Vajilla Panel — Design Tokens === */
    :root {
      --red:        #A91818;
      --red-dark:   #6b0e0e;
      --black:      #1a1a1a;
      --white:      #ffffff;
      --gray-50:    #f8f8f8;
      --gray-100:   #f1f1f1;
      --gray-200:   #e2e2e2;
      --gray-400:   #9ca3af;
      --gray-600:   #475569;
      --gray-900:   #0f172a;
      --font-display: 'Playfair Display SC', serif;
      --font-ui:      'Karla', sans-serif;
      --sidebar-w:  240px;
      --topbar-h:   56px;
      --radius:     8px;
      --shadow:     0 2px 12px rgba(0,0,0,0.08);
      --shadow-lg:  0 8px 32px rgba(0,0,0,0.12);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font-ui); color: var(--gray-900); background: var(--gray-50); }
    a { color: inherit; text-decoration: none; }
    input, textarea, select, button { font-family: var(--font-ui); }

    /* === LOGIN PAGE === */
    .login-page { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--gray-50); }
    .login-wrap { width: 100%; max-width: 400px; padding: 24px; }
    .login-box { background: var(--white); border-radius: 16px; padding: 40px 36px; box-shadow: var(--shadow-lg); }
    .login-logo { font-family: var(--font-display); font-size: 1.75rem; color: var(--red); text-align: center; margin-bottom: 4px; }
    .login-subtitle { color: var(--gray-400); text-align: center; font-size: 0.875rem; margin-bottom: 28px; }
    .login-error { background: #fef2f2; border: 1px solid #fca5a5; color: #b91c1c; border-radius: var(--radius); padding: 10px 14px; margin-bottom: 16px; font-size: 0.875rem; }

    /* === FORM FIELDS === */
    .field-group { margin-bottom: 16px; }
    .field-group label { display: block; font-size: 0.8125rem; font-weight: 600; color: var(--gray-600); margin-bottom: 6px; }
    .field-group input,
    .field-group textarea,
    .field-group select { width: 100%; padding: 10px 14px; border: 1.5px solid var(--gray-200); border-radius: var(--radius); font-size: 0.9375rem; color: var(--gray-900); background: var(--white); transition: border-color 0.15s; }
    .field-group input:focus,
    .field-group textarea:focus,
    .field-group select:focus { outline: none; border-color: var(--red); }
    .field-group textarea { resize: vertical; min-height: 80px; }

    /* === BUTTONS === */
    .btn-primary { background: var(--red); color: var(--white); border: none; padding: 11px 20px; border-radius: var(--radius); font-size: 0.9375rem; font-weight: 700; cursor: pointer; transition: background 0.15s; }
    .btn-primary:hover { background: var(--red-dark); }
    .btn-secondary { background: var(--white); color: var(--gray-600); border: 1.5px solid var(--gray-200); padding: 10px 20px; border-radius: var(--radius); font-size: 0.9375rem; font-weight: 600; cursor: pointer; transition: border-color 0.15s; }
    .btn-secondary:hover { border-color: var(--gray-400); }
    .btn-danger { background: var(--white); color: #b91c1c; border: 1.5px solid #fca5a5; padding: 10px 20px; border-radius: var(--radius); font-size: 0.9375rem; font-weight: 600; cursor: pointer; }
    .btn-danger:hover { background: #fef2f2; }
    .btn-full { width: 100%; }
    .btn-sm { padding: 6px 14px; font-size: 0.8125rem; }

    /* === PANEL LAYOUT === */
    .panel-page { display: grid; grid-template-rows: var(--topbar-h) 1fr; grid-template-columns: var(--sidebar-w) 1fr; min-height: 100vh; }

    .topbar { grid-column: 1 / -1; background: var(--black); display: flex; align-items: center; padding: 0 20px; gap: 16px; position: sticky; top: 0; z-index: 100; }
    .topbar-logo { font-family: var(--font-display); color: var(--red); font-size: 1.1rem; flex: 1; }
    .topbar-logout { color: var(--gray-400); font-size: 0.875rem; cursor: pointer; padding: 6px 12px; border-radius: var(--radius); }
    .topbar-logout:hover { color: var(--white); background: rgba(255,255,255,0.08); }
    .hamburger { display: none; background: none; border: none; color: var(--white); font-size: 1.25rem; cursor: pointer; padding: 4px 8px; }

    .sidebar { background: var(--white); border-right: 1px solid var(--gray-100); padding: 20px 0; }
    .sidebar-nav { list-style: none; }
    .nav-link { display: flex; align-items: center; gap: 10px; padding: 11px 20px; font-size: 0.9375rem; font-weight: 500; color: var(--gray-600); transition: all 0.15s; cursor: pointer; }
    .nav-link:hover, .nav-link.active { color: var(--red); background: rgba(169,24,24,0.06); }
    .nav-link.active { border-left: 3px solid var(--red); }
    .nav-link.nav-disabled { opacity: 0.45; cursor: not-allowed; pointer-events: none; }
    .nav-icon { width: 18px; text-align: center; font-size: 0.75rem; }
    .badge-soon { font-size: 0.65rem; background: var(--gray-100); color: var(--gray-400); padding: 2px 7px; border-radius: 999px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }

    .main-content { padding: 28px 32px; overflow-y: auto; }
    .hidden { display: none !important; }

    /* === DASHBOARD === */
    .dashboard-greeting { font-size: 1.375rem; font-weight: 700; color: var(--gray-900); margin-bottom: 24px; }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px; }
    .stat-card { background: var(--white); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
    .stat-card.stat-red { background: var(--red); color: var(--white); }
    .stat-card.stat-black { background: var(--black); color: var(--white); }
    .stat-number { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-label { font-size: 0.8125rem; opacity: 0.75; margin-top: 4px; }
    .quick-access { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; }
    .qa-card { background: var(--white); border: 1.5px solid var(--gray-200); border-radius: var(--radius); padding: 20px; text-align: center; cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s; }
    .qa-card:hover { border-color: var(--red); box-shadow: var(--shadow); }
    .qa-icon { font-size: 1.75rem; margin-bottom: 8px; }
    .qa-label { font-size: 0.875rem; font-weight: 700; color: var(--gray-900); }

    /* === PRODUCTOS LIST === */
    .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px; }
    .section-title { font-size: 1.25rem; font-weight: 700; }
    .filter-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
    .filter-chip { padding: 6px 14px; border-radius: 999px; border: 1.5px solid var(--gray-200); font-size: 0.8125rem; font-weight: 600; cursor: pointer; background: var(--white); color: var(--gray-600); transition: all 0.15s; }
    .filter-chip.active { background: var(--red); color: var(--white); border-color: var(--red); }
    .productos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; }
    .prod-card { background: var(--white); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); cursor: pointer; transition: box-shadow 0.15s, transform 0.15s; }
    .prod-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
    .prod-card-img { width: 100%; aspect-ratio: 1; object-fit: cover; background: var(--gray-100); display: flex; align-items: center; justify-content: center; }
    .prod-card-body { padding: 12px; }
    .prod-card-name { font-size: 0.875rem; font-weight: 600; margin-bottom: 6px; }
    .prod-card-badges { display: flex; gap: 4px; flex-wrap: wrap; }
    .badge { font-size: 0.625rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 3px 8px; border-radius: 999px; }
    .badge-red { background: var(--red); color: var(--white); }
    .badge-orange { background: #f97316; color: var(--white); }
    .badge-gray { background: var(--gray-100); color: var(--gray-600); }

    /* === PRODUCTO FORM === */
    .form-section { background: var(--white); border-radius: var(--radius); padding: 24px; box-shadow: var(--shadow); margin-bottom: 20px; }
    .form-section-title { font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--gray-600); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--gray-100); }
    .bilingual-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .lang-label { font-size: 0.8125rem; font-weight: 700; margin-bottom: 12px; }
    .lang-es { color: var(--red); }
    .lang-en { color: #1a6ebf; }
    .translate-btn { font-size: 0.75rem; background: #e8f0fe; color: #1a6ebf; border: 1px solid #c5d8f8; padding: 4px 10px; border-radius: 999px; cursor: pointer; font-weight: 600; white-space: nowrap; }
    .translate-btn:hover { background: #d2e3fc; }
    .lang-en-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .foto-upload-area { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 12px; }
    .foto-slot { aspect-ratio: 1; border: 2px dashed var(--gray-200); border-radius: var(--radius); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; position: relative; transition: border-color 0.15s; }
    .foto-slot:hover { border-color: var(--red); }
    .foto-slot img { width: 100%; height: 100%; object-fit: cover; }
    .foto-slot .slot-label { font-size: 0.65rem; color: var(--gray-400); text-align: center; padding: 4px; }
    .foto-slot .remove-foto { position: absolute; top: 4px; right: 4px; background: rgba(0,0,0,0.6); color: var(--white); border: none; border-radius: 999px; width: 20px; height: 20px; font-size: 0.75rem; cursor: pointer; display: none; align-items: center; justify-content: center; }
    .foto-slot:hover .remove-foto { display: flex; }
    .form-actions { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; padding-top: 20px; }

    /* === LOADING / TOASTS === */
    .loading { display: flex; align-items: center; justify-content: center; padding: 60px; color: var(--gray-400); font-size: 0.9375rem; }
    .toast { position: fixed; bottom: 24px; right: 24px; background: var(--black); color: var(--white); padding: 12px 20px; border-radius: var(--radius); font-size: 0.9rem; font-weight: 500; box-shadow: var(--shadow-lg); z-index: 9999; opacity: 0; transform: translateY(10px); transition: all 0.25s; max-width: 320px; }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.toast-success { background: #166534; }
    .toast.toast-error { background: #991b1b; }

    /* === RESPONSIVE === */
    @media (max-width: 768px) {
      .panel-page { grid-template-columns: 1fr; }
      .sidebar { position: fixed; left: -100%; top: var(--topbar-h); bottom: 0; width: var(--sidebar-w); z-index: 50; transition: left 0.25s; box-shadow: var(--shadow-lg); }
      .sidebar.open { left: 0; }
      .hamburger { display: block; }
      .main-content { padding: 20px 16px; }
      .bilingual-grid { grid-template-columns: 1fr; }
      .foto-upload-area { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 480px) {
      .productos-grid { grid-template-columns: repeat(2, 1fr); }
      .foto-upload-area { grid-template-columns: repeat(2, 1fr); }
    }
    </style>

    <div id="mbvp-root" class="panel-page">
        <!-- Top bar -->
        <header class="topbar">
            <button class="hamburger" id="hamburger" aria-label="Menú">&#9776;</button>
            <span class="topbar-logo">MB Vajilla</span>
            <a href="<?php echo esc_url( wp_logout_url( home_url('/panel-admin/') ) ); ?>" class="topbar-logout">Salir</a>
        </header>

        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <ul class="sidebar-nav">
                <li><a href="#dashboard" class="nav-link active" data-section="dashboard"><span class="nav-icon">&#9632;</span> Dashboard</a></li>
                <li><a href="#productos" class="nav-link" data-section="productos"><span class="nav-icon">&#9633;</span> Productos</a></li>
                <li><a href="#contenido" class="nav-link" data-section="contenido"><span class="nav-icon">&#9632;</span> Contenido</a></li>
                <li><a class="nav-link nav-disabled"><span class="nav-icon">&#9632;</span> Multimedia <span class="badge-soon">Pronto</span></a></li>
                <li><a class="nav-link nav-disabled"><span class="nav-icon">&#9632;</span> Estadísticas <span class="badge-soon">Pronto</span></a></li>
            </ul>
        </nav>

        <!-- Main -->
        <main class="main-content">
            <div id="section-dashboard"></div>
            <div id="section-productos" class="hidden"></div>
            <div id="section-contenido" class="hidden"></div>
        </main>
    </div>

    <script>
    document.body.classList.add('mbvp-active');
    document.documentElement.classList.add('mbvp-html');

    // Force-hide Flatsome header/footer/adminbar by direct DOM manipulation
    // (CSS-only approach can miss elements loaded after paint)
    (function hideChromeNow() {
        const selectors = [
            '#header', '#header-outer', '#header-section', '.header-wrapper',
            'header.header', '.site-header', '#top-bar', '.nav-bar', '.header-nav',
            '#footer', '#footer-outer', 'footer.footer', '.site-footer',
            '#breadcrumbs', '.breadcrumbs', '.flatsome-breadcrumbs',
            '.entry-header', '.page-title', '#wpadminbar'
        ];
        selectors.forEach(function(sel) {
            document.querySelectorAll(sel).forEach(function(el) {
                el.style.setProperty('display', 'none', 'important');
            });
        });
        document.documentElement.style.setProperty('margin-top', '0', 'important');
        document.body.style.setProperty('margin-top', '0', 'important');
    })();

    window.MBV_NONCE = <?php echo json_encode( $nonce ); ?>;
    window.MBV_API   = <?php echo json_encode( $api_base ); ?>;

    // === MB Vajilla Panel JS — WP REST API direct ===
    const NONCE = window.MBV_NONCE;
    const API   = window.MBV_API;  // e.g. "https://www.mbvajilla.com.ar/wp-json/"

    // --- WP REST API helpers ---
    async function wpGet(path) {
        const res = await fetch(API + path, {
            headers: { 'X-WP-Nonce': NONCE }
        });
        if (res.status === 401 || res.status === 403) { alert('Sesión expirada. Recargá la página.'); return null; }
        return res.json();
    }

    async function wpPost(path, data) {
        const res = await fetch(API + path, {
            method: 'POST',
            headers: { 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return res.json();
    }

    async function wpDelete(path) {
        const res = await fetch(API + path, {
            method: 'DELETE',
            headers: { 'X-WP-Nonce': NONCE }
        });
        return res.json();
    }

    async function wpUpload(file) {
        const fd = new FormData();
        fd.append('file', file, file.name);
        const res = await fetch(API + 'wp/v2/media', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': NONCE,
                'Content-Disposition': 'attachment; filename="' + file.name + '"'
            },
            body: fd
        });
        return res.json();
    }

    // --- Dashboard stats from products list ---
    async function getDashboardStats() {
        const prods = await wpGet('wp/v2/producto?lang=es&per_page=100&acf_format=standard');
        if (!prods || !Array.isArray(prods)) return { total_productos: 0, sin_medidas: 0, ultima_actualizacion: '-' };
        return {
            total_productos: prods.length,
            sin_medidas: prods.filter(p => !p.acf?.medidas).length,
            ultima_actualizacion: new Date().toLocaleDateString('es-AR') + ' ' + new Date().toLocaleTimeString('es-AR', {hour:'2-digit',minute:'2-digit'})
        };
    }

    // --- Toast notifications ---
    function toast(msg, type = '') {
        let el = document.getElementById('mbv-toast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'mbv-toast';
            el.className = 'toast';
            document.body.appendChild(el);
        }
        el.textContent = msg;
        el.className = 'toast show' + (type ? ' toast-' + type : '');
        clearTimeout(el._t);
        el._t = setTimeout(() => { el.className = 'toast'; }, 3500);
    }

    // --- Shared state ---
    let _productosState = { list: [], categories: [], filter: 'all' };

    // --- Router ---
    function navigate(section) {
        document.querySelectorAll('.nav-link').forEach(l =>
            l.classList.toggle('active', l.dataset.section === section)
        );
        ['dashboard', 'productos', 'contenido'].forEach(s => {
            const el = document.getElementById('section-' + s);
            if (el) el.classList.toggle('hidden', s !== section);
        });
        document.getElementById('sidebar')?.classList.remove('open');
        if (section === 'dashboard') renderDashboard();
        if (section === 'productos') renderProductos();
        if (section === 'contenido') renderContenido();
    }

    // Hamburger toggle
    document.getElementById('hamburger')?.addEventListener('click', () => {
        document.getElementById('sidebar')?.classList.toggle('open');
    });

    // Nav links
    document.querySelectorAll('.nav-link:not(.nav-disabled)').forEach(link => {
        link.addEventListener('click', e => { e.preventDefault(); navigate(link.dataset.section); });
    });

    // Quick access card clicks (delegated)
    document.addEventListener('click', e => {
        const qa = e.target.closest('.qa-card[data-nav]');
        if (qa) navigate(qa.dataset.nav);
    });

    // =====================================================================
    // DASHBOARD
    // =====================================================================
    async function renderDashboard() {
        const el = document.getElementById('section-dashboard');
        el.innerHTML = '<div class="loading">Cargando...</div>';
        const stats = await getDashboardStats();
        if (!stats) return;
        const h = new Date().getHours();
        const greeting = h < 12 ? 'Buenos días' : h < 19 ? 'Buenas tardes' : 'Buenas noches';
        el.innerHTML = `
            <p class="dashboard-greeting">${greeting}, Lorena 👋</p>
            <div class="stats-grid">
              <div class="stat-card stat-red">
                <div class="stat-number">${stats.total_productos}</div>
                <div class="stat-label">Productos</div>
              </div>
              <div class="stat-card stat-black">
                <div class="stat-number">${stats.sin_medidas}</div>
                <div class="stat-label">Sin medidas</div>
              </div>
              <div class="stat-card">
                <div class="stat-number" style="font-size:1rem;color:var(--gray-600)">${stats.ultima_actualizacion}</div>
                <div class="stat-label">Última actualización</div>
              </div>
            </div>
            <div class="quick-access">
              <div class="qa-card" data-nav="productos">
                <div class="qa-icon">📦</div>
                <div class="qa-label">Productos</div>
              </div>
              <div class="qa-card" style="opacity:.45;cursor:not-allowed">
                <div class="qa-icon">✏️</div>
                <div class="qa-label">Contenido<small style="display:block;font-size:.65rem;color:var(--gray-400)">Pronto</small></div>
              </div>
              <div class="qa-card" style="opacity:.45;cursor:not-allowed">
                <div class="qa-icon">🖼️</div>
                <div class="qa-label">Multimedia<small style="display:block;font-size:.65rem;color:var(--gray-400)">Pronto</small></div>
              </div>
              <div class="qa-card" style="opacity:.45;cursor:not-allowed">
                <div class="qa-icon">📊</div>
                <div class="qa-label">Estadísticas<small style="display:block;font-size:.65rem;color:var(--gray-400)">Pronto</small></div>
              </div>
            </div>`;
    }

    // =====================================================================
    // PRODUCTOS LIST
    // =====================================================================
    async function renderProductos() {
        const el = document.getElementById('section-productos');
        el.innerHTML = '<div class="loading">Cargando productos...</div>';
        const [cats, prods] = await Promise.all([
            wpGet('wp/v2/categoria_producto?per_page=50&lang=es'),
            wpGet('wp/v2/producto?lang=es&per_page=100&acf_format=standard'),
        ]);
        if (!cats || !prods) return;
        _productosState.categories = Array.isArray(cats) ? cats : [];
        _productosState.list = Array.isArray(prods) ? prods : [];
        _productosState.filter = 'all';
        renderProductosList(el);
    }

    function renderProductosList(el) {
        const { list, categories, filter } = _productosState;
        const filtered = filter === 'all'
            ? list
            : list.filter(p => Array.isArray(p.categoria_producto) && p.categoria_producto.includes(parseInt(filter)));

        const chips = [{ id: 'all', name: 'Todos' }, ...categories]
            .map(c => `<button class="filter-chip${filter == c.id ? ' active' : ''}" data-cat="${c.id}">${c.name}</button>`)
            .join('');

        const cards = filtered.length
            ? filtered.map(p => {
                const foto = p.acf?.foto_principal?.url || p.acf?.foto_principal?.source_url || '';
                const nombre = p.title?.rendered || p.title || '(sin nombre)';
                const destacado = p.acf?.destacado;
                const sinMedidas = !p.acf?.medidas;
                const badge = p.acf?.badge;
                const enId = p.translations?.en || p.acf?._en_id || '';
                return `
                  <div class="prod-card" data-id="${p.id}" data-en-id="${enId}">
                    ${foto
                      ? `<img class="prod-card-img" src="${foto}" alt="${nombre}" loading="lazy">`
                      : `<div class="prod-card-img" style="color:var(--gray-400);font-size:.75rem">Sin foto</div>`}
                    <div class="prod-card-body">
                      <div class="prod-card-name">${nombre}</div>
                      <div class="prod-card-badges">
                        ${badge ? `<span class="badge badge-red">${badge}</span>` : ''}
                        ${destacado ? `<span class="badge badge-orange">★ Home</span>` : ''}
                        ${sinMedidas ? `<span class="badge badge-gray">Sin medidas</span>` : ''}
                      </div>
                    </div>
                  </div>`;
            }).join('')
            : '<p style="color:var(--gray-400);padding:20px 0">No hay productos en esta categoría.</p>';

        el.innerHTML = `
            <div class="section-header">
              <h2 class="section-title">Productos</h2>
              <div style="display:flex;gap:8px">
                <button class="btn-secondary btn-sm" id="btn-manage-cats" style="display:flex;align-items:center;gap:6px">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                  Categorías
                </button>
                <button class="btn-primary" id="btn-new-producto">+ Nuevo producto</button>
              </div>
            </div>
            <div class="filter-bar">${chips}</div>
            <div class="productos-grid">${cards}</div>`;

        el.querySelectorAll('.filter-chip').forEach(btn => {
            btn.addEventListener('click', () => { _productosState.filter = btn.dataset.cat; renderProductosList(el); });
        });
        el.querySelectorAll('.prod-card').forEach(card => {
            card.addEventListener('click', () =>
                renderProductoForm(el, parseInt(card.dataset.id), parseInt(card.dataset.enId) || null)
            );
        });
        document.getElementById('btn-new-producto')?.addEventListener('click', () => renderProductoForm(el, null, null));
        document.getElementById('btn-manage-cats')?.addEventListener('click', () => {
            const sec = document.getElementById('section-productos');
            renderCategorias(sec);
        });
    }

    // =====================================================================
    // AUTO-TRANSLATE
    // =====================================================================
    async function autoTranslate(text) {
        if (!text.trim()) return '';
        try {
            const url = `https://api.mymemory.translated.net/get?q=${encodeURIComponent(text)}&langpair=es|en&de=bazarcotidiano@gmail.com`;
            const res = await fetch(url);
            const data = await res.json();
            return data.responseData?.translatedText || '';
        } catch {
            toast('Error al traducir. Intentá de nuevo.', 'error');
            return '';
        }
    }

    // =====================================================================
    // PRODUCTO FORM
    // =====================================================================
    const FOTO_LABELS = ['Principal', 'Hover', 'Extra 1', 'Extra 2', 'Extra 3'];

    async function renderProductoForm(el, esId, enId) {
        el.innerHTML = '<div class="loading">Cargando...</div>';
        let esData = {}, enData = {};

        if (esId) {
            const [esRes, enRes] = await Promise.all([
                wpGet('wp/v2/producto/' + esId + '?acf_format=standard'),
                enId ? wpGet('wp/v2/producto/' + enId + '?acf_format=standard') : Promise.resolve({}),
            ]);
            if (!esRes) return;
            esData = esRes || {};
            enData = enRes || {};
            // If enId wasn't passed, try to get it from the ES post translations
            if (!enId && esData.translations?.en) enId = esData.translations.en;
        }

        const acf   = esData.acf || {};
        const acfEn = enData.acf || {};
        const cats  = _productosState.categories;

        const catOptions = cats.map(c =>
            `<option value="${c.id}"${(esData.categoria_producto || []).includes(c.id) ? ' selected' : ''}>${c.name}</option>`
        ).join('');

        // Build 5 foto slots
        const fotoSources = [
            acf.foto_principal,
            acf.foto_hover,
            ...(Array.isArray(acf.fotos_extra) ? acf.fotos_extra : [null, null, null]),
        ].slice(0, 5);

        const fotoSlots = fotoSources.map((foto, i) => {
            const url = foto?.url || foto?.source_url || '';
            const mediaId = foto?.id || foto?.ID || '';
            if (url) {
                return `<div class="foto-slot" data-slot="${i}" data-media-id="${mediaId}">
                  <img src="${url}" alt="Foto ${i+1}">
                  <button class="remove-foto" title="Quitar">✕</button>
                </div>`;
            }
            return `<div class="foto-slot" data-slot="${i}">
              <span style="font-size:1.5rem;color:var(--gray-400)">+</span>
              <span class="slot-label">${FOTO_LABELS[i]}</span>
              <input type="file" accept="image/*" style="display:none" data-slot="${i}">
            </div>`;
        }).join('');

        el.innerHTML = `
            <div class="section-header">
              <button class="btn-secondary btn-sm" id="btn-back">← Volver</button>
              <h2 class="section-title">${esId ? 'Editar producto' : 'Nuevo producto'}</h2>
              <div></div>
            </div>

            <div class="form-section">
              <div class="form-section-title">Fotos (hasta 5)</div>
              <div class="foto-upload-area" id="foto-slots">${fotoSlots}</div>
              <p style="font-size:.8rem;color:var(--gray-400)">1ª foto = principal · 2ª foto = hover al pasar el mouse · Resto = galería extra</p>
            </div>

            <div class="form-section">
              <div class="form-section-title">Datos compartidos (ES + EN)</div>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                <div class="field-group">
                  <label>Badge (opcional)</label>
                  <input type="text" id="f-badge" placeholder="NUEVO, OFERTA..." value="${acf.badge || ''}">
                </div>
                <div class="field-group">
                  <label>Medidas (separadas por coma)</label>
                  <input type="text" id="f-medidas" placeholder="32x22x3 cm, 450ml, 200g" value="${acf.medidas || ''}">
                </div>
                <div class="field-group">
                  <label>Categoría</label>
                  <select id="f-categoria">${catOptions || '<option value="">Sin categoría</option>'}</select>
                </div>
              </div>
              <div class="field-group" style="margin-top:4px">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600">
                  <input type="checkbox" id="f-destacado"${acf.destacado ? ' checked' : ''} style="accent-color:var(--red);width:16px;height:16px">
                  Mostrar en home (producto destacado)
                </label>
              </div>
            </div>

            <div class="form-section">
              <div class="form-section-title">Nombre y descripción</div>
              <div class="bilingual-grid">
                <div>
                  <div class="lang-label lang-es">🇦🇷 Español</div>
                  <div class="field-group">
                    <label>Nombre</label>
                    <input type="text" id="f-nombre-es" value="${esData.title?.rendered || ''}">
                  </div>
                  <div class="field-group">
                    <label>Descripción</label>
                    <textarea id="f-desc-es">${acf.descripcion || ''}</textarea>
                  </div>
                </div>
                <div>
                  <div class="lang-en-header">
                    <span class="lang-label lang-en" style="margin-bottom:0">🇺🇸 English</span>
                    <button class="translate-btn" id="btn-translate">✨ Auto-traducir</button>
                  </div>
                  <div class="field-group">
                    <label>Name</label>
                    <input type="text" id="f-nombre-en" value="${enData.title?.rendered || ''}">
                  </div>
                  <div class="field-group">
                    <label>Description</label>
                    <textarea id="f-desc-en">${acfEn.descripcion || ''}</textarea>
                  </div>
                </div>
              </div>
            </div>

            <div class="form-actions">
              ${esId ? '<button class="btn-danger" id="btn-delete">🗑 Eliminar producto</button>' : '<div></div>'}
              <div style="display:flex;gap:10px">
                <button class="btn-secondary" id="btn-cancel">Cancelar</button>
                <button class="btn-primary" id="btn-save">💾 Guardar todo</button>
              </div>
            </div>

            <input type="hidden" id="f-es-id" value="${esId || ''}">
            <input type="hidden" id="f-en-id" value="${enId || enData.id || ''}">`;

        // --- Wire events ---
        document.getElementById('btn-back')?.addEventListener('click', () => renderProductos());
        document.getElementById('btn-cancel')?.addEventListener('click', () => renderProductos());
        document.getElementById('btn-save')?.addEventListener('click', saveProducto);
        document.getElementById('btn-delete')?.addEventListener('click', () => {
            if (!confirm('¿Estás segura de que querés eliminar este producto? Esta acción no se puede deshacer.')) return;
            deleteProducto(esId, enId || enData.id);
        });

        document.getElementById('btn-translate')?.addEventListener('click', async () => {
            const btn = document.getElementById('btn-translate');
            btn.textContent = 'Traduciendo...';
            btn.disabled = true;
            const [nom, desc] = await Promise.all([
                autoTranslate(document.getElementById('f-nombre-es').value),
                autoTranslate(document.getElementById('f-desc-es').value),
            ]);
            if (nom) document.getElementById('f-nombre-en').value = nom;
            if (desc) document.getElementById('f-desc-en').value = desc;
            btn.textContent = '✨ Auto-traducir';
            btn.disabled = false;
            toast('Traducción lista. Revisá antes de guardar.', 'success');
        });

        // Photo slots
        wireFotoSlots();
    }

    // =====================================================================
    // PHOTO UPLOAD
    // =====================================================================
    function wireFotoSlots() {
        document.querySelectorAll('.foto-slot').forEach(slot => {
            // Click slot → open file picker (unless clicking remove button)
            slot.addEventListener('click', e => {
                if (e.target.classList.contains('remove-foto')) return;
                let inp = slot.querySelector('input[type=file]');
                if (!inp) {
                    inp = document.createElement('input');
                    inp.type = 'file';
                    inp.accept = 'image/*';
                    inp.style.display = 'none';
                    inp.dataset.slot = slot.dataset.slot;
                    slot.appendChild(inp);
                    inp.addEventListener('change', () => handleFotoUpload(inp));
                }
                inp.click();
            });

            // Wire existing file inputs
            slot.querySelectorAll('input[type=file]').forEach(inp => {
                inp.addEventListener('change', () => handleFotoUpload(inp));
            });

            // Wire existing remove buttons
            slot.querySelector('.remove-foto')?.addEventListener('click', e => {
                e.stopPropagation();
                clearFotoSlot(slot);
            });
        });
    }

    function clearFotoSlot(slot) {
        const i = parseInt(slot.dataset.slot);
        slot.removeAttribute('data-media-id');
        slot.innerHTML = `
            <span style="font-size:1.5rem;color:var(--gray-400)">+</span>
            <span class="slot-label">${FOTO_LABELS[i]}</span>
            <input type="file" accept="image/*" style="display:none" data-slot="${i}">`;
        slot.querySelector('input[type=file]').addEventListener('change', e => handleFotoUpload(e.target));
    }

    async function handleFotoUpload(input) {
        const slot = input.closest('.foto-slot');
        const file = input.files[0];
        if (!file) return;
        slot.innerHTML = '<span style="font-size:.75rem;color:var(--gray-400)">Subiendo...</span>';
        const media = await wpUpload(file);
        if (!media?.id) {
            toast('Error al subir la foto. Intentá de nuevo.', 'error');
            clearFotoSlot(slot);
            return;
        }
        const url = media.source_url || media.guid?.rendered || '';
        slot.dataset.mediaId = media.id;
        slot.innerHTML = '<img src="' + url + '" alt="Foto"><button class="remove-foto" title="Quitar">✕</button>';
        slot.querySelector('.remove-foto').addEventListener('click', e => { e.stopPropagation(); clearFotoSlot(slot); });
        toast('Foto subida correctamente.', 'success');
    }

    // =====================================================================
    // SAVE PRODUCT
    // =====================================================================
    async function saveProducto() {
        const btn = document.getElementById('btn-save');
        btn.textContent = 'Guardando...';
        btn.disabled = true;

        const slots = document.querySelectorAll('.foto-slot');
        const mediaIds = Array.from(slots).map(s => parseInt(s.dataset.mediaId) || null);
        const nombreEs = document.getElementById('f-nombre-es')?.value.trim() || '';
        if (!nombreEs) {
            toast('El nombre en español es obligatorio.', 'error');
            btn.textContent = '💾 Guardar todo'; btn.disabled = false; return;
        }

        const esId = parseInt(document.getElementById('f-es-id')?.value) || 0;
        const enId = parseInt(document.getElementById('f-en-id')?.value) || 0;
        const catId = parseInt(document.getElementById('f-categoria')?.value) || null;

        const acfShared = {
            medidas:        document.getElementById('f-medidas')?.value.trim() || '',
            badge:          document.getElementById('f-badge')?.value.trim() || '',
            destacado:      document.getElementById('f-destacado')?.checked || false,
            foto_principal: mediaIds[0],
            foto_hover:     mediaIds[1],
            fotos_extra:    mediaIds.slice(2).filter(Boolean),
        };

        const esData = {
            title:   document.getElementById('f-nombre-es')?.value.trim(),
            content: document.getElementById('f-desc-es')?.value.trim(),
            status: 'publish',
            lang:   'es',
            acf: { ...acfShared, descripcion: document.getElementById('f-desc-es')?.value.trim() || '' },
        };
        if (catId) esData.categoria_producto = [catId];

        const enData = {
            title:   document.getElementById('f-nombre-en')?.value.trim(),
            content: document.getElementById('f-desc-en')?.value.trim(),
            status: 'publish',
            lang:   'en',
            acf: { ...acfShared, descripcion: document.getElementById('f-desc-en')?.value.trim() || '' },
        };

        const esPath = esId ? 'wp/v2/producto/' + esId : 'wp/v2/producto';
        const enPath = enId ? 'wp/v2/producto/' + enId : 'wp/v2/producto';

        let esResult, enResult;

        if (!esId && !enId) {
            // Producto nuevo: crear en secuencia para vincular las traducciones en Polylang
            esResult = await wpPost(esPath, esData);
            if (esResult?.id) {
                enData.translations = { es: esResult.id };
            }
            enResult = await wpPost(enPath, enData);
            // Vincular el EN de vuelta en el post ES
            if (esResult?.id && enResult?.id) {
                await wpPost('wp/v2/producto/' + esResult.id, { translations: { en: enResult.id } });
            }
        } else {
            // Edición: guardar en paralelo
            [esResult, enResult] = await Promise.all([
                wpPost(esPath, esData),
                wpPost(enPath, enData),
            ]);
        }

        btn.textContent = '💾 Guardar todo'; btn.disabled = false;

        if (esResult?.id || enResult?.id) {
            toast('Producto guardado correctamente.', 'success');
            setTimeout(() => renderProductos(), 1000);
        } else {
            toast('Error al guardar. Revisá los campos e intentá de nuevo.', 'error');
        }
    }

    // =====================================================================
    // DELETE PRODUCT
    // =====================================================================
    async function deleteProducto(esId, enId) {
        const btn = document.getElementById('btn-delete');
        if (btn) { btn.textContent = 'Eliminando...'; btn.disabled = true; }
        await Promise.all([
            esId ? wpDelete('wp/v2/producto/' + esId + '?force=true') : Promise.resolve(),
            enId ? wpDelete('wp/v2/producto/' + enId + '?force=true') : Promise.resolve(),
        ]);
        toast('Producto eliminado.', 'success');
        setTimeout(() => renderProductos(), 800);
    }

    // =====================================================================
    // CONTENIDO DEL SITIO
    // =====================================================================
    let _contenidoState = { stats: [], faqs: [] };

    function _esc(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
    }

    async function renderContenido() {
        const el = document.getElementById('section-contenido');
        el.innerHTML = '<div class="loading">Cargando contenido...</div>';
        const [stats, faqs] = await Promise.all([
            wpGet('mbv/v1/home-stats'),
            wpGet('mbv/v1/faqs'),
        ]);
        if (!stats || !faqs) {
            el.innerHTML = '<div class="loading" style="color:var(--red)">Error al cargar. Recargá la página.</div>';
            return;
        }
        _contenidoState.stats = Array.isArray(stats) ? stats : [];
        _contenidoState.faqs  = Array.isArray(faqs)  ? faqs  : [];

        el.innerHTML = `
            <p class="dashboard-greeting" style="font-size:1.125rem">Contenido del sitio</p>

            <div class="form-section">
              <div class="form-section-title">Las 4 cifras del home</div>
              <p style="font-size:.8125rem;color:var(--gray-600);margin-bottom:20px">
                Aparecen en la barra de estadísticas de la página principal y en la página de FAQs.
              </p>
              <div id="stats-rows">
                ${_contenidoState.stats.map((s,i) => `
                  <div style="display:grid;grid-template-columns:160px 1fr 1fr;gap:12px;margin-bottom:12px;align-items:end">
                    <div class="field-group" style="margin-bottom:0">
                      <label>Número ${i+1}</label>
                      <input type="text" id="st-num-${i}" value="${_esc(s.number)}" placeholder="+8.400">
                    </div>
                    <div class="field-group" style="margin-bottom:0">
                      <label><span style="color:var(--red)">🇦🇷 ES</span> — Etiqueta</label>
                      <input type="text" id="st-es-${i}" value="${_esc(s.label_es)}">
                    </div>
                    <div class="field-group" style="margin-bottom:0">
                      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                        <label style="margin-bottom:0"><span style="color:#1a6ebf">🇺🇸 EN</span> — Label</label>
                        <button class="translate-btn stat-xlat-btn" data-i="${i}" style="font-size:.7rem">✨</button>
                      </div>
                      <input type="text" id="st-en-${i}" value="${_esc(s.label_en)}">
                    </div>
                  </div>`).join('')}
              </div>
              <div style="display:flex;align-items:center;justify-content:space-between;margin-top:16px">
                <button class="translate-btn" id="btn-xlat-all-stats" style="font-size:.8rem;padding:6px 14px">✨ Traducir todas las etiquetas EN</button>
                <button class="btn-primary" id="btn-save-stats">💾 Guardar estadísticas</button>
              </div>
            </div>

            <div class="form-section">
              <div class="form-section-title" style="display:flex;align-items:center;justify-content:space-between">
                <span id="faqs-count-label">Preguntas frecuentes (${_contenidoState.faqs.length})</span>
                <button class="btn-primary btn-sm" id="btn-show-add-faq">+ Nueva pregunta</button>
              </div>
              <div id="faq-add-wrap" style="display:none;margin-top:16px;background:var(--gray-50);border-radius:var(--radius);padding:20px">
                ${_faqFormHtml('new')}
              </div>
              <div id="faqs-list" style="margin-top:12px">${_renderFaqsHtml(_contenidoState.faqs)}</div>
            </div>`;

        _wireContenido();
    }

    function _faqFormHtml(prefix) {
        return `
            <div class="bilingual-grid" style="gap:16px;margin-bottom:12px">
              <div>
                <div class="lang-label lang-es" style="margin-bottom:8px">🇦🇷 Español</div>
                <div class="field-group"><label>Pregunta</label>
                  <input type="text" id="${prefix}-q-es" placeholder="¿...?"></div>
                <div class="field-group"><label>Respuesta</label>
                  <textarea id="${prefix}-a-es" rows="3"></textarea></div>
              </div>
              <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                  <span class="lang-label lang-en" style="margin-bottom:0">🇺🇸 English</span>
                  <button class="translate-btn faq-xlat-btn" data-prefix="${prefix}">✨ Auto-traducir</button>
                </div>
                <div class="field-group"><label>Question</label>
                  <input type="text" id="${prefix}-q-en" placeholder="...?"></div>
                <div class="field-group"><label>Answer</label>
                  <textarea id="${prefix}-a-en" rows="3"></textarea></div>
              </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
              <button class="btn-secondary btn-sm faq-cancel-btn">Cancelar</button>
              <button class="btn-primary btn-sm faq-save-btn" data-prefix="${prefix}">💾 Guardar pregunta</button>
            </div>`;
    }

    function _renderFaqsHtml(faqs) {
        if (!faqs.length) return '<p style="color:var(--gray-400);padding:16px 0">No hay preguntas frecuentes todavía.</p>';
        return faqs.map((f, i) => `
            <div class="faq-item" data-i="${i}"
                 style="border:1px solid var(--gray-200);border-radius:var(--radius);margin-bottom:8px;overflow:hidden">
              <div style="display:flex;align-items:center;gap:10px;padding:13px 16px">
                <div style="flex:1;min-width:0">
                  <div style="font-weight:600;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${_esc(f.q_es)}</div>
                  <div style="font-size:.8rem;color:var(--gray-400);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:2px">${_esc(f.q_en)}</div>
                </div>
                <div style="display:flex;gap:4px;flex-shrink:0">
                  <button class="btn-secondary btn-sm faq-up" ${i===0?'disabled':''} style="padding:5px 10px">↑</button>
                  <button class="btn-secondary btn-sm faq-down" ${i===faqs.length-1?'disabled':''} style="padding:5px 10px">↓</button>
                  <button class="btn-secondary btn-sm faq-edit-btn">Editar</button>
                  <button class="btn-danger btn-sm faq-del-btn">✕</button>
                </div>
              </div>
              <div class="faq-edit-form" style="display:none;padding:0 16px 16px;border-top:1px solid var(--gray-100)">
                ${_faqFormHtml('edit-'+i)}
              </div>
            </div>`).join('');
    }

    function _wireContenido() {
        // Stats — traducir etiqueta individual
        document.querySelectorAll('.stat-xlat-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const i = btn.dataset.i;
                const esVal = document.getElementById(`st-es-${i}`)?.value.trim();
                if (!esVal) { toast('Escribí la etiqueta en español primero.', 'error'); return; }
                btn.textContent = '...'; btn.disabled = true;
                const translated = await autoTranslate(esVal);
                if (translated) {
                    const enEl = document.getElementById(`st-en-${i}`);
                    if (enEl) enEl.value = translated;
                    toast('Traducción lista.', 'success');
                }
                btn.textContent = '✨'; btn.disabled = false;
            });
        });

        // Stats — traducir todas las etiquetas EN de una vez
        document.getElementById('btn-xlat-all-stats').addEventListener('click', async () => {
            const btn = document.getElementById('btn-xlat-all-stats');
            btn.textContent = '✨ Traduciendo...'; btn.disabled = true;
            const translations = await Promise.all([0,1,2,3].map(i =>
                autoTranslate(document.getElementById(`st-es-${i}`)?.value.trim() || '')
            ));
            translations.forEach((t, i) => {
                if (t) { const el = document.getElementById(`st-en-${i}`); if (el) el.value = t; }
            });
            btn.textContent = '✨ Traducir todas las etiquetas EN'; btn.disabled = false;
            toast('Etiquetas traducidas. Revisá antes de guardar.', 'success');
        });

        // Stats save
        document.getElementById('btn-save-stats').addEventListener('click', _saveStats);

        // Add FAQ toggle
        document.getElementById('btn-show-add-faq').addEventListener('click', () => {
            const w = document.getElementById('faq-add-wrap');
            w.style.display = w.style.display === 'none' ? 'block' : 'none';
        });
        _wireFaqForm('faq-add-wrap', 'new', addFaqItem);

        // FAQ list — delegated
        document.getElementById('faqs-list').addEventListener('click', async (e) => {
            const item = e.target.closest('.faq-item');
            if (!item) return;
            const i = parseInt(item.dataset.i);

            if (e.target.classList.contains('faq-del-btn')) {
                const q = _contenidoState.faqs[i]?.q_es || '';
                if (!confirm(`¿Eliminar esta pregunta?\n"${q}"`)) return;
                _contenidoState.faqs.splice(i, 1);
                await _saveFaqs(); _refreshFaqsList();
            }
            if (e.target.classList.contains('faq-up') && i > 0) {
                [_contenidoState.faqs[i-1], _contenidoState.faqs[i]] = [_contenidoState.faqs[i], _contenidoState.faqs[i-1]];
                await _saveFaqs(); _refreshFaqsList();
            }
            if (e.target.classList.contains('faq-down') && i < _contenidoState.faqs.length - 1) {
                [_contenidoState.faqs[i], _contenidoState.faqs[i+1]] = [_contenidoState.faqs[i+1], _contenidoState.faqs[i]];
                await _saveFaqs(); _refreshFaqsList();
            }
            if (e.target.classList.contains('faq-edit-btn')) {
                const form = item.querySelector('.faq-edit-form');
                const opening = form.style.display === 'none';
                document.querySelectorAll('.faq-edit-form').forEach(f => f.style.display = 'none');
                if (opening) {
                    form.style.display = 'block';
                    const f = _contenidoState.faqs[i];
                    const pfx = 'edit-'+i;
                    _setFaqVals(pfx, f.q_es, f.a_es, f.q_en, f.a_en);
                    _wireFaqForm(null, pfx, () => _updateFaqItem(i, pfx), form);
                }
            }
        });
    }

    function _wireFaqForm(wrapId, prefix, saveCallback, formEl) {
        const wrap = formEl || (wrapId ? document.getElementById(wrapId) : null);
        if (!wrap) return;
        wrap.querySelector('.faq-cancel-btn')?.addEventListener('click', () => {
            if (wrapId) { document.getElementById(wrapId).style.display = 'none'; _clearFaqVals(prefix); }
            else { wrap.style.display = 'none'; }
        });
        wrap.querySelector('.faq-xlat-btn')?.addEventListener('click', async (e) => {
            const p = e.currentTarget.dataset.prefix;
            const btn = e.currentTarget;
            btn.textContent = 'Traduciendo...'; btn.disabled = true;
            const [q, a] = await Promise.all([
                autoTranslate(document.getElementById(`${p}-q-es`)?.value || ''),
                autoTranslate(document.getElementById(`${p}-a-es`)?.value || ''),
            ]);
            if (q) { const el = document.getElementById(`${p}-q-en`); if (el) el.value = q; }
            if (a) { const el = document.getElementById(`${p}-a-en`); if (el) el.value = a; }
            btn.textContent = '✨ Auto-traducir'; btn.disabled = false;
            toast('Traducción lista. Revisá antes de guardar.', 'success');
        });
        wrap.querySelector('.faq-save-btn')?.addEventListener('click', saveCallback);
    }

    function _getFaqVals(prefix) {
        return {
            q_es: document.getElementById(`${prefix}-q-es`)?.value.trim() || '',
            a_es: document.getElementById(`${prefix}-a-es`)?.value.trim() || '',
            q_en: document.getElementById(`${prefix}-q-en`)?.value.trim() || '',
            a_en: document.getElementById(`${prefix}-a-en`)?.value.trim() || '',
        };
    }
    function _setFaqVals(prefix, qes, aes, qen, aen) {
        [['q-es',qes],['a-es',aes],['q-en',qen],['a-en',aen]].forEach(([k,v]) => {
            const el = document.getElementById(`${prefix}-${k}`); if (el) el.value = v || '';
        });
    }
    function _clearFaqVals(prefix) { _setFaqVals(prefix,'','','',''); }

    async function _saveStats() {
        const btn = document.getElementById('btn-save-stats');
        btn.textContent = 'Guardando...'; btn.disabled = true;
        const stats = [0,1,2,3].map(i => ({
            number:   document.getElementById(`st-num-${i}`)?.value.trim() || '',
            label_es: document.getElementById(`st-es-${i}`)?.value.trim() || '',
            label_en: document.getElementById(`st-en-${i}`)?.value.trim() || '',
        }));
        const res = await wpPost('mbv/v1/home-stats', stats);
        btn.textContent = '💾 Guardar estadísticas'; btn.disabled = false;
        if (res?.success) { _contenidoState.stats = stats; toast('Estadísticas guardadas. Ya se ven en el home.', 'success'); }
        else toast('Error al guardar.', 'error');
    }

    async function _saveFaqs() {
        const res = await wpPost('mbv/v1/faqs', _contenidoState.faqs);
        return res;
    }

    function _refreshFaqsList() {
        const list = document.getElementById('faqs-list');
        if (list) list.innerHTML = _renderFaqsHtml(_contenidoState.faqs);
        const lbl = document.getElementById('faqs-count-label');
        if (lbl) lbl.textContent = `Preguntas frecuentes (${_contenidoState.faqs.length})`;
        // Re-attach delegated listener on new element
        if (list) {
            list.addEventListener('click', async (e) => {
                const item = e.target.closest('.faq-item');
                if (!item) return;
                const i = parseInt(item.dataset.i);
                if (e.target.classList.contains('faq-del-btn')) {
                    const q = _contenidoState.faqs[i]?.q_es || '';
                    if (!confirm(`¿Eliminar esta pregunta?\n"${q}"`)) return;
                    _contenidoState.faqs.splice(i, 1);
                    await _saveFaqs(); _refreshFaqsList();
                }
                if (e.target.classList.contains('faq-up') && i > 0) {
                    [_contenidoState.faqs[i-1], _contenidoState.faqs[i]] = [_contenidoState.faqs[i], _contenidoState.faqs[i-1]];
                    await _saveFaqs(); _refreshFaqsList();
                }
                if (e.target.classList.contains('faq-down') && i < _contenidoState.faqs.length - 1) {
                    [_contenidoState.faqs[i], _contenidoState.faqs[i+1]] = [_contenidoState.faqs[i+1], _contenidoState.faqs[i]];
                    await _saveFaqs(); _refreshFaqsList();
                }
                if (e.target.classList.contains('faq-edit-btn')) {
                    const form = item.querySelector('.faq-edit-form');
                    const opening = form.style.display === 'none';
                    document.querySelectorAll('.faq-edit-form').forEach(f => f.style.display = 'none');
                    if (opening) {
                        form.style.display = 'block';
                        const f = _contenidoState.faqs[i];
                        const pfx = 'edit-'+i;
                        _setFaqVals(pfx, f.q_es, f.a_es, f.q_en, f.a_en);
                        _wireFaqForm(null, pfx, () => _updateFaqItem(i, pfx), form);
                    }
                }
            });
        }
    }

    async function addFaqItem() {
        const vals = _getFaqVals('new');
        if (!vals.q_es) { toast('Escribí al menos la pregunta en español.', 'error'); return; }
        _contenidoState.faqs.push(vals);
        const res = await _saveFaqs();
        if (res?.success) {
            toast('Pregunta agregada.', 'success');
            _clearFaqVals('new');
            document.getElementById('faq-add-wrap').style.display = 'none';
            _refreshFaqsList();
        } else { _contenidoState.faqs.pop(); toast('Error al guardar.', 'error'); }
    }

    async function _updateFaqItem(i, prefix) {
        const vals = _getFaqVals(prefix);
        if (!vals.q_es) { toast('La pregunta en español es obligatoria.', 'error'); return; }
        _contenidoState.faqs[i] = vals;
        const res = await _saveFaqs();
        if (res?.success) { toast('Pregunta actualizada.', 'success'); _refreshFaqsList(); }
        else { toast('Error al guardar.', 'error'); }
    }

    // =====================================================================
    // CATEGORÍAS
    // =====================================================================
    async function renderCategorias(el) {
        el.innerHTML = '<div class="loading">Cargando categorías...</div>';
        const cats = await wpGet('wp/v2/categoria_producto?per_page=100&lang=es');
        if (!cats) return;

        el.innerHTML = `
            <div class="section-header">
              <button class="btn-secondary btn-sm" id="btn-back-cats">← Volver a Productos</button>
              <h2 class="section-title">Categorías</h2>
              <div></div>
            </div>

            <div class="form-section">
              <div class="form-section-title">Nueva categoría</div>
              <div style="display:flex;gap:12px;align-items:flex-end">
                <div class="field-group" style="flex:1;margin-bottom:0">
                  <label>Nombre</label>
                  <input type="text" id="new-cat-name" placeholder="Ej: Tazas">
                </div>
                <button class="btn-primary" id="btn-add-cat" style="white-space:nowrap">+ Agregar</button>
              </div>
            </div>

            <div class="form-section">
              <div class="form-section-title">Categorías (${cats.length})</div>
              <div id="cats-list">
                ${cats.length
                  ? cats.map(c => catRowHtml(c)).join('')
                  : '<p style="color:var(--gray-400);padding:8px 0">No hay categorías todavía.</p>'
                }
              </div>
            </div>`;

        document.getElementById('btn-back-cats').addEventListener('click', () => renderProductos());
        document.getElementById('btn-add-cat').addEventListener('click', () => addCategoria(el));
        document.getElementById('new-cat-name').addEventListener('keydown', e => {
            if (e.key === 'Enter') addCategoria(el);
        });
        wireCatRows(el);
    }

    function catRowHtml(c) {
        const count = c.count || 0;
        return `
            <div class="cat-row" data-id="${c.id}" data-name="${c.name.replace(/"/g,'&quot;')}"
                 style="display:flex;align-items:center;gap:12px;padding:13px 0;border-bottom:1px solid var(--gray-100)">
              <span class="cat-name" style="flex:1;font-weight:600;color:var(--gray-900)">${c.name}</span>
              <span style="font-size:.8rem;color:var(--gray-400);white-space:nowrap">${count} producto${count !== 1 ? 's' : ''}</span>
              <button class="btn-secondary btn-sm btn-edit-cat" style="white-space:nowrap">Renombrar</button>
              <button class="btn-danger btn-sm btn-del-cat" style="white-space:nowrap">Eliminar</button>
            </div>`;
    }

    function wireCatRows(el) {
        // Renombrar
        el.querySelectorAll('.btn-edit-cat').forEach(btn => {
            btn.addEventListener('click', () => {
                const row = btn.closest('.cat-row');
                const catId = parseInt(row.dataset.id);
                const currentName = row.querySelector('.cat-name').textContent;
                const count = row.querySelector('span:nth-child(2)').textContent;
                row.innerHTML = `
                    <input type="text" class="cat-edit-input" value="${currentName}"
                           style="flex:1;padding:8px 12px;border:1.5px solid var(--red);border-radius:var(--radius);font-size:.9375rem;min-width:0">
                    <span style="font-size:.8rem;color:var(--gray-400);white-space:nowrap">${count}</span>
                    <button class="btn-primary btn-sm btn-save-cat">Guardar</button>
                    <button class="btn-secondary btn-sm btn-cancel-edit">Cancelar</button>`;
                row.style.flexWrap = 'wrap';
                row.querySelector('.cat-edit-input').focus();

                row.querySelector('.btn-save-cat').addEventListener('click', async () => {
                    const newName = row.querySelector('.cat-edit-input').value.trim();
                    if (!newName) return;
                    row.querySelector('.btn-save-cat').textContent = 'Guardando...';
                    row.querySelector('.btn-save-cat').disabled = true;
                    await wpPost('wp/v2/categoria_producto/' + catId, { name: newName });
                    toast('Categoría actualizada.', 'success');
                    // Refresh state
                    _productosState.categories = await wpGet('wp/v2/categoria_producto?per_page=100&lang=es') || _productosState.categories;
                    renderCategorias(document.getElementById('section-productos'));
                });

                row.querySelector('.btn-cancel-edit').addEventListener('click', () => {
                    renderCategorias(document.getElementById('section-productos'));
                });

                row.querySelector('.cat-edit-input').addEventListener('keydown', e => {
                    if (e.key === 'Enter') row.querySelector('.btn-save-cat').click();
                    if (e.key === 'Escape') row.querySelector('.btn-cancel-edit').click();
                });
            });
        });

        // Eliminar
        el.querySelectorAll('.btn-del-cat').forEach(btn => {
            btn.addEventListener('click', async () => {
                const row = btn.closest('.cat-row');
                const catId = parseInt(row.dataset.id);
                const catName = row.querySelector('.cat-name').textContent;
                const count = parseInt(row.querySelector('span:nth-child(2)').textContent) || 0;

                let msg = `¿Eliminar la categoría "${catName}"?`;
                if (count > 0) msg += `\n\nTiene ${count} producto(s) asignado(s). Los productos quedarán sin categoría.`;
                if (!confirm(msg)) return;

                btn.textContent = 'Eliminando...'; btn.disabled = true;
                await wpDelete('wp/v2/categoria_producto/' + catId + '?force=true');
                toast('Categoría eliminada.', 'success');
                _productosState.categories = await wpGet('wp/v2/categoria_producto?per_page=100&lang=es') || _productosState.categories;
                renderCategorias(document.getElementById('section-productos'));
            });
        });
    }

    async function addCategoria(el) {
        const input = document.getElementById('new-cat-name');
        const name = input?.value.trim();
        if (!name) { toast('Escribí un nombre para la categoría.', 'error'); input?.focus(); return; }

        const btn = document.getElementById('btn-add-cat');
        btn.textContent = 'Agregando...'; btn.disabled = true;

        const result = await wpPost('wp/v2/categoria_producto', { name, lang: 'es' });
        if (result?.id) {
            toast(`Categoría "${name}" creada.`, 'success');
            _productosState.categories = await wpGet('wp/v2/categoria_producto?per_page=100&lang=es') || _productosState.categories;
            renderCategorias(el);
        } else {
            const msg = result?.message || 'Error al crear la categoría.';
            toast(msg, 'error');
            btn.textContent = '+ Agregar'; btn.disabled = false;
        }
    }

    // =====================================================================
    // INIT
    // =====================================================================
    renderDashboard();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'mbv_panel', 'mbv_panel_shortcode' );
