/**
 * Poverty-Mapping GIS — script.js
 * WebGIS Distribusi Bantuan Sosial
 */

// ============================================================
// CONFIG
// ============================================================
const MAP_CENTER     = [-0.0532, 109.3458];
const MAP_ZOOM       = 15;
const DEFAULT_RADIUS = 300;

// ============================================================
// AUTH CHECK
// ============================================================
let userSession = null;

async function checkAuth() {
    try {
        const response = await fetch('check_auth.php');
        if (response.status === 401) {
            window.location.href = 'login.html';
            return;
        }
        const data = await response.json();
        userSession = data.user;
        setRoleFromServer(data.user.role);
    } catch (err) {
        console.error('Auth check failed:', err);
        window.location.href = 'login.html';
    }
}

async function handleLogout() {
    try {
        await fetch('logout_api.php', { method: 'POST' });
        window.location.href = 'login.html';
    } catch (err) {
        console.error('Logout failed:', err);
    }
}

function setRoleFromServer(role) {
    const roleMap = { 'admin': 'admin', 'surveyor': 'surveyer', 'viewer': 'viewer' };
    currentRole = roleMap[role] || 'viewer';
    const cfg = ROLE_CONFIG[currentRole];
    document.getElementById('roleIcon').textContent = cfg.icon;
    document.getElementById('roleLabel').textContent = cfg.label;
    applyRoleUI();
}

// ============================================================
// ROLE SYSTEM
// ============================================================
let currentRole = 'admin';

const ROLE_CONFIG = {
    admin: {
        label: 'Admin', icon: '👑',
        canAdd: true, canEdit: true, canDelete: true, canReset: true,
        canReport: true, canViewReport: true, canViewDetail: true,
        canEditKas: true, canDragRadius: true
    },
    surveyer: {
        label: 'Surveyer', icon: '📋',
        canAdd: true, canEdit: true, canDelete: false, canReset: false,
        canReport: true, canViewReport: true, canViewDetail: true,
        canEditKas: false, canDragRadius: false
    },
    viewer: {
        label: 'Viewer', icon: '👁',
        canAdd: false, canEdit: false, canDelete: false, canReset: false,
        canReport: true, canViewReport: false, canViewDetail: true,
        canEditKas: false, canDragRadius: false
    }
};

function can(action) { return ROLE_CONFIG[currentRole]?.[action] === true; }

function openRoleModal() {
    document.getElementById('roleModal').classList.remove('hidden');
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active-role'));
    document.getElementById(`roleCard_${currentRole}`)?.classList.add('active-role');
}
function closeRoleModal() { document.getElementById('roleModal').classList.add('hidden'); }

function setRole(role) {
    currentRole = role;
    const cfg = ROLE_CONFIG[role];
    document.getElementById('roleIcon').textContent  = cfg.icon;
    document.getElementById('roleLabel').textContent = cfg.label;
    document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active-role'));
    document.getElementById(`roleCard_${role}`)?.classList.add('active-role');
    applyRoleUI();
    closeRoleModal();
}

function applyRoleUI() {
    const cfg = ROLE_CONFIG[currentRole];

    const btnGroup = document.getElementById('actionBtnGroup');
    if (btnGroup) btnGroup.style.display = cfg.canAdd ? 'flex' : 'none';

    const btnReset = document.getElementById('btnReset');
    if (btnReset) btnReset.style.display = cfg.canReset ? 'block' : 'none';

    const notice = document.getElementById('viewerNotice');
    if (notice) notice.style.display = currentRole === 'viewer' ? 'block' : 'none';

    const reportColList   = document.getElementById('reportColList');
    const reportColViewer = document.getElementById('reportColViewer');
    if (cfg.canViewReport) {
        if (reportColList)   reportColList.style.display   = 'flex';
        if (reportColViewer) reportColViewer.style.display = 'none';
    } else {
        if (reportColList)   reportColList.style.display   = 'none';
        if (reportColViewer) reportColViewer.style.display = 'flex';
    }

    updateReportBadge();

    centers.forEach(c => { if (c.marker) c.marker.setPopupContent(buildCenterPopup(c)); });
    houses.forEach(h => { if (h.marker) h.marker.setPopupContent(buildHouseMapPopup(h)); });
    updateSidebar();

    centers.forEach(c => {
        if (c.handle) {
            if (cfg.canDragRadius) c.handle.dragging?.enable();
            else c.handle.dragging?.disable();
        }
        if (c.marker) {
            if (cfg.canEdit) c.marker.dragging?.enable();
            else c.marker.dragging?.disable();
        }
    });
}

// ============================================================
// NAVIGATION
// ============================================================
function navigateTo(page) {
    const pagePeta      = document.getElementById('pagePeta');
    const pagePelaporan = document.getElementById('pagePelaporan');
    const navHome       = document.getElementById('navHome');
    const navReport     = document.getElementById('navReport');

    if (page === 'map') {
        pagePeta.classList.add('active-page');
        pagePelaporan.classList.remove('active-page');
        navHome.classList.add('active');
        navReport.classList.remove('active');
        setTimeout(() => map.invalidateSize(), 100);
    } else {
        pagePeta.classList.remove('active-page');
        pagePelaporan.classList.add('active-page');
        navHome.classList.remove('active');
        navReport.classList.add('active');
        applyRoleUI();
        renderReportList();
    }
}

// ============================================================
// MAP INIT
// ============================================================
const map = L.map('map', { zoomControl: false }).setView(MAP_CENTER, MAP_ZOOM);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

L.control.zoom({ position: 'bottomright' }).addTo(map);

// ============================================================
// STATE
// ============================================================
let isAddingCenter = false;
let isAddingHouse  = false;
let centers        = [];
let houses         = [];
let reports        = [];

// ============================================================
// ICON TYPES
// ============================================================
const CENTER_TYPE_MAP = {
    'masjid':           { emoji: '🕌', color: '#0d9488' },
    'musholla':         { emoji: '🕌', color: '#0d9488' },
    'surau':            { emoji: '🕌', color: '#0d9488' },
    'gereja katedral':  { emoji: '⛪', color: '#2563eb' },
    'gereja katolik':   { emoji: '⛪', color: '#2563eb' },
    'katedral':         { emoji: '⛪', color: '#2563eb' },
    'gereja protestan': { emoji: '✝️', color: '#7c3aed' },
    'gereja':           { emoji: '⛪', color: '#1d4ed8' },
    'kapel':            { emoji: '✝️', color: '#7c3aed' },
    'vihara':           { emoji: '🛕', color: '#d97706' },
    'klenteng':         { emoji: '🛕', color: '#d97706' },
    'pura':             { emoji: '🛕', color: '#d97706' },
    'kuil':             { emoji: '🛕', color: '#d97706' },
    'sinagog':          { emoji: '✡️', color: '#1d4ed8' },
    'default':          { emoji: '🏛️', color: '#64748b' }
};

function getCenterType(name) {
    if (!name) return 'default';
    const lower = name.toLowerCase();
    const keys  = Object.keys(CENTER_TYPE_MAP)
        .filter(k => k !== 'default')
        .sort((a, b) => b.length - a.length);
    for (const k of keys) { if (lower.includes(k)) return k; }
    return 'default';
}

function createCenterIcon(name) {
    const info = CENTER_TYPE_MAP[getCenterType(name)] || CENTER_TYPE_MAP['default'];
    return L.divIcon({
        className: '',
        html: `<div style="width:38px;height:38px;background:${info.color};
            border:3px solid #fff;border-radius:50% 50% 50% 0;transform:rotate(-45deg);
            display:flex;align-items:center;justify-content:center;
            box-shadow:0 3px 12px rgba(0,0,0,.3);">
            <span style="transform:rotate(45deg);font-size:18px;line-height:1">${info.emoji}</span></div>`,
        iconSize: [38, 38], iconAnchor: [10, 38]
    });
}

function createHouseIcon(aidStatus, hasData) {
    const palette = {
        helped:     { bg: '#d97706', border: '#78350f', emoji: '🏠' },
        not_helped: { bg: '#dc2626', border: '#7f1d1d', emoji: '🏚' },
        outside:    { bg: '#16a34a', border: '#14532d', emoji: '🏠' },
        nodata:     { bg: '#94a3b8', border: '#475569', emoji: '📍' }
    };
    const key = !hasData ? 'nodata' : (aidStatus || 'outside');
    const c   = palette[key] || palette.nodata;
    return L.divIcon({
        className: '',
        html: `<div style="width:24px;height:24px;background:${c.bg};
            border:2.5px solid ${c.border};border-radius:4px 4px 4px 0;
            transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;
            font-size:11px;line-height:1;box-shadow:0 2px 8px rgba(0,0,0,.25);">
            <span style="transform:rotate(45deg)">${c.emoji}</span></div>`,
        iconSize: [24, 24], iconAnchor: [6, 24]
    });
}

function createHandleIcon() {
    return L.divIcon({
        className: 'radius-handle',
        html: `<div style="width:14px;height:14px;background:white;border:2.5px solid #3b82f6;
            border-radius:50%;box-shadow:0 2px 6px rgba(59,130,246,.5);cursor:ew-resize;"></div>`,
        iconSize: [14, 14], iconAnchor: [7, 7]
    });
}

// ============================================================
// UTILS
// ============================================================
function haversineDistance(lat1, lng1, lat2, lng2) {
    const R  = 6371000;
    const φ1 = lat1 * Math.PI / 180, φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lng2 - lng1) * Math.PI / 180;
    const a  = Math.sin(Δφ / 2) ** 2 + Math.cos(φ1) * Math.cos(φ2) * Math.sin(Δλ / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function radiusPoint(lat, lng, dist, bearing = 90) {
    const R  = 6371000;
    const φ1 = lat * Math.PI / 180, λ1 = lng * Math.PI / 180;
    const brng = bearing * Math.PI / 180;
    const φ2 = Math.asin(Math.sin(φ1) * Math.cos(dist / R) + Math.cos(φ1) * Math.sin(dist / R) * Math.cos(brng));
    const λ2 = λ1 + Math.atan2(Math.sin(brng) * Math.sin(dist / R) * Math.cos(φ1), Math.cos(dist / R) - Math.sin(φ1) * Math.sin(φ2));
    return L.latLng(φ2 * 180 / Math.PI, λ2 * 180 / Math.PI);
}

async function reverseGeocode(lat, lng) {
    try {
        const res  = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`,
            { headers: { 'Accept-Language': 'id' } }
        );
        const data = await res.json();
        const addr = data.address || {};
        return {
            full:        data.display_name || '',
            road:        addr.road || addr.pedestrian || '',
            village:     addr.village || addr.suburb || addr.neighbourhood || '',
            subdistrict: addr.city_district || addr.town || '',
            city:        addr.city || addr.county || '',
            postcode:    addr.postcode || ''
        };
    } catch {
        return { full: `${lat.toFixed(5)}, ${lng.toFixed(5)}`, road: '', village: '', subdistrict: '', city: '', postcode: '' };
    }
}

function formatRupiah(num) {
    if (num === null || num === undefined || num === '') return '—';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function formatDate(d) {
    if (!d) return '—';
    const dt = new Date(d);
    if (isNaN(dt)) return d;
    return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

function calcAge(tglLahir) {
    if (!tglLahir) return null;
    const today = new Date(), birth = new Date(tglLahir);
    let age = today.getFullYear() - birth.getFullYear();
    if (today < new Date(today.getFullYear(), birth.getMonth(), birth.getDate())) age--;
    return age;
}

// ============================================================
// AID STATUS LOGIC
// ============================================================
function getAidStatus(house) {
    if (!house.hasData) return 'outside';
    if (house.aidStatus === 'helped') return 'helped';
    for (const c of centers) {
        if (haversineDistance(house.lat, house.lng, c.lat, c.lng) <= c.radius) return 'not_helped';
    }
    return 'outside';
}

function getCoveringCenters(house) {
    return centers.filter(c => haversineDistance(house.lat, house.lng, c.lat, c.lng) <= c.radius);
}

function findNearestCenter(house) {
    let nearest = null, minDist = Infinity;
    centers.forEach(c => {
        const d = haversineDistance(house.lat, house.lng, c.lat, c.lng);
        if (d < minDist) { minDist = d; nearest = c; }
    });
    return { center: nearest, distance: Math.round(minDist) };
}

function updateAllHouseIcons() {
    houses.forEach(h => {
        const status = getAidStatus(h);
        if (h.aidStatus !== 'helped') h.aidStatus = (status === 'not_helped') ? 'not_helped' : 'outside';
        h.marker.setIcon(createHouseIcon(h.aidStatus, h.hasData));
    });
    updateStats();
}

// ============================================================
// STATS
// ============================================================
function updateStats() {
    const total   = houses.filter(h => h.hasData).length;
    const helped  = houses.filter(h => h.aidStatus === 'helped').length;
    const inside  = houses.filter(h => getAidStatus(h) === 'not_helped').length;
    const outside = houses.filter(h => hasData && getAidStatus(h) === 'outside').length;

    document.getElementById('statTotal').textContent   = houses.length;
    document.getElementById('statHelped').textContent  = helped;
    document.getElementById('statInside').textContent  = inside;
    document.getElementById('statOutside').textContent = houses.filter(h => h.hasData && getAidStatus(h) === 'outside').length;
}

// ============================================================
// RADIUS DRAG HANDLE
// ============================================================
function addRadiusHandle(centerObj) {
    if (centerObj.handle) map.removeLayer(centerObj.handle);
    const tooltip = document.getElementById('radiusTooltip');
    const handle  = L.marker(radiusPoint(centerObj.lat, centerObj.lng, centerObj.radius), {
        icon: createHandleIcon(), draggable: true, zIndexOffset: 500
    }).addTo(map);

    handle.on('drag', function(e) {
        if (!can('canDragRadius')) return;
        centerObj.radius = Math.max(50, Math.round(
            haversineDistance(centerObj.lat, centerObj.lng, e.target.getLatLng().lat, e.target.getLatLng().lng)
        ));
        centerObj.circle.setRadius(centerObj.radius);
        tooltip.textContent = `⭕ Radius: ${centerObj.radius} m`;
        tooltip.classList.remove('hidden');
        updateAllHouseIcons(); updateSidebar();
    });
    handle.on('dragend', function() {
        if (!can('canDragRadius')) { handle.setLatLng(radiusPoint(centerObj.lat, centerObj.lng, centerObj.radius)); return; }
        handle.setLatLng(radiusPoint(centerObj.lat, centerObj.lng, centerObj.radius));
        tooltip.classList.add('hidden');
        saveCenterBackend(centerObj, true);
        if (centerObj.marker.isPopupOpen()) centerObj.marker.setPopupContent(buildCenterPopup(centerObj));
    });
    centerObj.handle = handle;
}

// ============================================================
// POPUPS
// ============================================================
function buildHouseMapPopup(house) {
    const status   = getAidStatus(house);
    const badgeCls = !house.hasData ? 'nodata' : status;
    const badgeTxt = !house.hasData ? 'Belum ada data'
                   : status === 'helped'     ? 'Sudah Dibantu'
                   : status === 'not_helped' ? 'Dalam Radius'
                   : 'Luar Radius';

    const kkName = house.anggota?.[0]?.nama || '';

    let actionBtns = '';
    if (house.hasData) {
        actionBtns = `<button class="popup-btn primary" onclick="openDetailModal(${house.id})">📋 Detail</button>`;
        if (can('canEdit')) actionBtns += `<button class="popup-btn orange" onclick="openHouseModal(${house.id})">✏️ Edit</button>`;
    } else {
        if (can('canEdit')) actionBtns = `<button class="popup-btn orange" style="flex:2" onclick="openHouseModal(${house.id})">➕ Isi Data</button>`;
        else actionBtns = `<span style="font-size:11px;color:var(--text-muted);padding:4px">Belum ada data</span>`;
    }

    const deleteBtn = can('canDelete')
        ? `<button class="popup-btn" style="background:var(--danger-light);color:var(--danger);border:1px solid #fca5a5;" onclick="deleteHouse(${house.id})">🗑</button>`
        : '';

    return `<div class="map-popup">
        <div class="map-popup-badge ${badgeCls}">● ${badgeTxt}</div>
        <div class="map-popup-title">🏠 Rumah #${house.id}${kkName ? ' — ' + kkName : ''}</div>
        <div class="map-popup-addr">${house.address || '<em style="color:var(--text-muted)">Mengambil alamat...</em>'}</div>
    </div>
    <div class="map-popup-actions">${actionBtns}${deleteBtn}</div>`;
}

function buildCenterPopup(c) {
    const covered = houses.filter(h => haversineDistance(h.lat, h.lng, c.lat, c.lng) <= c.radius);
    const helped  = covered.filter(h => h.aidStatus === 'helped').length;
    const info    = CENTER_TYPE_MAP[getCenterType(c.name)] || CENTER_TYPE_MAP['default'];

    const kasEditHtml = can('canEditKas') ? `
        <button onclick="toggleEditKas(${c.id})" style="margin-left:6px;padding:2px 8px;background:white;border:1px solid #86efac;border-radius:6px;font-size:10px;cursor:pointer;color:#16a34a;font-weight:600;">✏️ Edit</button>` : '';

    const kasFormHtml = can('canEditKas') ? `
        <div id="kasEditForm_${c.id}" style="display:none;margin-bottom:8px;padding:8px 10px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;">
            <div style="font-size:10px;font-weight:700;color:#16a34a;margin-bottom:6px">Edit Kas</div>
            <div style="display:flex;gap:6px;align-items:center;">
                <input type="number" id="kasInput_${c.id}" value="${c.kas}" min="0" step="1000"
                    style="flex:1;padding:6px 8px;border:1.5px solid #86efac;border-radius:6px;font-size:12px;font-weight:600;color:#16a34a;outline:none;"/>
                <button onclick="saveKas(${c.id})" style="padding:6px 10px;background:#16a34a;color:white;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">Simpan</button>
                <button onclick="toggleEditKas(${c.id})" style="padding:6px 8px;background:white;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;cursor:pointer;">Batal</button>
            </div>
        </div>` : '';

    const editBtn = can('canEdit')
        ? `<div style="padding:5px 13px 0;">
               <button onclick="openCenterEditModal(${c.id})" style="width:100%;padding:7px;background:#fff7ed;color:var(--orange);border:1px solid #fdba74;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">✏️ Edit Rumah Ibadah</button>
           </div>` : '';

    const deleteBtn = can('canDelete')
        ? `<div style="padding:6px 13px 10px;">
               <button onclick="deleteCenter(${c.id})" style="width:100%;padding:7px;background:var(--danger-light);color:var(--danger);border:1px solid #fca5a5;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">🗑 Hapus</button>
           </div>` : '';

    return `<div class="center-popup">
        <div class="center-popup-header">
            <div class="center-popup-icon">${info.emoji}</div>
            <div class="center-popup-title">${c.name}</div>
        </div>
        <div class="center-popup-addr">📍 ${c.address || '—'}</div>
        <div class="kas-badge" id="kasBadge_${c.id}">
            <span class="kas-label">💰 Kas</span>
            <span class="kas-value" id="kasVal_${c.id}">${formatRupiah(c.kas)}</span>
            ${kasEditHtml}
        </div>
        ${kasFormHtml}
        <div class="radius-info-box">
            <span>⭕ Radius</span><span class="radius-val">${c.radius} m</span>
        </div>
        <div class="radius-info-box" style="background:#fef9c3;border-color:#fde047;color:#854d0e;">
            <span>🏠 Dalam radius</span><span class="radius-val">${covered.length} (${helped} dibantu)</span>
        </div>
        <div class="radius-drag-hint">${can('canDragRadius') ? '💡 Drag titik biru untuk ubah radius' : '👁 Radius hanya bisa dilihat'}</div>
    </div>${editBtn}${deleteBtn}`;
}

// ============================================================
// KAS EDIT (popup inline)
// ============================================================
function toggleEditKas(centerId) {
    const form  = document.getElementById(`kasEditForm_${centerId}`);
    const badge = document.getElementById(`kasBadge_${centerId}`);
    if (!form) return;
    const visible = form.style.display !== 'none';
    form.style.display  = visible ? 'none' : 'block';
    badge.style.display = visible ? 'flex' : 'none';
    if (!visible) setTimeout(() => document.getElementById(`kasInput_${centerId}`)?.focus(), 50);
}

function saveKas(centerId) {
    const center = centers.find(c => c.id === centerId);
    if (!center) return;
    center.kas = parseFloat(document.getElementById(`kasInput_${centerId}`)?.value) || 0;
    const kasVal = document.getElementById(`kasVal_${centerId}`);
    if (kasVal) kasVal.textContent = formatRupiah(center.kas);
    toggleEditKas(centerId);
    updateCenterList();
    saveCenterBackend(center, true);
}

// ============================================================
// CENTER EDIT MODAL
// ============================================================
function openCenterEditModal(centerId) {
    if (!can('canEdit')) { alert('Role Anda tidak dapat mengedit data.'); return; }
    const c = centers.find(c => c.id === centerId);
    if (!c) return;
    map.closePopup();

    document.getElementById('ce_id').value      = c.id;
    document.getElementById('ce_name').value    = c.name;
    document.getElementById('ce_address').value = c.address || '';
    document.getElementById('ce_kas').value     = c.kas || 0;
    document.getElementById('ce_radius').value  = c.radius;

    const sel   = document.getElementById('ce_type');
    const type  = getCenterType(c.name);
    const match = Array.from(sel.options).find(o => o.value === type);
    sel.value   = match ? type : 'default';

    document.getElementById('centerEditModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('ce_name')?.focus(), 100);
}
function closeCenterEditModal() { document.getElementById('centerEditModal').classList.add('hidden'); }

function saveCenterEdit() {
    const id      = parseInt(document.getElementById('ce_id')?.value);
    const name    = document.getElementById('ce_name')?.value?.trim();
    const address = document.getElementById('ce_address')?.value?.trim();
    const kas     = parseFloat(document.getElementById('ce_kas')?.value) || 0;
    const radius  = parseInt(document.getElementById('ce_radius')?.value) || DEFAULT_RADIUS;

    if (!name) { document.getElementById('ce_name').focus(); return; }
    if (radius < 50) { alert('Radius minimal 50 meter!'); return; }

    const center = centers.find(c => c.id === id);
    if (!center) return;

    center.name    = name;
    center.address = address;
    center.kas     = kas;
    center.radius  = radius;

    center.marker.setIcon(createCenterIcon(name));
    center.circle.setRadius(radius);
    if (center.handle) center.handle.setLatLng(radiusPoint(center.lat, center.lng, radius));
    center.marker.setPopupContent(buildCenterPopup(center));

    closeCenterEditModal();
    updateAllHouseIcons();
    updateSidebar();
    saveCenterBackend(center, true);
}

// ============================================================
// ADD MODES
// ============================================================
function startAddingCenter() {
    if (!can('canAdd')) { alert('Role Anda tidak dapat menambah data.'); return; }
    if (isAddingCenter) { cancelModes(); return; }
    cancelModes(); isAddingCenter = true; setModeUI('center');
}
function startAddingHouse() {
    if (!can('canAdd')) { alert('Role Anda tidak dapat menambah data.'); return; }
    if (isAddingHouse) { cancelModes(); return; }
    cancelModes(); isAddingHouse = true; setModeUI('house');
}
function setModeUI(mode) {
    const badge = document.getElementById('modeIndicator');
    badge.classList.remove('hidden', 'house-mode');
    map.getContainer().classList.add('adding-mode');
    if (mode === 'center') {
        document.getElementById('modeIndicatorText').textContent = 'Klik peta untuk menempatkan rumah ibadah';
        document.getElementById('btnAddCenter').classList.add('active');
        document.getElementById('btnAddCenter').textContent = '✕ Batalkan';
    } else {
        badge.classList.add('house-mode');
        document.getElementById('modeIndicatorText').textContent = 'Klik peta untuk menempatkan titik rumah miskin';
        document.getElementById('btnAddHouse').classList.add('active');
        document.getElementById('btnAddHouse').textContent = '✕ Batalkan';
    }
}
function cancelModes() {
    isAddingCenter = isAddingHouse = false;
    document.getElementById('modeIndicator').classList.add('hidden');
    document.getElementById('modeIndicator').classList.remove('house-mode');
    document.getElementById('btnAddCenter').classList.remove('active');
    document.getElementById('btnAddCenter').innerHTML = '🕌 Tambah Rumah Ibadah';
    document.getElementById('btnAddHouse').classList.remove('active');
    document.getElementById('btnAddHouse').innerHTML = '🏠 Tambah Rumah Miskin';
    map.getContainer().classList.remove('adding-mode');
}

map.on('click', async function(e) {
    if (!isAddingCenter && !isAddingHouse) return;
    const { lat, lng } = e.latlng;
    if (isAddingCenter) { cancelModes(); openCenterForm(lat, lng, e.latlng); }
    else { cancelModes(); placeHousePin(lat, lng); }
});

// ============================================================
// CENTER FORM (map popup)
// ============================================================
async function openCenterForm(lat, lng, latlng) {
    const popup = L.popup({ maxWidth: 290, closeOnClick: false })
        .setLatLng(latlng)
        .setContent(`
            <div class="form-popup">
                <h3>🕌 Tambah Rumah Ibadah</h3>
                <div class="form-group">
                    <label class="form-label">Jenis</label>
                    <select class="form-input" id="fpType">
                        <option value="masjid">🕌 Masjid / Musholla</option>
                        <option value="gereja katedral">⛪ Gereja Katolik</option>
                        <option value="gereja protestan">✝️ Gereja Protestan</option>
                        <option value="vihara">🛕 Vihara / Klenteng / Pura</option>
                        <option value="sinagog">✡️ Sinagog</option>
                        <option value="default">🏛️ Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nama *</label>
                    <input class="form-input" id="fpName" type="text" placeholder="Nama rumah ibadah..." autofocus/>
                </div>
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <div id="fpAddrLoading" style="font-size:11px;color:var(--text-muted);padding:4px 0">⏳ Mengambil alamat...</div>
                    <input class="form-input" id="fpAddr" type="text" placeholder="Alamat..." style="display:none"/>
                </div>
                <div class="form-group">
                    <label class="form-label">Kas / Dana (Rp)</label>
                    <input class="form-input" id="fpKas" type="number" placeholder="0" min="0" step="1000"/>
                </div>
                <div class="form-group">
                    <label class="form-label">Radius Awal (meter)</label>
                    <input class="form-input" id="fpRadius" type="number" value="${DEFAULT_RADIUS}" min="50" step="10"/>
                </div>
                <button class="form-submit" onclick="submitCenter(${lat},${lng})">💾 Simpan</button>
                <button class="form-cancel" onclick="map.closePopup()" style="width:100%;margin-top:6px;padding:8px;border:1px solid var(--border);border-radius:9px;background:var(--bg);cursor:pointer;font-size:12px;">Batal</button>
            </div>`)
        .openOn(map);

    const geo = await reverseGeocode(lat, lng);
    const loadEl = document.getElementById('fpAddrLoading');
    const addrEl = document.getElementById('fpAddr');
    if (loadEl) loadEl.style.display = 'none';
    if (addrEl) { addrEl.style.display = 'block'; addrEl.value = geo.full; }
}

async function submitCenter(lat, lng) {
    const name    = document.getElementById('fpName')?.value?.trim();
    const address = document.getElementById('fpAddr')?.value?.trim() || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    const kas     = parseFloat(document.getElementById('fpKas')?.value) || 0;
    const radius  = parseInt(document.getElementById('fpRadius')?.value) || DEFAULT_RADIUS;
    if (!name) { document.getElementById('fpName')?.focus(); return; }
    map.closePopup();
    addCenter({ name, address, kas, lat, lng, radius });
}

function addCenter(data) {
    const marker = L.marker([data.lat, data.lng], {
        icon: createCenterIcon(data.name),
        draggable: can('canEdit'),
        zIndexOffset: 200
    }).addTo(map);

    const circle = L.circle([data.lat, data.lng], {
        radius: data.radius, color: '#3b82f6', weight: 1.5,
        fillColor: '#3b82f6', fillOpacity: 0.07, dashArray: '6 4'
    }).addTo(map);

    const centerObj = {
        id: data.id || null, name: data.name, address: data.address,
        kas: data.kas || 0, lat: data.lat, lng: data.lng,
        radius: data.radius, marker, circle, handle: null
    };

    marker.bindPopup(() => buildCenterPopup(centerObj), { maxWidth: 260 });
    marker.on('popupopen', () => marker.setPopupContent(buildCenterPopup(centerObj)));
    marker.on('dragend', function(e) {
        centerObj.lat = e.target.getLatLng().lat;
        centerObj.lng = e.target.getLatLng().lng;
        circle.setLatLng([centerObj.lat, centerObj.lng]);
        if (centerObj.handle) centerObj.handle.setLatLng(radiusPoint(centerObj.lat, centerObj.lng, centerObj.radius));
        updateAllHouseIcons(); updateSidebar();
        saveCenterBackend(centerObj, true);
    });

    addRadiusHandle(centerObj);
    if (!can('canDragRadius')) centerObj.handle?.dragging?.disable();
    if (!can('canEdit')) marker.dragging?.disable();

    centers.push(centerObj);

    if (!data.id) {
        saveCenterBackend(centerObj, false);
    } else {
        marker.openPopup();
        updateAllHouseIcons(); updateSidebar();
    }
}

async function saveCenterBackend(centerObj, isUpdate) {
    const body = new URLSearchParams({
        id: centerObj.id || 0,
        name: centerObj.name, address: centerObj.address || '',
        kas: centerObj.kas, lat: centerObj.lat, lng: centerObj.lng,
        radius: centerObj.radius, update: isUpdate ? '1' : '0'
    });
    try {
        const res  = await fetch('simpan_pusat.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success && !centerObj.id) {
            centerObj.id = data.id;
            centerObj.marker.setPopupContent(buildCenterPopup(centerObj));
            centerObj.marker.openPopup();
            updateAllHouseIcons(); updateSidebar();
        }
    } catch (e) { console.error('saveCenterBackend', e); }
}

// ============================================================
// HOUSE PIN
// ============================================================
async function placeHousePin(lat, lng) {
    const marker = L.marker([lat, lng], { icon: createHouseIcon(null, false), zIndexOffset: 100 }).addTo(map);

    const house = {
        id: null, lat, lng, address: '',
        rt: '', rw: '', kelurahan: '',
        statusMiskin: '', jumlahAnggota: 0, anggota: [],
        aidStatus: 'outside', hasData: false,
        marker, _geoData: null
    };
    houses.push(house);

    marker.bindPopup(() => buildHouseMapPopup(house), { maxWidth: 270 });
    marker.on('popupopen', () => marker.setPopupContent(buildHouseMapPopup(house)));
    marker.openPopup();

    try {
        const geo = await reverseGeocode(lat, lng);
        house.address  = geo.full || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
        house._geoData = geo;
    } catch {
        house.address = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
    }

    marker.setPopupContent(buildHouseMapPopup(house));
    updateStats(); updateHouseList();
    await saveHouseBackend(house, false);
}

async function saveHouseBackend(house, isUpdate) {
    const body = new URLSearchParams({
        id:             house.id || 0,
        lat:            house.lat,
        lng:            house.lng,
        address:        house.address || '',
        rt:             house.rt || '',
        rw:             house.rw || '',
        kelurahan:      house.kelurahan || '',
        status_miskin:  house.statusMiskin || '',
        jumlah_anggota: house.jumlahAnggota || 0,
        anggota:        JSON.stringify(house.anggota || []),
        aid_status:     house.aidStatus || 'outside',
        has_data:       house.hasData ? 1 : 0,
        update:         isUpdate ? '1' : '0'
    });
    try {
        const res  = await fetch('simpan_rumah.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success && !house.id) {
            house.id = data.id;
            house.marker.setPopupContent(buildHouseMapPopup(house));
            updateHouseList();
        }
    } catch (e) { console.error('saveHouseBackend', e); }
}

// ============================================================
// HOUSE DATA MODAL
// ============================================================
function openHouseModal(houseId) {
    if (!can('canEdit')) { alert('Role Anda tidak dapat mengedit data.'); return; }
    const house = houses.find(h => h.id === houseId);
    if (!house) return;
    map.closePopup();

    const covering  = getCoveringCenters(house);
    const nearestR  = findNearestCenter(house);
    const geo       = house._geoData || {};

    const centersHtml = covering.length > 0
        ? covering.map(c => {
            const info = CENTER_TYPE_MAP[getCenterType(c.name)] || CENTER_TYPE_MAP['default'];
            return `<span style="display:inline-flex;align-items:center;gap:4px;background:var(--primary-light);color:var(--primary);padding:2px 8px;border-radius:12px;font-size:10px;font-weight:600;margin:2px;">${info.emoji} ${c.name}</span>`;
          }).join('')
        : `<span style="font-size:11px;color:var(--text-muted)">Tidak ada — Terdekat: ${nearestR.center ? nearestR.center.name + ' (' + nearestR.distance + ' m)' : '—'}</span>`;

    document.getElementById('houseModalBody').innerHTML = `
        <div class="modal-section">
            <div class="modal-section-title">📍 Informasi Lokasi</div>
            <div class="info-box">
                <div class="info-box-row"><span class="info-box-label">Alamat</span><span class="info-box-val">${geo.road || house.address?.split(',')[0] || '—'}</span></div>
                <div class="info-box-row"><span class="info-box-label">Kelurahan</span><span class="info-box-val">${geo.village || '—'}</span></div>
                <div class="info-box-row"><span class="info-box-label">Kecamatan</span><span class="info-box-val">${geo.subdistrict || '—'}</span></div>
                <div class="info-box-row"><span class="info-box-label">Kota</span><span class="info-box-val">${geo.city || '—'}</span></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">RT</label>
                    <input class="form-input" id="hm_rt" type="text" placeholder="001" value="${house.rt || ''}"/>
                </div>
                <div class="form-group">
                    <label class="form-label">RW</label>
                    <input class="form-input" id="hm_rw" type="text" placeholder="001" value="${house.rw || ''}"/>
                </div>
                <div class="form-group" style="flex:2">
                    <label class="form-label">Kelurahan</label>
                    <input class="form-input" id="hm_kel" type="text" value="${house.kelurahan || geo.village || ''}"/>
                </div>
            </div>
        </div>
        <div class="modal-section">
            <div class="modal-section-title">🕌 Rumah Ibadah yang Menangani</div>
            <div style="margin-bottom:8px">${centersHtml}</div>
        </div>
        <div class="modal-section">
            <div class="modal-section-title">📊 Status Kemiskinan</div>
            <div class="status-pills">
                <div class="status-pill" data-val="sangat_miskin" onclick="selectStatusMiskin('sangat_miskin')">😢 Sangat Miskin</div>
                <div class="status-pill" data-val="miskin" onclick="selectStatusMiskin('miskin')">😟 Miskin</div>
                <div class="status-pill" data-val="tidak_miskin" onclick="selectStatusMiskin('tidak_miskin')">😊 Tidak Miskin</div>
            </div>
            <input type="hidden" id="hm_statusMiskin" value="${house.statusMiskin || ''}"/>
        </div>
        <div class="modal-section">
            <div class="modal-section-title">👨‍👩‍👧 Anggota Keluarga</div>
            <div class="form-group">
                <label class="form-label">Jumlah Anggota Keluarga *</label>
                <input class="form-input" id="hm_jumlah" type="number" min="1" max="20"
                    placeholder="Masukkan jumlah..." value="${house.jumlahAnggota || ''}"
                    onchange="renderMemberForms(${houseId})"/>
            </div>
            <div id="memberFormsContainer"></div>
        </div>`;

    if (house.jumlahAnggota > 0) renderMemberForms(houseId);
    if (house.statusMiskin) setTimeout(() => selectStatusMiskin(house.statusMiskin), 0);

    document.getElementById('houseModal').classList.remove('hidden');

    const modal  = document.getElementById('houseModal');
    const oldBar = modal.querySelector('.modal-actions');
    if (oldBar) oldBar.remove();
    const bar = document.createElement('div');
    bar.className = 'modal-actions';
    bar.innerHTML = `
        <button class="btn-modal-cancel" onclick="closeHouseModal()">Batal</button>
        <button class="btn-modal-save" onclick="saveHouseData(${houseId})">💾 Simpan Data</button>`;
    modal.querySelector('.modal-box').appendChild(bar);
}

function closeHouseModal() { document.getElementById('houseModal').classList.add('hidden'); }

function selectStatusMiskin(val) {
    document.querySelectorAll('.status-pill').forEach(el => el.classList.remove('selected', 'sangat_miskin', 'miskin', 'tidak_miskin'));
    document.querySelector(`.status-pill[data-val="${val}"]`)?.classList.add('selected', val);
    const hidden = document.getElementById('hm_statusMiskin');
    if (hidden) hidden.value = val;
}

function renderMemberForms(houseId) {
    const house     = houses.find(h => h.id === houseId);
    const jumlah    = parseInt(document.getElementById('hm_jumlah')?.value) || 0;
    const container = document.getElementById('memberFormsContainer');
    if (!container) return;
    container.innerHTML = '';
    for (let i = 0; i < Math.min(jumlah, 20); i++) {
        container.insertAdjacentHTML('beforeend', buildMemberForm(i, house?.anggota?.[i] || {}));
    }
}

function buildMemberForm(i, data = {}) {
    const statusOpts  = ['Kepala Keluarga', 'Istri/Suami', 'Anak', 'Orang Tua', 'Saudara', 'Lainnya']
        .map(s => `<option ${data.statusAnggota === s ? 'selected' : ''}>${s}</option>`).join('');
    const pekerjaanOpts = ['Tidak Bekerja', 'Pelajar/Mahasiswa', 'Buruh/Karyawan', 'Wiraswasta', 'PNS/TNI/Polri', 'Petani/Nelayan', 'Lainnya']
        .map(p => `<option ${data.pekerjaan === p ? 'selected' : ''}>${p}</option>`).join('');
    const showSalary = data.pekerjaan && !['Tidak Bekerja', 'Pelajar/Mahasiswa'].includes(data.pekerjaan);
    const displayDate = data.tglLahir ? formatDate(data.tglLahir) : '';

    return `<div class="member-card">
        <div class="member-card-header"><div class="member-num">${i + 1}</div> Anggota ke-${i + 1}</div>
        <div class="member-card-body">
            <div class="form-row">
                <div class="form-group" style="flex:2">
                    <label class="form-label">Nama Lengkap</label>
                    <input class="form-input" id="m_nama_${i}" type="text" value="${data.nama || ''}" placeholder="Nama..."/>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select class="form-input" id="m_status_${i}">${statusOpts}</select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tanggal Lahir</label>
                    <div class="date-input-wrap">
                        <button type="button" class="date-display-btn" id="m_tgl_btn_${i}"
                            onclick="openDatepicker('m_tgl_${i}', 'm_tgl_btn_${i}')">${displayDate || '📅 Pilih tanggal'}</button>
                        <input type="hidden" id="m_tgl_${i}" value="${data.tglLahir || ''}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Pekerjaan</label>
                    <select class="form-input" id="m_pkrj_${i}" onchange="toggleSalary(${i})">${pekerjaanOpts}</select>
                </div>
            </div>
            <div class="form-row salary-row${showSalary ? ' visible' : ''}" id="salary_row_${i}">
                <div class="form-group" style="width:100%">
                    <label class="form-label">Penghasilan per Bulan (Rp)</label>
                    <input class="form-input" id="m_gaji_${i}" type="number" min="0" step="50000" value="${data.gaji || ''}" placeholder="0"/>
                </div>
            </div>
        </div>
    </div>`;
}

function toggleSalary(i) {
    const pekerjaan = document.getElementById(`m_pkrj_${i}`)?.value;
    const row = document.getElementById(`salary_row_${i}`);
    if (!row) return;
    const hide = ['Tidak Bekerja', 'Pelajar/Mahasiswa'].includes(pekerjaan);
    row.classList.toggle('visible', !hide);
}

async function saveHouseData(houseId) {
    const house = houses.find(h => h.id === houseId);
    if (!house) return;

    house.rt          = document.getElementById('hm_rt')?.value?.trim() || '';
    house.rw          = document.getElementById('hm_rw')?.value?.trim() || '';
    house.kelurahan   = document.getElementById('hm_kel')?.value?.trim() || '';
    house.statusMiskin = document.getElementById('hm_statusMiskin')?.value || '';
    house.jumlahAnggota = parseInt(document.getElementById('hm_jumlah')?.value) || 0;

    house.anggota = [];
    for (let i = 0; i < house.jumlahAnggota; i++) {
        house.anggota.push({
            nama:         document.getElementById(`m_nama_${i}`)?.value?.trim() || '',
            statusAnggota: document.getElementById(`m_status_${i}`)?.value || '',
            tglLahir:     document.getElementById(`m_tgl_${i}`)?.value || '',
            pekerjaan:    document.getElementById(`m_pkrj_${i}`)?.value || '',
            gaji:         parseFloat(document.getElementById(`m_gaji_${i}`)?.value) || 0
        });
    }
    house.hasData = house.jumlahAnggota > 0 || !!house.statusMiskin;

    const status = getAidStatus(house);
    if (house.aidStatus !== 'helped') house.aidStatus = status;

    house.marker.setIcon(createHouseIcon(house.aidStatus, house.hasData));
    house.marker.setPopupContent(buildHouseMapPopup(house));

    closeHouseModal();
    updateStats(); updateHouseList();
    await saveHouseBackend(house, true);
}

// ============================================================
// DETAIL MODAL
// ============================================================
function openDetailModal(houseId) {
    const house = houses.find(h => h.id === houseId);
    if (!house) return;
    map.closePopup();

    const covering = getCoveringCenters(house);
    const status   = getAidStatus(house);
    const nearestR = findNearestCenter(house);

    const statusLabels = {
        sangat_miskin: '😢 Sangat Miskin',
        miskin:        '😟 Miskin',
        tidak_miskin:  '😊 Tidak Miskin',
        '':            '—'
    };
    const aidLabels = {
        helped:     '✅ Sudah Dibantu',
        not_helped: '⏳ Belum Dibantu',
        outside:    '🟢 Luar Radius'
    };

    const membersHtml = house.anggota.length > 0
        ? `<table class="member-table"><thead><tr>
               <th>#</th><th>Nama</th><th>Status</th><th>Usia</th><th>Pekerjaan</th><th>Penghasilan</th>
           </tr></thead><tbody>` +
          house.anggota.map((m, i) => {
              const age = calcAge(m.tglLahir);
              return `<tr>
                  <td>${i + 1}</td>
                  <td>${m.nama || '—'}</td>
                  <td>${m.statusAnggota || '—'}</td>
                  <td>${age !== null ? age + ' th' : '—'}</td>
                  <td>${m.pekerjaan || '—'}</td>
                  <td>${m.gaji ? formatRupiah(m.gaji) : '—'}</td>
              </tr>`;
          }).join('') + `</tbody></table>`
        : '<div class="empty-state">Belum ada data anggota keluarga.</div>';

    const nearestInfo = nearestR.center
        ? `${nearestR.center.name} (${nearestR.distance} m)`
        : '—';

    const aidBtns = can('canEdit') ? `
        <div class="aid-btn-row">
            <button class="aid-btn${house.aidStatus === 'helped' ? ' active-helped' : ''}" onclick="setAidStatus(${house.id}, 'helped', '${covering[0]?.id || 0}')">✅ Sudah Dibantu</button>
            <button class="aid-btn${house.aidStatus === 'not_helped' ? ' active-not_helped' : ''}" onclick="setAidStatus(${house.id}, 'not_helped', '${covering[0]?.id || 0}')">⏳ Belum Dibantu</button>
            <button class="aid-btn${house.aidStatus === 'outside' ? ' active-outside' : ''}" onclick="setAidStatus(${house.id}, 'outside', '0')">🟢 Luar Radius</button>
        </div>` : '';

    document.getElementById('detailModalBody').innerHTML = `
        <div class="detail-section">
            <div class="detail-section-title">📍 Lokasi</div>
            <div class="detail-grid">
                <div class="detail-kv"><div class="detail-key">Alamat</div><div class="detail-val">${house.address || '—'}</div></div>
                <div class="detail-kv"><div class="detail-key">RT / RW</div><div class="detail-val">${house.rt || '—'} / ${house.rw || '—'}</div></div>
                <div class="detail-kv"><div class="detail-key">Kelurahan</div><div class="detail-val">${house.kelurahan || '—'}</div></div>
                <div class="detail-kv"><div class="detail-key">Koordinat</div><div class="detail-val" style="font-family:var(--mono);font-size:11px">${house.lat.toFixed(5)}, ${house.lng.toFixed(5)}</div></div>
            </div>
        </div>
        <div class="detail-section">
            <div class="detail-section-title">📊 Status Bantuan</div>
            <div class="detail-grid">
                <div class="detail-kv"><div class="detail-key">Kemiskinan</div><div class="detail-val">${statusLabels[house.statusMiskin] || '—'}</div></div>
                <div class="detail-kv"><div class="detail-key">Status Bantuan</div><div class="detail-val">${aidLabels[status] || '—'}</div></div>
                <div class="detail-kv"><div class="detail-key">Rumah Ibadah</div><div class="detail-val">${covering.length > 0 ? covering.map(c => c.name).join(', ') : 'Luar radius'}</div></div>
                <div class="detail-kv"><div class="detail-key">Terdekat</div><div class="detail-val">${nearestInfo}</div></div>
            </div>
            ${aidBtns}
        </div>
        <div class="detail-section">
            <div class="detail-section-title">👨‍👩‍👧 Anggota Keluarga (${house.jumlahAnggota} orang)</div>
            ${membersHtml}
        </div>`;

    document.getElementById('detailModal').classList.remove('hidden');
}
function closeDetailModal() { document.getElementById('detailModal').classList.add('hidden'); }

async function setAidStatus(houseId, newStatus, centerId) {
    const house = houses.find(h => h.id === houseId);
    if (!house) return;
    house.aidStatus = newStatus;
    house.marker.setIcon(createHouseIcon(house.aidStatus, house.hasData));
    house.marker.setPopupContent(buildHouseMapPopup(house));
    updateStats(); updateHouseList();
    closeDetailModal();

    try {
        const body = new URLSearchParams({ house_id: houseId, aid_status: newStatus, center_id: centerId });
        await fetch('update_status.php', { method: 'POST', body });
    } catch (e) { console.error('setAidStatus', e); }
}

// ============================================================
// DELETE
// ============================================================
function deleteHouse(houseId) {
    if (!can('canDelete')) return;
    if (!confirm('Hapus data rumah ini?')) return;
    const idx = houses.findIndex(h => h.id === houseId);
    if (idx === -1) return;
    map.removeLayer(houses[idx].marker);
    houses.splice(idx, 1);
    updateStats(); updateHouseList();
    fetch('hapus_rumah.php', { method: 'POST', body: new URLSearchParams({ id: houseId }) });
}

function deleteCenter(centerId) {
    if (!can('canDelete')) return;
    if (!confirm('Hapus rumah ibadah ini beserta data terkait?')) return;
    const idx = centers.findIndex(c => c.id === centerId);
    if (idx === -1) return;
    map.removeLayer(centers[idx].marker);
    map.removeLayer(centers[idx].circle);
    if (centers[idx].handle) map.removeLayer(centers[idx].handle);
    centers.splice(idx, 1);
    map.closePopup();
    updateAllHouseIcons(); updateSidebar();
    fetch('hapus_pusat.php', { method: 'POST', body: new URLSearchParams({ id: centerId }) });
}

// ============================================================
// RESET
// ============================================================
function confirmReset() {
    if (!can('canReset')) return;
    if (!confirm('RESET semua data? Tindakan ini tidak dapat dibatalkan!')) return;
    centers.forEach(c => {
        map.removeLayer(c.marker);
        map.removeLayer(c.circle);
        if (c.handle) map.removeLayer(c.handle);
    });
    houses.forEach(h => map.removeLayer(h.marker));
    centers = []; houses = []; reports = [];
    updateStats(); updateSidebar();
    fetch('reset.php', { method: 'POST' });
}

// ============================================================
// SIDEBAR
// ============================================================
function updateSidebar() { updateCenterList(); updateHouseList(); }

function filterCenters() {
    const query = document.getElementById('searchCenter')?.value?.toLowerCase() || '';
    const sort  = document.getElementById('sortCenter')?.value || '';

    let list = centers.filter(c => c.name.toLowerCase().includes(query) || (c.address || '').toLowerCase().includes(query));

    if (sort === 'kas_desc') list.sort((a, b) => (b.kas || 0) - (a.kas || 0));
    else if (sort === 'kas_asc') list.sort((a, b) => (a.kas || 0) - (b.kas || 0));
    else if (sort === 'tanggungan_desc') list.sort((a, b) => countCovered(b) - countCovered(a));
    else if (sort === 'tanggungan_asc') list.sort((a, b) => countCovered(a) - countCovered(b));

    renderCenterList(list);
}

function countCovered(c) {
    return houses.filter(h => haversineDistance(h.lat, h.lng, c.lat, c.lng) <= c.radius).length;
}

function updateCenterList() {
    document.getElementById('centerCount').textContent = centers.length;
    filterCenters();
}

function renderCenterList(list) {
    const el = document.getElementById('centerList');
    if (list.length === 0) { el.innerHTML = '<div class="empty-state">Belum ada rumah ibadah.</div>'; return; }

    el.innerHTML = list.map(c => {
        const info     = CENTER_TYPE_MAP[getCenterType(c.name)] || CENTER_TYPE_MAP['default'];
        const covered  = countCovered(c);
        const helped   = houses.filter(h => haversineDistance(h.lat, h.lng, c.lat, c.lng) <= c.radius && h.aidStatus === 'helped').length;
        const delBtn   = can('canDelete') ? `<button class="center-item-del" onclick="event.stopPropagation();deleteCenter(${c.id})">🗑</button>` : '';
        return `<div class="center-item" onclick="focusCenter(${c.id})">
            <div class="center-item-icon">${info.emoji}</div>
            <div class="center-item-body">
                <div class="center-item-name">${c.name}</div>
                <div class="center-item-addr">${c.address || '—'}</div>
                <div class="center-item-meta">
                    <span class="meta-tag">⭕ ${c.radius}m</span>
                    <span class="meta-tag">🏠 ${covered}</span>
                    <span class="meta-tag">💰 ${formatRupiah(c.kas)}</span>
                    ${helped > 0 ? `<span class="meta-tag" style="background:#fef9c3;color:#854d0e">✅ ${helped}</span>` : ''}
                </div>
            </div>
            ${delBtn}
        </div>`;
    }).join('');
}

function focusCenter(centerId) {
    const c = centers.find(c => c.id === centerId);
    if (!c) return;
    map.setView([c.lat, c.lng], 16);
    c.marker.openPopup();
}

function filterHouses() {
    const query        = document.getElementById('searchHouse')?.value?.toLowerCase() || '';
    const filterStatus = document.getElementById('filterStatus')?.value || '';
    const filterMiskin = document.getElementById('filterMiskin')?.value || '';

    let list = houses.filter(h => {
        const kkName = h.anggota?.[0]?.nama?.toLowerCase() || '';
        const addr   = (h.address || '').toLowerCase();
        const matchQ = !query || kkName.includes(query) || addr.includes(query);
        const matchS = !filterStatus || getAidStatus(h) === filterStatus;
        const matchM = !filterMiskin || h.statusMiskin === filterMiskin;
        return matchQ && matchS && matchM;
    });

    renderHouseList(list);
}

function updateHouseList() {
    document.getElementById('houseCount').textContent = houses.length;
    filterHouses();
}

function renderHouseList(list) {
    const el = document.getElementById('houseList');
    if (list.length === 0) { el.innerHTML = '<div class="empty-state">Belum ada data.</div>'; return; }

    el.innerHTML = list.map(h => {
        const status  = getAidStatus(h);
        const kkName  = h.anggota?.[0]?.nama || `Rumah #${h.id}`;
        const addr    = h.kelurahan || h.address?.split(',')[0] || '—';
        const icon    = h.hasData ? (status === 'helped' ? '🏠' : status === 'not_helped' ? '🏚' : '🏠') : '📍';
        const delBtn  = can('canDelete') ? `<button class="house-item-del" onclick="event.stopPropagation();deleteHouse(${h.id})">🗑</button>` : '';
        const statusLabels = { helped: '✅ Dibantu', not_helped: '⏳ Belum', outside: '🟢 Luar', nodata: '📍 Pin' };
        const miskinLabels = { sangat_miskin: '😢 Sangat Miskin', miskin: '😟 Miskin', tidak_miskin: '😊 Tidak Miskin' };

        return `<div class="house-item status-${status}" onclick="focusHouse(${h.id})">
            <div class="house-item-icon">${icon}</div>
            <div class="house-item-body">
                <div class="house-item-name">${kkName}</div>
                <div class="house-item-addr">${addr}</div>
                <div class="house-item-tags">
                    <span class="tag tag-${h.hasData ? status : 'nodata'}">${statusLabels[h.hasData ? status : 'nodata'] || status}</span>
                    ${h.statusMiskin ? `<span class="tag tag-${h.statusMiskin}">${miskinLabels[h.statusMiskin]}</span>` : ''}
                </div>
            </div>
            ${delBtn}
        </div>`;
    }).join('');
}

function focusHouse(houseId) {
    const h = houses.find(h => h.id === houseId);
    if (!h) return;
    map.setView([h.lat, h.lng], 17);
    h.marker.openPopup();
}

// ============================================================
// REPORTS
// ============================================================
function updateReportBadge() {
    const badge = document.getElementById('navReportBadge');
    const count = can('canViewReport') ? reports.filter(r => r.status === 'baru').length : 0;
    if (badge) {
        badge.textContent = count;
        badge.classList.toggle('hidden', count === 0);
    }
}

function filterReports() {
    const query  = document.getElementById('searchReport')?.value?.toLowerCase() || '';
    const status = document.getElementById('filterReportStatus')?.value || '';
    const list   = reports.filter(r => {
        const matchQ = !query || r.text.toLowerCase().includes(query) || (r.name || '').toLowerCase().includes(query);
        const matchS = !status || r.status === status;
        return matchQ && matchS;
    });
    renderReportList(list);
}

function renderReportList(list) {
    if (!list) list = reports;
    document.getElementById('reportCount').textContent = reports.length;

    const el = document.getElementById('reportList');
    if (list.length === 0) { el.innerHTML = '<div class="empty-state" style="margin-top:20px">Belum ada laporan.</div>'; return; }

    el.innerHTML = list.map(r => {
        const statusBadge = `<span class="report-status ${r.status}">${r.status === 'baru' ? '🆕 Baru' : r.status === 'ditangani' ? '🔄 Ditangani' : '✅ Selesai'}</span>`;
        const img = r.imgBase64 ? `<img src="${r.imgBase64}" style="width:100%;max-height:140px;object-fit:cover;border-radius:6px;margin:0 14px 8px;width:calc(100% - 28px)"/>` : '';
        const actions = can('canDelete')
            ? `<button class="report-action-btn danger" onclick="deleteReport(${r.id})">🗑 Hapus</button>` : '';
        const statusBtns = can('canViewReport')
            ? `<button class="report-action-btn" onclick="changeReportStatus(${r.id}, 'baru')">🆕</button>
               <button class="report-action-btn" onclick="changeReportStatus(${r.id}, 'ditangani')">🔄</button>
               <button class="report-action-btn" onclick="changeReportStatus(${r.id}, 'selesai')">✅</button>` : '';

        return `<div class="report-card">
            <div class="report-card-header">
                <div><div class="report-card-name">👤 ${r.name || 'Anonim'}</div><div class="report-card-meta">${r.lokasi ? '📍 ' + r.lokasi + ' · ' : ''}${r.time}</div></div>
                ${statusBadge}
            </div>
            <div class="report-card-text">${r.text}</div>
            ${img}
            <div class="report-card-footer">
                <div class="report-card-time">#${r.id}</div>
                <div class="report-card-actions">${statusBtns}${actions}</div>
            </div>
        </div>`;
    }).join('');
}

async function changeReportStatus(reportId, newStatus) {
    const r = reports.find(r => r.id === reportId);
    if (!r) return;
    r.status = newStatus;
    filterReports();
    updateReportBadge();
    try {
        await fetch('update_laporan.php', { method: 'POST', body: new URLSearchParams({ id: reportId, status: newStatus }) });
    } catch (e) { console.error(e); }
}

async function deleteReport(reportId) {
    if (!can('canDelete')) return;
    if (!confirm('Hapus laporan ini?')) return;
    reports = reports.filter(r => r.id !== reportId);
    filterReports();
    updateReportBadge();
    try {
        await fetch('hapus_laporan.php', { method: 'POST', body: new URLSearchParams({ id: reportId }) });
    } catch (e) { console.error(e); }
}

// ============================================================
// REPORT SUBMIT
// ============================================================
let reportImgBase64 = '';

function previewImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    if (file.size > 5_000_000) { alert('Ukuran file maksimal 5MB'); return; }
    const reader = new FileReader();
    reader.onload = e => {
        reportImgBase64 = e.target.result;
        document.getElementById('previewImg').src = e.target.result;
        document.getElementById('imgPreview').style.display = 'block';
        document.getElementById('uploadArea').style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    reportImgBase64 = '';
    document.getElementById('reportImg').value = '';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('uploadArea').style.display = 'block';
}

async function submitReport() {
    const text   = document.getElementById('reportText')?.value?.trim();
    const name   = document.getElementById('reportName')?.value?.trim() || 'Anonim';
    const lokasi = document.getElementById('reportLocation')?.value?.trim() || '';

    if (!text) { document.getElementById('reportText')?.focus(); alert('Deskripsi laporan tidak boleh kosong'); return; }

    try {
        const body = new URLSearchParams({ name, text, lokasi, img: reportImgBase64 });
        const res  = await fetch('simpan_laporan.php', { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
            const newReport = {
                id: data.id, name, text, lokasi,
                imgBase64: reportImgBase64 || null,
                status: 'baru',
                time: new Date().toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
            };
            reports.unshift(newReport);
            document.getElementById('reportText').value    = '';
            document.getElementById('reportName').value    = '';
            document.getElementById('reportLocation').value = '';
            removeImage();
            filterReports();
            updateReportBadge();
            alert('Laporan berhasil dikirim! Terima kasih.');
        } else {
            alert('Gagal kirim laporan: ' + (data.message || 'Error'));
        }
    } catch (e) { console.error(e); alert('Terjadi kesalahan. Coba lagi.'); }
}

// ============================================================
// DATE PICKER
// ============================================================
let _dpTargetInput = null;
let _dpTargetBtn   = null;
let _dpYear        = new Date().getFullYear();
let _dpMonth       = new Date().getMonth();
let _dpSelected    = null;

function openDatepicker(inputId, btnId) {
    _dpTargetInput = document.getElementById(inputId);
    _dpTargetBtn   = document.getElementById(btnId);
    const existing = _dpTargetInput?.value;
    if (existing) {
        const d  = new Date(existing);
        _dpYear  = d.getFullYear();
        _dpMonth = d.getMonth();
        _dpSelected = existing;
    } else {
        _dpYear  = new Date().getFullYear();
        _dpMonth = new Date().getMonth();
        _dpSelected = null;
    }
    renderDatepicker();
    document.getElementById('datepickerOverlay').classList.remove('hidden');
}

function closeDatepicker() { document.getElementById('datepickerOverlay').classList.add('hidden'); }
function closeDatepickerIfOutside(e) { if (e.target.id === 'datepickerOverlay') closeDatepicker(); }

function renderDatepicker() {
    const box    = document.getElementById('datepickerBox');
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const days   = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

    const firstDay = new Date(_dpYear, _dpMonth, 1).getDay();
    const daysInMonth = new Date(_dpYear, _dpMonth + 1, 0).getDate();
    const today = new Date();

    let gridHtml = days.map(d => `<div class="dp-day-label">${d}</div>`).join('');
    for (let i = 0; i < firstDay; i++) {
        const prevDays = new Date(_dpYear, _dpMonth, 0).getDate();
        gridHtml += `<div class="dp-day other-month disabled">${prevDays - firstDay + i + 1}</div>`;
    }
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr  = `${_dpYear}-${String(_dpMonth + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const isToday  = d === today.getDate() && _dpMonth === today.getMonth() && _dpYear === today.getFullYear();
        const isSel    = dateStr === _dpSelected;
        const isFuture = new Date(_dpYear, _dpMonth, d) > today;
        gridHtml += `<div class="dp-day${isToday ? ' today' : ''}${isSel ? ' selected' : ''}${isFuture ? ' disabled' : ''}"
            onclick="${!isFuture ? `selectDate('${dateStr}')` : ''}">${d}</div>`;
    }

    box.innerHTML = `
        <div class="dp-header">
            <button class="dp-nav" onclick="dpNav(-1)">◀</button>
            <div class="dp-title">${months[_dpMonth]} ${_dpYear}</div>
            <button class="dp-nav" onclick="dpNav(1)">▶</button>
        </div>
        <div class="dp-grid">${gridHtml}</div>
        <div class="dp-footer">
            <button class="dp-btn" onclick="closeDatepicker()">Batal</button>
            <button class="dp-btn primary" onclick="confirmDate()">Pilih</button>
        </div>`;
}

function dpNav(dir) {
    _dpMonth += dir;
    if (_dpMonth < 0) { _dpMonth = 11; _dpYear--; }
    if (_dpMonth > 11) { _dpMonth = 0; _dpYear++; }
    renderDatepicker();
}

function selectDate(dateStr) {
    _dpSelected = dateStr;
    renderDatepicker();
}

function confirmDate() {
    if (!_dpSelected || !_dpTargetInput) { closeDatepicker(); return; }
    _dpTargetInput.value = _dpSelected;
    if (_dpTargetBtn) _dpTargetBtn.textContent = formatDate(_dpSelected);
    closeDatepicker();
}

// ============================================================
// LOAD DATA FROM BACKEND
// ============================================================
async function loadData() {
    try {
        const res  = await fetch('get_data.php');
        const data = await res.json();
        if (!data.success) return;

        data.centers.forEach(c => {
            addCenter({ id: c.id, name: c.name, address: c.address, kas: c.kas, lat: c.latitude, lng: c.longitude, radius: c.radius });
        });

        data.houses.forEach(h => {
            const marker = L.marker([h.latitude, h.longitude], {
                icon: createHouseIcon(h.aid_status, !!h.has_data),
                zIndexOffset: 100
            }).addTo(map);

            const house = {
                id: h.id, lat: h.latitude, lng: h.longitude,
                address: h.address, rt: h.rt, rw: h.rw, kelurahan: h.kelurahan,
                statusMiskin: h.status_miskin, jumlahAnggota: h.jumlah_anggota,
                anggota: h.anggota || [], aidStatus: h.aid_status,
                hasData: !!h.has_data, marker, _geoData: null
            };
            marker.bindPopup(() => buildHouseMapPopup(house), { maxWidth: 270 });
            marker.on('popupopen', () => marker.setPopupContent(buildHouseMapPopup(house)));
            houses.push(house);
        });

        reports = data.reports || [];

        updateStats();
        updateSidebar();
        updateReportBadge();
    } catch (e) {
        console.error('loadData', e);
    }
}

// ============================================================
// INIT
// ============================================================
checkAuth();
applyRoleUI();
loadData();
