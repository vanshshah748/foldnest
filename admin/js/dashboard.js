document.addEventListener('DOMContentLoaded', () => {
    // 1. Auth Check
    const adminData = localStorage.getItem('foldnest_admin');
    if (!adminData) {
        window.location.href = 'index.html';
        return;
    }
    
    const admin = JSON.parse(adminData);
    document.getElementById('admin-name').textContent = `${admin.role}: ${admin.username}`;

    // 2. Logout Logic
    document.getElementById('logout-btn').addEventListener('click', () => {
        localStorage.removeItem('foldnest_admin');
        window.location.href = 'index.html';
    });

    // 3. Fetch Dashboard Data
    fetchDashboardData();
});

async function fetchDashboardData() {
    try {
        const res = await fetch('../backend/api/admin/get_dashboard_stats.php');
        const json = await res.json();
        
        if (json.success) {
            populateCards(json.data);
            renderCharts(json.data);
            populateTables(json.data);
        } else {
            console.error('Failed to load dashboard:', json.message);
        }
    } catch (error) {
        console.error('Network error loading dashboard');
    }
}

function populateCards(data) {
    document.getElementById('val-total-revenue').textContent = '₹' + parseFloat(data.total_revenue).toLocaleString('en-IN');
    document.getElementById('val-monthly-revenue').textContent = '₹' + parseFloat(data.monthly_revenue).toLocaleString('en-IN');
    document.getElementById('val-total-orders').textContent = data.total_orders;
    document.getElementById('val-total-users').textContent = data.total_users;
    document.getElementById('val-pending-orders').textContent = data.pending_orders;
    document.getElementById('val-delivered-orders').textContent = data.delivered_orders;
    document.getElementById('val-total-products').textContent = data.total_products;
    document.getElementById('val-out-stock').textContent = data.out_of_stock;
}

function renderCharts(data) {
    // Set Chart.js defaults for Dark Theme
    Chart.defaults.color = '#94a3b8';
    Chart.defaults.borderColor = '#334155';

    // Sales Bar Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const labels = data.sales_chart.map(item => item.month);
    const revenues = data.sales_chart.map(item => parseFloat(item.revenue));
    
    new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: labels.length ? labels : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Revenue (₹)',
                data: revenues.length ? revenues : [0,0,0,0,0,0],
                backgroundColor: '#3b82f6',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    // Order Status Doughnut Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusLabels = data.status_chart.map(item => item.status.toUpperCase());
    const statusCounts = data.status_chart.map(item => item.count);
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels.length ? statusLabels : ['No Data'],
            datasets: [{
                data: statusCounts.length ? statusCounts : [1],
                backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#8b5cf6'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '75%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

function populateTables(data) {
    const ordersBody = document.getElementById('recent-orders-body');
    ordersBody.innerHTML = '';
    data.recent_orders.forEach(order => {
        let badgeClass = 'status-pending';
        if (order.status === 'delivered') badgeClass = 'status-delivered';
        if (order.status === 'cancelled') badgeClass = 'status-cancelled';
        
        ordersBody.innerHTML += `
            <tr>
                <td>#ORD-${order.id}</td>
                <td>₹${parseFloat(order.total_amount).toLocaleString('en-IN')}</td>
                <td><span class="status-badge ${badgeClass}">${order.status.toUpperCase()}</span></td>
                <td>${order.date}</td>
            </tr>
        `;
    });

    const usersBody = document.getElementById('recent-users-body');
    usersBody.innerHTML = '';
    data.recent_users.forEach(user => {
        usersBody.innerHTML += `
            <tr>
                <td>${user.first_name} ${user.last_name || ''}</td>
                <td>${user.email}</td>
                <td>${user.joined}</td>
            </tr>
        `;
    });
}
