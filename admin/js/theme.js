document.addEventListener("DOMContentLoaded", () => {
    const toggleBtn = document.getElementById('theme-toggle');
    if (!toggleBtn) return;
    
    // Check saved theme
    const savedTheme = localStorage.getItem('foldnest_theme');
    if (savedTheme === 'light') {
        document.body.classList.add('light-mode');
        toggleBtn.innerHTML = '🌙';
    } else {
        toggleBtn.innerHTML = '☀️';
    }

    toggleBtn.addEventListener('click', () => {
        document.body.classList.toggle('light-mode');
        if (document.body.classList.contains('light-mode')) {
            localStorage.setItem('foldnest_theme', 'light');
            toggleBtn.innerHTML = '🌙';
        } else {
            localStorage.setItem('foldnest_theme', 'dark');
            toggleBtn.innerHTML = '☀️';
        }
    });
});
