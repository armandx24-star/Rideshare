function showToast(message, type = 'success') {
    document.querySelectorAll('.rs-toast').forEach(t => t.remove());

    const colors = {
        success: '#00C853', error: '#FF4757', warning: '#F7C948', info: '#2196F3'
    };
    const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };

    const toast = document.createElement('div');
    toast.className = 'rs-toast';
    toast.style.cssText = `
        position:fixed;top:24px;right:24px;z-index:99999;
        background:#1A1A2E;border-left:4px solid ${colors[type]||colors.info};
        color:#f5f5f5;padding:14px 20px;border-radius:10px;
        box-shadow:0 8px 32px rgba(0,0,0,0.4);
        display:flex;align-items:center;gap:12px;
        max-width:360px;font-size:0.9rem;font-family:inherit;
        animation:slideInRight 0.3s ease;
    `;
    toast.innerHTML = `
        <span style="color:${colors[type]||colors.info};font-weight:700;font-size:1rem">${icons[type]||'ℹ'}</span>
        <span>${message}</span>
        <span onclick="this.parentElement.remove()" style="cursor:pointer;margin-left:auto;opacity:0.5;font-size:1.1rem">×</span>
    `;

    if (!document.getElementById('rs-toast-style')) {
        const s = document.createElement('style');
        s.id = 'rs-toast-style';
        s.textContent = '@keyframes slideInRight{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}';
        document.head.appendChild(s);
    }

    document.body.appendChild(toast);
    setTimeout(() => { if (toast.parentElement) toast.remove(); }, 4000);
}

function showLoader(message = 'Searching for driver...') {
    let overlay = document.getElementById('loaderOverlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loaderOverlay';
        overlay.className = 'loader-overlay';
        overlay.innerHTML = `
            <div style="text-align:center">
                <div class="loader-dots" style="justify-content:center;margin-bottom:20px">
                    <div class="loader-dot"></div><div class="loader-dot"></div><div class="loader-dot"></div>
                </div>
                <p id="loaderMsg" style="font-size:1.1rem;font-weight:600;color:#f5f5f5;margin:0"></p>
                <p style="color:#9E9E9E;font-size:0.85rem;margin-top:8px">Please wait...</p>
            </div>`;
        document.body.appendChild(overlay);
    }
    document.getElementById('loaderMsg').textContent = message;
    overlay.classList.add('show');
}
function hideLoader() {
    const overlay = document.getElementById('loaderOverlay');
    if (overlay) overlay.classList.remove('show');
}

function ajaxPost(url, data, callback) {
    const formData = new FormData();
    Object.keys(data).forEach(k => formData.append(k, data[k]));
    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(callback)
        .catch(err => {
            hideLoader();
            showToast('Network error: ' + err.message, 'error');
        });
}

function ajaxGet(url, callback) {
    fetch(url)
        .then(r => r.json())
        .then(callback)
        .catch(err => console.error('AJAX error:', err));
}

let statusInterval = null;

function startRideStatusPolling(pollUrl, onUpdate) {
    if (statusInterval) clearInterval(statusInterval);
    statusInterval = setInterval(() => {
        ajaxGet(pollUrl, onUpdate);
    }, 4000);
}

function stopRideStatusPolling() {
    if (statusInterval) { clearInterval(statusInterval); statusInterval = null; }
}

function toggleDriverStatus(toggleUrl, statusEl, toggleEl) {
    ajaxPost(toggleUrl, {}, function(res) {
        if (res.success) {
            if (res.online_status == 1) {
                toggleEl.classList.add('active');
                statusEl.textContent = 'Online';
                statusEl.className = 'badge badge-online';
                showToast('You are now Online', 'success');
            } else {
                toggleEl.classList.remove('active');
                statusEl.textContent = 'Offline';
                statusEl.className = 'badge badge-offline';
                showToast('You are now Offline', 'info');
            }
        }
    });
}

function initStarRating(containerId, inputId) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    if (!container || !input) return;

    const stars = container.querySelectorAll('.star');
    stars.forEach((star, i) => {
        star.addEventListener('click', () => {
            input.value = i + 1;
            stars.forEach((s, j) => s.classList.toggle('active', j <= i));
        });
        star.addEventListener('mouseover', () => {
            stars.forEach((s, j) => s.style.color = j <= i ? '#F7C948' : '#444');
        });
        star.addEventListener('mouseout', () => {
            const val = parseInt(input.value) || 0;
            stars.forEach((s, j) => s.style.color = j < val ? '#F7C948' : '#444');
        });
    });
}

function confirmAction(message, callback) {
    if (window.confirm(message)) callback();
}

function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}

document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.style.display = sidebar.style.display === 'block' ? 'none' : 'block';
        });
    }
});
