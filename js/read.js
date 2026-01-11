(() => {
    const API = 'fetch_products.php?format=json';

    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

    function debounce(fn, wait = 220) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), wait);
        };
    }

    async function fetchProducts(q = '') {
        const url = q ? `${API}&q=${encodeURIComponent(q)}` : API;
        const res = await fetch(url, { cache: 'no-store' });
        if (!res.ok) throw new Error('Network response was not ok');
        const data = await res.json();
        return data.products || [];
    }

    function createCard(p) {
        const card = document.createElement('article');
        card.className = 'product-card';

        const imgWrap = document.createElement('div');
        imgWrap.className = 'product-image';
        if (p.file_url) {
            const img = document.createElement('img');
            img.src = p.file_url;
            img.alt = p.name;
            imgWrap.appendChild(img);
        } else {
            imgWrap.textContent = p.name ? p.name.charAt(0).toUpperCase() : 'No image';
        }

        const info = document.createElement('div');
        info.className = 'product-info';
        info.innerHTML = `
            <div class="product-title">${escapeHtml(p.name)}</div>
            <div class="product-desc">${escapeHtml(p.description)}</div>
            <div class="product-price">R ${Number(p.price).toFixed(2)}</div>
        `;

        const actions = document.createElement('div');
        actions.className = 'product-actions';

        const edit = document.createElement('a');
        edit.href = `update.php?product_id=${encodeURIComponent(p.product_id)}`;
        edit.className = 'edit-btn';
        edit.textContent = 'Edit';

        const del = document.createElement('a');
        del.href = `delete.php?product_id=${encodeURIComponent(p.product_id)}`;
        del.className = 'delete-btn';
        del.textContent = 'Delete';
        del.addEventListener('click', (ev) => {
            if (!confirm('Are you sure you want to delete this product?')) ev.preventDefault();
        });

        actions.appendChild(edit);
        actions.appendChild(del);

        card.appendChild(imgWrap);
        card.appendChild(info);
        card.appendChild(actions);

        return card;
    }

    function escapeHtml(s = '') {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function render(q = '') {
        const loader = $('#loader');
        const grid = $('#productGrid');
        const empty = $('#noResults');

        loader.style.display = 'block';
        empty.style.display = 'none';
        grid.innerHTML = '';

        try {
            const products = await fetchProducts(q);
            loader.style.display = 'none';

            if (!products.length) {
                empty.style.display = 'block';
                return;
            }

            const frag = document.createDocumentFragment();
            products.forEach(p => frag.appendChild(createCard(p)));
            grid.appendChild(frag);
        } catch (err) {
            loader.textContent = 'Failed to load products.';
            console.error(err);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const search = $('#search');
        const refresh = $('#refreshBtn');

        render();

        const onSearch = debounce(() => render(search.value.trim()), 250);
        search.addEventListener('input', onSearch);

        refresh.addEventListener('click', () => render(search.value.trim()));
    });

})();
