/* =========================
   ORDERS HISTORY JS
   ======================== */

document.addEventListener('DOMContentLoaded', loadOrders);

function getUserId() {
  const user = JSON.parse(localStorage.getItem('foldnest_user'));
  return user ? user.id : null;
}

async function loadOrders() {
  const userId = getUserId();
  const loadingEl = document.getElementById('ordersLoading');
  const emptyEl = document.getElementById('ordersEmpty');
  const listEl = document.getElementById('ordersList');

  if (!userId) {
    loadingEl.style.display = 'none';
    emptyEl.innerHTML = '<div class="empty-icon">🔒</div><h3>Please Login</h3><p>Login to view your order history.</p><a href="login.html" class="btn">Login Now</a>';
    emptyEl.style.display = 'block';
    return;
  }

  try {
    const res = await fetch(`${API_URL}/get_orders.php?user_id=${userId}`);
    const data = await res.json();
    loadingEl.style.display = 'none';

    if (data.orders && data.orders.length > 0) {
      emptyEl.style.display = 'none';
      renderOrders(data.orders);
    } else {
      listEl.innerHTML = '';
      emptyEl.style.display = 'block';
    }
  } catch (err) {
    loadingEl.style.display = 'none';
    console.error('Load orders error:', err);
  }
}

function renderOrders(orders) {
  const container = document.getElementById('ordersList');
  container.innerHTML = '';

  orders.forEach(order => {
    const card = document.createElement('div');
    card.className = 'order-card';

    const statusClass = 'status-' + order.status;
    const statusLabel = order.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    const date = new Date(order.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });

    let itemsHTML = '';
    if (order.items && order.items.length > 0) {
      order.items.forEach(item => {
        let imgPath = item.image_url || 'assets/images/placeholder.jpg';
        if (!imgPath.startsWith('http')) {
            let cleanPath = imgPath.replace(/^([./\\]+|frontend\/)+/, '');
            if (cleanPath.startsWith('images/')) cleanPath = 'assets/' + cleanPath;
            if (cleanPath.startsWith('uploads/')) cleanPath = cleanPath.replace('uploads/', 'assets/images/');
            imgPath = window.location.origin + '/foldnest/frontend/' + cleanPath;
        }
        itemsHTML += `
          <div class="order-item-row">
            <img src="${imgPath}" alt="${item.name}" onerror="this.style.display='none'">
            <div class="order-item-info">
              <h4>${item.name}</h4>
              <p>Qty: ${item.quantity} × ₹${parseFloat(item.price_at_purchase).toLocaleString('en-IN')}</p>
            </div>
          </div>`;
      });
    }

    card.innerHTML = `
      <div class="order-header">
        <div class="order-header-left">
          <h3>Order #${order.id} ${order.tracking_id ? '— ' + order.tracking_id : ''}</h3>
          <p>Placed on ${date} • ${order.address || ''}</p>
        </div>
        <span class="status-badge ${statusClass}">${statusLabel}</span>
      </div>
      <div class="order-items-list">${itemsHTML}</div>
      <div class="order-footer">
        <div class="order-total">Total: ₹${parseFloat(order.total_amount).toLocaleString('en-IN')}</div>
        <div class="order-actions">
          ${order.tracking_id ? `<a href="tracking.html?id=${order.tracking_id}">📦 Track Order</a>` : ''}
        </div>
      </div>
    `;
    container.appendChild(card);
  });
}
