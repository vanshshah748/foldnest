let currentAdminId = null;

document.addEventListener('DOMContentLoaded', () => {
    // Auth Check
    const adminData = localStorage.getItem('foldnest_admin');
    if (!adminData) {
        window.location.href = 'index.html';
        return;
    }
    const admin = JSON.parse(adminData);
    currentAdminId = admin.id;
    document.getElementById('admin-name').textContent = `${admin.role}: ${admin.username}`;

    document.getElementById('logout-btn').addEventListener('click', () => {
        localStorage.removeItem('foldnest_admin');
        window.location.href = 'index.html';
    });

    document.getElementById('password-form').addEventListener('submit', handlePasswordUpdate);
});

async function handlePasswordUpdate(e) {
    e.preventDefault();
    
    const currentPwd = document.getElementById('current_password').value;
    const newPwd = document.getElementById('new_password').value;
    const confirmPwd = document.getElementById('confirm_password').value;

    if (newPwd !== confirmPwd) {
        return showToast('New passwords do not match.', true);
    }

    const btn = document.getElementById('pwd-btn');
    btn.textContent = 'Updating...';
    btn.disabled = true;

    try {
        const res = await fetch('../backend/api/admin/settings/update_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                admin_id: currentAdminId,
                current_password: currentPwd,
                new_password: newPwd
            })
        });
        const json = await res.json();
        
        if (json.success) {
            showToast('Password updated successfully!');
            e.target.reset();
        } else {
            showToast(json.message, true);
        }
    } catch (error) {
        showToast('Error updating password', true);
    } finally {
        btn.textContent = 'Update Password';
        btn.disabled = false;
    }
}

function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show ' + (isError ? 'error' : '');
    setTimeout(() => { t.classList.remove('show'); }, 3000);
}
