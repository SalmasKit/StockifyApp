function openUserModal(type, name, url, actionText = '', csrf = '') {
  const modal = document.getElementById('userActionModal');
  const content = document.getElementById('modalContent');
  const title = document.getElementById('modalTitle');
  const desc = document.getElementById('modalDescription');
  const btn = document.getElementById('modalConfirmBtn');
  const form = document.getElementById('modalForm');
  const icon = document.getElementById('modalIcon');
  const iconBox = document.getElementById('modalIconContainer');
  const csrfInput = document.getElementById('modalCsrfToken');

  if (!modal || !content || !title || !desc || !btn || !form || !icon || !iconBox || !csrfInput) return;

  // Reset Defaults
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(() => { content.classList.add('scale-100', 'opacity-100'); }, 10);

  form.action = url;
  csrfInput.value = csrf;

  if (type === 'delete') {
    form.method = 'POST';
    title.innerText = 'Delete User';
    desc.innerHTML = `Are you sure you want to delete <b>${name}</b>? This action is permanent.`;
    btn.innerText = 'Confirm Delete';
    btn.className = 'w-full px-4 py-2.5 bg-red-600 text-white rounded-xl font-bold text-sm hover:bg-red-700 shadow-red-100';
    iconBox.className = 'w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4 bg-red-100 text-red-600';
    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>';
  } else {
    form.method = 'GET';
    const isApprove = actionText === 'approve';
    title.innerText = isApprove ? 'Approve Access' : 'Deactivate User';
    desc.innerHTML = isApprove
      ? `Give <b>${name}</b> full access to the Stockify system?`
      : `Are you sure you want to disable <b>${name}</b>'s account?`;
    btn.innerText = isApprove ? 'Yes, Approve' : 'Yes, Deactivate';
    btn.className = isApprove
      ? 'w-full px-4 py-2.5 bg-blue-600 text-white rounded-xl font-bold text-sm hover:bg-blue-700 shadow-blue-100'
      : 'w-full px-4 py-2.5 bg-slate-800 text-white rounded-xl font-bold text-sm hover:bg-slate-900 shadow-slate-200';
    iconBox.className = isApprove
      ? 'w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4 bg-blue-100 text-blue-600'
      : 'w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4 bg-slate-100 text-slate-600';
    icon.innerHTML = isApprove
      ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
      : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/>';
  }
}

function closeUserModal() {
  const modal = document.getElementById('userActionModal');
  const content = document.getElementById('modalContent');
  if (!modal || !content) return;
  content.classList.remove('scale-100', 'opacity-100');
  setTimeout(() => {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }, 200);
}

function openAddUserModal() {
  const modal = document.getElementById('addUserModal');
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
}

function closeAddUserModal() {
  const modal = document.getElementById('addUserModal');
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}
