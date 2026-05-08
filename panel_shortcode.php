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
    .mbvp-active .site-header,
    .mbvp-active .site-footer,
    .mbvp-active #breadcrumbs,
    .mbvp-active .entry-header,
    .mbvp-active .page-title,
    .mbvp-active .flatsome-breadcrumbs { display: none !important; }
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
                <li><a class="nav-link nav-disabled"><span class="nav-icon">&#9632;</span> Contenido <span class="badge-soon">Pronto</span></a></li>
                <li><a class="nav-link nav-disabled"><span class="nav-icon">&#9632;</span> Multimedia <span class="badge-soon">Pronto</span></a></li>
                <li><a class="nav-link nav-disabled"><span class="nav-icon">&#9632;</span> Estadísticas <span class="badge-soon">Pronto</span></a></li>
            </ul>
        </nav>

        <!-- Main -->
        <main class="main-content">
            <div id="section-dashboard"></div>
            <div id="section-productos" class="hidden"></div>
        </main>
    </div>

    <script>
    document.body.classList.add('mbvp-active');
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
        ['dashboard', 'productos'].forEach(s => {
            const el = document.getElementById('section-' + s);
            if (el) el.classList.toggle('hidden', s !== section);
        });
        document.getElementById('sidebar')?.classList.remove('open');
        if (section === 'dashboard') renderDashboard();
        if (section === 'productos') renderProductos();
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
            wpGet('wp/v2/categoria_de_producto?per_page=50'),
            wpGet('wp/v2/producto?lang=es&per_page=100&acf_format=standard'),
        ]);
        if (!cats || !prods) return;
        _productosState.categories = Array.isArray(cats) ? cats.filter(c => !c.lang || c.lang === 'es') : [];
        _productosState.list = Array.isArray(prods) ? prods : [];
        _productosState.filter = 'all';
        renderProductosList(el);
    }

    function renderProductosList(el) {
        const { list, categories, filter } = _productosState;
        const filtered = filter === 'all'
            ? list
            : list.filter(p => Array.isArray(p.categoria_de_producto) && p.categoria_de_producto.includes(parseInt(filter)));

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
              <button class="btn-primary" id="btn-new-producto">+ Nuevo producto</button>
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
            `<option value="${c.id}"${(esData.categoria_de_producto || []).includes(c.id) ? ' selected' : ''}>${c.name}</option>`
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
            acf: { ...acfShared, descripcion: document.getElementById('f-desc-es')?.value.trim() || '' },
        };
        if (catId) esData.categoria_de_producto = [catId];

        const enData = {
            title:   document.getElementById('f-nombre-en')?.value.trim(),
            content: document.getElementById('f-desc-en')?.value.trim(),
            status: 'publish',
            acf: { ...acfShared, descripcion: document.getElementById('f-desc-en')?.value.trim() || '' },
        };

        const esPath = esId ? 'wp/v2/producto/' + esId : 'wp/v2/producto';
        const enPath = enId ? 'wp/v2/producto/' + enId : 'wp/v2/producto';

        const [esResult, enResult] = await Promise.all([
            wpPost(esPath, esData),
            wpPost(enPath, enData),
        ]);

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
    // INIT
    // =====================================================================
    renderDashboard();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'mbv_panel', 'mbv_panel_shortcode' );
