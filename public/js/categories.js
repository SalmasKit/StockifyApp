const catModal = document.getElementById('categoryModal');
const delModal = document.getElementById('deleteConfirmModal');
const catForm = document.getElementById('categoryForm');

function getFormField(fieldName) {
  return catForm.querySelector(`[name*="[${fieldName}]"]`);
}

function openAddModal() {
  const addUrl = catForm.dataset.addUrl;
  document.getElementById('modalTitle').innerText = "REGISTER CATEGORY";
  catForm.action = addUrl;
  catForm.reset();
  catModal.classList.replace('hidden', 'flex');
}

function openEditModal(id, name, description) {
  document.getElementById('modalTitle').innerText = "MODIFY CATEGORY";
  catForm.action = "/category/edit/" + id;
  const nameInput = getFormField('name');
  const descInput = getFormField('description');
  if (nameInput) nameInput.value = name;
  if (descInput) descInput.value = description;
  catModal.classList.replace('hidden', 'flex');
}

function closeCategoryModal() {
  catModal.classList.replace('flex', 'hidden');
}

function openDeleteModal(url, token) {
  document.getElementById('deleteForm').action = url;
  document.getElementById('deleteCsrfToken').value = token;
  delModal.classList.replace('hidden', 'flex');
}

function closeDeleteModal() {
  delModal.classList.replace('flex', 'hidden');
}

const searchInput = document.getElementById('tableSearch');
if (searchInput) {
  searchInput.addEventListener('input', function (e) {
    const term = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.category-card');
    const noResults = document.getElementById('noSearchResults');
    let visibleCount = 0;

    cards.forEach(card => {
      const text = card.querySelector('.category-name').innerText.toLowerCase();
      if (text.includes(term)) {
        card.style.display = 'block';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    visibleCount === 0 ? noResults.classList.remove('hidden') : noResults.classList.add('hidden');
  });
}

window.onclick = (e) => {
  if (e.target === catModal) closeCategoryModal();
  if (e.target === delModal) closeDeleteModal();
}
