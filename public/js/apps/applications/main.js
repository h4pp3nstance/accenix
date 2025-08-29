// axios is loaded globally via CDN in the Blade template

async function fetchApps() {
    try {
        const resp = await axios.get('/admin/api/applications?limit=10&offset=0');
        const data = resp.data;
        renderList(data);
    } catch (err) {
        console.error('fetchApps error', err);
        const el = document.getElementById('apps-list');
        const status = err.response?.status;
        const upstream = err.response?.data?.message || err.response?.data || null;

        let message = 'Failed to load applications.';
        if (status === 401) {
            message = 'Not authenticated with WSO2. Please login again.';
        } else if (status === 403) {
            message = 'Access denied: your WSO2 token lacks permission to list applications.';
        } else if (status === 502) {
            message = 'Upstream WSO2 error. See server logs for details.';
        }

        if (upstream) message += ' (' + String(upstream) + ')';

        if (el) el.innerText = message;
    }
}

function renderList(payload) {
    const container = document.getElementById('apps-list');
    if (!container) return;
    container.innerHTML = '';

    // The WSO2 response may contain `applications`, `list`, or a direct array. Try common shapes.
    const items = payload?.applications || payload?.list || payload?.data || payload || [];

    if (!items || items.length === 0) {
        container.innerHTML = '<div class="text-gray-500">No applications found.</div>';
        return;
    }

    items.forEach(app => {
        const el = document.createElement('div');
        el.className = 'p-4 border rounded flex items-center justify-between';
        el.innerHTML = `
            <div>
                <div class="font-medium">${escapeHtml(app.name || app.displayName || app.clientName || 'Unnamed')}</div>
                <div class="text-sm text-gray-500">${escapeHtml(app.description || app.type || '')}</div>
            </div>
            <div class="text-sm text-gray-700">${escapeHtml(app.id || app.clientId || '')}</div>
        `;
        container.appendChild(el);
    });
}

function escapeHtml(s) {
    if (!s) return '';
    return String(s).replace(/[&<>"']/g, function (c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c];
    });
}

fetchApps();

// Import form handling
document.addEventListener('DOMContentLoaded', function () {
    const importForm = document.getElementById('import-form');
    const importFile = document.getElementById('import-file');
    const importStatus = document.getElementById('import-status');

    if (!importForm) return;

    importForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        importStatus.innerText = '';

        if (!importFile.files || importFile.files.length === 0) {
            importStatus.innerText = 'Select a file first.';
            return;
        }

        const file = importFile.files[0];
        const formData = new FormData();
        formData.append('file', file, file.name);

        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const resp = await axios.post('/admin/api/applications/import', formData, {
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'multipart/form-data'
                }
            });

            importStatus.innerText = 'Import successful.';
            // Refresh list
            fetchApps();
        } catch (err) {
            console.error(err);
            importStatus.innerText = 'Import failed: ' + (err.response?.data?.message || err.message);
        }
    });
});
