document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        const name = form.querySelector('input[name="name"]').value.trim();
        const price = parseFloat(form.querySelector('input[name="price"]').value);

        if (name.length < 2) {
            e.preventDefault();
            alert('Product name must be at least 2 characters.');
            return;
        }

        if (isNaN(price) || price <= 0) {
            e.preventDefault();
            alert('Price must be a positive number.');
        }
    });
});
