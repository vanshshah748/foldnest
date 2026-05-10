document.addEventListener('DOMContentLoaded', () => {
    // Check if already logged in
    const adminData = localStorage.getItem('foldnest_admin');
    if (adminData) {
        window.location.href = 'dashboard.html';
    }

    const loginForm = document.getElementById('admin-login-form');
    const errorBox = document.getElementById('login-error');
    const loginBtn = document.getElementById('login-btn');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        loginBtn.textContent = 'Authenticating...';
        loginBtn.disabled = true;
        errorBox.style.display = 'none';

        try {
            const response = await fetch('../backend/api/admin/auth_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (response.ok) {
                // Save admin session securely
                localStorage.setItem('foldnest_admin', JSON.stringify(data.admin));
                
                loginBtn.style.background = '#10b981';
                loginBtn.textContent = 'Access Granted!';
                
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 1000);
            } else {
                showError(data.message || 'Authentication failed');
            }
        } catch (error) {
            showError('Server connection error. Try again later.');
        } finally {
            if (loginBtn.textContent === 'Authenticating...') {
                loginBtn.textContent = 'Secure Login';
                loginBtn.disabled = false;
            }
        }
    });

    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.style.display = 'block';
    }
});
