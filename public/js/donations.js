let manifest = [];

function addToManifest(dataset) {
  if (manifest.find(item => item.id === dataset.id)) return;

  // Explicitly create object 
  const itemData = {
    id: dataset.id,
    name: dataset.name,
    unit: dataset.unit,
    img: dataset.img,
    amount: 1
  };

  manifest.push(itemData);
  renderManifest();
}


async function processDonation() {
  const finalizeBtn = document.getElementById('finalizeBtn');
  const confirmUrl = finalizeBtn.dataset.confirmUrl;

  console.log("Using Confirm URL:", confirmUrl);
  console.log("Sending Payload:", JSON.stringify({ items: manifest }));

  try {
    const response = await fetch(confirmUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: manifest })
    });

    if (response.ok) {
      window.location.reload();
    } else {
      closeConfirmModal();
      const errorText = await response.text();
      console.error("Server Error:", response.status, errorText);

      const toast = document.getElementById('error-toast');
      const sub = document.getElementById('default-subtitle');
      toast.innerText = errorText || "Request failed with status " + response.status;
      toast.classList.remove('hidden');
      sub.classList.add('hidden');

      setTimeout(() => {
        toast.classList.add('hidden');
        sub.classList.remove('hidden');
      }, 5000);
    }
  } catch (error) {
    console.error("Network or Fetch Error:", error);
    alert("A network error occurred.");
  }
}

function removeFromManifest(id) {
  manifest = manifest.filter(item => item.id !== id);
  renderManifest();
}

function updateAmount(id, value) {
  const item = manifest.find(i => i.id === id);
  if (item) item.amount = parseFloat(value) || 0;
}

function renderManifest() {
  const body = document.getElementById('manifestBody');
  const emptyState = document.getElementById('emptyTableState');
  const badge = document.getElementById('manifestBadge');
  const finalizeBtn = document.getElementById('finalizeBtn');

  body.innerHTML = '';
  badge.innerText = manifest.length;

  if (manifest.length > 0) {
    emptyState.classList.add('hidden');
    finalizeBtn.className = "px-12 py-5 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-[2rem] shadow-xl shadow-blue-200 uppercase text-[11px] tracking-[0.2em] transition-all active:scale-95";
  } else {
    emptyState.classList.remove('hidden');
    finalizeBtn.className = "px-12 py-5 bg-slate-100 text-slate-400 cursor-not-allowed font-black rounded-[2rem] uppercase text-[11px] tracking-[0.2em] transition-all";
  }

  manifest.forEach(item => {
    body.innerHTML += `
        <tr class="hover:bg-slate-50/50 transition-all group">
            <td class="px-10 py-6">
                <div class="flex items-center gap-6">
                    <img src="${item.img || 'https://placehold.co/100'}" class="h-14 w-14 rounded-2xl object-cover border-2 border-white shadow-sm group-hover:scale-110 transition-transform">
                    <div>
                        <div class="font-black text-slate-800 uppercase text-xs tracking-tight">${item.name}</div>
                        <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Ref ID: ${item.id}</div>
                    </div>
                </div>
            </td>
            <td class="px-10 py-6 text-center">
                <div class="inline-flex items-center gap-3">
                    <input type="number" value="${item.amount}" min="1" onchange="updateAmount('${item.id}', this.value)"
                           class="w-24 px-4 py-3 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-2xl text-center font-black text-blue-600 outline-none transition-all">
                    <span class="text-[10px] font-black text-slate-400 uppercase w-10 text-left">${item.unit}</span>
                </div>
            </td>
            <td class="px-10 py-6 text-right">
                <button onclick="removeFromManifest('${item.id}')" class="p-3 text-slate-200 hover:text-red-500 hover:bg-red-50 rounded-2xl transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </td>
        </tr>
    `;
  });
}

function openConfirmModal() {
  if (manifest.length === 0) return;
  document.getElementById('modalItemCount').innerText = manifest.length;
  document.getElementById('confirmModal').classList.replace('hidden', 'flex');
}

function closeConfirmModal() {
  document.getElementById('confirmModal').classList.replace('flex', 'hidden');
}


function filterProducts() {
  let filter = document.getElementById('productSearch').value.toLowerCase();
  document.querySelectorAll('.product-item').forEach(item => {
    item.style.display = item.dataset.name.toLowerCase().includes(filter) ? 'flex' : 'none';
  });
}
