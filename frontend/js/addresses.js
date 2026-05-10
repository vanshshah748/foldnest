/* =========================
   ADDRESSES MANAGEMENT JS
   ======================== */

document.addEventListener('DOMContentLoaded', () => {
  loadAddresses();
  updateAuthLink();
});

// Get user from localStorage
function getUserId() {
  const user = JSON.parse(localStorage.getItem('foldnest_user'));
  return user ? user.id : null;
}

// Update auth link based on login state
function updateAuthLink() {
  const link = document.getElementById('authLink');
  if (!link) return;
  const user = JSON.parse(localStorage.getItem('foldnest_user'));
  if (user) {
    link.textContent = 'Profile';
    link.href = 'profile.html';
  }
}

// =============================================
// FIX #4: Helper to safely get the API base URL
// Falls back to hardcoded URL if config.js wasn't loaded
// =============================================
function getApiUrl() {
  if (typeof API_URL !== 'undefined') {
    return API_URL;
  }
  // Fallback — matches the hardcoded pattern used in cart.js
  return 'http://localhost/foldnest/backend/api';
}

// Load all saved addresses
async function loadAddresses() {
  const userId = getUserId();
  if (!userId) {
    document.getElementById('addressLoading').style.display = 'none';
    document.getElementById('emptyState').innerHTML = '<div class="empty-icon">🔒</div><h3>Please Login</h3><p>Login to manage your addresses.</p><a href="login.html" class="btn">Login Now</a>';
    document.getElementById('emptyState').style.display = 'block';
    return;
  }

  try {
    const res = await fetch(`${getApiUrl()}/get_addresses.php?user_id=${userId}`);
    const data = await res.json();
    document.getElementById('addressLoading').style.display = 'none';

    if (data.addresses && data.addresses.length > 0) {
      document.getElementById('emptyState').style.display = 'none';
      renderAddresses(data.addresses);
    } else {
      document.getElementById('addressList').innerHTML = '';
      document.getElementById('emptyState').style.display = 'block';
    }
  } catch (err) {
    console.error('Load addresses error:', err);
    document.getElementById('addressLoading').style.display = 'none';
  }
}

// Render address cards
function renderAddresses(addresses) {
  const container = document.getElementById('addressList');
  container.innerHTML = '';

  addresses.forEach(addr => {
    const card = document.createElement('div');
    card.className = `address-card${addr.is_default ? ' default' : ''}`;

    let badges = `<span class="address-badge badge-${addr.address_type.toLowerCase()}">${addr.address_type}</span>`;
    if (addr.is_default) badges += '<span class="address-badge badge-default">Default</span>';

    let fullAddress = addr.address_line_1;
    if (addr.address_line_2) fullAddress += ', ' + addr.address_line_2;
    if (addr.landmark) fullAddress += ', Near ' + addr.landmark;
    fullAddress += ', ' + addr.city + ', ' + addr.state + ' - ' + addr.pincode;
    if (addr.country && addr.country !== 'India') fullAddress += ', ' + addr.country;

    let actionsHTML = `
      <button onclick="editAddress(${addr.id})">✏️ Edit</button>
      <button class="delete-btn" onclick="deleteAddress(${addr.id})">🗑️ Delete</button>
    `;
    if (!addr.is_default) {
      actionsHTML += `<button class="default-btn" onclick="setDefault(${addr.id})">⭐ Set Default</button>`;
    }

    card.innerHTML = `
      <div class="address-badges">${badges}</div>
      <div class="address-name">${addr.full_name}</div>
      <div class="address-phone">📞 ${addr.mobile_number}${addr.alternate_mobile ? ' / ' + addr.alternate_mobile : ''}</div>
      <div class="address-text">${fullAddress}</div>
      <div class="address-actions">${actionsHTML}</div>
    `;
    container.appendChild(card);
  });
}

// Show / Hide form
function showAddressForm() {
  document.getElementById('addressFormWrapper').style.display = 'block';
  document.getElementById('formTitle').textContent = 'Add New Address';
  document.getElementById('editAddressId').value = '';
  document.getElementById('addressForm').reset();
  document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
  document.querySelector('.type-btn[data-type="Home"]').classList.add('active');
  document.getElementById('addressFormWrapper').scrollIntoView({ behavior: 'smooth' });
}

function hideAddressForm() {
  document.getElementById('addressFormWrapper').style.display = 'none';
}

// Toggle address type buttons
function selectAddressType(btn) {
  document.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}

// =============================================
// FIX #5: Completely rewritten saveAddress()
// - Better error logging with console.error
// - Reads response as text first, then parses JSON safely
// - Shows actual backend error message instead of generic "Network error"
// - Uses getApiUrl() fallback for URL resolution
// =============================================
async function saveAddress() {
  const userId = getUserId();
  if (!userId) { showToast('Please login first!'); return; }

  const editId = document.getElementById('editAddressId').value;
  const activeType = document.querySelector('.type-btn.active');

  const payload = {
    user_id: userId,
    full_name: document.getElementById('addr_full_name').value.trim(),
    mobile_number: document.getElementById('addr_mobile').value.trim(),
    alternate_mobile: document.getElementById('addr_alt_mobile').value.trim() || null,
    address_line_1: document.getElementById('addr_line1').value.trim(),
    address_line_2: document.getElementById('addr_line2').value.trim() || null,
    landmark: document.getElementById('addr_landmark').value.trim() || null,
    city: document.getElementById('addr_city').value.trim(),
    state: document.getElementById('addr_state').value,
    pincode: document.getElementById('addr_pincode').value.trim(),
    address_type: activeType ? activeType.dataset.type : 'Home',
    is_default: document.getElementById('addr_default').checked ? 1 : 0
  };

  // Client-side validation
  if (!payload.full_name || !payload.mobile_number || !payload.address_line_1 || !payload.city || !payload.state || !payload.pincode) {
    showToast('Please fill all required fields!'); return;
  }
  if (!/^[6-9]\d{9}$/.test(payload.mobile_number)) {
    showToast('Invalid mobile number! Must be 10 digits starting with 6-9.'); return;
  }
  if (!/^\d{6}$/.test(payload.pincode)) {
    showToast('Pincode must be exactly 6 digits!'); return;
  }

  const apiBase = getApiUrl();
  const url = editId ? `${apiBase}/update_address.php` : `${apiBase}/add_address.php`;
  if (editId) payload.address_id = editId;

  // Debug: log the request for troubleshooting
  console.log('[Address Save] URL:', url);
  console.log('[Address Save] Payload:', payload);

  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    // FIX: Read response as text first, THEN try to parse as JSON
    // This prevents res.json() from throwing on non-JSON responses (like PHP errors)
    const responseText = await res.text();
    console.log('[Address Save] Response status:', res.status);
    console.log('[Address Save] Response body:', responseText);

    let data;
    try {
      data = JSON.parse(responseText);
    } catch (parseErr) {
      // Response was NOT valid JSON — likely a PHP error page
      console.error('[Address Save] Response is not valid JSON:', responseText);
      showToast('Server error. Check if database migration has been run.');
      return;
    }

    if (res.ok) {
      showToast(editId ? 'Address updated!' : 'Address added!');
      hideAddressForm();
      loadAddresses();
    } else {
      // Show the actual error message from the backend
      showToast(data.message || 'Failed to save address');
    }
  } catch (err) {
    // This catch only fires for actual network failures (server down, CORS blocked, etc.)
    console.error('[Address Save] Network error:', err);
    showToast('Cannot connect to server. Check if XAMPP/Apache is running.');
  }
}

// Edit address — populate form
async function editAddress(addressId) {
  const userId = getUserId();
  try {
    const res = await fetch(`${getApiUrl()}/get_addresses.php?user_id=${userId}`);
    const data = await res.json();
    const addr = data.addresses.find(a => a.id === addressId);
    if (!addr) return;

    showAddressForm();
    document.getElementById('formTitle').textContent = 'Edit Address';
    document.getElementById('editAddressId').value = addr.id;
    document.getElementById('addr_full_name').value = addr.full_name;
    document.getElementById('addr_mobile').value = addr.mobile_number;
    document.getElementById('addr_alt_mobile').value = addr.alternate_mobile || '';
    document.getElementById('addr_line1').value = addr.address_line_1;
    document.getElementById('addr_line2').value = addr.address_line_2 || '';
    document.getElementById('addr_landmark').value = addr.landmark || '';
    document.getElementById('addr_city').value = addr.city;
    document.getElementById('addr_state').value = addr.state;
    document.getElementById('addr_pincode').value = addr.pincode;
    document.getElementById('addr_default').checked = addr.is_default;

    document.querySelectorAll('.type-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.type === addr.address_type);
    });
  } catch (err) { console.error('[Edit Address] Error:', err); }
}

// Delete address
async function deleteAddress(addressId) {
  if (!confirm('Are you sure you want to delete this address?')) return;
  const userId = getUserId();
  try {
    const res = await fetch(`${getApiUrl()}/delete_address.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ address_id: addressId, user_id: userId })
    });
    if (res.ok) { showToast('Address deleted'); loadAddresses(); }
    else { showToast('Failed to delete address'); }
  } catch (err) {
    console.error('[Delete Address] Error:', err);
    showToast('Cannot connect to server');
  }
}

// Set default address
async function setDefault(addressId) {
  const userId = getUserId();
  try {
    const res = await fetch(`${getApiUrl()}/update_address.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ address_id: addressId, user_id: userId, is_default: 1 })
    });
    if (res.ok) { showToast('Default address updated'); loadAddresses(); }
  } catch (err) {
    console.error('[Set Default] Error:', err);
    showToast('Cannot connect to server');
  }
}
