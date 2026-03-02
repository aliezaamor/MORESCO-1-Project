@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="grid-2" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
    <!-- Send Message Form -->
    <div id="sendMessageContainer">
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

                <div class="form-group" id="contactInput" style="font-size: 0.9rem; margin-top: 0.5rem; position: relative;">
                    <label class="form-label">Select Recipient (Contact)</label>
                    <input type="hidden" name="contact_id" id="contactIdInput">
                    <div id="customContactSelect" tabindex="0" class="form-control" style="padding: 0.75rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleContactDropdown(event)">
                        <span id="contactSelectText" style="color: var(--text-color);">Choose Contact...</span>
                        <i class="fa-solid fa-chevron-down" style="color: var(--text-light); font-size: 0.8em; pointer-events: none;"></i>
                    </div>
                    
                    <div id="contactDropdownList" style="display: none; position: absolute; top: calc(100% - 0.5rem); left: 0; right: 0; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem; flex-direction: column; z-index: 10; box-shadow: var(--shadow-md); margin-top: 0.5rem; background: var(--input-bg); max-height: 200px; overflow-y: auto;">
                        <div style="color: var(--text-light); font-size: 0.8rem; padding: 0.5rem;">Loading contacts...</div>
                    </div>
                </div>

                <!-- Broadcast Specific Options -->
                <div id="broadcastOptions" style="display: none;">
                    <div class="form-group" style="font-size: 0.9rem; margin-top: 1rem; position: relative;">
                        <label class="form-label">Select Target Group/s</label>
                        <div id="customGroupSelect" tabindex="0" class="form-control" style="padding: 0.75rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleGroupDropdown(event)">
                            <span id="groupSelectText" style="color: var(--text-color);">Select groups...</span>
                            <i class="fa-solid fa-chevron-down" style="color: var(--text-light); font-size: 0.8em; pointer-events: none;"></i>
                        </div>
                        
                        <div id="groupDropdownList" style="display: none; position: absolute; top: calc(100% - 0.5rem); left: 0; right: 0; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem; max-height: 200px; overflow-y: auto; flex-direction: column; gap: 0.5rem; z-index: 10; box-shadow: var(--shadow-md); margin-top: 0.5rem; background: var(--input-bg);">
                            <!-- Populated by JS -->
                            <div style="color: var(--text-light); font-size: 0.8rem;">Loading groups...</div>
                        </div>
                    </div>

                    <div class="form-group" style="font-size: 0.9rem; margin-top: 1rem; position: relative;">
                        <label class="form-label">Message Category</label>
                        <input type="hidden" name="category" id="categoryInput" value="ADVISORY">
                        <div id="customCategorySelect" tabindex="0" class="form-control" style="padding: 0.75rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleCategoryDropdown(event)">
                            <span id="categorySelectText" style="color: var(--text-color);">ADVISORY</span>
                            <i class="fa-solid fa-chevron-down" style="color: var(--text-light); font-size: 0.8em; pointer-events: none;"></i>
                        </div>
                        
                        <div id="categoryDropdownList" style="display: none; position: absolute; top: calc(100% - 0.5rem); left: 0; right: 0; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem; flex-direction: column; z-index: 10; box-shadow: var(--shadow-md); margin-top: 0.5rem; background: var(--input-bg);">
                            <div class="category-option" onclick="selectCategory('ADVISORY')" style="padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color);">ADVISORY</div>
                            <div class="category-option" onclick="selectCategory('OUTAGE')" style="padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color);">OUTAGE</div>
                            <div class="category-option" onclick="selectCategory('EVENTS')" style="padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color);">EVENTS</div>
                        </div>
                    </div>
                </div>

                <!-- Global Options: Schedule & No-Reply -->
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
        <div class="header" style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <h3 id="historyTitle" style="font-size: 1rem; margin: 0;">History</h3>
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-grow: 1; min-width: 150px;" id="searchContainer">
                <i class="fa-solid fa-magnifying-glass" style="color: var(--primary-color); font-size: 0.9rem;"></i>
                <input type="text" id="messageSearch" placeholder="Search..." 
                       style="padding: 0.4rem 0.75rem; border-radius: 8px; border: 1px solid var(--border-color); background: #ffffff; color: #334155; width: 100%; font-size: 0.8rem; transition: all 0.3s ease; box-shadow: var(--shadow-sm);"
                       oninput="filterMessages()">
            </div>
            
            <div id="calendarDatePickerContainer" style="display: none; align-items: center; gap: 0.5rem; flex-grow: 1; justify-content: flex-end;">
                <label for="calendarJumpDate" style="font-size: 0.8rem; font-weight: 500; color: var(--text-color);">Jump to Date:</label>
                <input type="date" id="calendarJumpDate" onchange="jumpToDate()" style="padding: 0.35rem 0.5rem; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.8rem; background: #ffffff; color: var(--text-color); box-shadow: var(--shadow-sm);">
            </div>
            
            <button class="btn" onclick="loadMessages(new URLSearchParams(window.location.search).get('scheduled') === '1')" style="background: #f1f5f9; padding: 0.4rem 0.8rem; font-size: 0.75rem; white-space: nowrap;">
                <i class="fa-solid fa-refresh"></i> Refresh
            </button>
        </div>
        <div class="card" id="historyCard" style="padding: 0; min-height: 405px;">
            <!-- View Controls for Scheduled Messages Settings (Hidden by default) -->
            <div id="scheduledViewControls" style="display: none; padding: 1rem 1.25rem 0 1.25rem; border-bottom: 1px solid var(--border-color); margin-bottom: 0.5rem;">
                <div style="display: flex; gap: 1rem;">
                    <button id="btnViewCalendar" onclick="toggleScheduledView('calendar')" style="background: none; border: none; padding: 0.5rem 1rem; border-bottom: 2px solid var(--primary-color); color: var(--primary-color); font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
                        <i class="fa-solid fa-calendar-days"></i> Calendar View
                    </button>
                    <button id="btnViewList" onclick="toggleScheduledView('list')" style="background: none; border: none; padding: 0.5rem 1rem; border-bottom: 2px solid transparent; color: var(--text-light); font-weight: 500; cursor: pointer; transition: all 0.2s ease;">
                        <i class="fa-solid fa-list-ul"></i> List View
                    </button>
                </div>
            </div>

            <div id="historyListContainer" class="scrollable-container" style="max-height: 405px; padding: 0;">
                <ul id="message-list" style="list-style: none; padding: 0;">
                    <!-- Populated by JS -->
                </ul>
            </div>
            
            <!-- Calendar Container, hidden by default -->
            <div id="calendarContainer" style="display: none; padding: 1rem; width: 100%; min-height: 600px;">
                <div id="calendar"></div>
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

    function toggleGroupDropdown(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('groupDropdownList');
        const catDropdown = document.getElementById('categoryDropdownList');
        const conDropdown = document.getElementById('contactDropdownList');
        if (catDropdown) catDropdown.style.display = 'none';
        if (conDropdown) conDropdown.style.display = 'none';
        dropdown.style.display = dropdown.style.display === 'none' ? 'flex' : 'none';
    }

    function toggleCategoryDropdown(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('categoryDropdownList');
        const groupDropdown = document.getElementById('groupDropdownList');
        const conDropdown = document.getElementById('contactDropdownList');
        if (groupDropdown) groupDropdown.style.display = 'none';
        if (conDropdown) conDropdown.style.display = 'none';
        dropdown.style.display = dropdown.style.display === 'none' ? 'flex' : 'none';
    }

    function toggleContactDropdown(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('contactDropdownList');
        const groupDropdown = document.getElementById('groupDropdownList');
        const catDropdown = document.getElementById('categoryDropdownList');
        if (groupDropdown) groupDropdown.style.display = 'none';
        if (catDropdown) catDropdown.style.display = 'none';
        dropdown.style.display = dropdown.style.display === 'none' ? 'flex' : 'none';
    }

    function selectContact(id, name, phone) {
        document.getElementById('contactIdInput').value = id;
        document.getElementById('contactSelectText').textContent = `${name} (${phone})`;
        document.getElementById('contactDropdownList').style.display = 'none';
    }

    function selectCategory(value) {
        document.getElementById('categoryInput').value = value;
        document.getElementById('categorySelectText').textContent = value;
        document.getElementById('categoryDropdownList').style.display = 'none';
    }

    function updateGroupSelectText() {
        const checkboxes = document.querySelectorAll('#groupDropdownList input[type="checkbox"]:checked');
        const textSpan = document.getElementById('groupSelectText');
        
        if (checkboxes.length === 0) {
            textSpan.textContent = "Select groups...";
            textSpan.style.color = "var(--text-color)";
        } else if (checkboxes.length === 1) {
            textSpan.textContent = checkboxes[0].nextElementSibling.textContent;
            textSpan.style.color = "var(--text-color)";
        } else {
            textSpan.textContent = `${checkboxes.length} groups selected`;
            textSpan.style.color = "var(--primary-color)";
            textSpan.style.fontWeight = "500";
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const groupDropdown = document.getElementById('groupDropdownList');
        const groupTrigger = document.getElementById('customGroupSelect');
        const catDropdown = document.getElementById('categoryDropdownList');
        const catTrigger = document.getElementById('customCategorySelect');
        const conDropdown = document.getElementById('contactDropdownList');
        const conTrigger = document.getElementById('customContactSelect');

        if (groupDropdown && groupDropdown.style.display === 'flex') {
            if (!groupTrigger.contains(event.target) && !groupDropdown.contains(event.target)) {
                groupDropdown.style.display = 'none';
            }
        }
        if (catDropdown && catDropdown.style.display === 'flex') {
            if (!catTrigger.contains(event.target) && !catDropdown.contains(event.target)) {
                catDropdown.style.display = 'none';
            }
        }
        if (conDropdown && conDropdown.style.display === 'flex') {
            if (!conTrigger.contains(event.target) && !conDropdown.contains(event.target)) {
                conDropdown.style.display = 'none';
            }
        }
    });

    function toggleScheduling() {
        const isScheduled = document.getElementById('isScheduled').checked;
        document.getElementById('scheduledAtInput').style.display = isScheduled ? 'block' : 'none';
    }

    async function loadOptions() {
        const [contacts, groups] = await Promise.all([
            fetchAPI('/contacts'),
            fetchAPI('/groups')
        ]);

        const contactContainer = document.getElementById('contactDropdownList');
        if (contacts.length === 0) {
            contactContainer.innerHTML = '<div style="color: var(--text-light); font-size: 0.8rem; padding: 0.5rem;">No contacts available.</div>';
        } else {
            contactContainer.innerHTML = contacts.map(c => `
                <div class="contact-option" onclick="selectContact('${c.id}', '${c.name}', '${c.phone_number}')" style="padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color); font-size: 0.85rem;">
                    ${c.name} (${c.phone_number})
                </div>
            `).join('');
        }

        const groupContainer = document.getElementById('groupDropdownList');
        if (groups.length === 0) {
            groupContainer.innerHTML = '<div style="color: var(--text-light); font-size: 0.8rem;">No groups available.</div>';
        } else {
            groupContainer.innerHTML = groups.map(g => `
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.25rem 0; font-size: 0.85rem; color: var(--text-color);" onclick="event.stopPropagation()">
                    <input type="checkbox" name="group_ids[]" value="${g.id}" onchange="updateGroupSelectText()">
                    <span style="color: inherit;">${g.name}</span>
                </label>
            `).join('');
        }
    }

    let calendar = null;

    function initCalendar(messages) {
        const calendarEl = document.getElementById('calendar');
        
        // Map messages to FullCalendar event objects
        const events = messages.map(m => {
            const isProcessed = m.is_scheduled && m.recipients.length > 0 && m.recipients.every(r => r.status && r.status !== 'pending');
            let color = isProcessed ? '#10b981' : '#64748b'; // Green if sent, else default individual
            let titleText = m.content.substring(0, 20) + '...';
            
            if (m.type === 'broadcast') {
                color = isProcessed ? '#10b981' : 'var(--primary-color)';
                const cat = m.category || 'Broadcast';
                titleText = m.recipients.length > 0 ? `${cat} (${m.recipients.length} recipients)` : cat;
            } else if (m.recipients.length > 0 && m.recipients[0].contact) {
                titleText = `To: ${m.recipients[0].contact.name}`;
            }

            if (isProcessed) {
                titleText += ' (Sent)';
            }
            
            return {
                id: m.id,
                title: titleText,
                start: m.scheduled_at,
                backgroundColor: color,
                borderColor: color,
                extendedProps: {
                    fullContent: m.content,
                    type: m.type,
                    category: m.category,
                    recipients: m.recipients.length > 0 ? m.recipients.map(r => r.contact ? r.contact.name : 'Unknown').join(', ') : '(No Recipients Attached)',
                    createdAt: m.created_at,
                }
            };
        });

        if (calendar) {
            calendar.destroy();
        }

        calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,dayGridWeek,listWeek'
            },
            buttonText: {
                today: 'Today',
                month: 'Month',
                week: 'Week',
                list: 'List'
            },
            events: events,
            eventClick: function(info) {
                const props = info.event.extendedProps;
                const modalHtml = `
                    <div style="text-align: left; font-size: 0.9rem;">
                        <p><strong>Type:</strong> <span class="badge" style="background: ${info.event.backgroundColor}; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">${props.type.toUpperCase()}</span> ${props.category ? '- ' + props.category : ''}</p>
                        <p><strong>Scheduled For:</strong> ${info.event.start.toLocaleString()}</p>
                        <p><strong>To:</strong> ${props.recipients}</p>
                        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 10px 0;">
                        <p style="white-space: pre-wrap; font-family: 'Inter', sans-serif;">${props.fullContent}</p>
                    </div>
                `;
                
                Swal.fire({
                    title: 'Scheduled Message Summary',
                    html: modalHtml,
                    confirmButtonText: 'Close',
                    confirmButtonColor: 'var(--primary-color)',
                    width: '500px'
                });
            }
        });
        
        calendar.render();
    }

    async function loadMessages(forceScheduled = false) {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const typeParam = urlParams.get('type');
            const scheduledParam = urlParams.get('scheduled');
            const isScheduledView = scheduledParam === '1' || forceScheduled;
            
            let endpoint = '/messages';
            if (typeParam) {
                endpoint += `?type=${typeParam}`;
                if (isScheduledView) {
                    endpoint += `&scheduled=1`;
                }
            } else if (isScheduledView) {
                endpoint += `?scheduled=1`;
            }
            
            const messages = await fetchAPI(endpoint);
            
            if (isScheduledView) {
                // Initialize calendar AND render list (so it's ready if toggled)
                initCalendar(messages);
                renderMessageList(messages, isScheduledView);
            } else {
                // Regular view
                renderMessageList(messages, isScheduledView);
            }
        } catch (err) {
            console.error(err);
            document.getElementById('message-list').innerHTML = `<li style="padding: 1rem; text-align: center; color: #ef4444; font-size: 0.85rem;">Failed to load history.</li>`;
        }
    }

    function renderMessageList(messages, isScheduledView) {
        const list = document.getElementById('message-list');
                
        if (messages.length === 0) {
             list.innerHTML = `<li style="padding: 1rem; text-align: center; color: var(--text-light); font-size: 0.85rem;">No messages found.</li>`;
             return;
        }

        list.innerHTML = messages.map(m => {
            let badgeColor = '#64748b'; // default individual
            let categoryLabel = '';
            
            if (m.type === 'broadcast') {
                badgeColor = 'var(--primary-color)';
                categoryLabel = `<span class="badge" style="background: var(--moresco-dark); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.6rem; margin-left: 0.5rem; border: 1px solid var(--border-color); font-weight: 600;">${m.category}</span>`;
            }
            
            if (m.type === 'incoming') badgeColor = '#f59e0b';
            if (m.type === 'auto_reply') badgeColor = '#10b981';

            const isProcessed = m.is_scheduled && m.recipients.length > 0 && m.recipients.every(r => r.status && r.status !== 'pending');
            const scheduledInfo = m.is_scheduled 
                ? `<div style="font-size: 0.65rem; color: ${isProcessed ? '#10b981' : '#ef4444'}; margin-top: 0.25rem; font-weight: 500;">
                    <i class="fa-solid ${isProcessed ? 'fa-check-circle' : 'fa-clock'}"></i> ${isProcessed ? 'Sent at:' : 'Scheduled for:'} ${new Date(m.scheduled_at).toLocaleString()}
                   </div>`
                : '';

            const noReplyBadge = m.no_reply && m.type !== 'incoming' && m.type !== 'auto_reply'
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
                    ${m.recipients.length > 0 ? m.recipients.map(r => r.contact ? r.contact.name : 'Unknown').join(', ') : '(No Recipients Attached)'}
                </div>
            </li>
            `;
        }).join('');
        filterMessages(); // Apply filter after loading
    }

    function filterMessages() {
        const query = document.getElementById('messageSearch').value.toLowerCase();
        const items = document.querySelectorAll('#message-list li');
        
        items.forEach(li => {
            const text = li.textContent.toLowerCase();
            if (text.includes(query)) {
                li.style.display = 'block';
                // Highlight matches if possible or just show
            } else {
                li.style.display = 'none';
            }
        });
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
        
        // Handle query parameter for auto-selecting broadcast or scheduled view
        const urlParams = new URLSearchParams(window.location.search);
        const typeParam = urlParams.get('type');
        const scheduledParam = urlParams.get('scheduled');
        
        if (typeParam) {
            document.getElementById('messageTypeSelect').value = typeParam;
            document.getElementById('messageTypeGroup').style.display = 'none'; // Hide selector if type is forced
            
            // Update Title
            let title = typeParam === 'broadcast' ? 'Send Broadcast Message' : 'Send Individual Notification';
            if (scheduledParam === '1') {
                // Hide send form and expand history to full width
                document.getElementById('sendMessageContainer').style.display = 'none';
                const gridContainer = document.querySelector('.grid-2');
                if (gridContainer) gridContainer.style.gridTemplateColumns = '1fr';
                
                // Show view controls
                document.getElementById('scheduledViewControls').style.display = 'block';
                
                // Show list, show calendar (defaulting to calendar)
                toggleScheduledView('calendar');
                
                // Update history title
                const historyHeader = document.getElementById('historyTitle');
                if (historyHeader) {
                    historyHeader.innerText = 'Scheduled Messages History';
                }
            } else {
                document.getElementById('formTitle').innerText = title;
                toggleRecipientInput();
            }
        }
    });

    function jumpToDate() {
        const dateVal = document.getElementById('calendarJumpDate').value;
        if (dateVal && calendar) {
            calendar.gotoDate(dateVal);
        }
    }

    function toggleScheduledView(view) {
        const calBtn = document.getElementById('btnViewCalendar');
        const listBtn = document.getElementById('btnViewList');
        
        if (view === 'calendar') {
            calBtn.style.borderBottomColor = 'var(--primary-color)';
            calBtn.style.color = 'var(--primary-color)';
            calBtn.style.fontWeight = '600';
            
            listBtn.style.borderBottomColor = 'transparent';
            listBtn.style.color = 'var(--text-light)';
            listBtn.style.fontWeight = '500';
            
            document.getElementById('historyListContainer').style.display = 'none';
            document.getElementById('calendarContainer').style.display = 'block';
            
            document.getElementById('calendarDatePickerContainer').style.display = 'flex';
            document.getElementById('searchContainer').style.display = 'none';
            
            if (calendar) calendar.render(); // Ensure correct sizing
        } else {
            listBtn.style.borderBottomColor = 'var(--primary-color)';
            listBtn.style.color = 'var(--primary-color)';
            listBtn.style.fontWeight = '600';
            
            calBtn.style.borderBottomColor = 'transparent';
            calBtn.style.color = 'var(--text-light)';
            calBtn.style.fontWeight = '500';
            
            document.getElementById('calendarContainer').style.display = 'none';
            document.getElementById('historyListContainer').style.display = 'block';
            
            document.getElementById('calendarDatePickerContainer').style.display = 'none';
            document.getElementById('searchContainer').style.display = 'flex';
        }
    }
</script>
@endpush
