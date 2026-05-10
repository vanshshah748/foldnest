/* =========================
   CHECKOUT JS — Multi-step checkout flow
   ======================== */

let selectedAddressId = null;
let selectedPaymentMethod = 'COD';
let cartData = null;
let addressesData = [];

document.addEventListener('DOMContentLoaded', () => {
  const user = JSON.parse(localStorage.getItem('foldnest_user'));
  if (!user) { window.location.href = 'login.html'; return; }
  loadCheckoutData();
});

function getUserId() {
  const user = JSON.parse(localStorage.getItem('foldnest_user'));
  return user ? user.id : null;
}

async function loadCheckoutData() {
  const userId = getUserId();
  try {
    // Load addresses and cart in parallel
    const [addrRes, cartRes] = await Promise.all([
      fetch(`${API_URL}/get_addresses.php?user_id=${userId}`),
      fetch(`${API_URL}/cart_view.php?user_id=${userId}`)
    ]);
    const addrData = await addrRes.json();
    cartData = await cartRes.json();
    addressesData = addrData.addresses || [];

    renderCheckoutAddresses();
  } catch (err) {
    console.error('Checkout load error:', err);
  }
}

function renderCheckoutAddresses() {
  const container = document.getElementById('checkoutAddresses');
  if (addressesData.length === 0) {
    container.innerHTML = '<p style="color:#666;">No saved addresses. <a href="addresses.html" style="color:var(--primary-color);">Add one now</a></p>';
    return;
  }

  container.innerHTML = '';
  addressesData.forEach(addr => {
    const div = document.createElement('div');
    div.className = `address-select-card${addr.is_default ? ' selected' : ''}`;
    if (addr.is_default) selectedAddressId = addr.id;

    div.onclick = () => {
      document.querySelectorAll('.address-select-card').forEach(c => c.classList.remove('selected'));
      div.classList.add('selected');
      selectedAddressId = addr.id;
    };

    let fullAddr = addr.address_line_1;
    if (addr.address_line_2) fullAddr += ', ' + addr.address_line_2;
    fullAddr += ', ' + addr.city + ', ' + addr.state + ' - ' + addr.pincode;

    div.innerHTML = `
      <strong>${addr.full_name}</strong> <span style="font-size:0.8rem; color:#999;">(${addr.address_type})</span>
      ${addr.is_default ? '<span style="color:#16a34a; font-size:0.8rem; float:right;">✓ Default</span>' : ''}
      <p style="margin-top:5px; color:#666; font-size:0.9rem;">📞 ${addr.mobile_number}</p>
      <p style="color:#555; font-size:0.9rem; margin-top:4px;">${fullAddr}</p>
    `;
    container.appendChild(div);
  });
}

function selectPayment(el, method) {
  document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
  el.classList.add('selected');
  selectedPaymentMethod = method;
}

function goToStep(step) {
  if (step === 2 && !selectedAddressId) {
    showToast('Please select a delivery address!'); return;
  }

  ['checkoutStep1', 'checkoutStep2', 'checkoutStep3'].forEach((id, i) => {
    document.getElementById(id).style.display = (i + 1 === step) ? 'block' : 'none';
  });

  ['step1', 'step2', 'step3'].forEach((id, i) => {
    const el = document.getElementById(id);
    el.classList.remove('active', 'done');
    if (i + 1 < step) el.classList.add('done');
    if (i + 1 === step) el.classList.add('active');
  });

  if (step === 3) renderReview();
}

function renderReview() {
  // Address
  const addr = addressesData.find(a => a.id === selectedAddressId);
  if (addr) {
    document.getElementById('orderReviewAddress').innerHTML = `
      <strong>📍 Delivering to:</strong> ${addr.full_name}, ${addr.address_line_1}, ${addr.city}, ${addr.state} - ${addr.pincode}<br>
      <small>📞 ${addr.mobile_number}</small>
    `;
  }

  // Payment
  document.getElementById('reviewPayment').textContent = selectedPaymentMethod;

  // Cart items
  const itemsEl = document.getElementById('orderReviewItems');
  if (cartData && cartData.items && cartData.items.length > 0) {
    let html = '';
    cartData.items.forEach(item => {
      html += `<div style="display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border-color);">
        <span>${item.name} × ${item.quantity}</span>
        <strong>₹${(item.price * item.quantity).toLocaleString('en-IN')}</strong>
      </div>`;
    });
    itemsEl.innerHTML = html;
    document.getElementById('reviewTotal').textContent = parseFloat(cartData.total_price).toLocaleString('en-IN');
  }
}

async function placeOrder() {
  const userId = getUserId();
  if (!userId || !selectedAddressId) return;

  const btn = document.getElementById('placeOrderBtn');
  const msg = document.getElementById('checkoutMessage');
  btn.disabled = true;
  btn.textContent = 'Placing Order...';

  try {
    const res = await fetch(`${API_URL}/place_order.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: userId,
        address_id: selectedAddressId,
        payment_method: selectedPaymentMethod
      })
    });
    const data = await res.json();

    if (res.ok) {
      // Show success
      document.getElementById('checkoutStep3').style.display = 'none';
      document.querySelectorAll('.checkout-steps .step-item').forEach(s => s.classList.add('done'));
      document.getElementById('orderSuccess').style.display = 'block';
      document.getElementById('successTrackingId').textContent = data.tracking_id;
      document.getElementById('successDelivery').textContent = new Date(data.estimated_delivery).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
      document.getElementById('successTrackLink').href = `tracking.html?id=${data.tracking_id}`;
    } else {
      msg.style.color = 'red';
      msg.textContent = data.message || 'Order failed!';
      btn.disabled = false;
      btn.textContent = '✅ Place Order';
    }
  } catch (err) {
    msg.style.color = 'red';
    msg.textContent = 'Network error. Try again.';
    btn.disabled = false;
    btn.textContent = '✅ Place Order';
  }
}
