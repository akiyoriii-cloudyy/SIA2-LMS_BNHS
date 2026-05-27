/* EduTrack Mobile – 3 key screens: Attend, Students, Reports (+ Settings for login/roster)
   - Roster via /api/mobile/roster, profile via /api/mobile/enrollments/{id}/profile
   - Submit syncs via /api/mobile/sync/attendance when online
*/

const DB_KEY = 'attendance_offline_queue_v2';
const TOKEN_KEY = 'attendance_bearer_token_v1';
const ACTIVE_SCHOOL_YEAR_KEY = 'attendance_active_school_year_id_v1';
const ROSTER_CACHE_KEY = 'attendance_roster_cache_v2';

const state = {
  activeSchoolYear: null,
  sections: [],
  sectionId: '',
  weekStart: '',
  roster: [],
  picks: new Map(), // enrollment_id -> status
  currentProfileEnrollmentId: null,
};

function $(id) {
  return document.getElementById(id);
}

function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, (m) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[m]);
}

function setOnlineUI(isOnline) {
  const dot = $('net-dot');
  const label = $('net-label');
  if (!dot || !label) return;
  if (isOnline) {
    dot.classList.remove('off');
    label.textContent = 'Online';
  } else {
    dot.classList.add('off');
    label.textContent = 'X Offline';
  }
  renderOfflineUI();
}

function getQueue() {
  return JSON.parse(localStorage.getItem(DB_KEY) || '[]');
}

function setQueue(queue) {
  localStorage.setItem(DB_KEY, JSON.stringify(queue));
  updateQueueUI();
}

function updateQueueUI() {
  const badge = $('queue-badge');
  const summary = $('offline-summary');
  if (badge) badge.textContent = `${getQueue().length} pending`;
  if (summary) summary.textContent = getQueue().length === 0 ? 'No records saved locally.' : `${getQueue().length} records saved locally and waiting to sync.`;
  renderOfflineUI();
}

function getRosterLookup() {
  const map = new Map();
  const add = (rows) => {
    if (!Array.isArray(rows)) return;
    for (const r of rows) {
      const id = Number(r.enrollment_id);
      if (!Number.isFinite(id)) continue;
      map.set(id, {
        name: r.student_name || 'Student',
        lrn: r.lrn ? String(r.lrn) : '—',
      });
    }
  };

  add(state.roster);
  const cached = readRosterCache();
  add(cached?.data);
  return map;
}

function renderOfflineUI() {
  const pill = $('offline-pill');
  if (!pill) return;
  const queue = getQueue();
  const isOnline = navigator.onLine;
  pill.classList.toggle('hidden', isOnline);

  const banner = $('offline-banner');
  const bannerSub = $('offline-banner-sub');
  if (banner && bannerSub) {
    if (queue.length === 0) {
      banner.classList.add('hidden');
    } else {
      banner.classList.remove('hidden');
      bannerSub.textContent = `${queue.length} entries pending sync. SMS queued for delivery.`;
    }
  }

  const listEl = $('offline-list');
  if (!listEl) return;
  listEl.innerHTML = '';

  if (queue.length === 0) return;

  const lookup = getRosterLookup();
  const groups = new Map();

  for (const rec of queue) {
    const enrollmentId = Number(rec.enrollment_id);
    if (!Number.isFinite(enrollmentId)) continue;
    const prev = groups.get(enrollmentId) || { enrollmentId, count: 0, last: rec };
    prev.count += 1;
    prev.last = rec;
    groups.set(enrollmentId, prev);
  }

  const items = Array.from(groups.values())
    .sort((a, b) => b.count - a.count)
    .slice(0, 8);

  for (const it of items) {
    const info = lookup.get(it.enrollmentId) || { name: 'Student', lrn: '—' };
    const initials = info.name
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((x) => x[0])
      .join('')
      .toUpperCase() || 'S';

    const status = String(it.last?.status || '').toLowerCase();
    const letter = status === 'present' ? 'P' : status === 'absent' ? 'A' : status === 'late' ? 'L' : '—';

    const row = document.createElement('div');
    row.className = 'student';
    row.innerHTML = [
      `<div class="student-left">`,
      `  <div class="avatar">${escapeHtml(initials)}</div>`,
      `  <div style="min-width:0;">`,
      `    <div class="sname">${escapeHtml(info.name)}</div>`,
      `    <div class="slrn">Saved locally ✓ · ${it.count} record(s) · LRN: ${escapeHtml(info.lrn)}</div>`,
      `  </div>`,
      `</div>`,
      `<div class="status-buttons">`,
      `  <button class="sb active" type="button" aria-label="Last status">${escapeHtml(letter)}</button>`,
      `</div>`,
    ].join('');

    listEl.appendChild(row);
  }
}

function cacheRoster(payload) {
  localStorage.setItem(ROSTER_CACHE_KEY, JSON.stringify(payload));
}

function readRosterCache() {
  try {
    return JSON.parse(localStorage.getItem(ROSTER_CACHE_KEY) || 'null');
  } catch {
    return null;
  }
}

function setStatus(message) {
  $('state').textContent = message;
}

function getBearer() {
  const token = ($('token').value || '').trim();
  return token ? `Bearer ${token}` : '';
}

function setTopSub() {
  const section = state.sections.find((s) => String(s.id) === String(state.sectionId));
  const dateStr = $('attendance_date').value || '';
  const dateObj = dateStr ? new Date(`${dateStr}T00:00:00`) : null;
  const dateLabel = dateObj
    ? dateObj.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' })
    : '—';
  const secLabel = section ? `Grade ${section.grade_level} ${section.name}` : 'Grade —';
  const meta = `${secLabel} - ${dateLabel}`;
  $('top-sub').textContent = meta;
  const attMeta = $('att-meta');
  if (attMeta) attMeta.textContent = meta;
}

async function login() {
  const email = $('email').value.trim();
  const password = $('password').value;
  const deviceName = ($('device_name').value || '').trim() || 'mobile-demo';

  if (!email || !password) {
    setStatus('Email/password required.');
    return;
  }

  try {
    const response = await fetch(new URL('api/auth/login', window.location.href), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password, device_name: deviceName }),
    });

    if (!response.ok) {
      setStatus('Login failed.');
      return;
    }

    const data = await response.json();
    const token = String(data.token || '').trim();
    if (!token) {
      setStatus('Login succeeded but no token returned.');
      return;
    }

    localStorage.setItem(TOKEN_KEY, token);
    $('token').value = token;
    const userName = data.user?.name || '';
    const roles = (data.user?.roles || []).join(', ');
    $('whoami').textContent = `Logged in: ${userName} (${roles})`;

    await loadBootstrap();
    setStatus('Logged in.');
  } catch {
    setStatus('Login error (offline?).');
  }
}

async function loadBootstrap() {
  const auth = getBearer();
  if (!auth) {
    setStatus('Login first (token missing).');
    return;
  }

  try {
    const response = await fetch(new URL('api/mobile/bootstrap', window.location.href), {
      method: 'GET',
      headers: { Authorization: auth },
    });

    if (!response.ok) {
      setStatus('Failed to load sections.');
      return;
    }

    const data = await response.json();
    state.activeSchoolYear = data.active_school_year || null;
    state.sections = Array.isArray(data.sections) ? data.sections : [];

    if (state.activeSchoolYear?.id) {
      localStorage.setItem(ACTIVE_SCHOOL_YEAR_KEY, String(state.activeSchoolYear.id));
    }

    const select = $('section_id');
    select.innerHTML = '';
    for (const section of state.sections) {
      const opt = document.createElement('option');
      opt.value = String(section.id);
      const label = `${section.name} (G${section.grade_level}${section.strand ? ' - ' + section.strand : ''})`;
      opt.textContent = label;
      select.appendChild(opt);
    }

    if (!state.sectionId && state.sections[0]) {
      state.sectionId = String(state.sections[0].id);
      select.value = state.sectionId;
    }

    select.onchange = () => {
      state.sectionId = select.value;
      setTopSub();
    };

    setTopSub();
    setStatus(`Loaded ${state.sections.length} sections.`);
  } catch {
    setStatus('Cannot refresh sections. Check connection.');
  }
}

function makeStatusBtn(label, value, active, enrollmentId) {
  const b = document.createElement('button');
  b.type = 'button';
  b.className = 'sb' + (active ? ' active' : '');
  b.textContent = label;
  b.onclick = () => {
    state.picks.set(enrollmentId, value);
    renderAttendAndStudents();
    renderReports();
  };
  return b;
}

function renderRoster(listEl, onRowClick) {
  listEl.innerHTML = '';
  for (const row of state.roster) {
    const enrollmentId = Number(row.enrollment_id);
    const name = row.student_name || 'Student';
    const lrn = row.lrn ? String(row.lrn) : '—';
    const initials = name
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((x) => x[0])
      .join('')
      .toUpperCase() || 'S';

    const pick = state.picks.get(enrollmentId) || 'present';

    const card = document.createElement('div');
    card.className = 'student';
    card.dataset.enrollmentId = String(enrollmentId);

    const left = document.createElement('div');
    left.className = 'student-left';
    left.innerHTML = [
      `<div class="avatar">${escapeHtml(initials)}</div>`,
      `<div style="min-width:0;">`,
      `  <div class="sname">${escapeHtml(name)}</div>`,
      `  <div class="slrn">LRN: ${escapeHtml(lrn)}</div>`,
      `</div>`,
    ].join('');

    const right = document.createElement('div');
    right.className = 'status-buttons';
    right.appendChild(makeStatusBtn('P', 'present', pick === 'present', enrollmentId));
    right.appendChild(makeStatusBtn('A', 'absent', pick === 'absent', enrollmentId));
    right.appendChild(makeStatusBtn('L', 'late', pick === 'late', enrollmentId));

    card.appendChild(left);
    card.appendChild(right);

    if (onRowClick) {
      left.style.cursor = 'pointer';
      left.onclick = () => onRowClick(enrollmentId);
    }

    listEl.appendChild(card);
  }
}

function renderAlertBanner() {
  const banner = $('alert-banner');
  const top = state.roster
    .map((r) => ({
      name: r.student_name,
      absences: Number(r.absences_this_week || 0),
      sms: r.sms_status_this_week || null,
    }))
    .sort((a, b) => b.absences - a.absences)[0];

  if (!top || top.absences < 5) {
    banner.classList.add('hidden');
    return;
  }

  banner.classList.remove('hidden');
  $('alert-text').textContent = `${top.name} – ${top.absences} absences this week.`;
  $('alert-sub').textContent = top.sms === 'sent'
    ? 'SMS sent.'
    : `SMS status: ${top.sms || 'queued'}.`;
}

function renderAttendAndStudents() {
  renderRoster($('roster-list'));
  renderRoster($('students-list'), openProfile);
  renderAlertBanner();
}

function renderReports() {
  const list = $('reports-list');
  list.innerHTML = '';

  const alerts = state.roster
    .map((r) => ({
      student_name: r.student_name,
      absences: Number(r.absences_this_week || 0),
      sms: r.sms_status_this_week || null,
    }))
    .filter((x) => x.absences >= 4)
    .sort((a, b) => b.absences - a.absences);

  if (alerts.length === 0) {
    const c = document.createElement('div');
    c.className = 'card';
    c.innerHTML = `<div class="muted">No absence alerts this week.</div>`;
    list.appendChild(c);
    return;
  }

  for (const a of alerts) {
    const card = document.createElement('div');
    card.className = 'banner';
    const icon = a.absences >= 5 ? '⚠' : '!';
    const sub = a.absences >= 5
      ? (a.sms === 'sent' ? 'SMS sent to parent.' : `SMS status: ${a.sms || 'queued'}.`)
      : '1 more triggers SMS.';

    card.innerHTML = [
      `<div class="ic">${escapeHtml(icon)}</div>`,
      `<div>`,
      `  <div style="font-weight:900;">${escapeHtml(a.student_name || 'Student')} — ${a.absences}/5 absences this week.</div>`,
      `  <div class="muted">${escapeHtml(sub)}</div>`,
      `</div>`,
    ].join('');
    list.appendChild(card);
  }
}

async function loadRoster() {
  const auth = getBearer();
  const sectionId = $('section_id').value;
  const schoolYearId = localStorage.getItem(ACTIVE_SCHOOL_YEAR_KEY) || '';

  if (!auth || !sectionId) {
    setStatus('Need token + section.');
    return;
  }

  const url = new URL('api/mobile/roster', window.location.href);
  url.searchParams.set('section_id', sectionId);
  if (schoolYearId) url.searchParams.set('school_year_id', schoolYearId);

  try {
    const response = await fetch(url.toString(), {
      method: 'GET',
      headers: { Authorization: auth },
    });

    if (!response.ok) {
      setStatus('Failed to load roster.');
      return;
    }

    const data = await response.json();
    state.weekStart = String(data.week_start || '');
    state.roster = Array.isArray(data.data) ? data.data : [];
    state.sectionId = sectionId;

    for (const row of state.roster) {
      const id = Number(row.enrollment_id);
      if (!state.picks.has(id)) state.picks.set(id, 'present');
    }

    cacheRoster({
      at: Date.now(),
      section_id: sectionId,
      week_start: state.weekStart,
      data: state.roster,
    });

    setTopSub();
    renderAttendAndStudents();
    renderReports();
    setStatus(`Loaded roster: ${state.roster.length} students.`);
  } catch {
    const cached = readRosterCache();
    if (cached?.data && Array.isArray(cached.data)) {
      state.roster = cached.data;
      state.weekStart = cached.week_start || '';
      renderAttendAndStudents();
      renderReports();
      setStatus(`Using cached roster (${state.roster.length}).`);
    } else {
      setStatus('Cannot load roster. Check connection and try again.');
    }
  }
}

async function refreshRoster() {
  if ($('token').value.trim()) {
    await loadRoster();
  } else {
    navTo('settings');
    setStatus('Login first.');
  }
}

function submitAttendance() {
  const date = $('attendance_date').value;
  if (!date) return;
  if (!navigator.onLine) {
    setStatus('Connect to the internet to submit attendance.');
    return;
  }

  const queue = getQueue();
  for (const row of state.roster) {
    const enrollmentId = Number(row.enrollment_id);
    const status = state.picks.get(enrollmentId) || 'present';
    queue.push({ enrollment_id: enrollmentId, attendance_date: date, status, remarks: null });
  }
  setQueue(queue);
  setStatus(`Submitting…`);
  syncNow();
}

async function syncNow() {
  const token = ($('token').value || '').trim();
  const deviceId = ($('device_id').value || '').trim();
  const records = getQueue();
  if (!token || !deviceId || records.length === 0) {
    setStatus(records.length === 0 ? 'No attendance to submit.' : 'No token/device.');
    return;
  }

  const batch_uuid = crypto.randomUUID();
  try {
    const response = await fetch(new URL('api/mobile/sync/attendance', window.location.href), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({ device_id: deviceId, batch_uuid, records }),
    });

    if (!response.ok) {
      setStatus('Submit failed. Try again.');
      return;
    }

    setQueue([]);
    setStatus('Attendance submitted.');
    await loadRoster();
  } catch {
    setStatus('Network error. Try again when connected.');
  }
}

function renderWeekGrid(records) {
  const grid = $('week-grid');
  grid.innerHTML = '';
  const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];

  const baseDate = new Date(`${$('attendance_date').value}T00:00:00`);
  const monday = new Date(baseDate);
  const dow = monday.getDay();
  const diff = (dow === 0 ? -6 : 1) - dow;
  monday.setDate(monday.getDate() + diff);

  for (let i = 0; i < 5; i++) {
    const d = new Date(monday);
    d.setDate(monday.getDate() + i);
    const key = d.toISOString().slice(0, 10);
    const status = records ? records[key] : null;
    const letter = status === 'present'
      ? 'P'
      : status === 'absent'
        ? 'A'
        : status === 'late'
          ? 'L'
          : '—';

    const cell = document.createElement('div');
    cell.className = 'day';
    cell.innerHTML = `<div class="d">${days[i]}</div><div class="v">${letter}</div>`;
    grid.appendChild(cell);
  }
}

function renderGuardian(primary) {
  const el = $('profile-guardian');
  if (!primary) {
    el.innerHTML = `<div class="muted" style="font-size:12px;">Parent: —</div>`;
    return;
  }

  el.innerHTML = [
    `<div style="border-top:1px dashed rgba(15,23,42,.22); margin-top:10px; padding-top:10px;">`,
    `  <div style="font-weight:900;">Parent: ${escapeHtml(primary.name || '—')} (${escapeHtml(primary.relationship || 'Guardian')})</div>`,
    `  <div class="muted" style="font-size:12px;">Contact: ${escapeHtml(primary.phone || '—')}</div>`,
    `</div>`,
  ].join('');
}

function renderProfileAlert(absences, smsStatus) {
  const ic = $('profile-alert-ic');
  const text = $('profile-alert-text');

  if (absences >= 5) {
    ic.textContent = '▲';
    const msg = smsStatus === 'sent'
      ? 'SMS sent to parent'
      : `SMS status: ${smsStatus || 'queued'}`;
    text.textContent = `${absences}/5 absences — ${msg}.`;
    return;
  }
  if (absences >= 4) {
    ic.textContent = '!';
    text.textContent = `${absences}/5 absences — 1 more triggers SMS.`;
    return;
  }

  ic.textContent = '✓';
  text.textContent = 'No absence alert this week.';
}

async function openProfile(enrollmentId) {
  state.currentProfileEnrollmentId = enrollmentId;
  navTo('profile');

  const row = state.roster.find((r) => Number(r.enrollment_id) === Number(enrollmentId));
  const fallbackName = row?.student_name || 'Student';
  const initials = fallbackName
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((x) => x[0])
    .join('')
    .toUpperCase() || 'S';

  $('profile-avatar').textContent = initials;
  $('profile-name').textContent = fallbackName;
  $('profile-lrn').textContent = `LRN: ${row?.lrn ? row.lrn : '—'}`;

  const section = state.sections.find((s) => String(s.id) === String(state.sectionId));
  const secLabel = section
    ? `Grade ${section.grade_level} · ${section.name}${section.strand ? ' · ' + section.strand : ''}`
    : '';
  $('profile-sec').textContent = secLabel;
  $('profile-sub').textContent = fallbackName;
  const topSub = $('top-profile-sub');
  if (topSub) topSub.textContent = fallbackName;

  renderWeekGrid(null);
  renderGuardian(row?.primary_guardian || null);
  renderProfileAlert(Number(row?.absences_this_week || 0), row?.sms_status_this_week || null);

  const auth = getBearer();
  if (!auth) return;

  try {
    const url = new URL(`api/mobile/enrollments/${enrollmentId}/profile`, window.location.href);
    const response = await fetch(url.toString(), { headers: { Authorization: auth } });
    if (!response.ok) return;

    const data = await response.json();
    const liveName = data.student?.name || fallbackName;
    $('profile-name').textContent = liveName;
    const topSubLive = $('top-profile-sub');
    if (topSubLive) topSubLive.textContent = liveName;
    $('profile-lrn').textContent = `LRN: ${data.student?.lrn || '—'}`;
    $('profile-sec').textContent = [
      `Grade ${data.section?.grade_level || '—'} · ${data.section?.name || '—'}`,
      data.section?.strand ? ` · ${data.section?.strand}` : '',
    ].join('');

    renderGuardian(data.primary_guardian || null);
    renderWeekGrid(data.attendance_this_week || {});
    renderProfileAlert(Number(data.absences_this_week || 0), data.sms_status_this_week || null);
  } catch {
    // offline fallback
  }
}

function closeProfile() {
  state.currentProfileEnrollmentId = null;
  navTo('students');
}

function navTo(which) {
  const views = ['attend', 'students', 'reports', 'settings', 'profile'];
  for (const v of views) {
    const el = $(`view-${v}`);
    if (el) el.classList.toggle('hidden', v !== which);
  }

  const navs = ['attend', 'students', 'reports'];
  for (const n of navs) {
    const btn = $(`nav-${n}`);
    if (btn) btn.classList.toggle('active', n === which);
  }

  const bottomNav = $('bottom-nav');
  if (bottomNav) bottomNav.style.display = which === 'settings' ? 'none' : 'block';
  if (which === 'profile') {
    const btn = $('nav-students');
    if (btn) btn.classList.add('active');
  }
  const settingsBackRow = $('settings-back-row');
  if (settingsBackRow) settingsBackRow.style.display = (which === 'settings' && getBearer()) ? 'flex' : 'none';

  setTopMode(which);
}

function openSettings() {
  navTo('settings');
}

function closeSettings() {
  navTo('attend');
}

function setTopMode(which) {
  const brandRow = $('top-brand-row');
  const profileRow = $('top-profile-row');
  if (!brandRow || !profileRow) return;

  const isProfile = which === 'profile';
  brandRow.classList.toggle('hidden', isProfile);
  profileRow.classList.toggle('hidden', !isProfile);
}

window.login = login;
window.loadBootstrap = loadBootstrap;
window.loadRoster = loadRoster;
window.refreshRoster = refreshRoster;
window.submitAttendance = submitAttendance;
window.syncNow = syncNow;
window.openSettings = openSettings;
window.openProfile = openProfile;
window.closeProfile = closeProfile;
window.closeSettings = closeSettings;
window.navTo = navTo;

window.addEventListener('online', () => {
  setOnlineUI(true);
  if (getQueue().length > 0) syncNow();
});
window.addEventListener('offline', () => setOnlineUI(false));

(async function init() {
  const tokenEl = $('token');
  if (tokenEl) tokenEl.value = (localStorage.getItem(TOKEN_KEY) || '').trim();
  updateQueueUI();
  setOnlineUI(navigator.onLine);

  const today = new Date();
  const dateInput = $('attendance_date');
  dateInput.value = today.toISOString().slice(0, 10);
  dateInput.onchange = setTopSub;

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('mobile-sw.js').catch(() => {});
  }

  const token = (tokenEl && tokenEl.value || '').trim();
  if (token) {
    await loadBootstrap();
    const cached = readRosterCache();
    if (cached?.data && Array.isArray(cached.data)) {
      state.roster = cached.data;
      state.weekStart = cached.week_start || '';
      renderAttendAndStudents();
      renderReports();
    }
  } else {
    navTo('settings');
    setStatus('Login first.');
  }

  setTopSub();
})();
