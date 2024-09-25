// js/script.js

document.addEventListener('DOMContentLoaded', () => {
    // Handle Clear Order Confirmation
    const clearButtons = document.querySelectorAll('.clear-button');
    clearButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Are you sure you want to clear the order?')) {
                e.preventDefault();
            }
        });
    });

    // Optional: Handle dynamic updates or AJAX for real-time interactions

    // Example: Update total amount dynamically (if using AJAX)
    /*
    const orderList = document.getElementById('order-list');
    const totalAmountSpan = document.getElementById('total-amount');

    function updateTotal() {
        let total = 0;
        const items = orderList.querySelectorAll('.order-item');
        items.forEach(item => {
            const subtotalText = item.querySelector('p:nth-child(2)').innerText;
            const subtotal = parseFloat(subtotalText.replace('Subtotal: ₱', ''));
            total += subtotal;
        });
        totalAmountSpan.innerText = total.toFixed(2);
    }

    // Call updateTotal initially
    updateTotal();

    // If items can be added/removed dynamically, ensure updateTotal is called accordingly
    */
});
