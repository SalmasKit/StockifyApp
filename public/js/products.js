(function () {
  // 1. Lightbox
  window.maximizeImage = function (img) {
    const lb = document.getElementById('image-lightbox');
    document.getElementById('lightbox-img').src = img.src;
    lb.classList.replace('hidden', 'flex');
    document.body.style.overflow = 'hidden';
  };
  window.closeLightbox = () => {
    const lightbox = document.getElementById('image-lightbox');
    if (lightbox) {
      lightbox.classList.replace('flex', 'hidden');
      document.body.style.overflow = 'auto';
    }
  };

  // 2. Export with Filter (Matching Controller 'q' param)
  const exportBtn = document.getElementById('exportBtn');
  const tableSearch = document.getElementById('tableSearch');
  if (exportBtn && tableSearch) {
    exportBtn.addEventListener('click', function (e) {
      e.preventDefault();
      const searchTerm = tableSearch.value.trim();
      const url = new URL(this.href, window.location.origin);
      if (searchTerm) url.searchParams.set('q', searchTerm); // SETS 'q' TO MATCH CONTROLLER
      window.location.href = url.toString();
    });
  }

  // 3. Instant Search (UI only)
  if (tableSearch) {
    tableSearch.addEventListener('input', function (e) {
      const term = e.target.value.toLowerCase();
      const cards = document.querySelectorAll('.product-card');
      let found = 0;
      cards.forEach(card => {
        const text = card.innerText.toLowerCase();
        const match = text.includes(term);
        card.style.display = match ? '' : 'none';
        if (match) found++;
      });
      const noResults = document.getElementById('noSearchResults');
      if (noResults) {
        noResults.classList.toggle('hidden', found > 0 || term === '');
      }
    });
  }

  // 4. Select Mode
  const toggleBtn = document.getElementById('toggleSelectMode');
  const selectModeText = document.getElementById('selectModeText');
  let isSelectMode = false;

  if (toggleBtn) {
    toggleBtn.addEventListener('click', () => {
      isSelectMode = !isSelectMode;
      toggleBtn.classList.toggle('bg-blue-600', isSelectMode);
      toggleBtn.classList.toggle('text-white', isSelectMode);
      if (selectModeText) selectModeText.innerText = isSelectMode ? "Cancel" : "Select Items";

      document.querySelectorAll('.checkbox-col').forEach(col => {
        col.classList.toggle('opacity-0', !isSelectMode);
        col.classList.toggle('pointer-events-none', !isSelectMode);
      });

      if (!isSelectMode) {
        document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
        updateBatchBar();
      }
    });
  }

  // 5. Batch Logic
  document.querySelectorAll('.product-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBatchBar);
  });

  function updateBatchBar() {
    const checked = document.querySelectorAll('.product-checkbox:checked');
    const bar = document.getElementById('batchBar');
    if (checked.length > 0) {
      const countEl = document.getElementById('selectedCount');
      const inputEl = document.getElementById('bulkIdsInput');
      if (countEl) countEl.innerText = checked.length;
      if (inputEl) inputEl.value = Array.from(checked).map(c => c.value).join(',');
      if (bar) bar.classList.remove('translate-y-40', 'opacity-0');
    } else {
      if (bar) bar.classList.add('translate-y-40', 'opacity-0');
    }
  }

  // 6. Delete Modal
  window.openConfirmModal = (type, url, token) => {
    const form = document.getElementById('modalDeleteForm');
    const tokenInput = document.getElementById('modalDeleteToken');
    const modal = document.getElementById('deleteModal');
    if (form) form.action = url;
    if (tokenInput) tokenInput.value = token;
    if (modal) modal.classList.replace('hidden', 'flex');
  };
  window.closeDeleteModal = () => {
    const modal = document.getElementById('deleteModal');
    if (modal) modal.classList.replace('flex', 'hidden');
  }
})();
