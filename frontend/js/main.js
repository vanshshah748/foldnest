/* =========================
   GLOBAL UTILITIES
   ======================== */

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initBackToTop();
  initFAQ();
  initChatbot();
});

function initChatbot() {
  const isRoot = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/foldnest/') || window.location.pathname.endsWith('foldnest/frontend/');
  const chatScript = document.createElement('script');
  chatScript.src = isRoot ? 'js/chatbot.js' : '../js/chatbot.js';
  document.body.appendChild(chatScript);
}

// Handle legacy single-page active section toggles
function showPage(pageId) {
  const sections = document.querySelectorAll('section');
  sections.forEach(section => {
    section.classList.remove('active');
  });
  const target = document.getElementById(pageId);
  if (target) target.classList.add('active');
}

/* =========================
   DARK MODE THEME
   ======================== */

function initTheme() {
  const savedTheme = localStorage.getItem('foldnest_theme');
  if (savedTheme === 'dark') {
    document.body.classList.add('dark-mode');
  } else {
    document.body.classList.remove('dark-mode');
  }
}

function toggleTheme() {
  document.body.classList.toggle('dark-mode');
  if (document.body.classList.contains('dark-mode')) {
    localStorage.setItem('foldnest_theme', 'dark');
    if (typeof showToast === 'function') showToast('Dark Mode Enabled');
  } else {
    localStorage.setItem('foldnest_theme', 'light');
    if (typeof showToast === 'function') showToast('Light Mode Enabled');
  }
}

/* =========================
   BACK TO TOP BUTTON
   ======================== */

function initBackToTop() {
  const btn = document.createElement('button');
  btn.id = 'backToTop';
  btn.innerHTML = '↑';
  btn.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });
  document.body.appendChild(btn);

  window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
      btn.style.display = 'block';
    } else {
      btn.style.display = 'none';
    }
  });
}

/* =========================
   FAQ ACCORDION LOGIC
   ======================== */

function initFAQ() {
  const questions = document.querySelectorAll('.faq-question');
  questions.forEach(question => {
    question.addEventListener('click', () => {
      const item = question.parentElement;
      item.classList.toggle('active');
    });
  });
}

/* =========================
   TOAST NOTIFICATIONS
   ======================== */

function showToast(message) {
  const toast = document.createElement('div');
  toast.innerText = message;
  toast.style.position = 'fixed';
  toast.style.bottom = '30px';
  toast.style.right = '30px';
  toast.style.background = 'var(--primary-color)';
  toast.style.color = 'white';
  toast.style.padding = '14px 22px';
  toast.style.borderRadius = '10px';
  toast.style.zIndex = '9999';
  toast.style.boxShadow = 'var(--shadow)';
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.remove();
  }, 2000);
}

/* =========================
   MODAL LOGIC
   ======================== */

function openModal(img) {
  const modal = document.getElementById('productModal');
  const modalImg = document.getElementById('modalImage');
  const caption = document.getElementById('caption');
  if (!modal) return;
  modal.style.display = 'block';
  modalImg.src = img.src;
  caption.innerText = img.alt;
}

function closeModal() {
  const modal = document.getElementById('productModal');
  if (modal) modal.style.display = 'none';
}

// Close modal when clicking outside of it
window.onclick = function(event) {
  const modal = document.getElementById('productModal');
  if (event.target === modal) {
    closeModal();
  }
};

/* =========================
   USER AUTHENTICATION
   ======================== */

async function loginUser() {
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const message = document.getElementById('loginMessage');
  
  if (email === '' || password === '') {
    message.style.color = 'red';
    message.innerText = 'Please fill all fields';
    return;
  }
  
  message.style.color = '#e2e8f0';
  message.innerText = 'Logging in...';
  
  try {
    const response = await fetch('http://localhost/foldnest/backend/api/auth_login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (response.ok) {
      message.style.color = 'green';
      message.innerText = 'Login Successful!';
      
      // Save user session
      localStorage.setItem('foldnest_user', JSON.stringify(data.user));
      localStorage.setItem('foldnest_token', data.token);
      
      setTimeout(() => {
        if (typeof showPage === 'function' && document.getElementById('profile')) {
          showPage('profile');
        } else {
          window.location.href = '../index.html'; // Or profile.html based on template
        }
      }, 1000);
    } else {
      message.style.color = 'red';
      message.innerText = data.message || 'Login failed';
    }
  } catch (error) {
    message.style.color = 'red';
    message.innerText = 'Network error. Please try again.';
  }
}

async function registerUser() {
  const fullName = document.getElementById('regName').value;
  const email = document.getElementById('regEmail').value;
  const password = document.getElementById('regPassword').value;
  const message = document.getElementById('registerMessage');
  
  if (fullName === '' || email === '' || password === '') {
    message.style.color = 'red';
    message.innerText = 'Please fill all fields';
    return;
  }
  
  message.style.color = '#e2e8f0';
  message.innerText = 'Creating account...';
  
  try {
    const response = await fetch('http://localhost/foldnest/backend/api/auth_register.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ full_name: fullName, email, password })
    });
    
    const data = await response.json();
    
    if (response.ok) {
      message.style.color = 'green';
      message.innerText = 'Registration Successful! Redirecting...';
      
      setTimeout(() => {
        window.location.href = 'login.html';
      }, 1500);
    } else {
      message.style.color = 'red';
      message.innerText = data.message || 'Registration failed';
    }
  } catch (error) {
    message.style.color = 'red';
    message.innerText = 'Network error. Please try again.';
  }
}
