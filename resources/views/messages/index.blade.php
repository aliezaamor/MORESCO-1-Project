@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="grid-2" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
    <!-- Send Message Form -->
    <div>
        <div class="card" style="padding: 1rem;">
            <h3 id="formTitle" style="font-size: 1rem; margin-bottom: 1rem; color: var(--moresco-blue);">Send New Message</h3>
            <form id="sendMessageForm">
                <div class="form-group" id="messageTypeGroup">
                    <label class="form-label" style="font-size: 0.9rem;">Message Type</label>
                    <select name="type" class="form-control" onchange="toggleRecipientInput()" id="messageTypeSelect" style="padding: 0.75rem;">
                        <option value="individual">Individual</option>
                        <option value="broadcast">Broadcast</option>
                    </select>
                </div>

                <div class="form-group" id="contactInput" style="font-size: 0.9rem; margin-top: 0.5rem;">
                    <label class="form-label">Select Recipient (Contact)</label>
                    <select name="contact_id" class="form-control" id="contactSelect" style="padding: 0.75rem;">
                        <option value="">Choose Contact...</option>
                    </select>
                </div>

                <!-- Broadcast Specific Options -->
                <div id="broadcastOptions" style="display: none;">
                    <div class="form-group" style="font-size: 0.9rem; margin-top: 1rem;">
                        <label class="form-label">Select Target Group/s</label>
                        <div id="groupCheckboxContainer" style="background: white; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem; max-height: 150px; overflow-y: auto; display: flex; flex-direction: column; gap: 0.5rem;">
                            <!-- Populated by JS -->
                            <div style="color: var(--text-light); font-size: 0.8rem;">Loading groups...</div>
                        </div>
                        <small style="color: var(--text-light); font-size: 0.7rem; margin-top: 0.25rem; display: block;">Select one or more groups for this broadcast</small>
                    </div>

                    <div class="form-group" style="font-size: 0.9rem; margin-top: 1rem;">
                        <label class="form-label">Message Category</label>
                        <select name="category" class="form-control" style="padding: 0.75rem;">
                            <option value="ADVISORY">ADVISORY</option>
                            <option value="OUTAGE">OUTAGE</option>
                            <option value="EVENTS">EVENTS</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; background: var(--item-hover); padding: 0.75rem; border-radius: 8px;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                            <input type="checkbox" name="is_scheduled" id="isScheduled" onchange="toggleScheduling()"> 
                            <span><i class="fa-solid fa-calendar-plus" style="color: var(--primary-color);"></i> Schedule Send</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                            <input type="checkbox" name="no_reply" checked> 
                            <span><i class="fa-solid fa-microphone-slash" style="color: #64748b;"></i> No-Reply Policy</span>
                        </label>
                    </div>

                    <div class="form-group" id="scheduledAtInput" style="font-size: 0.9rem; margin-top: 1rem; display: none;">
                        <label class="form-label">Schedule Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" style="padding: 0.75rem;">
                    </div>
                </div>

                <div class="form-group" style="font-size: 0.9rem; margin-top: 1rem;">
                    <label class="form-label">Message Content</label>
                    <textarea name="content" class="form-control" rows="6" placeholder="Write your message here..." required style="padding: 1rem; resize: none; height: 120px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-weight: 600; border-radius: 10px; height: 20px;">
                    <i class="fa-solid fa-paper-plane"></i> Dispatch Message
                </button>
            </form>
        </div>
    </div>

    <!-- Message History -->
    <div>
        <div class="header" style="margin-bottom: 1rem;">
            <h3 style="font-size: 1rem;">History</h3>
            <button class="btn" onclick="loadMessages()" style="background: #f1f5f9; padding: 0.4rem 0.8rem; font-size: 0.75rem;">
                <i class="fa-solid fa-refresh"></i> Refresh
            </button>
        </div>
        <div class="card" style="padding: 0;">
            <div class="scrollable-container" style="max-height: 405px; padding: 0;">
                <ul id="message-list" style="list-style: none; padding: 0;">
                    <!-- Populated by JS -->
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleRecipientInput() {
        const type = document.getElementById('messageTypeSelect').value;
        document.getElementById('contactInput').style.display = type === 'individual' ? 'block' : 'none';
        document.getElementById('broadcastOptions').style.display = type === 'broadcast' ? 'block' : 'none';
    }

    function toggleScheduling() {
        const isScheduled = document.getElementById('isScheduled').checked;
        document.getElementById('scheduledAtInput').style.display = isScheduled ? 'block' : 'none';
    }

    async function loadOptions() {
        const [contacts, groups] = await Promise.all([
            fetchAPI('/contacts'),
            fetchAPI('/groups')
        ]);

        const contactSelect = document.getElementById('contactSelect');
        contactSelect.innerHTML = '<option value="">Choose Contact...</option>' + 
            contacts.map(c => `<option value="${c.id}">${c.name} (${c.phone_number})</option>`).join('');

        const groupContainer = document.getElementById('groupCheckboxContainer');
        if (groups.length === 0) {
            groupContainer.innerHTML = '<div style="color: var(--text-light); font-size: 0.8rem;">No groups available.</div>';
        } else {
            groupContainer.innerHTML = groups.map(g => `
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.25rem 0; font-size: 0.85rem;">
                    <input type="checkbox" name="group_ids[]" value="${g.id}">
                    <span>${g.name}</span>
                </label>
            `).join('');
        }
    }

    async function loadMessages() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const typeParam = urlParams.get('type');
            const endpoint = typeParam ? `/messages?type=${typeParam}` : '/messages';
            
            const messages = await fetchAPI(endpoint);
            const list = document.getElementById('message-list');
            list.innerHTML = messages.map(m => {
                let badgeColor = '#64748b'; // default individual
                let categoryLabel = '';
                
                if (m.type === 'broadcast') {
                    badgeColor = 'var(--primary-color)';
                    categoryLabel = `<span class="badge" style="background: #e2e8f0; color: #475569; padding: 1px 6px; border-radius: 4px; font-size: 0.6rem; margin-left: 0.5rem; border: 1px solid #cbd5e1;">${m.category}</span>`;
                }
                
                if (m.type === 'incoming') badgeColor = '#f59e0b';
                if (m.type === 'auto_reply') badgeColor = '#10b981';

                const scheduledInfo = m.is_scheduled 
                    ? `<div style="font-size: 0.65rem; color: #ef4444; margin-top: 0.25rem;">
                        <i class="fa-solid fa-clock"></i> Scheduled for: ${new Date(m.scheduled_at).toLocaleString()}
                       </div>`
                    : '';

                const noReplyBadge = m.no_reply && m.type === 'broadcast'
                    ? `<span style="font-size: 0.6rem; color: #64748b; margin-left: auto;">
                        <i class="fa-solid fa-microphone-slash"></i> No-Reply
                       </span>`
                    : '';

                return `
                <li style="padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--border-color); ${m.type === 'incoming' ? 'background: var(--item-hover);' : ''}">
                    <div style="display: flex; align-items: center; margin-bottom: 0.25rem;">
                        <span class="badge" style="background: ${badgeColor}; color: white; padding: 1px 6px; border-radius: 4px; font-size: 0.65rem; text-transform: uppercase;">${m.type}</span>
                        ${categoryLabel}
                        ${noReplyBadge}
                        <span style="font-size: 0.7rem; color: var(--text-light); margin-left: ${noReplyBadge ? '0.5rem' : 'auto'};">${new Date(m.created_at).toLocaleString([], {year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                    ${scheduledInfo}
                    <div style="margin-top: 0.4rem; margin-bottom: 0.25rem; color: var(--text-color); font-size: 0.8125rem; font-weight: ${m.type === 'incoming' ? '600' : '400'}; line-height: 1.3; white-space: pre-wrap;">
                        ${m.type === 'incoming' ? '<i class="fa-solid fa-reply-all" style="font-size: 0.7rem; color: #d97706;"></i> ' : ''}
                        ${m.content}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-light); display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fa-solid fa-user" style="font-size: 0.65rem;"></i>
                        ${m.recipients.length > 0 ? m.recipients.map(r => r.contact ? r.contact.name : 'Unknown').join(', ') : (m.is_scheduled ? 'Scheduled Recipients' : 'No Recipients')}
                    </div>
                </li>
                `;
            }).join('');
        } catch (err) {
            console.error(err);
        }
    }

    document.getElementById('sendMessageForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Convert checkbox values to boolean strings for the API
        const data = Object.fromEntries(formData);
        data.is_scheduled = formData.get('is_scheduled') === 'on' ? '1' : '0';
        data.no_reply = formData.get('no_reply') === 'on' ? '1' : '0';
        
        // Handle multiple group IDs
        if (data.type === 'broadcast') {
            data.group_ids = formData.getAll('group_ids[]');
        }
        
        try {
            const response = await fetchAPI('/messages', {
                method: 'POST',
                body: JSON.stringify(data)
            });

            // Capture current type before reset
            const currentType = document.getElementById('messageTypeSelect').value;
            
            e.target.reset();
            
            // Restore type and update UI
            document.getElementById('messageTypeSelect').value = currentType;
            toggleRecipientInput();
            toggleScheduling();
            
            alert(response.message || 'Action completed successfully!');
            loadMessages();
        } catch (err) {
            alert('Action failed: ' + err.message);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadOptions();
        loadMessages();
        
        // Handle query parameter for auto-selecting broadcast
        const urlParams = new URLSearchParams(window.location.search);
        const typeParam = urlParams.get('type');
        if (typeParam) {
            document.getElementById('messageTypeSelect').value = typeParam;
            document.getElementById('messageTypeGroup').style.display = 'none'; // Hide selector if type is forced
            
            // Update Title
            const title = typeParam === 'broadcast' ? 'Send Broadcast Message' : 'Send Individual Notification';
            document.getElementById('formTitle').innerText = title;
            
            toggleRecipientInput();
        }
    });
</script>
@endpush
