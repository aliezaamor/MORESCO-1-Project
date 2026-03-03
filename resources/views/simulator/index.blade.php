@extends('layouts.app')

@section('title', 'SMS Simulator')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; height: calc(100vh - 200px); display: flex; gap: 2rem;">
    <!-- Left Sidebar: Contact Selection -->
    <div class="card" style="width: 300px; flex-shrink: 0; display: flex; flex-direction: column; padding: 1.5rem;">
        <h3 style="font-size: 1.125rem; margin-bottom: 1rem; color: var(--moresco-blue);">Select Consumer</h3>
        <div class="form-group" style="margin-bottom: 1rem;">
            <input type="text" id="contactSearch" class="form-control" placeholder="Search contact..." style="font-size: 0.875rem;">
        </div>
        <div id="contactList" class="scrollable-container" style="flex-grow: 1; border: 1px solid var(--border-color); border-radius: 8px;">
            <!-- Populated by JS -->
            <div style="padding: 1rem; text-align: center; color: var(--text-light); font-size: 0.875rem;">Loading contacts...</div>
        </div>
        <button class="btn" style="margin-top: 1rem; width: 100%; font-size: 0.875rem; background: var(--item-hover); color: var(--text-color); border: 1px solid var(--border-color);" onclick="showNewContactForm()">
            <i class="fa-solid fa-plus"></i> New Number
        </button>
    </div>

    <!-- Right Area: Chat Window -->
    <div class="card" style="flex-grow: 1; display: flex; flex-direction: column; padding: 0;">
        <!-- Chat Header -->
        <div id="chatHeader" style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); background: var(--item-hover); border-radius: 12px 12px 0 0;">
            <div style="font-weight: 600; color: var(--moresco-blue);" id="selectedContactName">Select a contact to start</div>
            <div style="font-size: 0.75rem; color: var(--text-light);" id="selectedContactPhone">---</div>
        </div>

        <!-- Messages Area -->
        <div id="chatMessages" class="scrollable-container" style="flex-grow: 1; padding: 1.5rem; background: var(--surface-color); display: flex; flex-direction: column; gap: 1rem;">
            <div style="height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-light);">
                <div style="text-align: center;">
                    <i class="fa-solid fa-comments" style="font-size: 3rem; opacity: 0.2; margin-bottom: 1rem;"></i>
                    <p>Select a contact to view conversation</p>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); background: var(--surface-color); border-radius: 0 0 12px 12px;">
            <form id="simulatorForm" style="display: flex; gap: 1rem;">
                <input type="hidden" id="activePhone">
                <input type="text" id="sim_content" class="form-control" placeholder="Type a message (e.g., BILL)..." required disabled>
                <button type="submit" id="sendBtn" class="btn btn-primary" style="padding: 0.5rem 1.5rem; border-radius: 8px;" disabled>
                    <i class="fa-solid fa-paper-plane"></i> Send
                </button>
            </form>
            <p style="font-size: 0.7rem; color: var(--text-light); margin-top: 0.5rem;">
                <i class="fa-solid fa-info-circle"></i> This simulates a message <strong>from</strong> the consumer <strong>to</strong> the system.
            </p>
        </div>
    </div>
</div>

<!-- Modal for New Number (Simple alert-based for now or hidden div) -->
<div id="newContactModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 400px; padding: 2rem;">
        <h3>Simulate New Number</h3>
        <div class="form-group" style="margin-top: 1rem;">
            <label class="form-label">Phone Number</label>
            <input type="text" id="newPhone" class="form-control" placeholder="e.g. 09123456789">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
            <button class="btn" style="background: var(--item-hover); color: var(--text-color); border: 1px solid var(--border-color);" onclick="document.getElementById('newContactModal').style.display='none'">Cancel</button>
            <button class="btn btn-primary" onclick="startNewSim()">Start Chat</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .contact-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--border-color);
        cursor: pointer;
        transition: background 0.2s;
    }
    .contact-item:hover { background: var(--item-hover); color: var(--text-color); }
    .contact-item.active { background: var(--item-hover); border-left: 4px solid var(--primary-color); }
    
    /* Dark mode override for contact hover */
    body.dark-mode .contact-item:hover {
        background: #334155 !important;
        color: #ffffff !important;
    }
    body.dark-mode .contact-item:hover div {
        color: #ffffff !important;
    }
    body.dark-mode .contact-item.active {
        background: #334155 !important;
        color: #ffffff !important;
    }
    body.dark-mode .contact-item.active div {
        color: #ffffff !important;
    }
    
    .chat-bubble {
        max-width: 80%;
        padding: 0.75rem 1rem;
        border-radius: 15px;
        font-size: 0.875rem;
        line-height: 1.4;
        position: relative;
    }
    .bubble-incoming {
        align-self: flex-end;
        background: var(--primary-color);
        color: white;
        border-bottom-right-radius: 2px;
    }
    .bubble-outgoing {
        align-self: flex-start;
        background: var(--item-hover);
        color: var(--text-color);
        border-bottom-left-radius: 2px;
    }
    .bubble-auto {
        align-self: flex-start;
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        border-bottom-left-radius: 2px;
    }
    .bubble-time {
        font-size: 0.7rem;
        margin-top: 0.25rem;
        opacity: 0.8;
    }
</style>

<script>
    let contacts = [];
    let activeContactId = null;

    async function loadContacts() {
        try {
            contacts = await fetchAPI('/contacts');
            renderContacts();
        } catch (err) {
            console.error('Failed to load contacts', err);
        }
    }

    function renderContacts() {
        const list = document.getElementById('contactList');
        const search = document.getElementById('contactSearch').value.toLowerCase();
        
        const filtered = contacts.filter(c => 
            c.name.toLowerCase().includes(search) || 
            c.phone_number.includes(search)
        );

        if (filtered.length === 0) {
            list.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--text-light); font-size: 0.875rem;">No results</div>';
            return;
        }

        list.innerHTML = filtered.map(c => `
            <div class="contact-item ${activeContactId == c.id ? 'active' : ''}" onclick="selectContact(${c.id})">
                <div style="font-weight: 600; font-size: 0.875rem;">${c.name}</div>
                <div style="font-size: 0.75rem; color: var(--text-light);">${c.phone_number}</div>
            </div>
        `).join('');
    }

    async function selectContact(id) {
        activeContactId = id;
        const contact = contacts.find(c => c.id == id);
        
        document.getElementById('selectedContactName').innerText = contact.name;
        document.getElementById('selectedContactPhone').innerText = contact.phone_number;
        document.getElementById('activePhone').value = contact.phone_number;
        document.getElementById('sim_content').disabled = false;
        document.getElementById('sendBtn').disabled = false;

        renderContacts();
        await loadHistory(id);
    }

    async function loadHistory(id) {
        const chatArea = document.getElementById('chatMessages');
        chatArea.innerHTML = '<div style="text-align:center; padding: 2rem; color: var(--text-light);">Loading...</div>';

        try {
            const history = await fetchAPI(`/simulator/history/${id}`);
            if (history.length === 0) {
                chatArea.innerHTML = '<div style="text-align:center; padding: 2rem; color: var(--text-light);">New Conversation</div>';
                return;
            }

            chatArea.innerHTML = history.map(m => {
                let typeClass = 'bubble-outgoing';
                let label = 'Moresco-1';
                
                if (m.type === 'incoming') {
                    typeClass = 'bubble-incoming';
                    label = 'You';
                } else if (m.type === 'auto_reply') {
                    typeClass = 'bubble-auto';
                    label = 'Moresco-1 (Auto)';
                }

                return `
                    <div class="chat-bubble ${typeClass}">
                        <div style="font-weight: 700; font-size: 0.65rem; margin-bottom: 0.25rem; text-transform: uppercase; opacity: 0.8;">${label}</div>
                        <div style="white-space: pre-wrap;">${m.content}</div>
                        <div class="bubble-time">${new Date(m.created_at).toLocaleString([], {year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute:'2-digit'})}</div>
                    </div>
                `;
            }).join('');
            
            chatArea.scrollTop = chatArea.scrollHeight;
        } catch (err) {
            chatArea.innerHTML = `<div style="text-align:center; padding: 2rem; color: var(--danger-color);">Error loading history: ${err.message}</div>`;
        }
    }

    function showNewContactForm() {
        document.getElementById('newContactModal').style.display = 'flex';
    }

    async function startNewSim() {
        const phone = document.getElementById('newPhone').value;
        if (!phone) return;

        // Reset UI
        document.getElementById('newContactModal').style.display = 'none';
        document.getElementById('selectedContactName').innerText = "New Consumer";
        document.getElementById('selectedContactPhone').innerText = phone;
        document.getElementById('activePhone').value = phone;
        document.getElementById('sim_content').disabled = false;
        document.getElementById('sendBtn').disabled = false;
        
        const chatArea = document.getElementById('chatMessages');
        chatArea.innerHTML = '<div style="text-align:center; padding: 2rem; color: var(--text-light);">Starting new session...</div>';
    }

    document.getElementById('simulatorForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const content = document.getElementById('sim_content').value;
        const phone = document.getElementById('activePhone').value;

        try {
            const result = await fetchAPI('/simulate-receive', {
                method: 'POST',
                body: JSON.stringify({ phone_number: phone, content: content })
            });

            document.getElementById('sim_content').value = '';
            
            // Reload contacts in case a new one was created
            await loadContacts();
            
            // If we just sent to a "New Consumer", find its actual ID now
            if (!activeContactId) {
                const newC = contacts.find(c => c.phone_number == phone);
                if (newC) activeContactId = newC.id;
            }

            if (activeContactId) {
                await loadHistory(activeContactId);
            }
        } catch (err) {
            alert('Simulation failed: ' + err.message);
        }
    });

    document.getElementById('contactSearch').oninput = renderContacts;

    document.addEventListener('DOMContentLoaded', loadContacts);
</script>
@endpush
