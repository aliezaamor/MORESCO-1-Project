@extends('layouts.app')

@section('title', 'Messages')

@section('content')
    <div class="grid-2" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
        <!-- Send Message Form -->
        <div id="sendMessageContainer">
            <div class="card" style="padding: 1rem;">
                <h3 id="formTitle" style="font-size: 1rem; margin-bottom: 1rem; color: var(--moresco-blue);">Send New
                    Message</h3>
                <form id="sendMessageForm">
                    <div class="form-group" id="messageTypeGroup">
                        <label class="form-label" style="font-size: 0.9rem;">Message Type</label>
                        <select name="type" class="form-control" onchange="toggleRecipientInput()" id="messageTypeSelect"
                            style="padding: 0.75rem;">
                            <option value="individual">Individual</option>
                            <option value="broadcast">Broadcast</option>
                        </select>
                    </div>

                    <div class="form-group" id="contactInput"
                        style="font-size: 0.9rem; margin-top: 0.5rem; position: relative;">
                        <label class="form-label">Select Recipient (Contact)</label>
                        <input type="hidden" name="contact_id" id="contactIdInput">
                        <input type="hidden" name="moresco_phone" id="morescoPhoneInput">
                        <input type="hidden" name="moresco_name" id="morescoNameInput">
                        <div id="customContactSelect" tabindex="0" class="form-control"
                            style="padding: 0.75rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center;"
                            onclick="toggleContactDropdown(event)">
                            <span id="contactSelectText" style="color: var(--text-color);">Choose Contact...</span>
                            <i class="fa-solid fa-chevron-down"
                                style="color: var(--text-light); font-size: 0.8em; pointer-events: none;"></i>
                        </div>

                        <div id="contactDropdownList"
                            style="display: none; position: absolute; top: calc(100% - 0.5rem); left: 0; right: 0; border: 1px solid var(--border-color); border-radius: 8px; z-index: 10; box-shadow: var(--shadow-md); margin-top: 0.5rem; background: var(--input-bg);">
                            <div style="padding: 0.5rem; border-bottom: 1px solid var(--border-color);">
                                <input type="text" id="contactSearchInput"
                                    placeholder="Search account number, name or phone..." oninput="onContactSearchInput()"
                                    onclick="event.stopPropagation()"
                                    style="width: 100%; padding: 0.4rem 0.6rem; border-radius: 6px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); font-size: 0.82rem; box-sizing: border-box;">
                            </div>
                            <div id="contactDropdownInner" style="max-height: 200px; overflow-y: auto; padding: 0.4rem;">
                                <div style="color: var(--text-light); font-size: 0.8rem; padding: 0.5rem;">Loading
                                    contacts...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Broadcast Specific Options -->
                    <div id="broadcastOptions" style="display: none;">
                        <div class="form-group" style="font-size: 0.9rem; margin-top: 1rem; position: relative;">
                            <label class="form-label">Select Target Group/s</label>
                            <div id="customGroupSelect" tabindex="0" class="form-control"
                                style="padding: 0.75rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center;"
                                onclick="toggleGroupDropdown(event)">
                                <span id="groupSelectText" style="color: var(--text-color);">Select groups...</span>
                                <i class="fa-solid fa-chevron-down"
                                    style="color: var(--text-light); font-size: 0.8em; pointer-events: none;"></i>
                            </div>

                            <div id="groupDropdownList"
                                style="display: none; position: absolute; top: calc(100% - 0.5rem); left: 0; right: 0; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.75rem; max-height: 200px; overflow-y: auto; flex-direction: column; gap: 0.5rem; z-index: 10; box-shadow: var(--shadow-md); margin-top: 0.5rem; background: var(--input-bg);">
                                <!-- Populated by JS -->
                                <div style="color: var(--text-light); font-size: 0.8rem;">Loading groups...</div>
                            </div>
                        </div>

                        <div class="form-group" style="font-size: 0.9rem; margin-top: 1rem; position: relative;">
                            <label class="form-label">Message Category</label>
                            <input type="hidden" name="category" id="categoryInput" value="ADVISORY">
                            <div id="customCategorySelect" tabindex="0" class="form-control"
                                style="padding: 0.75rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center;"
                                onclick="toggleCategoryDropdown(event)">
                                <span id="categorySelectText" style="color: var(--text-color);">ADVISORY</span>
                                <i class="fa-solid fa-chevron-down"
                                    style="color: var(--text-light); font-size: 0.8em; pointer-events: none;"></i>
                            </div>

                            <div id="categoryDropdownList"
                                style="display: none; position: absolute; top: calc(100% - 0.5rem); left: 0; right: 0; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem; flex-direction: column; z-index: 10; box-shadow: var(--shadow-md); margin-top: 0.5rem; background: var(--input-bg);">
                                <div class="category-option" onclick="selectCategory('ADVISORY')"
                                    style="padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color);">
                                    ADVISORY</div>
                                <div class="category-option" onclick="selectCategory('OUTAGE')"
                                    style="padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color);">
                                    OUTAGE</div>
                                <div class="category-option" onclick="selectCategory('EVENTS')"
                                    style="padding: 0.5rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color);">
                                    EVENTS</div>
                            </div>
                        </div>
                    </div>

                    <!-- Global Options: Schedule & No-Reply -->
                    <div
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; background: var(--item-hover); padding: 0.75rem; border-radius: 8px;">
                        <label
                            style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                            <input type="checkbox" name="is_scheduled" id="isScheduled" onchange="toggleScheduling()">
                            <span><i class="fa-solid fa-calendar-plus" style="color: var(--primary-color);"></i> Schedule
                                Send</span>
                        </label>
                        <label
                            style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.85rem; font-weight: 500;">
                            <input type="checkbox" name="no_reply" checked>
                            <span><i class="fa-solid fa-microphone-slash" style="color: #64748b;"></i> No-Reply
                                Policy</span>
                        </label>
                    </div>

                    <div class="form-group" id="scheduledAtInput"
                        style="font-size: 0.9rem; margin-top: 1rem; display: none;">
                        <label class="form-label">Schedule Date & Time</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" style="padding: 0.75rem;">
                    </div>

                    <div class="form-group" style="font-size: 0.9rem; margin-top: 1rem;">
                        <label class="form-label">Message Content</label>
                        <textarea name="content" class="form-control" rows="6" placeholder="Write your message here..."
                            required style="padding: 1rem; resize: none; height: 120px;"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary"
                        style="width: 100%; padding: 1rem; font-weight: 600; border-radius: 10px; height: 20px;">
                        <i class="fa-solid fa-paper-plane"></i> Dispatch Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Message History -->
        <div>
            <div class="header"
                style="margin-bottom: 1rem; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h3 id="historyTitle" style="font-size: 1rem; margin: 0;">History</h3>
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-grow: 1; min-width: 150px; flex-wrap: wrap;"
                    id="searchContainer">
                    <div style="position: relative; flex-grow: 1; display: flex; align-items: center;">
                        <i class="fa-solid fa-magnifying-glass"
                            style="position: absolute; left: 0.75rem; color: var(--text-light); font-size: 0.9rem;"></i>
                        <input type="text" id="messageSearch" placeholder="Search content..."
                            style="padding: 0.4rem 0.75rem 0.4rem 2rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); width: 100%; font-size: 0.8rem; transition: all 0.3s ease; box-shadow: var(--shadow-sm);"
                            oninput="filterMessages()">
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                        <input type="date" id="messageDateFilter" onchange="filterMessages()"
                            style="padding: 0.35rem 0.5rem; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.8rem; background: var(--input-bg); color: var(--text-color); box-shadow: var(--shadow-sm); cursor: pointer;"
                            title="Filter by Date">
                        <button class="btn btn-icon"
                            style="color: var(--text-light); width: 28px; height: 28px; font-size: 0.75rem; padding: 0;"
                            onclick="clearDateFilter()" title="Clear Date Filter">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div id="calendarDatePickerContainer"
                    style="display: none; align-items: center; gap: 0.5rem; flex-grow: 1; justify-content: flex-end;">
                    <label for="calendarJumpDate"
                        style="font-size: 0.8rem; font-weight: 500; color: var(--text-color);">Jump to Date:</label>
                    <input type="date" id="calendarJumpDate" onchange="jumpToDate()"
                        style="padding: 0.35rem 0.5rem; border-radius: 8px; border: 1px solid var(--border-color); font-size: 0.8rem; background: var(--input-bg); color: var(--text-color); box-shadow: var(--shadow-sm);">
                </div>

                <button class="btn"
                    onclick="loadMessages(new URLSearchParams(window.location.search).get('scheduled') === '1')"
                    style="background: #f1f5f9; padding: 0.4rem 0.8rem; font-size: 0.75rem; white-space: nowrap;">
                    <i class="fa-solid fa-refresh"></i> Refresh
                </button>

                <!-- Direction Filter Buttons (Individual Notification only) -->
                <div id="directionFilterBtns" style="display: none; align-items: center; gap: 0.35rem;">
                    <button id="btnFilterOutgoing" onclick="toggleDirectionFilter('outgoing')"
                        style="background: #f1f5f9; border: 1px solid var(--border-color); border-radius: 20px; padding: 0.25rem 0.75rem; font-size: 0.72rem; color: var(--text-light); cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 0.35rem; font-weight: 500; transition: all 0.2s;">
                        <i class="fa-solid fa-paper-plane" style="font-size: 0.6rem;"></i> Outgoing
                    </button>
                    <button id="btnFilterIncoming" onclick="toggleDirectionFilter('incoming')"
                        style="background: #f1f5f9; border: 1px solid var(--border-color); border-radius: 20px; padding: 0.25rem 0.75rem; font-size: 0.72rem; color: var(--text-light); cursor: pointer; white-space: nowrap; display: flex; align-items: center; gap: 0.35rem; font-weight: 500; transition: all 0.2s;">
                        <i class="fa-solid fa-envelope-open-text" style="font-size: 0.6rem; color: #f59e0b;"></i> Incoming
                    </button>
                </div>
            </div>
            <div class="card" id="historyCard" style="padding: 0; min-height: 405px;">
                <!-- View Controls for Scheduled Messages Settings (Hidden by default) -->
                <div id="scheduledViewControls"
                    style="display: none; padding: 1rem 1.25rem 0 1.25rem; border-bottom: 1px solid var(--border-color); margin-bottom: 0.5rem;">
                    <div style="display: flex; gap: 1rem;">
                        <button id="btnViewCalendar" onclick="toggleScheduledView('calendar')"
                            style="background: none; border: none; padding: 0.5rem 1rem; border-bottom: 2px solid var(--primary-color); color: var(--primary-color); font-weight: 600; cursor: pointer; transition: all 0.2s ease;">
                            <i class="fa-solid fa-calendar-days"></i> Calendar View
                        </button>
                        <button id="btnViewList" onclick="toggleScheduledView('list')"
                            style="background: none; border: none; padding: 0.5rem 1rem; border-bottom: 2px solid transparent; color: var(--text-light); font-weight: 500; cursor: pointer; transition: all 0.2s ease;">
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
            const isOpen = dropdown.style.display !== 'none' && dropdown.style.display !== '';
            dropdown.style.display = isOpen ? 'none' : 'block';
            if (!isOpen) {
                // Focus search input when opening
                const searchInput = document.getElementById('contactSearchInput');
                if (searchInput) setTimeout(() => searchInput.focus(), 50);
            }
        }

        function selectContact(id, name, phone, source) {
            // Clear both fields first
            document.getElementById('contactIdInput').value = '';
            document.getElementById('morescoPhoneInput').value = '';
            document.getElementById('morescoNameInput').value = '';

            if (source === 'moresco') {
                // MORESCO consumer — pass phone & name to backend directly
                document.getElementById('morescoPhoneInput').value = phone;
                document.getElementById('morescoNameInput').value = name;
                // Show name + account number + phone in trigger label
                document.getElementById('contactSelectText').textContent = `${name} · Acct# ${id} · ${phone}`;
            } else {
                // App contact — pass local DB id
                document.getElementById('contactIdInput').value = id;
                document.getElementById('contactSelectText').textContent = `${name} (${phone})`;
            }

            document.getElementById('contactDropdownList').style.display = 'none';
        }

        function filterContactDropdown() {
            onContactSearchInput();
        }

        function selectCategory(value) {
            document.getElementById('categoryInput').value = value;
            document.getElementById('categorySelectText').textContent = value;
            document.getElementById('categoryDropdownList').style.display = 'none';
        }

        function updateGroupSelectText() {
            const appCheckboxes = document.querySelectorAll('#groupDropdownList input[name="group_ids[]"]:checked');
            const morescoCheckboxes = document.querySelectorAll('#groupDropdownList input[name="moresco_group_codes[]"]:checked');
            const municipalChecks = document.querySelectorAll('#groupDropdownList input[name="moresco_municipalities[]"]:checked');
            const barangayChecks = document.querySelectorAll('#groupDropdownList input[name="moresco_barangays[]"]:checked');
            const totalChecked = appCheckboxes.length + morescoCheckboxes.length + municipalChecks.length + barangayChecks.length;
            const textSpan = document.getElementById('groupSelectText');

            if (totalChecked === 0) {
                textSpan.textContent = "Select groups...";
                textSpan.style.color = "var(--text-color)";
            } else if (totalChecked === 1) {
                const allChecked = [...appCheckboxes, ...morescoCheckboxes, ...municipalChecks, ...barangayChecks];
                textSpan.textContent = allChecked[0].closest('label').querySelector('span').textContent;
                textSpan.style.color = "var(--text-color)";
            } else {
                textSpan.textContent = `${totalChecked} groups selected`;
                textSpan.style.color = "var(--primary-color)";
                textSpan.style.fontWeight = "500";
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function (event) {
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
            // Contact dropdown: check block display (not flex) since it's a column
            if (conDropdown && conDropdown.style.display !== 'none' && conDropdown.style.display !== '') {
                if (!conTrigger.contains(event.target) && !conDropdown.contains(event.target)) {
                    conDropdown.style.display = 'none';
                }
            }
        });

        function toggleScheduling() {
            const isScheduled = document.getElementById('isScheduled').checked;
            document.getElementById('scheduledAtInput').style.display = isScheduled ? 'block' : 'none';
        }

        // ── Contact Search (Hybrid: 300 preloaded, full DB search when typing) ───
        let appContactsList = [];
        let morescoContactsList = []; // pre-loaded 300
        let morescoSearchTimer = null;

        function onContactSearchInput() {
            const query = document.getElementById('contactSearchInput').value.trim();

            // Always filter app contacts client-side
            filterAppContacts(query);

            // For MORESCO: if query is empty, restore the pre-loaded 300
            if (query.length === 0) {
                renderMorescoList(morescoContactsList);
                return;
            }

            // Debounce server-side search for MORESCO
            clearTimeout(morescoSearchTimer);
            morescoSearchTimer = setTimeout(() => searchMorescoContacts(query), 350);
        }

        function filterAppContacts(query) {
            const q = (query || '').toLowerCase();
            const inner = document.getElementById('contactDropdownInner');
            inner.querySelectorAll('.app-contact-option').forEach(el => {
                el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
            const appHeader = inner.querySelector('.app-contacts-header');
            if (appHeader) {
                const anyVisible = [...inner.querySelectorAll('.app-contact-option')].some(el => el.style.display !== 'none');
                appHeader.style.display = anyVisible ? '' : 'none';
            }
        }

        function renderMorescoList(list) {
            const morescoSection = document.getElementById('morescoContactSection');
            if (!morescoSection) return;
            let html = `<div class="contact-section-header" style="padding: 0.25rem 0.5rem; font-size: 0.7rem; font-weight: 700; color: var(--moresco-blue); text-transform: uppercase; border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem;">MORESCO System Contacts</div>`;
            if (list.length === 0) {
                html += `<div style="color: var(--text-light); font-size: 0.8rem; padding: 0.5rem;">No MORESCO contacts available.</div>`;
            } else {
                html += list.map(c => {
                    const name = (c.name || '').replace(/'/g, "&#39;");
                    const phone = (c.phone_number || '').replace(/'/g, "&#39;");
                    const memberId = (c.id || '').toString().replace(/'/g, "&#39;");
                    return `<div class="contact-option moresco-contact-option" onclick="selectContact('${memberId}', '${name}', '${phone}', 'moresco')" style="padding: 0.4rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color); font-size: 0.85rem; line-height: 1.5;">
                            <span style="font-weight: 500;">${c.name}</span>
                            <span style="background: var(--moresco-blue); color: #fff; font-size: 0.65rem; font-weight: 600; padding: 1px 5px; border-radius: 3px; margin-left: 0.35rem;">${c.id}</span>
                            <span style="color: var(--text-light); font-size: 0.75rem; margin-left: 0.25rem;">${c.phone_number}</span>
                        </div>`;
                }).join('');
            }
            morescoSection.innerHTML = html;
        }

        async function searchMorescoContacts(query) {
            const morescoSection = document.getElementById('morescoContactSection');
            if (!morescoSection) return;

            // Show spinner while waiting
            morescoSection.innerHTML = `
                    <div class="contact-section-header" style="padding: 0.25rem 0.5rem; font-size: 0.7rem; font-weight: 700; color: var(--moresco-blue); text-transform: uppercase; border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem;">MORESCO System Contacts</div>
                    <div style="color: var(--text-light); font-size: 0.78rem; padding: 0.4rem 0.75rem;"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>`;

            try {
                const results = await fetchAPI(`/contacts?source=moresco&picker=1&per_page=50&search=${encodeURIComponent(query)}`);
                const list = Array.isArray(results) ? results : (results.data || []);
                renderMorescoList(list);
                if (list.length === 50) {
                    morescoSection.insertAdjacentHTML('beforeend',
                        `<div style="font-size: 0.72rem; color: var(--text-light); padding: 0.35rem 0.75rem; font-style: italic;">Showing top 50 — refine search for more.</div>`);
                }
            } catch (e) {
                morescoSection.innerHTML = `<div style="color: #ef4444; font-size: 0.78rem; padding: 0.4rem 0.75rem;">Failed to search MORESCO contacts.</div>`;
            }
        }
        // ──────────────────────────────────────────────────────────────────────────

        // ── Group Dropdown Helpers ──────────────────────────────────────────────
        function sectionHeader(label) {
            return `<div style="padding: 0.25rem 0.5rem; font-size: 0.7rem; font-weight: 700; color: var(--moresco-blue); text-transform: uppercase; border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem;">${label}</div>`;
        }

        function buildGroupChecks(groups, inputName) {
            return groups.map(g => `
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.35rem 0.5rem; font-size: 0.85rem; color: var(--text-color);" onclick="event.stopPropagation()">
                        <input type="checkbox" name="${inputName}" value="${g.id}" onchange="updateGroupSelectText()">
                        <span style="color: inherit; flex: 1;">${g.name}</span>
                        <span style="background: var(--moresco-blue); color: #fff; font-size: 0.65rem; font-weight: 600; padding: 1px 6px; border-radius: 10px; white-space: nowrap;">${g.member_count} members</span>
                    </label>`).join('');
        }

        function toggleBarangayList() {
            const list = document.getElementById('barangayGroupList');
            const icon = document.getElementById('barangayToggleIcon');
            if (!list) return;
            const isOpen = list.style.display !== 'none';
            list.style.display = isOpen ? 'none' : 'block';
            if (icon) icon.textContent = isOpen ? '▼' : '▲';
        }
        // ──────────────────────────────────────────────────────────────────────────

        async function loadOptions() {
            const [appContacts, morescoContacts, appGroups, morescoGroups, morescoMunicipalities, morescoBarangays] = await Promise.all([
                fetchAPI('/contacts?source=app'),
                fetchAPI('/contacts?source=moresco&picker=1&per_page=300'),
                fetchAPI('/groups?source=app'),
                fetchAPI('/groups?source=moresco'),
                fetchAPI('/groups?source=moresco_municipality'),
                fetchAPI('/groups?source=moresco_barangay')
            ]);

            appContactsList = appContacts;
            morescoContactsList = Array.isArray(morescoContacts) ? morescoContacts : (morescoContacts.data || []);

            const contactInner = document.getElementById('contactDropdownInner');
            let contactHtml = '';

            if (appContacts.length > 0) {
                contactHtml += `<div class="contact-section-header app-contacts-header" style="padding: 0.25rem 0.5rem; font-size: 0.7rem; font-weight: 700; color: var(--text-light); text-transform: uppercase;">App Contacts</div>`;
                contactHtml += appContacts.map(c => {
                    const name = (c.name || '').replace(/'/g, "&#39;");
                    const phone = (c.phone_number || '').replace(/'/g, "&#39;");
                    return `<div class="contact-option app-contact-option" onclick="selectContact('${c.id}', '${name}', '${phone}', 'app')" style="padding: 0.4rem 0.75rem; cursor: pointer; border-radius: 4px; color: var(--text-color); font-size: 0.85rem;">
                            ${c.name} <span style="color: var(--text-light); font-size: 0.75rem;">(${c.phone_number})</span>
                        </div>`;
                }).join('');
            }

            // MORESCO section — pre-loaded 300, upgrades to full-DB search on typing
            contactHtml += `<div id="morescoContactSection"></div>`;
            contactInner.innerHTML = contactHtml;
            renderMorescoList(morescoContactsList);

            // ── Build Group Dropdown ─────────────────────────────────────────────
            const groupContainer = document.getElementById('groupDropdownList');
            let groupHtml = '';

            // App Groups
            if (appGroups.length > 0) {
                groupHtml += `<div style="padding: 0.25rem 0.5rem; font-size: 0.7rem; font-weight: 700; color: var(--text-light); text-transform: uppercase;">App Groups</div>`;
                groupHtml += appGroups.map(g => `
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.25rem 0.5rem; font-size: 0.85rem; color: var(--text-color);" onclick="event.stopPropagation()">
                            <input type="checkbox" name="group_ids[]" value="${g.id}" onchange="updateGroupSelectText()">
                            <span style="color: inherit;">${g.name}</span>
                            <span style="color: var(--text-light); font-size: 0.75rem; margin-left: auto;">${g.contacts_count ?? ''} members</span>
                        </label>
                    `).join('');
            }

            // MORESCO Service Areas
            if (morescoGroups.length > 0) {
                groupHtml += sectionHeader('MORESCO Service Areas');
                groupHtml += buildGroupChecks(morescoGroups, 'moresco_group_codes[]');
            }

            // MORESCO Municipalities
            if (morescoMunicipalities.length > 0) {
                groupHtml += sectionHeader('MORESCO Municipalities');
                groupHtml += buildGroupChecks(morescoMunicipalities, 'moresco_municipalities[]');
            }

            // MORESCO Barangays — potentially many, so show inside a collapsible
            if (morescoBarangays.length > 0) {
                groupHtml += `
                        <div style="padding: 0.25rem 0.5rem; font-size: 0.7rem; font-weight: 700; color: var(--moresco-blue); text-transform: uppercase; border-top: 1px solid var(--border-color); margin-top: 0.5rem; padding-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleBarangayList()">
                            MORESCO Barangays
                            <span id="barangayToggleIcon" style="font-size: 0.8rem;">▼</span>
                        </div>
                        <div id="barangayGroupList" style="display: none;">
                            ${buildGroupChecks(morescoBarangays, 'moresco_barangays[]')}
                        </div>`;
            }

            groupContainer.innerHTML = groupHtml;
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
                eventClick: function (info) {
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
                        width: '500px',
                        background: document.body.classList.contains('dark-mode') ? '#1e293b' : '#fff',
                        color: document.body.classList.contains('dark-mode') ? '#fff' : '#000'
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

            // Separate outgoing vs incoming
            const urlParams = new URLSearchParams(window.location.search);
            const typeParam = urlParams.get('type');
            const isIndividual = typeParam === 'individual';
            const isKeyword = typeParam === 'auto_reply';

            let outgoing = [];
            let incoming = [];

            if (isKeyword) {
                outgoing = messages.filter(m => m.type === 'auto_reply');
                incoming = messages.filter(m => m.type === 'incoming_keyword');
            } else {
                outgoing = messages.filter(m => m.type !== 'incoming' && m.type !== 'incoming_keyword' && m.type !== 'auto_reply');
                incoming = messages.filter(m => m.type === 'incoming');
            }

            function buildItem(m) {
                let badgeColor = '#64748b';
                let categoryLabel = '';

                if (m.type === 'broadcast') {
                    badgeColor = 'var(--primary-color)';
                    categoryLabel = `<span class="badge" style="background: var(--moresco-dark); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.6rem; margin-left: 0.5rem; border: 1px solid var(--border-color); font-weight: 600;">${m.category}</span>`;
                }
                if (m.type === 'incoming' || m.type === 'incoming_keyword') badgeColor = '#f59e0b';
                if (m.type === 'auto_reply') badgeColor = '#10b981';

                const isOutgoing = m.type !== 'incoming' && m.type !== 'incoming_keyword';
                const isProcessed = m.is_scheduled && m.recipients.length > 0 && m.recipients.every(r => r.status && r.status !== 'pending');

                const scheduledInfo = m.is_scheduled
                    ? `<div style="font-size: 0.65rem; color: ${isProcessed ? '#10b981' : '#ef4444'} !important; margin-top: 0.25rem; font-weight: 500;">
                               <i class="fa-solid ${isProcessed ? 'fa-check-circle' : 'fa-clock'}"></i>
                               ${isProcessed ? 'Sent at:' : 'Scheduled for:'} ${new Date(m.scheduled_at).toLocaleString()}
                           </div>`
                    : '';

                const noReplyBadge = m.no_reply && isOutgoing
                    ? `<span style="font-size: 0.6rem; color: #64748b; margin-left: auto;">
                               <i class="fa-solid fa-microphone-slash"></i> No-Reply
                           </span>`
                    : '';

                // Outgoing: right-aligned bubble; Incoming: left-aligned highlight
                const liStyle = isOutgoing
                    ? `padding: 0.6rem 1.25rem; border-bottom: 1px solid var(--border-color);`
                    : `padding: 0.6rem 1.25rem; border-bottom: 1px solid var(--border-color); background: var(--item-hover);`;

                const bubbleWrap = isOutgoing
                    ? `display: flex; flex-direction: column; align-items: flex-end;`
                    : `display: flex; flex-direction: column; align-items: flex-start;`;

                const bubble = isOutgoing
                    ? `background: var(--primary-color); color: #fff; padding: 0.5rem 0.75rem; border-radius: 12px 12px 2px 12px; max-width: 90%; font-size: 0.8125rem; line-height: 1.4; white-space: pre-wrap; word-break: break-word;`
                    : `background: transparent; color: var(--text-color); font-size: 0.8125rem; font-weight: 600; line-height: 1.3; white-space: pre-wrap;`;

                const recipientsRow = `
                        <div style="font-size: 0.72rem; color: ${isOutgoing ? 'var(--text-light)' : 'var(--text-light)'}; display: flex; align-items: center; gap: 0.35rem; margin-top: 0.2rem; ${isOutgoing ? 'justify-content: flex-end;' : ''}">
                            <i class="fa-solid ${isOutgoing ? 'fa-paper-plane' : 'fa-envelope-open-text'}" style="font-size: 0.6rem;"></i>
                            ${m.recipients.length > 0 ? m.recipients.map(r => r.contact ? r.contact.name : 'Unknown').join(', ') : '(No Recipients Attached)'}
                        </div>`;

                return `
                    <li data-date="${new Date(m.created_at).toISOString().split('T')[0]}" data-direction="${isOutgoing ? 'outgoing' : 'incoming'}" style="${liStyle}">
                        <div style="${bubbleWrap}">
                            <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.25rem; ${isOutgoing ? 'flex-direction: row-reverse;' : ''}">
                                <span class="badge" style="background: ${badgeColor}; color: white; padding: 1px 6px; border-radius: 4px; font-size: 0.65rem; text-transform: uppercase;">${m.type.replace('_', ' ')}</span>
                                ${categoryLabel}
                                ${noReplyBadge}
                                <span style="font-size: 0.68rem; color: var(--text-light);">${new Date(m.created_at).toLocaleString([], { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                            ${scheduledInfo}
                            <div style="${bubble}">
                                ${!isOutgoing ? '<i class="fa-solid fa-reply-all" style="font-size: 0.7rem; color: #d97706; margin-right: 0.25rem;"></i>' : ''}${m.content}
                            </div>
                            ${recipientsRow}
                        </div>
                    </li>`;
            }

            let html = '';

            if (isKeyword) {
                // Two-column grid for Keyword History
                html += `
                <li class="keyword-wrapper-li" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding: 0.75rem; background: var(--input-bg);">
                    <!-- Outgoing Column -->
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); overflow: hidden; display: flex; flex-direction: column;">
                        <div style="padding: 0.6rem; font-weight: 600; font-size: 0.85rem; text-align: center; border-bottom: 1px solid var(--border-color); background: var(--item-hover); color: var(--text-color);">
                            <i class="fa-solid fa-paper-plane" style="color: var(--primary-color);"></i> Outgoing (Auto-Replies)
                        </div>
                        <ul class="scrollable-container" style="list-style: none; padding: 0; margin: 0; overflow-y: auto; max-height: 405px;">
                            ${outgoing.length > 0 ? outgoing.map(buildItem).join('') : '<li style="padding: 1rem; text-align: center; color: var(--text-light); font-size: 0.8rem;">No outgoing auto-replies.</li>'}
                        </ul>
                    </div>

                    <!-- Incoming Column -->
                    <div style="border: 1px solid var(--border-color); border-radius: 8px; background: var(--card-bg); overflow: hidden; display: flex; flex-direction: column;">
                        <div style="padding: 0.6rem; font-weight: 600; font-size: 0.85rem; text-align: center; border-bottom: 1px solid var(--border-color); background: var(--item-hover); color: var(--text-color);">
                            <i class="fa-solid fa-envelope-open-text" style="color: #f59e0b;"></i> Incoming (Keyword Requests)
                        </div>
                        <ul class="scrollable-container" style="list-style: none; padding: 0; margin: 0; overflow-y: auto; max-height: 405px;">
                            ${incoming.length > 0 ? incoming.map(buildItem).join('') : '<li style="padding: 1rem; text-align: center; color: var(--text-light); font-size: 0.8rem;">No incoming requests.</li>'}
                        </ul>
                    </div>
                </li>
                `;
            } else if (isIndividual && (outgoing.length > 0 || incoming.length > 0)) {
                // Render outgoing first
                if (outgoing.length > 0) {
                    html += outgoing.map(buildItem).join('');
                }

                // Separator button
                if (outgoing.length > 0 && incoming.length > 0) {
                    html += `
                        <li class="msg-separator" style="list-style:none; padding: 0.5rem 1.25rem; display: flex; align-items: center; gap: 0.75rem; background: var(--item-hover);">
                            <hr style="flex: 1; border: none; border-top: 1px dashed var(--border-color);">
                            <button style="background: none; border: 1px solid var(--border-color); border-radius: 20px; padding: 0.25rem 0.85rem; font-size: 0.72rem; color: var(--text-light); cursor: default; white-space: nowrap; display: flex; align-items: center; gap: 0.4rem; font-weight: 500;">
                                <i class="fa-solid fa-envelope-open-text" style="font-size: 0.65rem; color: #f59e0b;"></i>
                                Incoming Messages
                            </button>
                            <hr style="flex: 1; border: none; border-top: 1px dashed var(--border-color);">
                        </li>`;
                } else if (outgoing.length === 0 && incoming.length > 0) {
                    html += `
                        <li class="msg-separator" style="list-style:none; padding: 0.5rem 1.25rem; display: flex; align-items: center; gap: 0.75rem; background: var(--item-hover);">
                            <hr style="flex: 1; border: none; border-top: 1px dashed var(--border-color);">
                            <button style="background: none; border: 1px solid var(--border-color); border-radius: 20px; padding: 0.25rem 0.85rem; font-size: 0.72rem; color: var(--text-light); cursor: default; white-space: nowrap; display: flex; align-items: center; gap: 0.4rem; font-weight: 500;">
                                <i class="fa-solid fa-envelope-open-text" style="font-size: 0.65rem; color: #f59e0b;"></i>
                                Incoming Messages
                            </button>
                            <hr style="flex: 1; border: none; border-top: 1px dashed var(--border-color);">
                        </li>`;
                }

                // Render incoming after separator
                if (incoming.length > 0) {
                    html += incoming.map(buildItem).join('');
                }
            } else {
                // Broadcast / other views — render as-is
                html = messages.map(buildItem).join('');
            }

            list.innerHTML = html;
            filterMessages();
        }

        function clearDateFilter() {
            document.getElementById('messageDateFilter').value = '';
            filterMessages();
        }

        function filterMessages() {
            const query = document.getElementById('messageSearch').value.toLowerCase();
            const dateFilter = document.getElementById('messageDateFilter').value; // format: YYYY-MM-DD
            const items = document.querySelectorAll('#message-list li');

            items.forEach(li => {
                // Separator row or wrapper row: hide if applicable, keep otherwise
                if (li.classList.contains('msg-separator')) {
                    li.style.display = activeDirectionFilter ? 'none' : 'flex';
                    return;
                }
                if (li.classList.contains('keyword-wrapper-li')) {
                    return; // Ignore the wrapper `li` itself for text/date filtering (we filter the inner ones)
                }

                const text = li.textContent.toLowerCase();
                const liDate = li.getAttribute('data-date'); // format: YYYY-MM-DD
                const liDir  = li.getAttribute('data-direction'); // 'outgoing' | 'incoming'

                const matchesText = text.includes(query);
                const matchesDate = !dateFilter || liDate === dateFilter;
                const matchesDir  = !activeDirectionFilter || liDir === activeDirectionFilter;

                li.style.display = (matchesText && matchesDate && matchesDir) ? 'block' : 'none';
            });
        }

        document.getElementById('sendMessageForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);

            // Convert checkbox values to boolean strings for the API
            const data = Object.fromEntries(formData);
            data.is_scheduled = formData.get('is_scheduled') === 'on' ? '1' : '0';
            data.no_reply = formData.get('no_reply') === 'on' ? '1' : '0';

            // Remove empty moresco fields so backend doesn't trip on them
            if (!data.moresco_phone) delete data.moresco_phone;
            if (!data.moresco_name) delete data.moresco_name;
            if (!data.contact_id) delete data.contact_id;

            // Handle multiple group IDs (app groups + MORESCO service area codes + municipalities + barangays)
            if (data.type === 'broadcast') {
                data.group_ids = formData.getAll('group_ids[]');
                data.moresco_group_codes = formData.getAll('moresco_group_codes[]');
                data.moresco_municipalities = formData.getAll('moresco_municipalities[]');
                data.moresco_barangays = formData.getAll('moresco_barangays[]');
                if (data.group_ids.length === 0) delete data.group_ids;
                if (data.moresco_group_codes.length === 0) delete data.moresco_group_codes;
                if (data.moresco_municipalities.length === 0) delete data.moresco_municipalities;
                if (data.moresco_barangays.length === 0) delete data.moresco_barangays;
            }

            try {
                const response = await fetchAPI('/messages', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });

                // Capture current type before reset
                const currentType = document.getElementById('messageTypeSelect').value;

                e.target.reset();

                // Reset contact picker display text and hidden values
                document.getElementById('contactSelectText').textContent = 'Choose Contact...';
                document.getElementById('contactIdInput').value = '';
                document.getElementById('morescoPhoneInput').value = '';
                document.getElementById('morescoNameInput').value = '';
                if (document.getElementById('contactSearchInput')) {
                    document.getElementById('contactSearchInput').value = '';
                    filterContactDropdown();
                }

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
                if (typeParam !== 'auto_reply') {
                    document.getElementById('messageTypeSelect').value = typeParam;
                    document.getElementById('messageTypeGroup').style.display = 'none'; // Hide selector if type is forced
                }

                // Update Title
                let title = typeParam === 'broadcast' ? 'Send Broadcast Message' : 'Send Individual Notification';
                if (scheduledParam === '1' || typeParam === 'auto_reply') {
                    // Hide send form and expand history to full width
                    document.getElementById('sendMessageContainer').style.display = 'none';
                    const gridContainer = document.querySelector('.grid-2');
                    if (gridContainer) gridContainer.style.gridTemplateColumns = '1fr';

                    if (scheduledParam === '1') {
                        // Show view controls
                        document.getElementById('scheduledViewControls').style.display = 'block';

                        // Show list, show calendar (defaulting to calendar)
                        toggleScheduledView('calendar');
                    }

                    // Update history title
                    const historyHeader = document.getElementById('historyTitle');
                    if (historyHeader) {
                        historyHeader.innerText = typeParam === 'auto_reply' ? 'Keyword Auto-Reply History' : 'Scheduled Messages History';
                    }
                } else {
                    document.getElementById('formTitle').innerText = title;
                    toggleRecipientInput();
                }

                // Show Outgoing/Incoming filter buttons on individual view
                if (typeParam === 'individual') {
                    const dirBtns = document.getElementById('directionFilterBtns');
                    if (dirBtns) dirBtns.style.display = 'flex';
                }
            }
        });

        let activeDirectionFilter = null; // 'outgoing' | 'incoming' | null

        function toggleDirectionFilter(dir) {
            const btnOut = document.getElementById('btnFilterOutgoing');
            const btnIn = document.getElementById('btnFilterIncoming');

            if (activeDirectionFilter === dir) {
                // Deselect — show all
                activeDirectionFilter = null;
                btnOut.style.background = '#f1f5f9';
                btnOut.style.color = 'var(--text-light)';
                btnOut.style.borderColor = 'var(--border-color)';
                btnIn.style.background = '#f1f5f9';
                btnIn.style.color = 'var(--text-light)';
                btnIn.style.borderColor = 'var(--border-color)';
            } else {
                activeDirectionFilter = dir;
                // Reset both
                btnOut.style.background = '#f1f5f9';
                btnOut.style.color = 'var(--text-light)';
                btnOut.style.borderColor = 'var(--border-color)';
                btnIn.style.background = '#f1f5f9';
                btnIn.style.color = 'var(--text-light)';
                btnIn.style.borderColor = 'var(--border-color)';
                // Highlight active
                if (dir === 'outgoing') {
                    btnOut.style.background = 'var(--primary-color)';
                    btnOut.style.color = '#fff';
                    btnOut.style.borderColor = 'var(--primary-color)';
                } else {
                    btnIn.style.background = '#f59e0b';
                    btnIn.style.color = '#fff';
                    btnIn.style.borderColor = '#f59e0b';
                }
            }
            filterMessages();
        }

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