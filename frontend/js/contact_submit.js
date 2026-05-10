// frontend/js/contact_submit.js

async function submitContactQuery() {
    const nameInput = document.getElementById('contact-name');
    const emailInput = document.getElementById('contact-email');
    const phoneInput = document.getElementById('contact-phone');
    const subjectInput = document.getElementById('contact-subject');
    const messageInput = document.getElementById('contact-message');
    const submitBtn = document.getElementById('contact-submit-btn');

    if (!nameInput.value.trim() || !emailInput.value.trim() || !messageInput.value.trim()) {
        showToast('Please fill out Name, Email, and Message.');
        return;
    }

    const data = {
        name: nameInput.value.trim(),
        email: emailInput.value.trim(),
        phone: phoneInput.value.trim(),
        subject: subjectInput.value.trim() || 'General Inquiry',
        message: messageInput.value.trim()
    };

    submitBtn.textContent = 'Sending...';
    submitBtn.disabled = true;

    try {
        const response = await fetch('http://localhost/foldnest/backend/api/submit_query.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showToast('Message Sent Successfully!');
            // Clear inputs
            nameInput.value = '';
            emailInput.value = '';
            phoneInput.value = '';
            subjectInput.value = '';
            messageInput.value = '';
        } else {
            showToast(result.message || 'Failed to send message.');
        }
    } catch (error) {
        console.error('Error submitting query:', error);
        showToast('Network error occurred.');
    } finally {
        submitBtn.textContent = 'Send Message';
        submitBtn.disabled = false;
    }
}
