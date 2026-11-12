// Sessions page ng admin: kinukuha ang listahan sa sessions_data.php at inilalagay sa table.
// Textcontent lang ang gamit (hindi innerHTML), para hindi makapasok ang HTML galing sa username.
(() => {
    const dataUrl = 'sessions_data.php';
    const refreshMs = 30 * 1000;

    const body = document.getElementById('sessions-body');
    const stateBox = document.getElementById('sessions-state');
    const summary = document.getElementById('sessions-summary');
    const tableWrap = document.getElementById('sessions-table-wrap');
    const refreshButton = document.getElementById('sessions-refresh');
    let isLoading = false;

    // Ipakita ang loading / empty / error message at itago ang table
    function showState(kind, text, withRetry) {
        stateBox.className = 'alert alert-' + kind;
        stateBox.textContent = text;

        if (withRetry) {
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn btn-sm btn-outline-danger ms-3';
            retry.textContent = 'Try again';
            retry.addEventListener('click', () => loadSessions(true));
            stateBox.appendChild(retry);
        }

        tableWrap.classList.add('d-none');
        summary.classList.add('d-none');
    }

    function formatDuration(totalSeconds) {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (hours > 0) return hours + 'h ' + minutes + 'm';
        if (minutes > 0) return minutes + 'm ' + seconds + 's';
        return seconds + 's';
    }

    function addCell(row, text) {
        const cell = document.createElement('td');
        cell.textContent = text;
        row.appendChild(cell);
        return cell;
    }

    function renderSessions(data) {
        body.replaceChildren();

        if (data.sessions.length === 0) {
            showState('secondary', 'No sessions recorded yet. They appear here after someone logs in.', false);
            return;
        }

        data.sessions.forEach((item) => {
            const row = document.createElement('tr');
            addCell(row, String(item.id));
            addCell(row, item.username);

            const roleCell = addCell(row, '');
            const roleBadge = document.createElement('span');
            roleBadge.className = 'badge sn-role-' + item.role;
            roleBadge.textContent = item.role;
            roleCell.appendChild(roleBadge);

            addCell(row, item.timein);
            addCell(row, item.timeout === null ? '-' : item.timeout);
            addCell(row, formatDuration(item.duration_seconds));
            addCell(row, item.auto_logout_seconds === null ? 'Not applied' : (item.auto_logout_seconds / 60) + ' min idle');

            const statusCell = addCell(row, '');
            const statusBadge = document.createElement('span');
            statusBadge.className = 'badge ' + (item.state === 'active' ? 'sn-status-active' : 'sn-status-ended');
            statusBadge.textContent = item.state === 'active' ? 'Active' : 'Ended';
            statusCell.appendChild(statusBadge);

            body.appendChild(row);
        });

        stateBox.className = 'd-none';
        stateBox.textContent = '';
        summary.textContent = data.active_count + ' active, ' + data.sessions.length + ' shown. Updated ' + data.generated_at + '.';
        summary.classList.remove('d-none');
        tableWrap.classList.remove('d-none');
    }

    // showLoading = false kapag auto-refresh, para hindi kumukurap ang table
    async function loadSessions(showLoading) {
        if (isLoading) return;
        isLoading = true;
        refreshButton.disabled = true;

        if (showLoading) {
            showState('info', 'Loading sessions...', false);
        }

        try {
            const response = await fetch(dataUrl, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' }
            });

            // Expired na ang session ng admin, balik sa login
            if (response.status === 401) {
                window.location.replace('../index.php?expired=1');
                return;
            }
            if (response.status === 403) {
                showState('danger', 'You are not allowed to view this page.', false);
                return;
            }
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();
            if (data.status !== 'ok' || !Array.isArray(data.sessions)) {
                throw new Error('Unexpected response');
            }
            renderSessions(data);
        } catch (error) {
            console.error('Unable to load sessions.', error);
            showState('danger', 'Sessions could not be loaded. Check your connection and try again.', true);
        } finally {
            isLoading = false;
            refreshButton.disabled = false;
        }
    }

    refreshButton.addEventListener('click', () => loadSessions(true));

    // Auto-refresh, pero hindi kapag nakatago ang tab. Hindi ito nagpapahaba ng session (tingnan ang sessions_data.php)
    setInterval(() => {
        if (!document.hidden) loadSessions(false);
    }, refreshMs);

    loadSessions(true);
})();
