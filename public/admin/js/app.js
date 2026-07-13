const API_BASE = window.location.origin;
let token = localStorage.getItem('token') || '';
let licenses = [];
let currentActionId = null;
let currentFilter = '';

function login() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const errorEl = document.getElementById('loginError');

    if (!username || !password) {
        errorEl.textContent = 'Username dan password wajib diisi';
        return;
    }

    fetch(API_BASE + '/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            token = res.token;
            localStorage.setItem('token', token);
            showDashboard();
            loadData();
        } else {
            errorEl.textContent = res.message || 'Login gagal';
        }
    })
    .catch(() => {
        errorEl.textContent = 'Gagal terhubung ke server';
    });
}

function logout() {
    token = '';
    localStorage.removeItem('token');
    document.getElementById('loginPage').style.display = '';
    document.getElementById('dashboardPage').style.display = 'none';
}

function showDashboard() {
    document.getElementById('loginPage').style.display = 'none';
    document.getElementById('dashboardPage').style.display = '';
}

function loadData() {
    loadStats();
    loadLicenses();
}

function loadStats() {
    fetch(API_BASE + '/api/license/stats')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const d = res.data;
                document.getElementById('statTotal').textContent = d.total || 0;
                document.getElementById('statPending').textContent = d.pending || 0;
                document.getElementById('statActive').textContent = d.active || 0;
                document.getElementById('statTrialExpired').textContent = d.trial_expired || 0;
                document.getElementById('statBlocked').textContent = d.blocked || 0;
            }
        });
}

function loadLicenses() {
    fetch(API_BASE + '/api/license/list')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                licenses = res.data;
                renderTable();
            }
        });
}

function renderTable() {
    const tbody = document.getElementById('licenseTableBody');
    const search = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value || currentFilter;

    let filtered = licenses.filter(l => {
        const matchSearch = !search ||
            (l.business_name && l.business_name.toLowerCase().includes(search)) ||
            (l.owner_name && l.owner_name.toLowerCase().includes(search)) ||
            (l.phone_number && l.phone_number.toLowerCase().includes(search)) ||
            (l.device_id && l.device_id.toLowerCase().includes(search)) ||
            (l.license_key && l.license_key.toLowerCase().includes(search)) ||
            (l.city && l.city.toLowerCase().includes(search));
        let matchStatus = true;
        if (statusFilter === 'TRIAL') {
            matchStatus = l.license_type === 'TRIAL';
        } else if (statusFilter === 'LIFETIME') {
            matchStatus = l.license_type === 'LIFETIME';
        } else if (statusFilter) {
            matchStatus = l.license_status === statusFilter;
        }
        return matchSearch && matchStatus;
    });

    if (statusFilter) {
        document.getElementById('statusFilter').value = statusFilter;
    }

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="13" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(l => {
        const key = l.license_key || '-';
        const date = l.activation_date ? new Date(l.activation_date + 'Z').toLocaleDateString('id-ID') : (l.created_at ? new Date(l.created_at + 'Z').toLocaleDateString('id-ID') : '-');
        const statusClass = 'status-' + l.license_status;
        const licenseType = l.license_type || '-';
        const price = l.purchase_price ? 'Rp' + formatNum(l.purchase_price) : '-';
        const version = l.major_version ? 'v' + l.major_version + '.' + (l.minor_version || '0') : '-';
        const city = l.city || '-';
        const deviceId = l.device_id || '-';
        const cloneIcon = l.clone_detected == 1 ? ' <span title="Clone terdeteksi" style="color:#F44336">&#9888;</span>' : '';

        let actions = '';
        if (l.license_status === 'PENDING') {
            actions = `
                <button class="action-btn approve" onclick="openApproveWithType(${l.id})">Setujui</button>
                <button class="action-btn reject" onclick="openReject(${l.id}, '${escapeHtml(l.owner_name)}')">Tolak</button>
            `;
        }
        if (l.license_status === 'ACTIVE') {
            actions = `
                <button class="action-btn transfer" onclick="openTransfer(${l.id}, '${escapeHtml(l.owner_name)}')">Transfer</button>
            `;
        }
        actions += `<button class="action-btn detail" onclick="openDetail(${l.id})">Detail</button>`;
        if (l.license_status === 'ACTIVE') {
            actions += `<button class="action-btn history" onclick="openTransferHistory(${l.id})">Riwayat</button>`;
            actions += `<button class="action-btn" style="background:#9C27B0" onclick="openCertificateForLicense(${l.id})">Sertifikat</button>`;
        }

        return `<tr>
            <td>${l.id}</td>
            <td>${escapeHtml(l.business_name)}</td>
            <td>${escapeHtml(l.owner_name)}</td>
            <td>${escapeHtml(l.phone_number || '-')}</td>
            <td>${escapeHtml(city)}</td>
            <td style="font-family:monospace;font-size:12px">${deviceId}${cloneIcon}</td>
            <td style="font-family:monospace;font-size:11px">${key}</td>
            <td><span class="status-badge ${statusClass}">${l.license_status}</span></td>
            <td>${licenseType}</td>
            <td style="font-size:12px">${date}</td>
            <td style="font-size:12px">${price}</td>
            <td style="font-size:12px">${version}</td>
            <td class="action-cell">${actions}</td>
        </tr>`;
    }).join('');
}

function filterStatus(status) {
    currentFilter = status;
    document.getElementById('statusFilter').value = status;
    renderTable();
}

function filterLicenses() {
    renderTable();
}

function openApprove(id, name) {
    currentActionId = id;
    document.getElementById('approveName').textContent = name;
    document.getElementById('approveModal').style.display = '';
}

function confirmApprove() {
    fetch(API_BASE + '/api/license/approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentActionId, approved_by: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        closeModal('approveModal');
        if (res.success) {
            showToast('Lisensi berhasil diaktifkan!');
            loadData();
        } else {
            alert(res.message || 'Gagal approve');
        }
    });
}

function openApproveWithType(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;
    currentActionId = id;
    document.getElementById('approveTypeName').textContent = escapeHtml(l.business_name);
    document.getElementById('approveTypeOwner').textContent = escapeHtml(l.owner_name);
    document.getElementById('approveTypePhone').textContent = escapeHtml(l.phone_number || '-');
    document.querySelector('input[name="licenseType"][value="TRIAL"]').checked = true;
    document.getElementById('approveTrialDays').value = '30';
    document.getElementById('approvePurchasePrice').value = '';
    toggleApproveTypeFields();
    document.getElementById('approveWithTypeModal').style.display = '';
}

function toggleApproveTypeFields() {
    const isTrial = document.querySelector('input[name="licenseType"]:checked').value === 'TRIAL';
    document.getElementById('approveTrialFields').style.display = isTrial ? '' : 'none';
    document.getElementById('approveLifetimeFields').style.display = isTrial ? 'none' : '';
}

function confirmApproveWithType() {
    const licenseType = document.querySelector('input[name="licenseType"]:checked').value;
    const body = {
        id: currentActionId,
        license_type: licenseType,
        approved_by: 'Admin'
    };
    if (licenseType === 'TRIAL') {
        body.trial_days = parseInt(document.getElementById('approveTrialDays').value) || 30;
    } else {
        body.purchase_price = parseInt(document.getElementById('approvePurchasePrice').value) || 0;
    }

    fetch(API_BASE + '/api/license/approve-with-type', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    })
    .then(r => r.json())
    .then(res => {
        closeModal('approveWithTypeModal');
        if (res.success) {
            showToast('Lisensi berhasil diaktifkan sebagai ' + licenseType + '!');
            loadData();
        } else {
            alert(res.message || 'Gagal approve');
        }
    });
}

function openReject(id, name) {
    currentActionId = id;
    document.getElementById('rejectName').textContent = name;
    document.getElementById('rejectRemarks').value = '';
    document.getElementById('rejectModal').style.display = '';
}

function confirmReject() {
    const remarks = document.getElementById('rejectRemarks').value;
    fetch(API_BASE + '/api/license/reject', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentActionId, remarks: remarks || 'Ditolak oleh Admin', approved_by: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        closeModal('rejectModal');
        if (res.success) {
            showToast('Lisensi ditolak');
            loadData();
        } else {
            alert(res.message || 'Gagal reject');
        }
    });
}

function openDetail(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;

    const content = document.getElementById('detailContent');
    const cloneStatus = l.clone_detected == 1 ? '<span style="color:#F44336;font-weight:600">&#9888; CLONE DETECTED</span>' : 'Tidak';
    const fp = l.device_fingerprint ? `<span style="font-family:monospace;font-size:11px">${l.device_fingerprint}</span>` : '-';
    const lastOnline = l.last_online ? new Date(l.last_online + 'Z').toLocaleString('id-ID') : '-';
    const licenseType = l.license_type || '-';
    const price = l.purchase_price ? 'Rp' + formatNum(l.purchase_price) : '-';
    const version = l.major_version ? 'v' + l.major_version + '.' + (l.minor_version || '0') : '-';
    const activationDate = l.activation_date ? new Date(l.activation_date + 'Z').toLocaleString('id-ID') : '-';
    const blockedDate = l.blocked_date ? new Date(l.blocked_date + 'Z').toLocaleString('id-ID') : '-';
    const transferCount = l.transfer_count != null ? l.transfer_count : '0';
    const maxTransfer = l.max_transfer != null ? (l.max_transfer >= 999 ? 'Unlimited' : l.max_transfer) : '3';
    const notes = l.notes || '-';

    content.innerHTML = `
        <div class="detail-row"><div class="detail-label">ID</div><div class="detail-value">${l.id}</div></div>
        <div class="detail-row"><div class="detail-label">Nama Rental</div><div class="detail-value">${escapeHtml(l.business_name)}</div></div>
        <div class="detail-row"><div class="detail-label">Pemilik</div><div class="detail-value">${escapeHtml(l.owner_name)}</div></div>
        <div class="detail-row"><div class="detail-label">No. HP</div><div class="detail-value">${escapeHtml(l.phone_number || '-')}</div></div>
        <div class="detail-row"><div class="detail-label">Email</div><div class="detail-value">${escapeHtml(l.email || '-')}</div></div>
        <div class="detail-row"><div class="detail-label">Kota</div><div class="detail-value">${escapeHtml(l.city)}</div></div>
        <div class="detail-row"><div class="detail-label">License Key</div><div class="detail-value" style="font-family:monospace">${l.license_key || '-'}</div></div>
        <div class="detail-row"><div class="detail-label">Status</div><div class="detail-value"><span class="status-badge status-${l.license_status}">${l.license_status}</span></div></div>
        <div class="detail-row"><div class="detail-label">Jenis Lisensi</div><div class="detail-value">${licenseType}</div></div>
        <div class="detail-row"><div class="detail-label">Harga Pembelian</div><div class="detail-value">${price}</div></div>
        <div class="detail-row"><div class="detail-label">Versi</div><div class="detail-value">${version}</div></div>
        <div class="detail-row"><div class="detail-label">Device ID</div><div class="detail-value" style="font-family:monospace">${l.device_id || '-'}</div></div>
        <div class="detail-row"><div class="detail-label">Fingerprint</div><div class="detail-value" style="word-break:break-all">${fp}</div></div>
        <div class="detail-row"><div class="detail-label">Device Name</div><div class="detail-value">${escapeHtml(l.device_name || '-')}</div></div>
        <div class="detail-row"><div class="detail-label">Platform</div><div class="detail-value">${escapeHtml(l.platform || '-')}</div></div>
        <div class="detail-row"><div class="detail-label">App Version</div><div class="detail-value">${escapeHtml(l.app_version || '-')}</div></div>
        <div class="detail-row"><div class="detail-label">Last Online</div><div class="detail-value">${lastOnline}</div></div>
        <div class="detail-row"><div class="detail-label">Clone Detect</div><div class="detail-value">${cloneStatus}</div></div>
        <div class="detail-row"><div class="detail-label">Tanggal Aktivasi</div><div class="detail-value">${activationDate}</div></div>
        <div class="detail-row"><div class="detail-label">Tanggal Pembelian</div><div class="detail-value">${l.purchase_date ? new Date(l.purchase_date + 'Z').toLocaleString('id-ID') : '-'}</div></div>
        <div class="detail-row"><div class="detail-label">Tanggal Diblokir</div><div class="detail-value">${blockedDate}</div></div>
        <div class="detail-row"><div class="detail-label">Alasan Blokir</div><div class="detail-value">${l.blocked_reason || '-'}</div></div>
        <div class="detail-row"><div class="detail-label">Transfer</div><div class="detail-value">${transferCount} / ${maxTransfer}</div></div>
        <div class="detail-row"><div class="detail-label">Dibuat</div><div class="detail-value">${l.created_at ? new Date(l.created_at + 'Z').toLocaleString('id-ID') : '-'}</div></div>
        <div class="detail-row"><div class="detail-label">Approved By</div><div class="detail-value">${l.approved_by || '-'}</div></div>
        <div class="detail-row"><div class="detail-label">Catatan</div><div class="detail-value">${escapeHtml(notes)}</div></div>
    `;
    document.getElementById('detailModal').style.display = '';
}

function openTransfer(id, name) {
    currentActionId = id;
    document.getElementById('transferName').textContent = name;
    document.getElementById('transferReason').value = '';
    document.getElementById('transferModal').style.display = '';
}

function confirmTransfer() {
    const reason = document.getElementById('transferReason').value || 'Transfer lisensi ke perangkat baru';
    fetch(API_BASE + '/api/license/transfer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentActionId, admin_name: 'Admin', reason: reason })
    })
    .then(r => r.json())
    .then(res => {
        closeModal('transferModal');
        if (res.success) {
            showToast('Lisensi berhasil ditransfer!');
            loadData();
        } else {
            alert(res.message || 'Gagal transfer');
        }
    });
}

function openTransferHistory(id) {
    fetch(API_BASE + '/api/license/transfer-history/' + id)
        .then(r => r.json())
        .then(res => {
            const content = document.getElementById('historyContent');
            if (!res.success || !res.data || res.data.length === 0) {
                content.innerHTML = '<p class="text-muted">Belum ada riwayat transfer.</p>';
            } else {
                let html = '';
                res.data.forEach(h => {
                    const date = h.created_at ? new Date(h.created_at + 'Z').toLocaleString('id-ID') : '-';
                    html += `<div class="detail-row">
                        <div class="detail-label">${date}</div>
                        <div class="detail-value" style="font-size:12px">
                            <div>Admin: ${escapeHtml(h.admin_name)}</div>
                            <div style="font-family:monospace;font-size:11px;color:#6C6C80">Lama: ${h.old_fingerprint ? h.old_fingerprint.substring(0, 16) + '...' : '-'}</div>
                            <div style="font-family:monospace;font-size:11px;color:#6C6C80">Baru: ${h.new_fingerprint ? h.new_fingerprint.substring(0, 16) + '...' : '(dihapus)'}</div>
                            <div style="color:#B0B0C3;font-size:11px">Alasan: ${escapeHtml(h.reason || '-')}</div>
                        </div>
                    </div>`;
                });
                content.innerHTML = html;
            }
            document.getElementById('historyModal').style.display = '';
        });
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function showToast(msg) {
    const el = document.createElement('div');
    el.textContent = msg;
    el.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#4CAF50;color:#fff;padding:12px 20px;border-radius:8px;z-index:2000;font-size:14px';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
}

function escapeHtml(text) {
    if (!text) return '-';
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

function formatNum(n) {
    if (!n) return '0';
    return Number(n).toLocaleString('id-ID');
}

// ==================== TAB NAVIGATION ====================
function showPage(page) {
    document.getElementById('pageLicenses').style.display = '';
    document.getElementById('pagePlans').style.display = page === 'plans' ? '' : 'none';
    document.getElementById('pagePackages').style.display = page === 'packages' ? '' : 'none';
    document.getElementById('pageControl').style.display = page === 'control' ? '' : 'none';
    document.getElementById('pageManagement').style.display = page === 'management' ? '' : 'none';
    document.getElementById('pageProductKeys').style.display = page === 'productkeys' ? '' : 'none';
    document.getElementById('pageCertificates').style.display = page === 'certificates' ? '' : 'none';
    document.getElementById('tabLicenses').className = 'nav-btn' + (page === 'licenses' ? ' active' : '');
    document.getElementById('tabPlans').className = 'nav-btn' + (page === 'plans' ? ' active' : '');
    document.getElementById('tabPackages').className = 'nav-btn' + (page === 'packages' ? ' active' : '');
    document.getElementById('tabControl').className = 'nav-btn' + (page === 'control' ? ' active' : '');
    document.getElementById('tabManagement').className = 'nav-btn' + (page === 'management' ? ' active' : '');
    document.getElementById('tabProductKeys').className = 'nav-btn' + (page === 'productkeys' ? ' active' : '');
    document.getElementById('tabCertificates').className = 'nav-btn' + (page === 'certificates' ? ' active' : '');
    if (page === 'plans') loadPlans();
    if (page === 'packages') loadPackages();
    if (page === 'control') loadControlCenter();
    if (page === 'management') loadManagementPage();
    if (page === 'productkeys') loadProductKeys();
    if (page === 'certificates') loadCertificates();
}

// ==================== LICENSE PLANS ====================
function loadPlans() {
    fetch(API_BASE + '/api/plans')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const tbody = document.getElementById('plansTableBody');
            if (!res.data || res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
                return;
            }
            tbody.innerHTML = res.data.map(p => {
                const active = p.is_active == 1;
                const transfer = p.allow_transfer == 1 ? `${p.max_transfer}x` : 'Tidak';
                const duration = p.license_duration_days >= 99999 ? 'Seumur Hidup' : p.license_duration_days + ' hari';
                const offline = p.offline_days + ' hari';
                const maxTv = p.max_tv >= 999 ? 'Unlimited' : p.max_tv;
                return `<tr>
                    <td>${p.id}</td>
                    <td><strong>${escapeHtml(p.plan_name)}</strong></td>
                    <td>${duration}</td>
                    <td>${offline}</td>
                    <td>${maxTv}</td>
                    <td>${transfer}</td>
                    <td><span class="status-badge ${active ? 'status-ACTIVE' : 'status-BLOCKED'}">${active ? 'AKTIF' : 'NONAKTIF'}</span></td>
                    <td>
                        <button class="action-btn detail" onclick="openEditPlan(${p.id})">Edit</button>
                        <button class="action-btn reject" onclick="deletePlan(${p.id}, '${escapeHtml(p.plan_name)}')">Hapus</button>
                    </td>
                </tr>`;
            }).join('');
        });
}

function openAddPlan() {
    document.getElementById('planModalTitle').textContent = 'Tambah Paket';
    document.getElementById('planEditId').value = '';
    document.getElementById('planName').value = '';
    document.getElementById('planDesc').value = '';
    document.getElementById('planOfflineDays').value = '30';
    document.getElementById('planDurationDays').value = '365';
    document.getElementById('planMaxTv').value = '4';
    document.getElementById('planMaxTransfer').value = '1';
    document.getElementById('planAllowTransfer').checked = true;
    document.getElementById('planActive').checked = true;
    document.getElementById('planSaveBtn').textContent = 'Simpan';
    document.getElementById('planModal').style.display = '';
}

function openEditPlan(id) {
    fetch(API_BASE + '/api/plans')
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data) return;
            const p = res.data.find(x => x.id === id);
            if (!p) return;
            document.getElementById('planModalTitle').textContent = 'Edit Paket';
            document.getElementById('planEditId').value = p.id;
            document.getElementById('planName').value = p.plan_name;
            document.getElementById('planDesc').value = p.description || '';
            document.getElementById('planOfflineDays').value = p.offline_days;
            document.getElementById('planDurationDays').value = p.license_duration_days;
            document.getElementById('planMaxTv').value = p.max_tv;
            document.getElementById('planMaxTransfer').value = p.max_transfer;
            document.getElementById('planAllowTransfer').checked = p.allow_transfer == 1;
            document.getElementById('planActive').checked = p.is_active == 1;
            document.getElementById('planSaveBtn').textContent = 'Simpan';
            document.getElementById('planModal').style.display = '';
        });
}

function savePlan() {
    const id = document.getElementById('planEditId').value;
    const data = {
        plan_name: document.getElementById('planName').value.trim(),
        description: document.getElementById('planDesc').value.trim(),
        offline_days: parseInt(document.getElementById('planOfflineDays').value) || 30,
        license_duration_days: parseInt(document.getElementById('planDurationDays').value) || 365,
        max_tv: parseInt(document.getElementById('planMaxTv').value) || 4,
        max_transfer: parseInt(document.getElementById('planMaxTransfer').value) || 0,
        allow_transfer: document.getElementById('planAllowTransfer').checked ? 1 : 0,
        is_active: document.getElementById('planActive').checked ? 1 : 0,
    };

    if (!data.plan_name) { alert('Nama paket wajib diisi'); return; }

    const url = id ? (API_BASE + '/api/plans/' + id) : (API_BASE + '/api/plans');
    const method = id ? 'PUT' : 'POST';

    fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(r => r.json())
        .then(res => {
            closeModal('planModal');
            if (res.success) {
                showToast(id ? 'Paket berhasil diperbarui!' : 'Paket berhasil dibuat!');
                loadPlans();
            } else {
                alert(res.message || 'Gagal menyimpan paket');
            }
        });
}

function deletePlan(id, name) {
    if (!confirm('Hapus paket "' + name + '"?')) return;
    fetch(API_BASE + '/api/plans/' + id, { method: 'DELETE' })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Paket berhasil dihapus!');
                loadPlans();
            } else {
                alert(res.message || 'Gagal menghapus paket');
            }
        });
}

// ==================== LICENSE CONTROL CENTER ====================
let controlLicenses = [];

function loadControlCenter() {
    fetch(API_BASE + '/api/license/connected')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const tbody = document.getElementById('controlTableBody');
            const data = res.data || [];
            document.getElementById('onlineCount').textContent = 'Online: ' + (res.total_online || 0);
            document.getElementById('offlineCount').textContent = 'Offline: ' + (res.total_offline || 0);
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada lisensi terdaftar</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(l => {
                const statusClass = 'status-' + l.license_status;
                const lastOnline = l.last_online ? new Date(l.last_online + 'Z').toLocaleString('id-ID') : '-';
                const isOnline = l.last_online && (Date.now() - new Date(l.last_online + 'Z').getTime()) < 300000;
                const onlineDot = isOnline ? '<span style="color:#4CAF50;margin-right:6px">&#9679;</span>' : '<span style="color:#6C6C80;margin-right:6px">&#9679;</span>';
                return `<tr>
                    <td>${l.id}</td>
                    <td>${escapeHtml(l.business_name)}</td>
                    <td>${escapeHtml(l.owner_name)}</td>
                    <td style="font-family:monospace;font-size:12px">${l.device_id || '-'}</td>
                    <td><span class="status-badge ${statusClass}">${l.license_status}</span></td>
                    <td>${l.platform || '-'}</td>
                    <td style="font-size:12px">${onlineDot}${lastOnline}</td>
                    <td>
                        <button class="action-btn detail" onclick="openRemoteAction(${l.id})">Remote</button>
                        <button class="action-btn history" onclick="openCommandHistory(${l.id})">Riwayat</button>
                    </td>
                </tr>`;
            }).join('');
        });
}

function openRemoteAction(id) {
    fetch(API_BASE + '/api/license/list')
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const license = res.data.find(x => x.id === id);
            if (!license) return;
            currentActionId = id;
            document.getElementById('remoteInfo').innerHTML =
                '<strong>' + escapeHtml(license.business_name) + '</strong> - ' + escapeHtml(license.owner_name) +
                ' | Status: <span class="status-badge status-' + license.license_status + '">' + license.license_status + '</span>';
            const actionsDiv = document.getElementById('remoteActions');
            const status = license.license_status;
            let btns = '';
            if (status === 'PENDING') {
                btns += '<button class="btn btn-success btn-sm" onclick="confirmAction(\'activate\')">Aktifkan</button>';
                btns += '<button class="btn btn-danger btn-sm" onclick="confirmAction(\'block\')">Blokir</button>';
            } else if (status === 'ACTIVE' || status === 'SUSPENDED') {
                btns += '<button class="btn btn-danger btn-sm" onclick="confirmAction(\'block\')">Blokir</button>';
                btns += '<button class="btn btn-warning btn-sm" onclick="confirmAction(\'suspend\')">Suspend</button>';
                btns += '<button class="btn btn-success btn-sm" onclick="confirmAction(\'unsuspend\')">Unsuspend</button>';
                btns += '<button class="btn btn-primary btn-sm" onclick="showExtend()">Perpanjang</button>';
                btns += '<button class="btn btn-primary btn-sm" onclick="showChangePlan()">Ganti Paket</button>';
                btns += '<button class="btn btn-primary btn-sm" onclick="showSendMessage()">Kirim Pesan</button>';
                btns += '<button class="btn btn-outline btn-sm" onclick="confirmAction(\'force-sync\')">Sinkronkan</button>';
                btns += '<button class="btn btn-outline btn-sm" onclick="confirmAction(\'restart-app\')">Restart App</button>';
                btns += '<button class="btn btn-outline btn-sm" onclick="confirmAction(\'logout\')">Logout</button>';
            } else if (status === 'BLOCKED') {
                btns += '<button class="btn btn-success btn-sm" onclick="confirmAction(\'activate\')">Aktifkan</button>';
            } else {
                btns += '<button class="btn btn-success btn-sm" onclick="confirmAction(\'activate\')">Aktifkan</button>';
                btns += '<button class="btn btn-danger btn-sm" onclick="confirmAction(\'block\')">Blokir</button>';
            }
            actionsDiv.innerHTML = btns;
            document.getElementById('remoteMessage').style.display = 'none';
            document.getElementById('remoteExtend').style.display = 'none';
            document.getElementById('remotePlan').style.display = 'none';
            document.getElementById('remoteModal').style.display = '';
        });
}

function confirmAction(action) {
    let url = '', body = { license_id: currentActionId, admin_name: 'Admin' };
    switch (action) {
        case 'block': url = '/api/license/control/block'; body.reason = prompt('Alasan blokir (opsional):') || 'Diblokir oleh Admin'; break;
        case 'suspend': url = '/api/license/control/suspend'; body.reason = prompt('Alasan suspend (opsional):') || 'Ditangguhkan oleh Admin'; break;
        case 'unsuspend': url = '/api/license/control/unsuspend'; break;
        case 'activate': url = '/api/license/control/activate'; break;
        case 'force-sync': url = '/api/license/control/force-sync'; break;
        case 'restart-app': url = '/api/license/control/restart-app'; break;
        case 'logout': url = '/api/license/control/logout'; break;
    }
    if (!url) return;
    if (!confirm('Kirim perintah ' + action + ' untuk lisensi ini?')) return;
    fetch(API_BASE + url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Perintah ' + action + ' berhasil dikirim!');
                closeModal('remoteModal');
                loadControlCenter();
                loadData();
            } else {
                alert(res.message || 'Gagal');
            }
        });
}

function showSendMessage() {
    document.getElementById('remoteMessage').style.display = '';
    document.getElementById('remoteExtend').style.display = 'none';
    document.getElementById('remotePlan').style.display = 'none';
}

function confirmSendMessage() {
    const msg = document.getElementById('remoteMessageText').value.trim();
    if (!msg) { alert('Pesan wajib diisi'); return; }
    fetch(API_BASE + '/api/license/control/send-message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: currentActionId, admin_name: 'Admin', message: msg })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Pesan berhasil dikirim!');
            closeModal('remoteModal');
        } else {
            alert(res.message || 'Gagal');
        }
    });
}

function showExtend() {
    document.getElementById('remoteExtend').style.display = '';
    document.getElementById('remoteMessage').style.display = 'none';
    document.getElementById('remotePlan').style.display = 'none';
}

function confirmExtend() {
    const days = parseInt(document.getElementById('remoteExtendDays').value);
    fetch(API_BASE + '/api/license/control/extend', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: currentActionId, admin_name: 'Admin', days: days })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Perintah perpanjangan dikirim!');
            closeModal('remoteModal');
        } else {
            alert(res.message || 'Gagal');
        }
    });
}

function showChangePlan() {
    document.getElementById('remotePlan').style.display = '';
    document.getElementById('remoteMessage').style.display = 'none';
    document.getElementById('remoteExtend').style.display = 'none';
    fetch(API_BASE + '/api/plans')
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data) return;
            const select = document.getElementById('remotePlanSelect');
            select.innerHTML = res.data.map(p =>
                '<option value="' + p.id + '">' + escapeHtml(p.plan_name) + '</option>'
            ).join('');
        });
}

function confirmChangePlan() {
    const planId = parseInt(document.getElementById('remotePlanSelect').value);
    fetch(API_BASE + '/api/license/control/change-plan', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: currentActionId, admin_name: 'Admin', plan_id: planId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Paket berhasil diubah!');
            closeModal('remoteModal');
        } else {
            alert(res.message || 'Gagal');
        }
    });
}

function openCommandHistory(id) {
    fetch(API_BASE + '/api/license/command-history/' + id)
        .then(r => r.json())
        .then(res => {
            const content = document.getElementById('commandHistoryContent');
            if (!res.success || !res.data || res.data.length === 0) {
                content.innerHTML = '<p class="text-muted">Belum ada perintah.</p>';
            } else {
                let html = '';
                res.data.forEach(c => {
                    const date = c.created_at ? new Date(c.created_at + 'Z').toLocaleString('id-ID') : '-';
                    const statusClass = c.status === 'EXECUTED' ? 'status-ACTIVE' : (c.status === 'FAILED' ? 'status-BLOCKED' : 'status-PENDING');
                    html += `<div class="detail-row">
                        <div class="detail-label">${date}</div>
                        <div class="detail-value" style="font-size:12px">
                            <div><strong>${c.command}</strong> <span class="status-badge ${statusClass}">${c.status}</span></div>
                            <div style="color:#6C6C80;font-size:11px">Oleh: ${escapeHtml(c.created_by)} | Hasil: ${escapeHtml(c.result || '-')}</div>
                        </div>
                    </div>`;
                });
                content.innerHTML = html;
            }
            document.getElementById('commandHistoryModal').style.display = '';
        });
}

// ==================== MANAGEMENT PAGE (TAHAP 6) ====================

function loadManagementPage() {
    fetch(API_BASE + '/api/license/list')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                licenses = res.data;
                renderManagementTable();
            }
        });
    fetch(API_BASE + '/api/license/stats')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const d = res.data;
                document.getElementById('mgmtStatTotal').textContent = d.total || 0;
                document.getElementById('mgmtStatPending').textContent = d.pending || 0;
                document.getElementById('mgmtStatActive').textContent = d.active || 0;
                document.getElementById('mgmtStatTrialExpired').textContent = d.trial_expired || 0;
                document.getElementById('mgmtStatBlocked').textContent = d.blocked || 0;
            }
        });
}

function renderManagementTable() {
    const tbody = document.getElementById('managementTableBody');
    const search = document.getElementById('managementSearch').value.toLowerCase();
    const typeFilter = document.getElementById('managementTypeFilter').value;

    let filtered = licenses.filter(l => {
        const matchSearch = !search ||
            (l.business_name && l.business_name.toLowerCase().includes(search)) ||
            (l.owner_name && l.owner_name.toLowerCase().includes(search)) ||
            (l.phone_number && l.phone_number.toLowerCase().includes(search)) ||
            (l.device_id && l.device_id.toLowerCase().includes(search)) ||
            (l.license_key && l.license_key.toLowerCase().includes(search)) ||
            (l.city && l.city.toLowerCase().includes(search));
        let matchType = true;
        if (typeFilter === 'TRIAL') {
            matchType = l.license_type === 'TRIAL';
        } else if (typeFilter === 'LIFETIME') {
            matchType = l.license_type === 'LIFETIME';
        } else if (typeFilter === 'BLOCKED') {
            matchType = l.license_status === 'BLOCKED';
        } else if (typeFilter === 'TRIAL_EXPIRED') {
            matchType = l.license_status === 'TRIAL_EXPIRED' || (l.license_type === 'TRIAL' && l.license_status === 'ACTIVE' && l.expired_at && new Date(l.expired_at + 'Z') < new Date());
        }
        return matchSearch && matchType;
    });

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="13" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(l => {
        const key = l.license_key || '-';
        const date = l.activation_date ? new Date(l.activation_date + 'Z').toLocaleDateString('id-ID') : (l.created_at ? new Date(l.created_at + 'Z').toLocaleDateString('id-ID') : '-');
        const statusClass = 'status-' + l.license_status;
        const licenseType = l.license_type || '-';
        const price = l.purchase_price ? 'Rp' + formatNum(l.purchase_price) : '-';
        const version = l.major_version ? 'v' + l.major_version + '.' + (l.minor_version || '0') : '-';
        const city = l.city || '-';
        const deviceId = l.device_id || '-';
        const cloneIcon = l.clone_detected == 1 ? ' <span title="Clone terdeteksi" style="color:#F44336">&#9888;</span>' : '';

        let actions = '';
        if (l.license_status === 'PENDING') {
            actions = `
                <button class="action-btn approve" onclick="openApproveWithType(${l.id})">Setujui Tipe</button>
                <button class="action-btn reject" onclick="openReject(${l.id}, '${escapeHtml(l.owner_name)}')">Tolak</button>
            `;
        }
        if (l.license_status === 'ACTIVE') {
            actions = `
                <button class="action-btn transfer" onclick="openTransfer(${l.id}, '${escapeHtml(l.owner_name)}')">Transfer</button>
                <button class="action-btn" style="background:#FF9800" onclick="openResetDevice(${l.id})">Reset</button>
                <button class="action-btn" style="background:#9C27B0" onclick="openEditPrice(${l.id})">Harga</button>
                <button class="action-btn" style="background:#607D8B" onclick="openEditNotes(${l.id})">Catatan</button>
                <button class="action-btn" style="background:#795548" onclick="openUpgradeVersion(${l.id})">Versi</button>
                <button class="action-btn" style="background:#F44336" onclick="openBlockLicense(${l.id})">Blokir</button>
            `;
        }
        if (l.license_status === 'BLOCKED') {
            actions = `
                <button class="action-btn" style="background:#4CAF50" onclick="openUnblockLicense(${l.id})">Buka Blokir</button>
            `;
        }
        if (l.license_status === 'TRIAL_EXPIRED') {
            actions = `
                <button class="action-btn approve" onclick="openApproveWithType(${l.id})">Setujui Tipe</button>
            `;
        }
        actions += `<button class="action-btn detail" onclick="openDetail(${l.id})">Detail</button>`;
        actions += `<button class="action-btn history" onclick="openLicenseHistory(${l.id})">Riwayat</button>`;
        if (l.license_status === 'ACTIVE') {
            actions += `<button class="action-btn" style="background:#9C27B0" onclick="openCertificateForLicense(${l.id})">Sertifikat</button>`;
        }

        return `<tr>
            <td>${l.id}</td>
            <td>${escapeHtml(l.business_name)}</td>
            <td>${escapeHtml(l.owner_name)}</td>
            <td>${escapeHtml(l.phone_number || '-')}</td>
            <td>${escapeHtml(city)}</td>
            <td style="font-family:monospace;font-size:12px">${deviceId}${cloneIcon}</td>
            <td style="font-family:monospace;font-size:11px">${key}</td>
            <td><span class="status-badge ${statusClass}">${l.license_status}</span></td>
            <td>${licenseType}</td>
            <td style="font-size:12px">${date}</td>
            <td style="font-size:12px">${price}</td>
            <td style="font-size:12px">${version}</td>
            <td class="action-cell" style="max-width:300px">${actions}</td>
        </tr>`;
    }).join('');
}

// ==================== MANAGEMENT ACTIONS ====================

function openBlockLicense(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;
    currentActionId = id;
    const reason = prompt('Alasan blokir untuk ' + escapeHtml(l.business_name) + ':\n(Pelanggaran, Refund, Pembajakan, Chargeback, Dijual kembali)');
    if (!reason) return;
    if (!confirm('Blokir lisensi ' + escapeHtml(l.business_name) + '?')) return;
    fetch(API_BASE + '/api/license/control/block', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: id, admin_name: 'Admin', reason: reason })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Lisensi berhasil diblokir!');
            loadData();
            loadManagementPage();
        } else {
            alert(res.message || 'Gagal blokir');
        }
    });
}

function openUnblockLicense(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;
    currentActionId = id;
    if (!confirm('Buka blokir lisensi ' + escapeHtml(l.business_name) + '?')) return;
    fetch(API_BASE + '/api/license/control/unblock', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: id, admin_name: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Lisensi berhasil dibuka blokirnya!');
            loadData();
            loadManagementPage();
        } else {
            alert(res.message || 'Gagal unblock');
        }
    });
}

function openResetDevice(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;
    currentActionId = id;
    if (!confirm('Reset device untuk ' + escapeHtml(l.business_name) + '? Device binding akan dihapus.')) return;
    fetch(API_BASE + '/api/license/control/reset-device', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: id, admin_name: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Device berhasil direset!');
            loadData();
            loadManagementPage();
        } else {
            alert(res.message || 'Gagal reset device');
        }
    });
}

function openEditPrice(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;
    currentActionId = id;
    document.getElementById('editPriceName').textContent = escapeHtml(l.business_name);
    document.getElementById('editPriceCurrent').textContent = 'Harga saat ini: ' + (l.purchase_price ? 'Rp' + formatNum(l.purchase_price) : '-');
    document.getElementById('editPriceValue').value = l.purchase_price || '';
    document.getElementById('editPriceModal').style.display = '';
}

function confirmEditPrice() {
    const price = parseInt(document.getElementById('editPriceValue').value);
    if (isNaN(price) || price < 0) { alert('Harga tidak valid'); return; }
    fetch(API_BASE + '/api/license/control/edit-price', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: currentActionId, price: price, admin_name: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        closeModal('editPriceModal');
        if (res.success) {
            showToast('Harga berhasil diperbarui!');
            loadData();
            loadManagementPage();
        } else {
            alert(res.message || 'Gagal edit harga');
        }
    });
}

function openEditNotes(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;
    currentActionId = id;
    document.getElementById('editNotesName').textContent = escapeHtml(l.business_name);
    document.getElementById('editNotesValue').value = l.notes || '';
    document.getElementById('editNotesModal').style.display = '';
}

function confirmEditNotes() {
    const notes = document.getElementById('editNotesValue').value.trim();
    fetch(API_BASE + '/api/license/control/edit-notes', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: currentActionId, notes: notes, admin_name: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        closeModal('editNotesModal');
        if (res.success) {
            showToast('Catatan berhasil diperbarui!');
            loadData();
            loadManagementPage();
        } else {
            alert(res.message || 'Gagal edit catatan');
        }
    });
}

function openUpgradeVersion(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;
    currentActionId = id;
    document.getElementById('upgradeVersionName').textContent = escapeHtml(l.business_name);
    document.getElementById('upgradeVersionCurrent').textContent = 'Versi saat ini: ' + (l.major_version ? 'v' + l.major_version + '.' + (l.minor_version || '0') : '-');
    document.getElementById('upgradeVersionValue').value = l.major_version || '1';
    document.getElementById('upgradeVersionModal').style.display = '';
}

function confirmUpgradeVersion() {
    const majorVersion = parseInt(document.getElementById('upgradeVersionValue').value);
    if (isNaN(majorVersion) || majorVersion < 1) { alert('Versi tidak valid'); return; }
    fetch(API_BASE + '/api/license/control/upgrade-version', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: currentActionId, major_version: majorVersion, admin_name: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        closeModal('upgradeVersionModal');
        if (res.success) {
            showToast('Versi berhasil diupgrade!');
            loadData();
            loadManagementPage();
        } else {
            alert(res.message || 'Gagal upgrade versi');
        }
    });
}

function openUpdateMaxTransfer(id) {
    const l = licenses.find(x => x.id === id);
    if (!l) return;
    currentActionId = id;
    document.getElementById('updateMaxTransferName').textContent = escapeHtml(l.business_name);
    const current = l.max_transfer != null ? (l.max_transfer >= 999 ? 'Unlimited' : l.max_transfer) : '3';
    document.getElementById('updateMaxTransferCurrent').textContent = 'Max transfer saat ini: ' + current;
    document.getElementById('updateMaxTransferValue').value = l.max_transfer || '3';
    document.getElementById('updateMaxTransferModal').style.display = '';
}

function confirmUpdateMaxTransfer() {
    const maxTransfer = parseInt(document.getElementById('updateMaxTransferValue').value);
    if (isNaN(maxTransfer) || maxTransfer < 0) { alert('Nilai tidak valid'); return; }
    fetch(API_BASE + '/api/license/control/update-max-transfer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: currentActionId, max_transfer: maxTransfer, admin_name: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        closeModal('updateMaxTransferModal');
        if (res.success) {
            showToast('Max transfer berhasil diperbarui!');
            loadData();
            loadManagementPage();
        } else {
            alert(res.message || 'Gagal update max transfer');
        }
    });
}

function openLicenseHistory(id) {
    fetch(API_BASE + '/api/license/history/' + id)
        .then(r => r.json())
        .then(res => {
            const content = document.getElementById('licenseHistoryContent');
            if (!res.success || !res.data || res.data.length === 0) {
                content.innerHTML = '<p class="text-muted">Belum ada riwayat.</p>';
            } else {
                let html = '';
                res.data.forEach(h => {
                    const date = h.created_at ? new Date(h.created_at + 'Z').toLocaleString('id-ID') : '-';
                    let actionLabel = h.action || '-';
                    let detailHtml = '';
                    if (h.details) {
                        try {
                            const details = typeof h.details === 'string' ? JSON.parse(h.details) : h.details;
                            detailHtml = Object.entries(details).map(([k, v]) =>
                                `<div style="color:#6C6C80;font-size:11px">${escapeHtml(k)}: ${escapeHtml(String(v))}</div>`
                            ).join('');
                        } catch (e) {
                            detailHtml = `<div style="color:#6C6C80;font-size:11px">${escapeHtml(String(h.details))}</div>`;
                        }
                    }
                    html += `<div class="detail-row">
                        <div class="detail-label" style="min-width:140px;font-size:11px">${date}</div>
                        <div class="detail-value" style="font-size:12px">
                            <div><span class="status-badge status-ACTIVE" style="font-size:10px;padding:2px 6px">${escapeHtml(actionLabel)}</span></div>
                            <div style="color:#B0B0C3;font-size:11px">Admin: ${escapeHtml(h.admin_name || '-')}</div>
                            ${h.ip_address ? `<div style="color:#6C6C80;font-size:11px">IP: ${escapeHtml(h.ip_address)}</div>` : ''}
                            ${detailHtml}
                        </div>
                    </div>`;
                });
                content.innerHTML = html;
            }
            document.getElementById('licenseHistoryModal').style.display = '';
        });
}

function checkExpiredTrials() {
    if (!confirm('Periksa dan update trial yang sudah expired?')) return;
    fetch(API_BASE + '/api/license/check-expired-trials', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.message || 'Pemeriksaan selesai!');
            loadData();
            loadManagementPage();
        } else {
            alert(res.message || 'Gagal memeriksa trial');
        }
    });
}

// ==================== EXPORT FUNCTIONS ====================

function exportPDF() {
    alert('Fitur export PDF akan segera tersedia.');
}

function exportExcel() {
    alert('Fitur export Excel akan segera tersedia.');
}

function exportCSV() {
    if (!licenses.length) { alert('Tidak ada data untuk diexport'); return; }
    let csv = '\uFEFF';
    csv += 'ID,Nama Rental,Pemilik,No. HP,Kota,Device ID,License Key,Status,Jenis Lisensi,Tgl Aktivasi,Harga,Versi\n';
    licenses.forEach(l => {
        const date = l.activation_date ? new Date(l.activation_date + 'Z').toLocaleDateString('id-ID') : (l.created_at ? new Date(l.created_at + 'Z').toLocaleDateString('id-ID') : '-');
        const price = l.purchase_price ? l.purchase_price : '';
        const version = l.major_version ? 'v' + l.major_version + '.' + (l.minor_version || '0') : '';
        csv += `${l.id},"${(l.business_name || '').replace(/"/g, '""')}","${(l.owner_name || '').replace(/"/g, '""')}","${(l.phone_number || '').replace(/"/g, '""')}","${(l.city || '').replace(/"/g, '""')}","${(l.device_id || '').replace(/"/g, '""')}","${(l.license_key || '').replace(/"/g, '""')}",${l.license_status},${l.license_type || ''},${date},${price},${version}\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'lisensi_nakako_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
    showToast('Data berhasil diexport CSV!');
}

// ==================== PRODUCT KEY SYSTEM (TAHAP 6.1) ====================

let productKeys = [];

function loadProductKeys() {
    fetch(API_BASE + '/api/product-key/stats')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const d = res.data;
                document.getElementById('pkStatTotal').textContent = d.total || 0;
                document.getElementById('pkStatUnused').textContent = d.unused || 0;
                document.getElementById('pkStatUsed').textContent = d.used || 0;
                document.getElementById('pkStatBlocked').textContent = d.blocked || 0;
            }
        });

    fetch(API_BASE + '/api/product-key/list')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                productKeys = res.data;
                renderProductKeys();
            }
        });
}

function renderProductKeys() {
    const tbody = document.getElementById('productKeyTableBody');
    const search = document.getElementById('pkSearchInput').value.toLowerCase();

    let filtered = productKeys.filter(pk => {
        return !search ||
            (pk.product_key && pk.product_key.toLowerCase().includes(search)) ||
            (pk.generated_by && pk.generated_by.toLowerCase().includes(search));
    });

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(pk => {
        const statusClass = 'status-' + (pk.status === 'UNUSED' ? 'PENDING' : (pk.status === 'USED' ? 'ACTIVE' : 'BLOCKED'));
        const generatedAt = pk.generated_at ? new Date(pk.generated_at + 'Z').toLocaleString('id-ID') : '-';
        const activatedAt = pk.activated_at ? new Date(pk.activated_at + 'Z').toLocaleString('id-ID') : '-';
        const licenseId = pk.license_id || '-';
        const licenseType = pk.license_type || '-';
        const generatedBy = pk.generated_by || '-';

        let actions = '';
        if (pk.status === 'UNUSED') {
            actions = `<button class="action-btn reject" onclick="confirmBlockProductKey(${pk.id}, '${escapeHtml(pk.product_key)}')">Blokir</button>`;
        }
        actions += `<button class="action-btn detail" onclick="copyText('${escapeHtml(pk.product_key)}')">Copy</button>`;

        return `<tr>
            <td>${pk.id}</td>
            <td style="font-family:monospace;font-size:12px;font-weight:bold">${escapeHtml(pk.product_key)}</td>
            <td><span class="status-badge ${statusClass}">${pk.status}</span></td>
            <td>${licenseType}</td>
            <td style="font-size:12px">${generatedAt}</td>
            <td style="font-size:12px">${activatedAt}</td>
            <td>${licenseId}</td>
            <td style="font-size:12px">${escapeHtml(generatedBy)}</td>
            <td class="action-cell">${actions}</td>
        </tr>`;
    }).join('');
}

function openGenerateProductKey() {
    document.getElementById('generatePKType').value = 'LIFETIME';
    document.getElementById('generatePKResult').style.display = 'none';
    document.getElementById('generatePKModal').style.display = '';
}

function confirmGenerateProductKey() {
    const licenseType = document.getElementById('generatePKType').value;
    fetch(API_BASE + '/api/product-key/generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_type: licenseType, generated_by: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('generatePKResultText').textContent = res.data.product_key;
            document.getElementById('generatePKResult').style.display = '';
            showToast('Product Key berhasil digenerate!');
            loadProductKeys();
        } else {
            alert(res.message || 'Gagal generate');
        }
    });
}

function copyPKResult() {
    const text = document.getElementById('generatePKResultText').textContent;
    copyText(text);
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Teks berhasil disalin!');
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        showToast('Teks berhasil disalin!');
    });
}

function openGenerateMultipleProductKeys() {
    document.getElementById('generateMultiplePKCount').value = '10';
    document.getElementById('generateMultiplePKType').value = 'LIFETIME';
    document.getElementById('generateMultiplePKResult').style.display = 'none';
    document.getElementById('generateMultiplePKModal').style.display = '';
}

function confirmGenerateMultipleProductKeys() {
    const count = parseInt(document.getElementById('generateMultiplePKCount').value) || 10;
    const licenseType = document.getElementById('generateMultiplePKType').value;
    fetch(API_BASE + '/api/product-key/generate-multiple', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ count: count, license_type: licenseType, generated_by: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('generateMultiplePKResultText').textContent = res.message;
            document.getElementById('generateMultiplePKResult').style.display = '';
            showToast(res.message);
            loadProductKeys();
        } else {
            alert(res.message || 'Gagal generate');
        }
    });
}

function confirmBlockProductKey(id, key) {
    if (!confirm('Blokir Product Key ' + key + '?')) return;
    fetch(API_BASE + '/api/product-key/block', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id, admin_name: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Product Key berhasil diblokir!');
            loadProductKeys();
        } else {
            alert(res.message || 'Gagal blokir');
        }
    });
}

function exportProductKeysCSV() {
    if (!productKeys.length) { alert('Tidak ada data untuk diexport'); return; }
    let csv = '\uFEFF';
    csv += 'ID,Product Key,Status,Tipe,Generated At,Activated At,License ID,Generated By\n';
    productKeys.forEach(pk => {
        const genAt = pk.generated_at ? new Date(pk.generated_at + 'Z').toLocaleString('id-ID') : '';
        const actAt = pk.activated_at ? new Date(pk.activated_at + 'Z').toLocaleString('id-ID') : '';
        csv += `${pk.id},"${(pk.product_key || '').replace(/"/g, '""')}",${pk.status},${pk.license_type || ''},"${genAt}","${actAt}",${pk.license_id || ''},"${(pk.generated_by || '').replace(/"/g, '""')}"\n`;
    });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'product_keys_nakako_' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
    showToast('Data berhasil diexport CSV!');
}

// ==================== LICENSE CERTIFICATE SYSTEM (TAHAP 6.2) ====================

let certificates = [];
let currentCertId = null;
let currentCertData = null;

function loadCertificates() {
    fetch(API_BASE + '/api/certificate/list')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                certificates = res.data;
                renderCertificates();
            }
        });
    fetch(API_BASE + '/api/certificate/list')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('certStatTotal').textContent = (res.data || []).length;
            }
        });
}

function renderCertificates() {
    const tbody = document.getElementById('certificateTableBody');
    const search = document.getElementById('certSearchInput').value.toLowerCase();

    let filtered = certificates.filter(c => {
        return !search ||
            (c.certificate_number && c.certificate_number.toLowerCase().includes(search)) ||
            (c.business_name && c.business_name.toLowerCase().includes(search));
    });

    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Tidak ada data</td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(c => {
        const genAt = c.generated_at ? new Date(c.generated_at + 'Z').toLocaleString('id-ID') : '-';
        const licenseType = c.license_type || '-';
        const businessName = c.business_name || '-';
        const generatedBy = c.generated_by || '-';

        return `<tr>
            <td>${c.id}</td>
            <td style="font-family:monospace;font-weight:bold">${escapeHtml(c.certificate_number)}</td>
            <td>${c.license_id}</td>
            <td>${escapeHtml(businessName)}</td>
            <td>${licenseType}</td>
            <td style="font-size:12px">${genAt}</td>
            <td style="font-size:12px">${escapeHtml(generatedBy)}</td>
            <td class="action-cell">
                <button class="action-btn approve" onclick="openCertificate(${c.id})">Lihat</button>
                <button class="action-btn detail" onclick="copyText('${escapeHtml(c.certificate_number)}')">Copy No.</button>
            </td>
        </tr>`;
    }).join('');
}

function openCertificate(certId) {
    currentCertId = certId;
    const c = certificates.find(x => x.id === certId);
    if (!c) {
        showCertificateError('Sertifikat tidak ditemukan');
        return;
    }
    currentCertData = c;
    const genAt = c.generated_at ? new Date(c.generated_at + 'Z').toLocaleString('id-ID') : '-';
    document.getElementById('certificateInfo').innerHTML = `
        <div class="detail-row"><div class="detail-label">No. Sertifikat</div><div class="detail-value" style="font-family:monospace;font-weight:bold">${escapeHtml(c.certificate_number)}</div></div>
        <div class="detail-row"><div class="detail-label">Nama Rental</div><div class="detail-value">${escapeHtml(c.business_name || '-')}</div></div>
        <div class="detail-row"><div class="detail-label">Pemilik</div><div class="detail-value">${escapeHtml(c.owner_name || '-')}</div></div>
        <div class="detail-row"><div class="detail-label">License Key</div><div class="detail-value" style="font-family:monospace">${escapeHtml(c.license_key || '-')}</div></div>
        <div class="detail-row"><div class="detail-label">Jenis</div><div class="detail-value">${c.license_type || '-'}</div></div>
        <div class="detail-row"><div class="detail-label">Digenerate</div><div class="detail-value">${genAt}</div></div>
    `;
    document.getElementById('certificateError').style.display = 'none';
    document.getElementById('certificateModal').style.display = '';
}

function showCertificateError(msg) {
    const errEl = document.getElementById('certificateError');
    errEl.textContent = msg;
    errEl.style.display = '';
}

function downloadCertificate() {
    const btn = document.getElementById('certDownloadBtn');
    btn.textContent = 'Mengunduh...';
    btn.disabled = true;
    fetch(API_BASE + '/api/certificate/download', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cert_id: currentCertId })
    })
    .then(r => r.json())
    .then(res => {
        btn.textContent = 'Download PDF';
        btn.disabled = false;
        if (res.success && res.pdf) {
            const byteChars = atob(res.pdf);
            const byteNums = new Array(byteChars.length);
            for (let i = 0; i < byteChars.length; i++) {
                byteNums[i] = byteChars.charCodeAt(i);
            }
            const byteArr = new Uint8Array(byteNums);
            const blob = new Blob([byteArr], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = res.filename || 'sertifikat_' + (currentCertData ? currentCertData.certificate_number : 'unknown') + '.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Sertifikat berhasil diunduh!');
        } else {
            showCertificateError(res.message || 'Gagal mengunduh sertifikat');
        }
    })
    .catch(() => {
        btn.textContent = 'Download PDF';
        btn.disabled = false;
        showCertificateError('Gagal terhubung ke server');
    });
}

function printCertificate() {
    fetch(API_BASE + '/api/certificate/download', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cert_id: currentCertId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.pdf) {
            const byteChars = atob(res.pdf);
            const byteNums = new Array(byteChars.length);
            for (let i = 0; i < byteChars.length; i++) {
                byteNums[i] = byteChars.charCodeAt(i);
            }
            const byteArr = new Uint8Array(byteNums);
            const blob = new Blob([byteArr], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const w = window.open(url);
            if (w) {
                w.focus();
                setTimeout(() => {
                    w.print();
                }, 500);
            } else {
                showCertificateError('Popup diblokir. Izinkan popup untuk mencetak.');
            }
            URL.revokeObjectURL(url);
        } else {
            showCertificateError(res.message || 'Gagal memproses sertifikat');
        }
    })
    .catch(() => {
        showCertificateError('Gagal terhubung ke server');
    });
}

function openCertificateForLicense(id) {
    fetch(API_BASE + '/api/certificate/by-license', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data) {
            currentCertId = res.data.id;
            currentCertData = res.data;
            const genAt = res.data.generated_at ? new Date(res.data.generated_at + 'Z').toLocaleString('id-ID') : '-';
            document.getElementById('certificateInfo').innerHTML = `
                <div class="detail-row"><div class="detail-label">No. Sertifikat</div><div class="detail-value" style="font-family:monospace;font-weight:bold">${escapeHtml(res.data.certificate_number)}</div></div>
                <div class="detail-row"><div class="detail-label">Nama Rental</div><div class="detail-value">${escapeHtml(res.data.business_name || '-')}</div></div>
                <div class="detail-row"><div class="detail-label">Pemilik</div><div class="detail-value">${escapeHtml(res.data.owner_name || '-')}</div></div>
                <div class="detail-row"><div class="detail-label">License Key</div><div class="detail-value" style="font-family:monospace">${escapeHtml(res.data.license_key || '-')}</div></div>
                <div class="detail-row"><div class="detail-label">Jenis</div><div class="detail-value">${res.data.license_type || '-'}</div></div>
                <div class="detail-row"><div class="detail-label">Digenerate</div><div class="detail-value">${genAt}</div></div>
            `;
            document.getElementById('certificateError').style.display = 'none';
            document.getElementById('certificateModal').style.display = '';
        } else {
            // No certificate yet, offer to generate
            if (confirm('Belum ada sertifikat untuk lisensi ini. Generate sekarang?')) {
                generateCertificateForLicense(id);
            }
        }
    });
}

function generateCertificateForLicense(id) {
    fetch(API_BASE + '/api/certificate/generate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ license_id: id, generated_by: 'Admin' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('Sertifikat berhasil digenerate!');
            openCertificateForLicense(id);
            loadCertificates();
        } else {
            alert(res.message || 'Gagal generate sertifikat');
        }
    });
}

// ==================== TASK 11.1: LICENSE PACKAGES ====================

let packages = [];

function loadPackages() {
    fetch(API_BASE + '/api/packages')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                packages = res.data;
                renderPackages();
            }
        });
}

function renderPackages() {
    const tbody = document.getElementById('packagesTableBody');
    if (!packages || packages.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Tidak ada data</td></tr>';
        return;
    }
    tbody.innerHTML = packages.map(p => {
        const statusClass = p.status === 'ACTIVE' ? 'status-ACTIVE' : 'status-BLOCKED';
        const statusLabel = p.status === 'ACTIVE' ? 'AKTIF' : 'NONAKTIF';
        const price = p.price ? 'Rp' + formatNum(p.price) : 'Gratis';
        const duration = p.duration_days >= 99999 ? 'Seumur Hidup' : p.duration_days + ' hari';
        const maxDevices = p.max_devices >= 999 ? 'Unlimited' : p.max_devices;
        return `<tr>
            <td>${p.id}</td>
            <td><strong>${escapeHtml(p.name)}</strong></td>
            <td>${price}</td>
            <td>${duration}</td>
            <td>${maxDevices}</td>
            <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
            <td>
                <button class="action-btn detail" onclick="openEditPackage(${p.id})">Edit</button>
                <button class="action-btn reject" onclick="deletePackage(${p.id}, '${escapeHtml(p.name)}')">Hapus</button>
            </td>
        </tr>`;
    }).join('');
}

function openAddPackage() {
    document.getElementById('packageModalTitle').textContent = 'Tambah Paket Lisensi';
    document.getElementById('packageEditId').value = '';
    document.getElementById('packageName').value = '';
    document.getElementById('packageDesc').value = '';
    document.getElementById('packagePrice').value = '';
    document.getElementById('packageDuration').value = '30';
    document.getElementById('packageMaxDevices').value = '1';
    document.getElementById('packageFeatures').value = '';
    document.getElementById('packageStatus').value = 'ACTIVE';
    document.getElementById('packageSaveBtn').textContent = 'Simpan';
    document.getElementById('packageModal').style.display = '';
}

function openEditPackage(id) {
    const p = packages.find(x => x.id === id);
    if (!p) return;
    document.getElementById('packageModalTitle').textContent = 'Edit Paket Lisensi';
    document.getElementById('packageEditId').value = p.id;
    document.getElementById('packageName').value = p.name;
    document.getElementById('packageDesc').value = p.description || '';
    document.getElementById('packagePrice').value = p.price || '';
    document.getElementById('packageDuration').value = p.duration_days;
    document.getElementById('packageMaxDevices').value = p.max_devices;
    document.getElementById('packageFeatures').value = p.features || '';
    document.getElementById('packageStatus').value = p.status;
    document.getElementById('packageSaveBtn').textContent = 'Simpan';
    document.getElementById('packageModal').style.display = '';
}

function savePackage() {
    const id = document.getElementById('packageEditId').value;
    const data = {
        name: document.getElementById('packageName').value.trim(),
        description: document.getElementById('packageDesc').value.trim(),
        price: parseFloat(document.getElementById('packagePrice').value) || 0,
        duration_days: parseInt(document.getElementById('packageDuration').value) || 30,
        max_devices: parseInt(document.getElementById('packageMaxDevices').value) || 1,
        features: document.getElementById('packageFeatures').value.trim(),
        status: document.getElementById('packageStatus').value,
    };
    if (!data.name) { alert('Nama paket wajib diisi'); return; }
    const url = id ? (API_BASE + '/api/packages/' + id) : (API_BASE + '/api/packages');
    const method = id ? 'PUT' : 'POST';
    fetch(url, { method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(r => r.json())
        .then(res => {
            closeModal('packageModal');
            if (res.success) {
                showToast(id ? 'Paket berhasil diperbarui!' : 'Paket berhasil dibuat!');
                loadPackages();
            } else {
                alert(res.message || 'Gagal menyimpan paket');
            }
        });
}

function deletePackage(id, name) {
    if (!confirm('Hapus paket "' + name + '"?')) return;
    fetch(API_BASE + '/api/packages/' + id, { method: 'DELETE' })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast('Paket berhasil dihapus!');
                loadPackages();
            } else {
                alert(res.message || 'Gagal menghapus paket');
            }
        });
}

// ==================== UPDATE loadData to include management + product keys + certificates refresh ====================
const origLoadData = loadData;
loadData = function() {
    origLoadData();
    if (document.getElementById('pagePackages').style.display !== 'none') loadPackages();
    if (document.getElementById('pageControl').style.display !== 'none') loadControlCenter();
    if (document.getElementById('pageManagement').style.display !== 'none') loadManagementPage();
    if (document.getElementById('pageProductKeys').style.display !== 'none') loadProductKeys();
    if (document.getElementById('pageCertificates').style.display !== 'none') loadCertificates();
};

// Auto-login check
if (token) {
    showDashboard();
    loadData();
    setInterval(loadData, 10000);
} else {
    document.getElementById('loginPage').style.display = '';
}
