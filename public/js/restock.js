let selectedProducts = new Map();

function toggleSelection(el) {
  const id = el.dataset.id;
  if (selectedProducts.has(id)) {
    selectedProducts.delete(id);
    el.classList.remove('selected');
  } else {
    // Explicitly extract to avoid DOMStringMap
    const prodData = {
      name: el.dataset.name,
      unit: el.dataset.unit,
      amount: 0
    };
    selectedProducts.set(id, prodData);
    el.classList.add('selected');
  }
  updateActionBar();
}

function updateActionBar() {
  const bar = document.getElementById('actionBar');
  const count = document.getElementById('selectedCount');
  count.innerText = selectedProducts.size;

  if (selectedProducts.size > 0) {
    bar.classList.replace('translate-y-48', 'translate-y-0');
  } else {
    bar.classList.replace('translate-y-0', 'translate-y-48');
  }
}

function openQuantityDrawer() {
  const list = document.getElementById('modalList');
  list.innerHTML = '';
  selectedProducts.forEach((data, id) => {
    list.innerHTML += `
        <div class="flex items-center justify-between p-8 bg-slate-50/50 rounded-[2.5rem] border border-slate-100 hover:border-emerald-200 transition-all">
            <div>
                <h4 class="font-black text-slate-900 uppercase text-sm tracking-tight">${data.name}</h4>
                <p class="text-[9px] font-black text-emerald-500 uppercase tracking-widest mt-1">Inbound Unit: ${data.unit}</p>
            </div>
            <div class="flex items-center gap-5">
                <input type="number" placeholder="0" onchange="updateAmount('${id}', this.value)"
                       class="w-36 px-6 py-4 bg-white border-2 border-slate-100 focus:border-emerald-500 rounded-2xl text-center font-black text-slate-900 outline-none transition-all shadow-sm">
            </div>
        </div>
    `;
  });
  document.getElementById('entryModal').classList.replace('hidden', 'flex');
}

function updateAmount(id, val) {
  if (selectedProducts.has(id)) {
    selectedProducts.get(id).amount = parseFloat(val) || 0;
  }
}

function requestFinalConfirmation() {
  const items = Array.from(selectedProducts.values());
  if (items.some(i => i.amount > 0)) {
    document.getElementById('safetyModal').classList.replace('hidden', 'flex');
  } else {
    alert("Please enter a valid amount.");
  }
}

async function confirmAndSend() {
  const btn = document.getElementById('finalConfirmBtn');
  const confirmUrl = btn.dataset.confirmUrl;
  btn.disabled = true;
  btn.innerText = "Syncing Master Data...";

  const payload = Array.from(selectedProducts, ([id, value]) => ({ id, amount: value.amount }));

  console.log("Restock Confirm URL:", confirmUrl);
  console.log("Restock Payload:", JSON.stringify({ items: payload }));

  try {
    const response = await fetch(confirmUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ items: payload })
    });

    if (response.ok) {
      window.location.reload();
    } else {
      const errorText = await response.text();
      console.error("Restock Server Error:", response.status, errorText);
      alert("Sync Failed: " + errorText);
      btn.disabled = false;
      btn.innerText = "Confirm & Update Stock";
    }
  } catch (e) {
    console.error("Network Error:", e);
    alert("Network error during sync.");
    btn.disabled = false;
  }
}

function closeModal(id) { document.getElementById(id).classList.replace('flex', 'hidden'); }

function filterProducts() {
  let filter = document.getElementById('restockSearch').value.toLowerCase();
  document.querySelectorAll('.product-card').forEach(card => {
    const match = card.dataset.name.toLowerCase().includes(filter);
    card.style.display = match ? 'block' : 'none';
  });
}

function clearAll() {
  selectedProducts.clear();
  document.querySelectorAll('.product-card').forEach(c => c.classList.remove('selected'));
  updateActionBar();
}
