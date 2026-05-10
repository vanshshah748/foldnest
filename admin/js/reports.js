let chartInstance = null;
let currentReportData = [];
let currentReportTitle = "";
let currentType = "";

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

    loadReport(); // Load default
});

async function loadReport() {
    currentType = document.getElementById('report-type').value;
    
    document.getElementById('report-tbody').innerHTML = '<tr><td colspan="3" style="text-align: center;">Generating report...</td></tr>';

    try {
        const res = await fetch(`../backend/api/admin/reports/get_report.php?type=${currentType}`);
        const json = await res.json();
        
        if (json.success) {
            currentReportData = json.data;
            currentReportTitle = json.title;
            
            document.getElementById('chart-title').textContent = currentReportTitle + ' (Chart)';
            document.getElementById('table-title').textContent = currentReportTitle + ' (Raw Data)';
            
            updateTable();
            updateChart();
        } else {
            showToast('Failed to load report', true);
        }
    } catch (e) {
        showToast('Network error', true);
    }
}

function updateTable() {
    const thead = document.getElementById('report-thead');
    const tbody = document.getElementById('report-tbody');
    
    thead.innerHTML = '';
    tbody.innerHTML = '';

    if (currentReportData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No data available for this report.</td></tr>';
        return;
    }

    // Dynamic Headers based on the first object's keys
    const keys = Object.keys(currentReportData[0]);
    let trHead = '<tr>';
    keys.forEach(k => {
        trHead += `<th>${k.replace(/_/g, ' ').toUpperCase()}</th>`;
    });
    trHead += '</tr>';
    thead.innerHTML = trHead;

    // Rows
    currentReportData.forEach(row => {
        let tr = '<tr>';
        keys.forEach(k => {
            // Formatting currency
            if (k === 'value' && currentType !== 'low_stock' && currentType !== 'cancellations') {
                tr += `<td>₹${parseFloat(row[k]).toLocaleString('en-IN')}</td>`;
            } else if (k === 'revenue' || k === 'lost_revenue' || k === 'price') {
                tr += `<td>₹${parseFloat(row[k]).toLocaleString('en-IN')}</td>`;
            } else {
                tr += `<td>${row[k]}</td>`;
            }
        });
        tr += '</tr>';
        tbody.innerHTML += tr;
    });
}

function updateChart() {
    const ctx = document.getElementById('mainChart').getContext('2d');
    
    if (chartInstance) {
        chartInstance.destroy();
    }

    if (currentReportData.length === 0) return;

    const labels = currentReportData.map(d => d.label);
    const data = currentReportData.map(d => parseFloat(d.value));

    // Determine Chart Type
    let chartType = 'bar';
    if (currentType === 'sales' || currentType === 'cancellations') chartType = 'line';
    if (currentType === 'top_products') chartType = 'doughnut';

    const colors = chartType === 'doughnut' ? 
        ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16'] : 
        'rgba(59, 130, 246, 0.8)';

    chartInstance = new Chart(ctx, {
        type: chartType,
        data: {
            labels: labels,
            datasets: [{
                label: currentReportTitle,
                data: data,
                backgroundColor: colors,
                borderColor: chartType === 'line' ? '#3b82f6' : 'transparent',
                borderWidth: 2,
                tension: 0.4,
                fill: chartType === 'line' ? {
                    target: 'origin',
                    above: 'rgba(59, 130, 246, 0.1)'
                } : false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: chartType === 'doughnut' ? 'right' : 'top',
                    labels: { color: '#94a3b8' }
                }
            },
            scales: chartType === 'doughnut' ? {} : {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#94a3b8' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8' }
                }
            }
        }
    });
}

// Export functions
function exportCSV() {
    if (currentReportData.length === 0) return showToast('No data to export', true);
    
    const keys = Object.keys(currentReportData[0]);
    let csv = keys.join(',') + '\n';
    
    currentReportData.forEach(row => {
        csv += keys.map(k => `"${row[k]}"`).join(',') + '\n';
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `foldnest_${currentType}_report.csv`;
    a.click();
    showToast('CSV Downloaded');
}

function exportPDF() {
    if (currentReportData.length === 0) return showToast('No data to export', true);

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    doc.setFontSize(18);
    doc.text(`FoldNest Admin - ${currentReportTitle}`, 14, 22);
    
    const keys = Object.keys(currentReportData[0]);
    const headers = [keys.map(k => k.replace(/_/g, ' ').toUpperCase())];
    const data = currentReportData.map(row => keys.map(k => row[k]));

    doc.autoTable({
        head: headers,
        body: data,
        startY: 30,
        theme: 'grid',
        headStyles: { fillColor: [59, 130, 246] }
    });

    doc.save(`foldnest_${currentType}_report.pdf`);
    showToast('PDF Downloaded');
}

function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast show ' + (isError ? 'error' : '');
    setTimeout(() => { t.classList.remove('show'); }, 3000);
}
