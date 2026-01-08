function updatePreview(event) {
  const reader = new FileReader();
  reader.onload = function () {
    const preview = document.getElementById('new-preview');
    const placeholder = document.getElementById('placeholder-icon');
    const current = document.getElementById('current-view');

    if (preview) {
      preview.src = reader.result;
      preview.classList.remove('hidden');
    }
    if (placeholder) {
      placeholder.classList.add('hidden');
    }
    if (current) {
      current.classList.add('hidden');
    }
  };
  if (event.target.files[0]) {
    reader.readAsDataURL(event.target.files[0]);
  }
}

function validateAndOpenModal(actionType) {
  const form = document.getElementById('product-form');
  const modal = document.getElementById('confirm-modal');
  const title = document.getElementById('modal-title');
  const desc = document.getElementById('modal-desc');
  const iconContainer = document.getElementById('modal-icon-container');
  const modalActions = document.getElementById('modal-actions');
  const modalIcon = document.getElementById('modal-icon');

  if (!form || !modal || !title || !desc || !iconContainer || !modalActions || !modalIcon) return;

  // Trigger HTML5 Validation check
  if (!form.checkValidity()) {
    // If invalid, show an "Error" version of the modal
    title.innerText = "Missing Information";
    desc.innerText = "Please fill in all required fields (*) before saving.";
    iconContainer.className = "w-20 h-20 bg-red-50 text-red-600 rounded-[1.5rem] flex items-center justify-center mb-6 mx-auto";
    modalIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>';
    modalActions.innerHTML = '<button type="button" onclick="closeConfirmModal()" class="w-full py-4 bg-slate-900 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest">I will fix it</button>';

    // Trigger native browser tooltips
    form.reportValidity();
  } else {
    // If valid, show the confirmation modal
    title.innerText = actionType === 'create' ? "Create Product" : "Update Product";
    desc.innerText = "Are you sure you want to proceed with this inventory update?";
    iconContainer.className = "w-20 h-20 bg-blue-50 text-blue-600 rounded-[1.5rem] flex items-center justify-center mb-6 mx-auto";
    modalIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>';
    modalActions.innerHTML = `
            <button type="button" onclick="closeConfirmModal()" class="flex-1 py-4 bg-slate-50 text-slate-600 rounded-2xl font-black uppercase text-[10px] tracking-widest">Cancel</button>
            <button type="button" onclick="submitProductForm()" class="flex-1 py-4 bg-blue-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-blue-100">Confirm</button>
        `;
  }
  modal.classList.replace('hidden', 'flex');
}

function closeConfirmModal() {
  const modal = document.getElementById('confirm-modal');
  if (modal) modal.classList.replace('flex', 'hidden');
}

function submitProductForm() {
  const form = document.getElementById('product-form');
  if (form) form.submit();
}
