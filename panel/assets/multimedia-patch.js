// =====================================================================
// MULTIMEDIA — agregar este bloque al FINAL del panel.js del servidor
// También agregar 'multimedia' a la lista de secciones en navigate()
// y agregar: if (section === 'multimedia') renderMultimedia();
// =====================================================================

// =====================================================================
// MULTIMEDIA
// =====================================================================
let _multimediaState = { items: [], search: '', page: 1 };

async function renderMultimedia() {
  const el = document.getElementById('section-multimedia');
  if (!el) return;
  _multimediaState.page = 1;
  _multimediaState.search = '';
  el.innerHTML = '<div class="loading">Cargando imágenes...</div>';
  await loadMultimediaPage(el);
}

async function loadMultimediaPage(el) {
  const { page, search } = _multimediaState;
  const items = await api('media_list', { page, per_page: 60, search });
  if (!items) return;
  _multimediaState.items = Array.isArray(items) ? items : [];
  renderMultimediaGrid(el);
}

function renderMultimediaGrid(el) {
  const { items, search } = _multimediaState;

  const cards = items.length
    ? items.map(m => {
        const url   = m.source_url || m.guid?.rendered || '';
        const thumb = m.media_details?.sizes?.thumbnail?.source_url || url;
        const name  = m.title?.rendered || m.slug || '';
        const date  = m.date ? new Date(m.date).toLocaleDateString('es-AR') : '';
        const size  = m.media_details?.filesize
          ? (m.media_details.filesize / 1024 < 1000
              ? Math.round(m.media_details.filesize / 1024) + ' KB'
              : (m.media_details.filesize / 1048576).toFixed(1) + ' MB')
          : '';
        return `
          <div class="media-card" data-url="${url}" data-id="${m.id}">
            <div class="media-thumb">
              <img src="${thumb}" alt="${name}" loading="lazy">
            </div>
            <div class="media-info">
              <div class="media-name" title="${name}">${name}</div>
              <div class="media-meta">${[date, size].filter(Boolean).join(' · ')}</div>
            </div>
            <div class="media-actions">
              <button class="btn-copy-url btn-sm btn-secondary" data-url="${url}" title="Copiar URL">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                Copiar URL
              </button>
              <button class="btn-delete-media btn-sm btn-danger" data-id="${m.id}" data-name="${name}" title="Eliminar">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4h6v2"></path></svg>
                Eliminar
              </button>
            </div>
          </div>`;
      }).join('')
    : '<p style="color:var(--gray-400);padding:40px 0;text-align:center">No hay imágenes.</p>';

  el.innerHTML = `
    <div class="section-header">
      <h2 class="section-title">Multimedia</h2>
      <button class="btn-primary" id="btn-upload-media">+ Subir imagen</button>
    </div>

    <div class="media-search-bar">
      <input type="text" class="media-search-input" id="media-search" placeholder="Buscar por nombre..." value="${search}">
    </div>

    <div class="media-grid" id="media-grid">${cards}</div>

    <input type="file" id="media-file-input" accept="image/*" style="display:none" multiple>
  `;

  // Upload button
  document.getElementById('btn-upload-media')?.addEventListener('click', () => {
    document.getElementById('media-file-input').click();
  });

  // File input
  document.getElementById('media-file-input')?.addEventListener('change', async e => {
    const files = Array.from(e.target.files);
    if (!files.length) return;
    const btn = document.getElementById('btn-upload-media');
    btn.textContent = 'Subiendo...';
    btn.disabled = true;
    for (const file of files) {
      const fd = new FormData();
      fd.append('file', file, file.name);
      await api('media_upload', {}, fd);
    }
    btn.textContent = '+ Subir imagen';
    btn.disabled = false;
    e.target.value = '';
    toast(`${files.length} imagen${files.length > 1 ? 'es' : ''} subida${files.length > 1 ? 's' : ''} correctamente.`, 'success');
    await loadMultimediaPage(el);
  });

  // Search
  document.getElementById('media-search')?.addEventListener('input', e => {
    _multimediaState.search = e.target.value;
    clearTimeout(window._mediaSearchTimer);
    window._mediaSearchTimer = setTimeout(() => loadMultimediaPage(el), 400);
  });

  // Copy URL buttons
  el.querySelectorAll('.btn-copy-url').forEach(btn => {
    btn.addEventListener('click', async () => {
      const url = btn.dataset.url;
      try {
        await navigator.clipboard.writeText(url);
        const orig = btn.innerHTML;
        btn.textContent = '✓ Copiado';
        setTimeout(() => { btn.innerHTML = orig; }, 1800);
      } catch {
        toast('No se pudo copiar al portapapeles.', 'error');
      }
    });
  });

  // Delete buttons
  el.querySelectorAll('.btn-delete-media').forEach(btn => {
    btn.addEventListener('click', async () => {
      const name = btn.dataset.name || 'esta imagen';
      if (!confirm(`¿Eliminar "${name}"? Esta acción no se puede deshacer.`)) return;
      btn.textContent = '...';
      btn.disabled = true;
      const result = await api('media_delete', {}, { id: parseInt(btn.dataset.id) });
      if (result?.ok) {
        toast('Imagen eliminada.', 'success');
        await loadMultimediaPage(el);
      } else {
        toast('Error al eliminar. La imagen puede estar en uso.', 'error');
        btn.textContent = 'Eliminar';
        btn.disabled = false;
      }
    });
  });
}
