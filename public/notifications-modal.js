(function () {
    const cfg = window.LMS_NOTIFICATIONS;
    if (!cfg) return;

    const modal = document.getElementById('lms-notif-modal');
    if (!modal) return;

    const listEl = modal.querySelector('.js-notif-list');
    const markAllBtn = modal.querySelector('.js-notif-mark-all');

    function setBadges(count) {
        const n = typeof count === 'number' ? count : parseInt(String(count), 10) || 0;
        const label = n > 99 ? '99+' : String(n);
        document.querySelectorAll('.js-notif-badge').forEach((el) => {
            el.textContent = label;
            if (n > 0) {
                el.classList.remove('notif-badge--hidden');
            } else {
                el.classList.add('notif-badge--hidden');
            }
        });
    }

    function openModal() {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.querySelectorAll('.js-open-notifications').forEach((b) => {
            b.setAttribute('aria-expanded', 'true');
            b.dataset.notifExpanded = 'true';
        });
        loadFeed();
        requestAnimationFrame(() => listEl?.focus?.());
    }

    function closeModal() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.querySelectorAll('.js-open-notifications').forEach((b) => {
            b.setAttribute('aria-expanded', 'false');
            b.dataset.notifExpanded = 'false';
        });
    }

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function fmtDate(iso) {
        if (!iso) return '';
        try {
            const d = new Date(iso);
            return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
        } catch {
            return String(iso);
        }
    }

    async function loadFeed() {
        if (!listEl) return;
        listEl.innerHTML = '<p class="muted js-notif-loading" style="margin:0;">Loading…</p>';
        if (markAllBtn) markAllBtn.disabled = true;
        try {
            const res = await fetch(cfg.feedUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Failed to load');
            const data = await res.json();
            setBadges(data.unread_count ?? 0);
            renderList(data.notifications || []);
            if (markAllBtn) markAllBtn.disabled = (data.unread_count ?? 0) === 0;
        } catch {
            listEl.innerHTML =
                '<p class="error" style="margin:0;">Could not load notifications. Please refresh and try again.</p>';
        }
    }

    function renderList(items) {
        if (!listEl) return;
        if (!items.length) {
            listEl.innerHTML = '<p class="muted" style="margin:0;">No notifications yet.</p>';
            return;
        }
        const frag = document.createDocumentFragment();
        items.forEach((n) => {
            const article = document.createElement('article');
            article.className = 'notif-modal-card' + (n.read_at ? ' notif-modal-card--read' : '');
            article.dataset.id = String(n.id);

            const main = document.createElement('div');
            main.className = 'notif-modal-card__main';
            main.innerHTML =
                '<div class="notif-modal-card__title">' +
                escapeHtml(n.title || '') +
                '</div>' +
                '<p class="notif-modal-card__msg">' +
                escapeHtml(n.message || '') +
                '</p>' +
                '<div class="notif-modal-card__meta">' +
                escapeHtml(fmtDate(n.created_at)) +
                '</div>';
            article.appendChild(main);

            const actions = document.createElement('div');
            actions.className = 'notif-modal-card__actions';

            if (n.action_url) {
                const openBtn = document.createElement('a');
                openBtn.className = 'btn btn-sm btn-primary';
                openBtn.href = n.action_url;
                openBtn.textContent = n.type === 'attendance_monthly_report' ? 'Open report' : 'Open';
                actions.appendChild(openBtn);
            }

            if (!n.read_at) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline js-notif-read-one';
                btn.dataset.id = String(n.id);
                btn.textContent = 'Mark as read';
                actions.appendChild(btn);
            } else {
                const span = document.createElement('span');
                span.className = 'muted notif-modal-card__readlbl';
                span.textContent = 'Read';
                actions.appendChild(span);
            }

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn btn-sm notif-btn-delete js-notif-delete-one';
            deleteBtn.dataset.id = String(n.id);
            deleteBtn.textContent = 'Delete';
            actions.appendChild(deleteBtn);

            article.appendChild(actions);
            frag.appendChild(article);
        });
        listEl.innerHTML = '';
        listEl.appendChild(frag);

        listEl.querySelectorAll('.js-notif-read-one').forEach((btn) => {
            btn.addEventListener('click', () => markOne(btn.dataset.id));
        });
        listEl.querySelectorAll('.js-notif-delete-one').forEach((btn) => {
            btn.addEventListener('click', () => deleteOne(btn.dataset.id));
        });
    }

    async function postForm(url, method) {
        const fd = new FormData();
        fd.append('_token', cfg.csrf);
        if (method === 'DELETE') {
            fd.append('_method', 'DELETE');
        }
        const res = await fetch(url, {
            method: method === 'DELETE' ? 'POST' : 'POST',
            body: fd,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error('Request failed');
        return res.json();
    }

    async function markOne(id) {
        const url = cfg.notificationsUrlPrefix + '/' + encodeURIComponent(id) + '/read';
        try {
            const data = await postForm(url, 'POST');
            if (data.unread_count !== undefined) setBadges(data.unread_count);
            await loadFeed();
        } catch {
            window.alert('Could not update notification.');
        }
    }

    async function deleteOne(id) {
        if (!window.confirm('Delete this notification?')) {
            return;
        }
        const url = cfg.notificationsUrlPrefix + '/' + encodeURIComponent(id);
        try {
            const data = await postForm(url, 'DELETE');
            if (data.unread_count !== undefined) setBadges(data.unread_count);
            await loadFeed();
        } catch {
            window.alert('Could not delete notification.');
        }
    }

    async function markAll() {
        try {
            const data = await postForm(cfg.readAllUrl, 'POST');
            if (data.unread_count !== undefined) setBadges(data.unread_count);
            await loadFeed();
        } catch {
            window.alert('Could not mark all as read.');
        }
    }

    document.querySelectorAll('.js-open-notifications').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal();
        });
    });

    modal.querySelectorAll('.js-notif-close').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    markAllBtn?.addEventListener('click', markAll);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });

    window.LMSNotificationsOpen = openModal;
})();
