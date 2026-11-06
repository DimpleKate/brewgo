document.addEventListener('DOMContentLoaded', () => {
    const panel = document.querySelector('[data-audit-feed]');

    if (!panel) {
        return;
    }

    const tbody = panel.querySelector('[data-audit-tbody]');
    const liveLabel = document.querySelector('[data-audit-live-label]');

    if (!tbody) {
        return;
    }

    const isLive = panel.dataset.auditLive === '1';
    let lastId = parseInt(tbody.dataset.auditLastId || '0', 10);
    let busy = false;
    let labelTimer = null;

    const setLiveLabel = (message) => {
        if (!liveLabel) {
            return;
        }

        window.clearTimeout(labelTimer);
        liveLabel.textContent = message;
    };

    const resetLiveLabel = (message = 'Live · listening', delay = 3200) => {
        if (!liveLabel) {
            return;
        }

        window.clearTimeout(labelTimer);
        labelTimer = window.setTimeout(() => {
            liveLabel.textContent = message;
        }, delay);
    };

    const cell = (row, text, className = '') => {
        const element = document.createElement('td');

        if (className) {
            element.className = className;
        }

        element.textContent = text ?? '—';
        row.appendChild(element);

        return element;
    };

    const statusBadge = (status) => {
        if (status >= 400) return 'text-bg-danger';
        if (status >= 300) return 'text-bg-warning';

        return 'text-bg-success';
    };

    const renderRow = (log) => {
        const row = document.createElement('tr');
        row.dataset.auditId = log.id;
        row.classList.add('audit-new');

        const timeCell = cell(row, '');
        const timeMain = document.createElement('div');
        timeMain.className = 'text-cream';
        timeMain.textContent = log.created_at_label;
        const timeMeta = document.createElement('div');
        timeMeta.className = 'text-muted-bp admin-meta';
        timeMeta.textContent = (log.created_at_human || '') + ' · PHT';
        timeCell.append(timeMain, timeMeta);

        const actorCell = cell(row, '');
        const actorName = document.createElement('div');
        actorName.className = 'text-cream';
        actorName.textContent = log.user_name;
        const actorRole = document.createElement('span');
        const isAdmin = (log.actor_role || 'admin') === 'admin';
        actorRole.className = 'badge '.concat(isAdmin ? 'text-bg-warning' : 'text-bg-info');
        actorRole.textContent = isAdmin ? 'Admin' : 'Customer';
        actorCell.append(actorName, actorRole);

        const actionCell = cell(row, '');
        const actionMain = document.createElement('div');
        actionMain.className = 'text-cream';
        actionMain.textContent = log.action;
        const actionMeta = document.createElement('div');
        actionMeta.className = 'text-muted-bp admin-meta';
        actionMeta.textContent = log.route_name || '—';
        actionCell.append(actionMain, actionMeta);

        const requestCell = cell(row, '');
        const method = document.createElement('span');
        method.className = 'badge text-bg-secondary me-2';
        method.textContent = log.method;
        const path = document.createElement('code');
        path.className = 'small';
        path.textContent = log.path;
        requestCell.append(method, path);

        cell(row, log.ip_address, 'text-muted-bp');

        const resultCell = cell(row, '');
        const result = document.createElement('span');
        result.className = 'badge '.concat(statusBadge(log.status_code));
        result.textContent = log.status_code ?? '—';
        resultCell.appendChild(result);

        return row;
    };

    const prepend = (log) => {
        if (tbody.querySelector(`[data-audit-id="${log.id}"]`)) {
            return false;
        }

        tbody.querySelector('[data-audit-empty]')?.remove();
        tbody.prepend(renderRow(log));

        return true;
    };

    const poll = async () => {
        if (busy || document.hidden || !isLive) {
            return;
        }

        busy = true;

        const params = new URLSearchParams(window.location.search);
        params.delete('page');
        params.set('after', String(lastId));

        try {
            const response = await fetch(panel.dataset.auditFeed + '?' + params.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Audit feed unavailable');
            }

            const data = await response.json();
            let added = 0;

            data.logs.forEach((log) => {
                if (prepend(log)) {
                    added += 1;
                }
            });

            lastId = Math.max(lastId, Number(data.last_id) || 0);

            if (added > 0) {
                setLiveLabel(`Live · ${added} new ${added === 1 ? 'activity' : 'activities'}`);
                resetLiveLabel();
            } else {
                resetLiveLabel('Live · listening', 0);
            }
        } catch (error) {
            setLiveLabel('Live · reconnecting');
            resetLiveLabel('Live · listening', 6000);
        } finally {
            busy = false;
        }
    };

    if (isLive) {
        liveLabel?.closest('[aria-live]')?.setAttribute('aria-live', 'polite');
        poll();
        window.setInterval(poll, 5000);

        window.addEventListener('focus', poll);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                poll();
            }
        });
    }
});
