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

    // Init Products
    loadProducts();

    // Event Listeners for Filters
    document.getElementById('search-input').addEventListener('input', debounce(loadProducts, 500));
    document.getElementById('status-filter').addEventListener('change', loadProducts);

    // Form Submit
    document.getElementById('product-form').addEventListener('submit', handleSaveProduct);
});

let allProductsRaw = [];

async function loadProducts() {
    const search = document.getElementById('search-input').value;
    const status = document.getElementById('status-filter').value;
    const tbody = document.getElementById('products-body');
    
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Loading...</td></tr>';

    try {
        const res = await fetch(`../backend/api/admin/products/list.php?search=${encodeURIComponent(search)}&status=${status}`);
        const json = await res.json();
        
        if (json.success) {
            allProductsRaw = json.data;
            renderTable(json.data);
        } else {
            showToast('Failed to load products', true);
        }
    } catch (e) {
        showToast('Network error loading products', true);
    }
}

function renderTable(products) {
    const tbody = document.getElementById('products-body');
    tbody.innerHTML = '';

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No products found.</td></tr>';
        return;
    }

    products.forEach(p => {
        // Fix image path resolving
        let imgUrl = p.image;
        if (!imgUrl.startsWith('http')) {
            imgUrl = '../frontend/' + imgUrl.replace(/^(\.\/|\.\.\/)+/, '').replace(/^frontend\//, '');
        }

        const isDeleted = p.deleted_at !== null;
        let actions = '';
        
        if (isDeleted) {
            actions = `<button class="action-btn btn-restore" onclick="handleAction(${p.id}, 'restore')">Restore</button>`;
        } else {
            actions = `
                <button class="action-btn btn-edit" onclick="editProduct(${p.id})">Edit</button>
                <button class="action-btn btn-delete" onclick="handleAction(${p.id}, 'soft_delete')">Delete</button>
            `;
        }

        tbody.innerHTML += `
            <tr style="${isDeleted ? 'opacity: 0.5' : ''}">
                <td><img src="${imgUrl}" onerror="this.src='../frontend/assets/images/placeholder.jpg'" class="thumb"></td>
                <td><strong>${p.name}</strong><br><small>ID: ${p.id}</small></td>
                <td>${p.category_name || 'N/A'}</td>
                <td>₹${parseFloat(p.price).toLocaleString('en-IN')}</td>
                <td>${p.stock}</td>
                <td>
                    <span class="status-badge ${p.is_active == 1 ? 'status-delivered' : 'status-cancelled'}" 
                          style="cursor:pointer" 
                          onclick="handleAction(${p.id}, 'toggle_status')"
                          title="Click to toggle visibility">
                        ${p.is_active == 1 ? 'Active' : 'Hidden'}
                    </span>
                </td>
                <td>${actions}</td>
            </tr>
        `;
    });
}

// Modal Logic
function openProductModal() {
    document.getElementById('product-form').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('modal-title').textContent = 'Add New Product';
    document.getElementById('product-modal').style.display = 'flex';
}

function closeProductModal() {
    document.getElementById('product-modal').style.display = 'none';
}

function editProduct(id) {
    const p = allProductsRaw.find(x => x.id == id);
    if (!p) return;
    
    document.getElementById('product_id').value = p.id;
    document.getElementById('name').value = p.name;
    document.getElementById('price').value = p.price;
    document.getElementById('stock').value = p.stock;
    document.getElementById('image_url').value = p.image;
    document.getElementById('is_active').checked = p.is_active == 1;
    // (Other fields would map similarly, keeping it brief)
    
    document.getElementById('modal-title').textContent = 'Edit Product #' + p.id;
    document.getElementById('product-modal').style.display = 'flex';
}

async function handleSaveProduct(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    // Checkboxes aren't included in FormData if unchecked
    data.is_active = document.getElementById('is_active').checked;
    data.is_featured = document.getElementById('is_featured').checked;

    document.getElementById('save-btn').textContent = 'Saving...';

    try {
        const res = await fetch('../backend/api/admin/products/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        
        if (json.success) {
            showToast('Product saved successfully!');
            closeProductModal();
            loadProducts();
        } else {
            showToast(json.message, true);
        }
    } catch (error) {
        showToast('Error saving product', true);
    } finally {
        document.getElementById('save-btn').textContent = 'Save Product';
    }
}

async function handleAction(id, action) {
    let msg = '';
    if (action === 'soft_delete') msg = 'Are you sure you want to move this product to Trash?';
    if (action === 'restore') msg = 'Restore this product?';
    if (action === 'toggle_status') msg = 'Change visibility status?';
    
    if (!confirm(msg)) return;

    try {
        const res = await fetch('../backend/api/admin/products/action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action })
        });
        const json = await res.json();
        
        if (json.success) {
            showToast(json.message);
            loadProducts();
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
