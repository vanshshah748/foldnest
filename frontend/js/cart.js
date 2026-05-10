/* =========================
   CART & CHECKOUT LOGIC (LIVE DB)
   ======================== */

// Helper to get user ID
function getUserId() {
  const user = JSON.parse(localStorage.getItem('foldnest_user'));
  return user ? user.id : null;
}

// Initialize cart UI on load
document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('cartItems')) {
    updateCart();
    loadRecommendations();
  }
});

async function addToCart(productId, productTitle, price) {
  const userId = getUserId();
  if (!userId) {
    if (typeof showToast === 'function') showToast('Please login to add to cart!');
    setTimeout(() => { window.location.href = 'login.html'; }, 1500);
    return;
  }

  try {
    const response = await fetch('http://localhost/foldnest/backend/api/cart_add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        user_id: userId,
        product_id: productId,
        quantity: 1
      })
    });
    
    if (response.ok) {
      if (typeof showToast === 'function') showToast(productTitle + ' added to cart');
      updateCart();
    } else {
      if (typeof showToast === 'function') showToast('Failed to add to cart');
    }
  } catch (error) {
    console.error('Add to cart error:', error);
  }
}

async function updateQuantity(cartItemId, change) {
  // In a full implementation, you would need a cart_update.php to change quantity directly.
  // For this sprint, since cart_remove.php expects just cart_item_id, we will build a minimal implementation.
  // We will simply remove the item if change is negative for now.
  if (change < 0) {
    removeItem(cartItemId);
  } else {
    // If we wanted to increment, we could call cart_add.php again with the product_id
    if (typeof showToast === 'function') showToast('Quantity updates coming soon!');
  }
}

async function updateCart() {
  const cartItemsContainer = document.getElementById('cartItems');
  const totalContainer = document.getElementById('total');
  
  if (!cartItemsContainer || !totalContainer) return;

  const userId = getUserId();
  if (!userId) {
    cartItemsContainer.innerHTML = '<p style="text-align:center; padding: 20px; color: #666;">Please log in to view your cart.</p>';
    totalContainer.innerText = '0';
    return;
  }

  try {
    const response = await fetch(`http://localhost/foldnest/backend/api/cart_view.php?user_id=${userId}`);
    const data = await response.json();
    
    cartItemsContainer.innerHTML = '';
    let totalPrice = 0;
    
    if (response.ok && data.items && data.items.length > 0) {
      data.items.forEach((item) => {
        cartItemsContainer.innerHTML += `
          <div class="cart-item" style="display:flex; justify-content:space-between; align-items:center; padding: 15px 0; border-bottom: 1px solid #ddd;">
            <div style="flex:1;">
              <h3 style="margin-bottom: 5px;">${item.name}</h3>
              <p style="color:#ea580c; font-weight:600;">₹${item.price}</p>
            </div>
            <div style="display:flex; align-items:center; gap: 15px;">
              <div style="display:flex; align-items:center; border: 1px solid #ddd; border-radius: 8px; overflow:hidden;">
                <span style="padding: 5px 12px; font-weight:500;">Qty: ${item.quantity}</span>
              </div>
              <button class="btn" style="padding: 8px 15px; background: #ef4444;" onclick="removeItem(${item.product_id})">Remove</button>
            </div>
          </div>
        `;
      });
      totalContainer.innerText = parseFloat(data.total_price).toLocaleString('en-IN');
    } else {
      cartItemsContainer.innerHTML = '<p style="text-align:center; padding: 20px; color: #666;">Your cart is empty.</p>';
      totalContainer.innerText = '0';
    }
  } catch (error) {
    console.error("Cart view error", error);
  }
}

async function removeItem(productId) {
  const userId = getUserId();
  if (!userId) return;

  try {
    const response = await fetch('http://localhost/foldnest/backend/api/cart_remove.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId, product_id: productId })
    });
    
    if (response.ok) {
      if (typeof showToast === 'function') showToast('Item removed');
      updateCart();
    }
  } catch (error) {
    console.error('Remove error:', error);
  }
}

async function makePayment() {
  const paymentMessage = document.getElementById('paymentMessage');
  const userId = getUserId();
  
  if (!userId) return;
  if (!paymentMessage) return;
  
  try {
    const response = await fetch('http://localhost/foldnest/backend/api/place_order.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ user_id: userId })
    });
    
    const data = await response.json();
    
    if (response.ok) {
      paymentMessage.innerText = 'Order Placed Successfully! (Txn ACID)';
      paymentMessage.style.color = 'green';
      
      setTimeout(() => {
        updateCart();
        paymentMessage.innerText = '';
      }, 2000);
    } else {
      paymentMessage.innerText = data.message || 'Checkout Failed';
      paymentMessage.style.color = 'red';
    }
  } catch (error) {
    console.error('Checkout error:', error);
  }
}

async function loadRecommendations() {
  const container = document.getElementById('recommendations-container');
  const section = document.getElementById('recommendations-wrapper');
  const titleEl = document.getElementById('recommendations-title');
  if (!container || !section) return;

  const userId = getUserId() || 0;

  try {
    const response = await fetch(`http://localhost/foldnest/backend/api/get_recommendations.php?user_id=${userId}`);
    const data = await response.json();

    if (data.success && data.recommendations && data.recommendations.length > 0) {
      if (titleEl && data.type) titleEl.innerText = data.type;
      
      container.innerHTML = '';
      const baseUrl = window.location.origin + '/foldnest/frontend/';

      data.recommendations.forEach(product => {
        let imagePath = product.image || 'assets/images/placeholder.jpg';
        if (!imagePath.startsWith('http')) {
          let cleanPath = imagePath.replace(/^([./\\]+|frontend\/)+/, '');
          if (cleanPath.startsWith('images/')) cleanPath = 'assets/' + cleanPath;
          if (cleanPath.startsWith('uploads/')) cleanPath = cleanPath.replace('uploads/', 'assets/images/');
          imagePath = baseUrl + cleanPath;
        }

        container.innerHTML += `
          <div class="card product">
            <div class="card-image-wrapper">
              <img src="${imagePath}" alt="${product.title}" loading="lazy" onerror="this.src='${baseUrl}assets/images/placeholder.jpg'" onclick="openModal(this)">
            </div>
            <div class="card-content">
              <h3>${product.title}</h3>
              <div class="price-row">
                <div class="price price-text">
                  ₹${parseFloat(product.price).toLocaleString('en-IN')}
                </div>
                <div class="rating-badge">
                  ★ ${product.rating || 0}
                </div>
              </div>
              <button class="btn add-to-cart-btn" onclick="addToCart(${product.id}, '${product.title.replace(/'/g, "\\'")}', ${product.price})">Add To Cart</button>
            </div>
          </div>
        `;
      });
      section.style.display = 'block';
    }
  } catch (error) {
    console.error('Error loading recommendations:', error);
  }
}
