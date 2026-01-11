document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('search');
  const productList = document.getElementById('productList');
  const products = Array.from(productList.querySelectorAll('.product-card'));
  const totalValueEl = document.getElementById('totalValue');
  const countEl = document.getElementById('productCount');

  function formatCurrency(v) {
    return 'R ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updateStats() {
    const visible = products.filter(p => !p.classList.contains('hidden'));
    const count = visible.length;
    let total = 0;
    visible.forEach(p => {
      const price = parseFloat(p.getAttribute('data-price')) || 0;
      total += price;
    });
    countEl.textContent = count;
    totalValueEl.textContent = formatCurrency(total);
  }

  function filter(q) {
    const term = q.trim().toLowerCase();
    products.forEach(p => {
      const name = p.getAttribute('data-name') || '';
      const desc = p.getAttribute('data-desc') || '';
      const match = term === '' || name.includes(term) || desc.includes(term);
      if (match) {
        p.classList.remove('hidden');
        // fade-in effect
        p.style.display = '';
        requestAnimationFrame(() => p.style.opacity = '1');
      } else {
        // hide with animation
        p.style.opacity = '0';
        setTimeout(() => p.classList.add('hidden'), 260);
      }
    });
    updateStats();
  }

  const debounce = (fn, t=200) => { let timer; return (...a) => { clearTimeout(timer); timer = setTimeout(() => fn(...a), t); } };

  // initialize
  products.forEach(p => { p.style.transition = 'opacity .24s ease, transform .24s ease'; });
  updateStats();

  if (searchInput) {
    searchInput.addEventListener('input', debounce((e) => filter(e.target.value), 180));
  }

});
