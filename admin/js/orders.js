document.addEventListener('DOMContentLoaded', () => {
    // Auth Check
    const adminData = localStorage.getItem('foldnest_admin');
    if (!adminData) {
        window.location.href = 'index.html';
        return;
    }
    const admin = JSON.parse(adminData);
    document.getElementById('admin-name').textContent = `${admin.role}: ${admin.username}`;

    document.getElementById('logout-btn').addEventListener('click', () => {
        localStorage.removeItem('foldnest_admin');
        window.location.href = 'index.html';
    });

    loadOrders();

    document.getElementById('search-input').addEventListener('input', debounce(loadOrders, 500));
    document.getElementById('status-filter').addEventListener('change', loadOrders);
    
    document.getElementById('order-form').addEventListener('submit', handleUpdateOrder);
});

let allOrdersRaw = [];

async function loadOrders() {
    const search = document.getElementById('search-input').value;
    const status = document.getElementById('status-filter').value;
    const tbody = document.getElementById('orders-body');
    
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">Loading...</td></tr>';

    try {
        const res = await fetch(`../backend/api/admin/orders/list.php?search=${encodeURIComponent(search)}&status=${status}`);
        const json = await res.json();
        
        if (json.success) {
            allOrdersRaw = json.data;
            renderOrdersTable(json.data);
        } else {
            showToast('Failed to load orders', true);
        }
    } catch (e) {
        showToast('Network error loading orders', true);
    }
}

function renderOrdersTable(orders) {
    const tbody = document.getElementById('orders-body');
    tbody.innerHTML = '';

    if (orders.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No orders found.</td></tr>';
        return;
    }

    orders.forEach(o => {
        let badgeClass = 'status-pending';
        if (o.status === 'delivered') badgeClass = 'status-delivered';
        if (o.status === 'cancelled') badgeClass = 'status-cancelled';

        tbody.innerHTML += `
            <tr>
                <td><strong>#ORD-${o.id}</strong><br><small>${o.tracking_id || 'No Tracking'}</small></td>
                <td>${o.first_name} ${o.last_name || ''}<br><small>${o.email}</small></td>
                <td>${o.order_date}</td>
                <td>₹${parseFloat(o.total_amount).toLocaleString('en-IN')}</td>
                <td><span class="status-badge ${badgeClass}">${o.status.toUpperCase()}</span></td>
                <td>
                    <button class="action-btn btn-view" onclick="viewOrder(${o.id})">Manage</button>
                </td>
            </tr>
        `;
    });
}

function viewOrder(id) {
    const o = allOrdersRaw.find(x => x.id == id);
    if (!o) return;

    document.getElementById('modal-title').textContent = `Manage Order #ORD-${o.id}`;
    
    // Populate Info Cards
    document.getElementById('c-name').textContent = `${o.first_name} ${o.last_name || ''}`;
    document.getElementById('c-email').textContent = o.email;
    
    document.getElementById('c-address').innerHTML = `${o.address_line1 || 'N/A'}<br>${o.city || ''}, ${o.state || ''} - ${o.pincode || ''}`;
    
    document.getElementById('p-method').textContent = (o.payment_method || 'COD').toUpperCase();
    document.getElementById('p-status').textContent = (o.payment_status || 'Pending').toUpperCase();
    document.getElementById('p-amount').textContent = `₹${parseFloat(o.total_amount).toLocaleString('en-IN')}`;

    // Populate Form
    document.getElementById('order_id').value = o.id;
    document.getElementById('order_status').value = o.status;
    document.getElementById('tracking_id').value = o.tracking_id || '';
    document.getElementById('estimated_delivery').value = o.estimated_delivery || '';

    document.getElementById('order-modal').style.display = 'flex';
}

function closeOrderModal() {
    document.getElementById('order-modal').style.display = 'none';
}

async function handleUpdateOrder(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    document.getElementById('save-btn').textContent = 'Updating...';

    try {
        const res = await fetch('../backend/api/admin/orders/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        
        if (json.success) {
            showToast('Order updated successfully!');
            closeOrderModal();
            loadOrders();
        } else {
            showToast(json.message, true);
        }
    } catch (error) {
        showToast('Error updating order', true);
    } finally {
        document.getElementById('save-btn').textContent = 'Update Order';
    }
}

// Utils
function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show ' + (isError ? 'error' : '');
    setTimeout(() => { t.classList.remove('show'); }, 3000);
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}
