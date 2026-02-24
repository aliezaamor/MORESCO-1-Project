@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="grid-4" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
        <div class="card" style="padding: 1rem;">
            <h3>Total Contacts</h3>
            <p id="total-contacts" style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">...</p>
        </div>
        <div class="card" style="padding: 1rem;">
            <h3>Total Groups</h3>
            <p id="total-groups" style="font-size: 2rem; font-weight: 700; color: #8b5cf6;">...</p>
        </div>
        <div class="card" style="padding: 1rem;">
            <h3>Total Messages</h3>
            <p id="total-messages" style="font-size: 2rem; font-weight: 700; color: var(--success-color);">...</p>
        </div>
        <div class="card" style="padding: 1rem;">
            <h3>Active Keywords</h3>
            <p id="active-keywords" style="font-size: 2rem; font-weight: 700; color: var(--danger-color);">...</p>
        </div>
    </div>

    <div class="grid-2" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
        <div class="card" style="padding: 1rem;">
            <h3 style="margin-bottom: 1rem;">Message Activity (Last 7 Days)</h3>
            <div style="height: 200px; width: 100%;">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        <div class="card" style="max-height: 300px">
             <h3>Recent Activity</h3>
             <div id="recent-logs" style="margin-top: 1rem; font-size: 0.85rem; color: var(--text-light); max-height: 200px; overflow-y: auto;">
                <div style="text-align: center; padding: 1rem;">Loading activity...</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            // Fetch counts
            const [contacts, messages, keywords, groups] = await Promise.all([
                fetchAPI('/contacts'),
                fetchAPI('/messages'),
                fetchAPI('/keywords'),
                fetchAPI('/groups')
            ]);

            document.getElementById('total-contacts').textContent = contacts.length || 0;
            document.getElementById('total-messages').textContent = messages.length || 0;
            document.getElementById('active-keywords').textContent = keywords.filter(k => k.is_active).length || 0;
            document.getElementById('total-groups').textContent = groups.length || 0;

            // Prepare Chart Data
            const last7Days = [...Array(7)].map((_, i) => {
                const d = new Date();
                d.setDate(d.getDate() - i);
                return d.toLocaleDateString(); // e.g., "2/10/2026"
            }).reverse();

            // Initialize counts
            const sentData = new Array(7).fill(0);
            const receivedData = new Array(7).fill(0);

            messages.forEach(m => {
                const date = new Date(m.created_at).toLocaleDateString();
                const index = last7Days.indexOf(date);
                if (index !== -1) {
                    if (m.type === 'incoming') {
                        receivedData[index]++;
                    } else {
                        sentData[index]++;
                    }
                }
            });

            const ctx = document.getElementById('activityChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: last7Days,
                    datasets: [
                        {
                            label: 'Sent',
                            data: sentData,
                            borderColor: '#3b82f6', // primary color
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Received',
                            data: receivedData,
                            borderColor: '#f59e0b', // amber
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

            // Recent Activity
            const recentLogs = document.getElementById('recent-logs');
            const sortedMessages = messages.sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 5);

            if (sortedMessages.length > 0) {
                 recentLogs.innerHTML = sortedMessages.map(m => {
                    const isIncoming = m.type === 'incoming';
                    const type = isIncoming ? 'Received from' : 'Sent to';
                    const icon = isIncoming 
                        ? '<i class="fa-solid fa-inbox" style="color: var(--success-color);"></i>' 
                        : '<i class="fa-solid fa-paper-plane" style="color: var(--primary-color);"></i>';
                    
                    let targets = 'Unknown';
                    if (m.recipients && m.recipients.length > 0) {
                        targets = m.recipients.map(r => r.contact ? r.contact.name : 'Unknown').join(', ');
                    }

                    return `
                        <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; align-items: flex-start; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                            <div style="margin-top: 3px;">${icon}</div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-color);">${type} ${targets}</div>
                                <div style="color: var(--text-light); font-size: 0.8rem; margin-top: 0.2rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">${m.content}</div>
                                <div style="color: #cbd5e1; font-size: 0.7rem; margin-top: 0.25rem;">${new Date(m.created_at).toLocaleString()}</div>
                            </div>
                        </div>
                    `;
                 }).join('');
            } else {
                recentLogs.innerHTML = '<div style="text-align: center; color: var(--text-light); padding: 1rem;">No recent activity found.</div>';
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
            document.getElementById('recent-logs').innerHTML = '<div style="color: var(--danger-color);">Error loading activity.</div>';
        }
    });
</script>
@endpush
