/* =========================
   PRODUCTS & WISHLIST LOGIC
   ======================== */

let wishlist = JSON.parse(localStorage.getItem('foldnest_wishlist')) || [];

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('products-container') || document.getElementById('featured-products')) {
    loadProducts();
  }
});

function saveWishlist() {
  localStorage.setItem('foldnest_wishlist', JSON.stringify(wishlist));
}

function toggleWishlist(productId) {
  const index = wishlist.indexOf(productId);
  if (index === -1) {
    wishlist.push(productId);
    if (typeof showToast === 'function') showToast('Added to Wishlist');
  } else {
    wishlist.splice(index, 1);
    if (typeof showToast === 'function') showToast('Removed from Wishlist');
  }
  saveWishlist();
  
  // Re-render to update heart icons
  if (window.allProducts) {
    renderProducts(window.allProducts);
  }
}

async function loadProducts() {
  try {
    // Fetch from our live PHP Backend
    const response = await fetch('http://localhost/foldnest/backend/api/get_products.php');
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.message || 'Failed to fetch products');
    }
    
    const products = data.products || [];
    
    if (products.length === 0) {
      const container = document.getElementById('products-container') || document.getElementById('featured-products');
      if (container) {
        container.innerHTML = '<p style="text-align:center;width:100%;">No products found in the database. Please add some products.</p>';
      }
      return;
    }

    window.allProducts = products;
    renderProducts(products);
  } catch (error) {
    console.error('Error loading products:', error);
    const container = document.getElementById('products-container') || document.getElementById('featured-products');
    if (container) {
      container.innerHTML = '<p style="text-align:center;width:100%;">Failed to load products. Please try again later.</p>';
    }
  }
}

function renderProducts(products) {
  const isHomePage = !window.location.pathname.includes('/pages/');
  const container = document.getElementById(isHomePage ? 'featured-products' : 'products-container');
  if (!container) return;
  
  // Filter out products with completely missing critical data (but allow missing images via fallback)
  let productsToRender = products.filter(p => p.title);
  
  container.innerHTML = '';
  
  // Build base URL for images so they work on any page
  const baseUrl = window.location.origin + '/foldnest/frontend/';
  
  productsToRender.forEach(product => {
    let imagePath = product.image;
    
    // 1. Fallback for empty or missing image
    if (!imagePath || imagePath.trim() === '') {
      imagePath = 'assets/images/placeholder.jpg';
    }
    
    // 2. Normalize path and convert to absolute URL
    if (!imagePath.startsWith('http')) {
      // Remove any leading slashes, dots or 'frontend/' to get a clean relative path
      let cleanPath = imagePath.replace(/^([./\\]+|frontend\/)+/, '');
      
      // Fix common mistakes like 'images/...' instead of 'assets/images/...'
      if (cleanPath.startsWith('images/')) {
        cleanPath = 'assets/' + cleanPath;
      }
      // Fix 'uploads/...' instead of 'assets/images/...' (if no actual uploads folder exists)
      if (cleanPath.startsWith('uploads/')) {
        cleanPath = cleanPath.replace('uploads/', 'assets/images/');
      }
      
      imagePath = baseUrl + cleanPath;
    }
    
    const isWished = wishlist.includes(product.id);
    const outOfStock = product.stock === 0;
    
    const card = document.createElement('div');
    card.className = 'card product';
    card.setAttribute('data-category', product.category);
    
    card.innerHTML = `
      <div class="card-image-wrapper">
        <img src="${imagePath}" alt="${product.title}" onclick="openModal(this)">
        <button onclick="toggleWishlist(${product.id})" class="wishlist-btn ${isWished ? 'active' : ''}">
          ${isWished ? '♥' : '♡'}
        </button>
      </div>
      <div class="card-content">
        <h3>${product.title}</h3>
        <p class="card-desc">
          ${product.description}
        </p>
        <div class="price-row">
          <div class="price price-text">
            ₹${product.price.toLocaleString('en-IN')}
            <span class="old-price">₹${product.oldPrice.toLocaleString('en-IN')}</span>
          </div>
          <div class="rating-badge">
            ★ ${product.rating}
          </div>
        </div>
        <button 
          class="btn add-to-cart-btn ${outOfStock ? 'out-of-stock-btn' : ''}" 
          onclick="${outOfStock ? '' : `addToCart(${product.id}, '${product.title}', ${product.price})`}">
          ${outOfStock ? 'Out of Stock' : 'Add To Cart'}
        </button>
      </div>
    `;
    
    container.appendChild(card);
  });
}

function scrollToProducts() {
  const productsSection = document.getElementById('products-container') || document.getElementById('featured-products');
  if (productsSection) {
    productsSection.scrollIntoView({ behavior: 'smooth' });
  }
}
