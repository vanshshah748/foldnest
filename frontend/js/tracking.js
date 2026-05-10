/* =========================
   TRACKING PAGE JS
   ======================== */

async function searchTracking() {
  const input = document.getElementById('trackingInput').value.trim();
  if (!input) { showToast('Please enter a tracking ID'); return; }

  document.getElementById('trackingResult').style.display = 'none';
  document.getElementById('trackingError').style.display = 'none';

  try {
    const res = await fetch(`${API_URL}/search_tracking.php?tracking_id=${encodeURIComponent(input)}`);
    const data = await res.json();

    if (res.ok) {
      displayTrackingResult(data);
    } else {
      document.getElementById('trackingError').style.display = 'block';
    }
  } catch (err) {
    showToast('Network error. Try again.');
  }
}

function displayTrackingResult(data) {
  document.getElementById('trackingResult').style.display = 'block';

  document.getElementById('trkId').textContent = data.order.tracking_id;
  document.getElementById('trkDate').textContent = new Date(data.order.created_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
  
  const statusClass = 'status-' + data.order.status;
  document.getElementById('trkStatusBadge').innerHTML = `<span class="status-badge ${statusClass}">${data.order.status_label}</span>`;
  
  if (data.order.estimated_delivery) {
    document.getElementById('trkDelivery').textContent = new Date(data.order.estimated_delivery).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
  } else {
    document.getElementById('trkDelivery').textContent = 'Calculating...';
  }

  // Render timeline
  const timelineEl = document.getElementById('trackingTimeline');
  timelineEl.innerHTML = '';

  if (data.is_cancelled) {
    timelineEl.innerHTML = '<div style="text-align:center;padding:20px;"><span class="status-badge status-cancelled" style="font-size:1rem;padding:8px 20px;">Order Cancelled</span></div>';
    return;
  }

  const icons = { pending: '⏳', confirmed: '✓', packed: '📦', shipped: '🚚', out_for_delivery: '🏍️', delivered: '✅' };

  data.timeline.forEach((step, idx) => {
    const stepEl = document.createElement('div');
    let cls = 'timeline-step';
    if (step.completed && !step.current) cls += ' completed';
    if (step.current) cls += ' current completed';
    stepEl.className = cls;

    stepEl.innerHTML = `
      <div class="timeline-dot">${step.completed ? '✓' : ''}</div>
      <div class="timeline-label">${icons[step.status] || ''} ${step.label}</div>
      <div class="timeline-sublabel">${step.current ? 'Current Status' : (step.completed ? 'Completed' : 'Upcoming')}</div>
    `;
    timelineEl.appendChild(stepEl);
  });
}

// Allow Enter key to search
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('trackingInput');
  if (input) {
    input.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') searchTracking();
    });
    // Check URL params for tracking_id
    const params = new URLSearchParams(window.location.search);
    const tid = params.get('id');
    if (tid) {
      input.value = tid;
      searchTracking();
    }
  }
});
