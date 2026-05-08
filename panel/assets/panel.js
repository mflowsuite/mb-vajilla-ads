// === MB Vajilla Panel JS ===
const CSRF = window.MBV_CSRF;

// --- API helper ---
async function api(action, params = {}, body = null) {
  const qs = Object.entries(params).map(([k,v]) => '&' + k + '=' + encodeURIComponent(v)).join('');
  const url = '/api.php?action=' + action + qs;
  const opts = { method: 'GET', headers: { 'X-CSRF-Token': CSRF } };
  if (body) {
    opts.method = 'POST';
    if (body instanceof FormData) {
      opts.body = body;
    } else {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }
  }
  const res = await fetch(url, opts);
  if (res.status === 401) { window.location.href = '/'; return null; }
  return res.json();
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
  const stats = await api('dashboard_stats');
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
    api('categories_list'),
    api('productos_list', { lang: 'es' }),
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
        // Try to find EN id from translations
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
    const pair = await api('producto_get', { es_id: esId, en_id: enId || '' });
    if (!pair) return;
    esData = pair.es || {};
    enData = pair.en || {};
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
  const i = parseInt(slot.dataset.slot);
  const file = input.files[0];
  if (!file) return;

  slot.innerHTML = `<span style="font-size:.75rem;color:var(--gray-400)">Subiendo...</span>`;

  const fd = new FormData();
  fd.append('file', file, file.name);
  const result = await api('media_upload', {}, fd);

  if (!result?.ok || !result.media?.id) {
    toast('Error al subir la foto. Intentá de nuevo.', 'error');
    clearFotoSlot(slot);
    return;
  }

  const url = result.media.source_url || result.media.guid?.rendered || '';
  slot.dataset.mediaId = result.media.id;
  slot.innerHTML = `
    <img src="${url}" alt="Foto">
    <button class="remove-foto" title="Quitar">✕</button>`;
  slot.querySelector('.remove-foto').addEventListener('click', e => {
    e.stopPropagation();
    clearFotoSlot(slot);
  });

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
    btn.textContent = '💾 Guardar todo';
    btn.disabled = false;
    return;
  }

  const payload = {
    es_id:             parseInt(document.getElementById('f-es-id')?.value) || null,
    en_id:             parseInt(document.getElementById('f-en-id')?.value) || null,
    nombre_es:         nombreEs,
    nombre_en:         document.getElementById('f-nombre-en')?.value.trim() || '',
    desc_es:           document.getElementById('f-desc-es')?.value.trim() || '',
    desc_en:           document.getElementById('f-desc-en')?.value.trim() || '',
    medidas:           document.getElementById('f-medidas')?.value.trim() || '',
    badge:             document.getElementById('f-badge')?.value.trim() || '',
    destacado:         document.getElementById('f-destacado')?.checked || false,
    categoria_es_id:   parseInt(document.getElementById('f-categoria')?.value) || null,
    foto_principal_id: mediaIds[0],
    foto_hover_id:     mediaIds[1],
    fotos_extra_ids:   mediaIds.slice(2).filter(Boolean),
  };

  const result = await api('producto_save', {}, payload);
  btn.textContent = '💾 Guardar todo';
  btn.disabled = false;

  if (!result?.ok) {
    toast('Error al guardar. Revisá los campos e intentá de nuevo.', 'error');
    return;
  }

  toast('Producto guardado correctamente.', 'success');
  setTimeout(() => renderProductos(), 1000);
}

// =====================================================================
// DELETE PRODUCT
// =====================================================================
async function deleteProducto(esId, enId) {
  const btn = document.getElementById('btn-delete');
  if (btn) { btn.textContent = 'Eliminando...'; btn.disabled = true; }

  const result = await api('producto_delete', {}, { es_id: esId, en_id: enId });

  if (!result?.ok) {
    toast('Error al eliminar. Intentá de nuevo.', 'error');
    if (btn) { btn.textContent = '🗑 Eliminar producto'; btn.disabled = false; }
    return;
  }

  toast('Producto eliminado.', 'success');
  setTimeout(() => renderProductos(), 800);
}

// =====================================================================
// INIT
// =====================================================================
renderDashboard();
