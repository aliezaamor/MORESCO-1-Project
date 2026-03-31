@extends('layouts.app')

@section('title', 'SMS Activity Monitor')

@section('content')
<div style="max-width: 1100px; margin: 0 auto;">

    {{-- Header --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-color);">
                <i class="fa-solid fa-chart-line" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
                SMS Activity Monitor
            </h2>
            <p style="font-size: 0.82rem; color: var(--text-light); margin: 0.25rem 0 0;">
                <span id="viewModeText">Live view of incoming SMS activity. Auto-refreshes every 15 seconds.</span><br>
                Rate window: <strong>{{ \App\Services\RateLimitService::WINDOW_MINUTES }} minutes</strong> &nbsp;|&nbsp;
                Warn at <strong>{{ \App\Services\RateLimitService::WARN_AT }}</strong> msgs &nbsp;|&nbsp;
                Throttle at <strong>{{ \App\Services\RateLimitService::THROTTLE_AT }}</strong> &nbsp;|&nbsp;
                Block at <strong>{{ \App\Services\RateLimitService::BLOCK_AT }}</strong>
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <input type="date" id="activityDate" onchange="handleDateChange()" title="Filter by date" style="font-size: 0.82rem; padding: 0.45rem 0.6rem; border: 1px solid var(--border-color); border-radius: 6px; background: transparent; color: var(--text-color); outline: none;">
            <span id="refreshCountdown" style="font-size: 0.78rem; color: var(--text-light);"></span>
            <button onclick="loadActivityData()" class="btn" style="padding: 0.45rem 0.9rem; font-size: 0.82rem; background: var(--item-hover);">
                <i class="fa-solid fa-rotate"></i> Refresh Now
            </button>
        </div>
    </div>

    {{-- Listener Status Panel --}}
    <div class="card" id="listenerPanel" style="margin-bottom: 1.25rem; padding: 0; overflow: hidden;">
        <div style="padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.75rem; cursor: pointer;" onclick="toggleListenerLogs()">
            <i class="fa-solid fa-satellite-dish" style="color: var(--primary-color);"></i>
            <span style="font-weight: 600; font-size: 0.88rem;">Yeastar AMI Listener</span>
            <span id="listenerStatusBadge" style="display: inline-flex; align-items: center; gap: 0.3rem; padding: 2px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 600; background: rgba(156,163,175,0.15); color: #6b7280; border: 1px solid #6b728033;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 0.6rem;"></i> Loading...
            </span>
            <span id="listenerPid" style="font-size: 0.75rem; color: var(--text-light); margin-left: 0.25rem;"></span>
            <span id="listenerLastSeen" style="font-size: 0.75rem; color: var(--text-light); margin-left: auto; margin-right: 0.5rem;"></span>
            <i id="listenerToggleIcon" class="fa-solid fa-chevron-down" style="font-size: 0.75rem; color: var(--text-light); transition: transform 0.2s;"></i>
        </div>
        <div id="listenerLogFeed" style="max-height: 200px; overflow-y: auto; padding: 0.5rem 1.25rem; font-family: monospace; font-size: 0.76rem; background: var(--item-hover); display: none;">
            <div style="color: var(--text-light); padding: 0.5rem 0;">Loading listener logs...</div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div id="summaryCards" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border-left: 4px solid #10b981;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.6rem; color: #10b981;"></i>
            <div>
                <div id="countNormal" style="font-size: 1.6rem; font-weight: 700; color: var(--text-color); line-height: 1;">—</div>
                <div style="font-size: 0.78rem; color: var(--text-light); margin-top: 0.2rem;">Normal</div>
            </div>
        </div>
        <div class="card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border-left: 4px solid #f59e0b;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 1.6rem; color: #f59e0b;"></i>
            <div>
                <div id="countWarning" style="font-size: 1.6rem; font-weight: 700; color: var(--text-color); line-height: 1;">—</div>
                <div style="font-size: 0.78rem; color: var(--text-light); margin-top: 0.2rem;">Warning / Nearing Limit</div>
            </div>
        </div>
        <div class="card" style="padding: 1rem 1.25rem; display: flex; align-items: center; gap: 1rem; border-left: 4px solid #ef4444;">
            <i class="fa-solid fa-ban" style="font-size: 1.6rem; color: #ef4444;"></i>
            <div>
                <div id="countBlocked" style="font-size: 1.6rem; font-weight: 700; color: var(--text-color); line-height: 1;">—</div>
                <div style="font-size: 0.78rem; color: var(--text-light); margin-top: 0.2rem;">Throttled / Blocked</div>
            </div>
        </div>
    </div>

    {{-- Activity Table --}}
    <div class="card" style="padding: 0; overflow: hidden;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.75rem;">
            <i class="fa-solid fa-table-list" style="color: var(--primary-color);"></i>
            <span id="tableTitle" style="font-weight: 600; font-size: 0.9rem;">Active Senders (Last Hour)</span>
            <span id="lastUpdated" style="font-size: 0.75rem; color: var(--text-light); margin-left: auto;"></span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: var(--item-hover); border-bottom: 1px solid var(--border-color);">
                        <th style="text-align: left; padding: 0.75rem 1.25rem; font-size: 0.75rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Contact</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-size: 0.75rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Phone</th>
                        <th style="text-align: center; padding: 0.75rem 1rem; font-size: 0.75rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Msgs (5 min)</th>
                        <th style="text-align: center; padding: 0.75rem 1rem; font-size: 0.75rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                        <th style="text-align: left; padding: 0.75rem 1rem; font-size: 0.75rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Last Seen</th>
                        <th style="text-align: center; padding: 0.75rem 1.25rem; font-size: 0.75rem; font-weight: 600; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.05em;">Action</th>
                    </tr>
                </thead>
                <tbody id="activityTableBody">
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-light); font-size: 0.85rem;">
                            <i class="fa-solid fa-spinner fa-spin"></i> Loading activity data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ── Listener Status ───────────────────────────────────────────────────────
    let listenerLogsVisible = false;

    const LISTENER_STATUS_CONFIG = {
        connected:    { label: 'Connected',    color: '#10b981', bg: 'rgba(16,185,129,0.12)',  icon: 'fa-circle-check' },
        disconnected: { label: 'Disconnected', color: '#ef4444', bg: 'rgba(239,68,68,0.12)',   icon: 'fa-circle-xmark' },
        unknown:      { label: 'Unknown',      color: '#f59e0b', bg: 'rgba(245,158,11,0.12)',  icon: 'fa-circle-question' },
    };

    const LOG_LEVEL_COLOR = {
        INFO:    'var(--text-color)',
        WARNING: '#f59e0b',
        ERROR:   '#ef4444',
        DEBUG:   'var(--text-light)',
    };

    async function loadListenerStatus() {
        try {
            const resp = await fetch('{{ route("sms.activity.listener") }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            renderListenerStatus(data);
        } catch (e) {
            console.error('Failed to load listener status', e);
        }
    }

    function renderListenerStatus(data) {
        const cfg    = LISTENER_STATUS_CONFIG[data.status] || LISTENER_STATUS_CONFIG.unknown;
        const badge  = document.getElementById('listenerStatusBadge');
        badge.style.background  = cfg.bg;
        badge.style.color       = cfg.color;
        badge.style.borderColor = cfg.color + '55';
        badge.innerHTML = `<i class="fa-solid ${cfg.icon}" style="font-size:0.6rem;"></i> ${cfg.label}`;

        const pidEl = document.getElementById('listenerPid');
        pidEl.textContent = data.pid ? `PID ${data.pid}` : '';

        const lastEl = document.getElementById('listenerLastSeen');
        lastEl.textContent = data.last_seen_at ? `Last: ${data.last_seen_at}` : 'No events logged today';

        const feed = document.getElementById('listenerLogFeed');
        if (!data.logs || data.logs.length === 0) {
            feed.innerHTML = '<div style="color: var(--text-light); padding: 0.5rem 0;">No Yeastar AMI log entries found for today. Restart the listener to begin logging.</div>';
            return;
        }

        feed.innerHTML = data.logs.slice().reverse().map(entry => {
            const color = LOG_LEVEL_COLOR[entry.level] || 'var(--text-color)';
            const levelBadge = `<span style="color:${color}; font-weight:600; min-width:4rem; display:inline-block;">[${entry.level}]</span>`;
            return `<div style="padding: 2px 0; border-bottom: 1px solid var(--border-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${escHtml(entry.message)}">
                <span style="color: var(--text-light); margin-right: 0.5rem;">${escHtml(entry.time)}</span>${levelBadge} ${escHtml(entry.message)}
            </div>`;
        }).join('');
    }

    function toggleListenerLogs() {
        listenerLogsVisible = !listenerLogsVisible;
        const feed = document.getElementById('listenerLogFeed');
        const icon = document.getElementById('listenerToggleIcon');
        feed.style.display = listenerLogsVisible ? 'block' : 'none';
        icon.style.transform = listenerLogsVisible ? 'rotate(180deg)' : 'rotate(0deg)';
    }

    const STATUS_CONFIG = {
        normal:    { label: 'Normal',    color: '#10b981', bg: 'rgba(16,185,129,0.12)', icon: 'fa-circle-check' },
        warning:   { label: 'Warning',   color: '#f59e0b', bg: 'rgba(245,158,11,0.12)', icon: 'fa-triangle-exclamation' },
        throttled: { label: 'Throttled', color: '#ef4444', bg: 'rgba(239,68,68,0.12)',  icon: 'fa-circle-pause' },
        blocked:   { label: 'Blocked',   color: '#dc2626', bg: 'rgba(220,38,38,0.12)',  icon: 'fa-ban' },
    };

    let refreshTimer   = null;
    let countdownTimer = null;
    let countdown      = 15;

    async function loadActivityData() {
        try {
            const dateVal = document.getElementById('activityDate').value;
            const query = dateVal ? `?date=${encodeURIComponent(dateVal)}` : '';
            const resp = await fetch(`{{ route("sms.activity.data") }}${query}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            renderTable(data);
            renderSummary(data);
            document.getElementById('lastUpdated').textContent = 'Updated: ' + new Date().toLocaleTimeString();
            resetCountdown();
        } catch (e) {
            console.error('Failed to fetch activity data', e);
        }
        loadListenerStatus();
    }

    function renderTable(data) {
        const tbody = document.getElementById('activityTableBody');
        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-light); font-size: 0.85rem;">
                <i class="fa-solid fa-inbox" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem; opacity: 0.4;"></i>
                No SMS activity in the last hour.
            </td></tr>`;
            return;
        }

        tbody.innerHTML = data.map(row => {
            const cfg   = STATUS_CONFIG[row.status] || STATUS_CONFIG.normal;
            const badge = `<span style="display: inline-flex; align-items: center; gap: 0.3rem; background: ${cfg.bg}; color: ${cfg.color}; padding: 3px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 600; border: 1px solid ${cfg.color}33;">
                <i class="fa-solid ${cfg.icon}" style="font-size: 0.65rem;"></i> ${cfg.label}
            </span>`;

            const meterWidth = Math.min(100, Math.round((row.message_count / {{ \App\Services\RateLimitService::BLOCK_AT }}) * 100));
            const meterColor = row.status === 'normal' ? '#10b981' : (row.status === 'warning' ? '#f59e0b' : '#ef4444');
            const meter = `<div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                <div style="width: 60px; height: 6px; background: var(--border-color); border-radius: 3px; overflow: hidden;">
                    <div style="width: ${meterWidth}%; height: 100%; background: ${meterColor}; border-radius: 3px; transition: width 0.4s ease;"></div>
                </div>
                <span style="font-weight: 600; color: ${meterColor};">${row.message_count}</span>
            </div>`;

            const canUnblock = row.status === 'blocked' || row.status === 'throttled' || row.status === 'warning';
            const origText = row.status === 'warning' ? 'Reset' : 'Unblock';
            const origIcon = row.status === 'warning' ? 'fa-rotate-left' : 'fa-lock-open';

            const actionBtn  = canUnblock
                ? `<button onclick="unblockContact(${row.contact_id}, this, '${origText}', '${origIcon}')"
                        style="padding: 0.3rem 0.75rem; font-size: 0.75rem; background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; border-radius: 6px; cursor: pointer; font-weight: 500; transition: all 0.2s;"
                        onmouseover="this.style.background='#dc2626';this.style.color='#fff';"
                        onmouseout="this.style.background='#fef2f2';this.style.color='#dc2626';">
                        <i class="fa-solid ${origIcon}"></i> ${origText}
                   </button>`
                : `<span style="color: var(--text-light); font-size: 0.75rem;">—</span>`;

            return `<tr style="border-bottom: 1px solid var(--border-color); transition: background 0.15s;"
                        onmouseover="this.style.background='var(--item-hover)'"
                        onmouseout="this.style.background=''">
                <td style="padding: 0.85rem 1.25rem;">
                    <div style="font-weight: 600; color: var(--text-color); font-size: 0.85rem;">${escHtml(row.name)}</div>
                </td>
                <td style="padding: 0.85rem 1rem; color: var(--text-light); font-family: monospace; font-size: 0.82rem;">${escHtml(row.phone)}</td>
                <td style="padding: 0.85rem 1rem; text-align: center;">${meter}</td>
                <td style="padding: 0.85rem 1rem; text-align: center;">${badge}</td>
                <td style="padding: 0.85rem 1rem; color: var(--text-light); font-size: 0.8rem;">${escHtml(row.last_seen_at)}</td>
                <td style="padding: 0.85rem 1.25rem; text-align: center;">${actionBtn}</td>
            </tr>`;
        }).join('');
    }

    function renderSummary(data) {
        const counts = { normal: 0, warning: 0, blocked: 0 };
        data.forEach(row => {
            if (row.status === 'normal')                             counts.normal++;
            else if (row.status === 'warning')                      counts.warning++;
            else if (row.status === 'throttled' || row.status === 'blocked') counts.blocked++;
        });
        document.getElementById('countNormal').textContent  = counts.normal;
        document.getElementById('countWarning').textContent = counts.warning;
        document.getElementById('countBlocked').textContent = counts.blocked;
    }

    async function unblockContact(contactId, btn, origText = 'Unblock', origIcon = 'fa-lock-open') {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        try {
            const resp = await fetch(`/sms/activity/${contactId}/unblock`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' }
            });
            const result = await resp.json();
            if (result.success) {
                await loadActivityData();
            } else {
                alert('Failed to process. Please try again.');
                btn.disabled = false;
                btn.innerHTML = `<i class="fa-solid ${origIcon}"></i> ${origText}`;
            }
        } catch (e) {
            alert('Error: ' + e.message);
            btn.disabled = false;
            btn.innerHTML = `<i class="fa-solid ${origIcon}"></i> ${origText}`;
        }
    }

    function escHtml(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function resetCountdown() {
        countdown = 15;
        clearInterval(countdownTimer);
        clearTimeout(refreshTimer);

        if (document.getElementById('activityDate').value) {
            document.getElementById('refreshCountdown').textContent = 'Auto-refresh paused';
            return; // Don't auto-refresh if viewing history
        }

        countdownTimer = setInterval(() => {
            countdown--;
            document.getElementById('refreshCountdown').textContent = `Next refresh in ${countdown}s`;
            if (countdown <= 0) {
                clearInterval(countdownTimer);
            }
        }, 1000);

        refreshTimer = setTimeout(() => {
            loadActivityData();
        }, 15000);
    }

    function handleDateChange() {
        const dateVal = document.getElementById('activityDate').value;
        const viewModeText = document.getElementById('viewModeText');
        const tableTitle = document.getElementById('tableTitle');

        if (dateVal) {
            viewModeText.textContent = `Historical view for ${dateVal}. (Auto-refresh paused)`;
            tableTitle.textContent = `Rate Limit History (${dateVal})`;
        } else {
            viewModeText.textContent = 'Live view of incoming SMS activity. Auto-refreshes every 15 seconds.';
            tableTitle.textContent = 'Active Senders (Last Hour)';
        }
        
        loadActivityData();
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadActivityData();
    });
</script>
@endpush
