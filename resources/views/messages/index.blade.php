@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="grid-2" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
    <!-- Send Message Form -->
    <div>
        <div class="card" style="padding: 1rem;">
            <h3 style="font-size: 1rem; margin-bottom: 1rem; color: var(--moresco-blue);">Send New Message</h3>
            <form id="sendMessageForm">
                <div class="form-group">
                    <label class="form-label" style="font-size: 0.9rem;">Message Type</label>
                    <div style="font-size: 0.9rem; display: flex; gap: 1.5rem; background: var(--item-hover); padding: 0.5rem; border-radius: 8px;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="type" value="individual" checked onchange="toggleRecipientInput()"> Individual
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="type" value="broadcast" onchange="toggleRecipientInput()"> Broadcast
                        </label>
                    </div>
                </div>

                <div class="form-group" id="contactInput" style="font-size: 0.9rem; margin-top: 0.5rem;">
                    <label class="form-label">Select Recipient (Contact)</label>
                    <select name="contact_id" class="form-control" id="contactSelect" style="padding: 0.75rem;">
                        <option value="">Choose Contact...</option>
                    </select>
                </div>

                <div class="form-group" id="groupInput" style="font-size: 0.9rem; display: none; margin-top: 0.5rem;">
                    <label class="form-label">Select Target (Group)</label>
                    <select name="group_id" class="form-control" id="groupSelect" style="padding: 0.75rem;">
                        <option value="">Choose Group...</option>
                    </select>
                </div>

                <div class="form-group" style="font-size: 0.9rem; margin-top: 0.5rem;">
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
        const type = document.querySelector('input[name="type"]:checked').value;
        document.getElementById('contactInput').style.display = type === 'individual' ? 'block' : 'none';
        document.getElementById('groupInput').style.display = type === 'broadcast' ? 'block' : 'none';
    }

    async function loadOptions() {
        const [contacts, groups] = await Promise.all([
            fetchAPI('/contacts'),
            fetchAPI('/groups')
        ]);

        const contactSelect = document.getElementById('contactSelect');
        contactSelect.innerHTML = '<option value="">Choose Contact...</option>' + 
            contacts.map(c => `<option value="${c.id}">${c.name} (${c.phone_number})</option>`).join('');

        const groupSelect = document.getElementById('groupSelect');
        groupSelect.innerHTML = '<option value="">Choose Group...</option>' + 
            groups.map(g => `<option value="${g.id}">${g.name}</option>`).join('');
    }

    async function loadMessages() {
        try {
            const messages = await fetchAPI('/messages');
            const list = document.getElementById('message-list');
            list.innerHTML = messages.map(m => {
                let badgeColor = '#64748b'; // default individual
                if (m.type === 'broadcast') badgeColor = 'var(--primary-color)';
                if (m.type === 'incoming') badgeColor = '#f59e0b'; // Amber for incoming
                if (m.type === 'auto_reply') badgeColor = '#10b981'; // Green for auto-reply

                return `
                <li style="padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--border-color); ${m.type === 'incoming' ? 'background: var(--item-hover);' : ''}">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span class="badge" style="background: ${badgeColor}; color: white; padding: 1px 6px; border-radius: 4px; font-size: 0.65rem; text-transform: uppercase;">${m.type}</span>
                        <span style="font-size: 0.7rem; color: var(--text-light);">${new Date(m.created_at).toLocaleString([], {year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                    <div style="margin-bottom: 0.25rem; color: var(--text-color); font-size: 0.8125rem; font-weight: ${m.type === 'incoming' ? '600' : '400'}; line-height: 1.3; white-space: pre-wrap;">
                        ${m.type === 'incoming' ? '<i class="fa-solid fa-reply-all" style="font-size: 0.7rem; color: #d97706;"></i> ' : ''}
                        ${m.content}
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-light); display: flex; align-items: center; gap: 0.4rem;">
                        <i class="fa-solid fa-user" style="font-size: 0.65rem;"></i>
                        ${m.recipients.map(r => r.contact ? r.contact.name : 'Unknown').join(', ')}
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
        const data = Object.fromEntries(formData);
        
        try {
            await fetchAPI('/messages', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            e.target.reset();
            alert('Message sent successfully!');
            loadMessages();
        } catch (err) {
            alert('Failed to send message: ' + err.message);
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadOptions();
        loadMessages();
    });
</script>
@endpush
