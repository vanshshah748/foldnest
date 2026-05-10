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

    loadQueries();

    document.getElementById('search-input').addEventListener('input', debounce(loadQueries, 500));
    document.getElementById('status-filter').addEventListener('change', loadQueries);
    
    document.getElementById('reply-form').addEventListener('submit', handleReplySend);
});

let allQueriesRaw = [];
let currentQueryId = null;

async function loadQueries() {
    const search = document.getElementById('search-input').value;
    const status = document.getElementById('status-filter').value;
    const tbody = document.getElementById('queries-body');
    
    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">Loading...</td></tr>';

    try {
        const res = await fetch(`../backend/api/admin/queries/list.php?search=${encodeURIComponent(search)}&status=${status}`);
        const json = await res.json();
        
        if (json.success) {
            allQueriesRaw = json.data;
            renderQueriesTable(json.data);
        } else {
            showToast('Failed to load queries', true);
        }
    } catch (e) {
        showToast('Network error loading queries', true);
    }
}

function renderQueriesTable(queries) {
    const tbody = document.getElementById('queries-body');
    tbody.innerHTML = '';

    if (queries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No queries found.</td></tr>';
        return;
    }

    queries.forEach(q => {
        let badgeClass = 'status-unread';
        if (q.status === 'read') badgeClass = 'status-read';
        if (q.status === 'resolved') badgeClass = 'status-resolved';

        const rowClass = q.status === 'unread' ? 'row-unread' : '';

        tbody.innerHTML += `
            <tr class="${rowClass}">
                <td><span class="status-badge ${badgeClass}">${q.status.toUpperCase()}</span></td>
                <td>${q.date}</td>
                <td><strong>${q.name}</strong><br><small>${q.email}</small></td>
                <td>${q.subject}</td>
                <td>
                    <button class="action-btn btn-view" onclick="openQuery(${q.id})">Open</button>
                </td>
            </tr>
        `;
    });
}

async function openQuery(id) {
    const q = allQueriesRaw.find(x => x.id == id);
    if (!q) return;

    currentQueryId = id;
    
    // Mark as read if it was unread
    if (q.status === 'unread') {
        await updateStatus(id, 'read');
    }

    document.getElementById('modal-ticket-id').textContent = q.id;
    
    // Populate Info
    document.getElementById('q-name').textContent = q.name;
    document.getElementById('q-email').textContent = q.email;
    document.getElementById('q-phone').textContent = q.phone || 'N/A';
    document.getElementById('q-subject').textContent = q.subject;
    document.getElementById('q-message').textContent = q.message;

    // Populate Hidden Form Fields
    document.getElementById('query_id').value = q.id;
    document.getElementById('query_email').value = q.email;
    document.getElementById('query_name').value = q.name;
    document.getElementById('query_subject').value = q.subject;
    document.getElementById('reply_message').value = '';

    document.getElementById('query-modal').style.display = 'flex';
}

function closeQueryModal() {
    document.getElementById('query-modal').style.display = 'none';
    currentQueryId = null;
    loadQueries(); // Refresh table status
}

async function updateStatus(id, status) {
    try {
        await fetch('../backend/api/admin/queries/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status })
        });
    } catch (e) {
        console.error('Failed to update status');
    }
}

async function markAsResolved() {
    if (!currentQueryId) return;
    
    try {
        await updateStatus(currentQueryId, 'resolved');
        showToast('Query marked as resolved!');
        closeQueryModal();
    } catch (e) {
        showToast('Error marking as resolved', true);
    }
}

async function handleReplySend(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    const btn = document.getElementById('send-btn');
    btn.textContent = 'Sending Email...';
    btn.disabled = true;

    try {
        const res = await fetch('../backend/api/admin/queries/reply.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const json = await res.json();
        
        if (json.success) {
            showToast(json.message);
            closeQueryModal();
        } else {
            showToast(json.message, true);
        }
    } catch (error) {
        showToast('Error sending reply', true);
    } finally {
        btn.textContent = 'Send Email & Resolve';
        btn.disabled = false;
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
