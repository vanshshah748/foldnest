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

    loadCustomers();

    document.getElementById('search-input').addEventListener('input', debounce(loadCustomers, 500));
    document.getElementById('status-filter').addEventListener('change', loadCustomers);
});

async function loadCustomers() {
    const search = document.getElementById('search-input').value;
    const status = document.getElementById('status-filter').value;
    const tbody = document.getElementById('customers-body');
    
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Loading...</td></tr>';

    try {
        const res = await fetch(`../backend/api/admin/users/list.php?search=${encodeURIComponent(search)}&status=${status}`);
        const json = await res.json();
        
        if (json.success) {
            renderCustomersTable(json.data);
        } else {
            showToast('Failed to load customers', true);
        }
    } catch (e) {
        showToast('Network error loading customers', true);
    }
}

function renderCustomersTable(customers) {
    const tbody = document.getElementById('customers-body');
    tbody.innerHTML = '';

    if (customers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No customers found.</td></tr>';
        return;
    }

    customers.forEach(c => {
        const isActive = c.is_active == 1;
        const badgeClass = isActive ? 'status-active' : 'status-blocked';
        const rowClass = !isActive ? 'row-blocked' : '';

        tbody.innerHTML += `
            <tr class="${rowClass}">
                <td><strong>${c.full_name}</strong><br><small>ID: ${c.id}</small></td>
                <td>${c.email}<br><small>${c.phone || 'No phone'}</small></td>
                <td>${c.joined}</td>
                <td>${c.total_orders}</td>
                <td>₹${parseFloat(c.total_spent).toLocaleString('en-IN')}</td>
                <td><span class="status-badge ${badgeClass}">${isActive ? 'Active' : 'Blocked'}</span></td>
                <td>
                    <button class="action-btn ${isActive ? 'btn-block' : 'btn-unblock'}" 
                            onclick="handleAction(${c.id}, 'toggle_block')">
                        ${isActive ? 'Block User' : 'Unblock'}
                    </button>
                    ${isActive ? `<button class="action-btn btn-block" onclick="handleAction(${c.id}, 'delete')" style="margin-left: 10px;">Delete</button>` : ''}
                </td>
            </tr>
        `;
    });
}

async function handleAction(id, action) {
    let msg = action === 'toggle_block' ? 'Are you sure you want to change this user\'s access status?' : 'Are you sure you want to permanently delete this user? This cannot be undone.';
    
    if (!confirm(msg)) return;

    try {
        const res = await fetch('../backend/api/admin/users/action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action })
        });
        const json = await res.json();
        
        if (json.success) {
            showToast(json.message);
            loadCustomers();
        } else {
            showToast(json.message, true);
        }
    } catch (e) {
        showToast('Action failed', true);
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
