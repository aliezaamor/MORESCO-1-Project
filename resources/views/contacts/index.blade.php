@extends('layouts.app')

@section('title', 'Contacts & Groups')

@section('content')
<div class="grid-2" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Contacts Section -->
    <div>
        <div class="header" style="margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 1rem; border-bottom: 1px solid var(--border-color); flex-grow: 1; margin-right: 1rem;">
                <button id="tab-app" class="source-tab" onclick="setSource('app')" style="background: none; border: none; padding: 0.5rem 1rem; border-bottom: 2px solid var(--primary-color); color: var(--primary-color); font-weight: 600; cursor: pointer; font-size: 1.1rem;">
                    App Contacts
                </button>
                <button id="tab-moresco" class="source-tab" onclick="setSource('moresco')" style="background: none; border: none; padding: 0.5rem 1rem; border-bottom: 2px solid transparent; color: var(--text-light); font-weight: 500; cursor: pointer; font-size: 1.1rem;">
                    MORESCO System Contacts
                </button>
            </div>
            
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary btn-add-entity" onclick="openContactModal()">
                    <i class="fa-solid fa-plus"></i> New Contact
                </button>
            </div>
        </div>
        
        <div id="sourceWarning" class="alert-warning" style="display: none;">
            <i class="fa-solid fa-circle-info"></i> <strong>Note:</strong> MORESCO System contacts and groups are synced automatically from external databases. You cannot add them manually here.
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="card bulk-actions-bar" style="display: none;">
            <div style="font-size: 0.875rem; color: var(--moresco-blue); font-weight: 600;">
                <span id="selectedCount">0</span> contacts selected
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <select id="bulkGroupSelect" class="form-control" style="width: 200px; padding: 0.4rem; font-size: 0.8125rem;">
                    <option value="">Add to Group...</option>
                </select>
                <button class="btn btn-primary" onclick="applyBulkAddToGroup()" style="padding: 0.4rem 1rem; font-size: 0.8125rem;">
                    Apply
                </button>
                <button class="btn" onclick="clearSelection()" style="padding: 0.4rem; background: transparent; color: var(--text-light);">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <!-- MORESCO Search bar (hidden by default, shown when source=moresco) -->
        <div id="morescoSearchBar" style="display: none; margin-bottom: 0.75rem;">
            <div style="position: relative; max-width: 380px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.9rem;"></i>
                <input type="text" id="morescoSearchInput" placeholder="Search by account number, name, or phone..."
                    style="padding: 0.5rem 0.75rem 0.5rem 2.25rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); width: 100%; font-size: 0.85rem;"
                    oninput="debouncedMorescoSearch()">
            </div>
        </div>

        <div class="card" style="padding: 0;">
            <div class="scrollable-container">
                <table class="table-dense" style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                        <tr style="text-align: left; border-bottom: 2px solid var(--border-color);">
                            <th style="width: 40px; text-align: center;" id="th-checkbox">
                                <input type="checkbox" id="selectAllContacts" onclick="toggleSelectAll(this)">
                            </th>
                            <th style="width: 40px;">ID</th>
                            <th style="width: 180px;">Name</th>
                            <th style="width: 140px;">Phone</th>
                            <th class="col-email">Email</th>
                            <th class="col-extra" style="display:none; width: 140px;">Service Area</th>
                            <th class="col-extra" style="display:none; width: 100px;">Status</th>
                            <th style="width: 100px; text-align: center;" id="th-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="contacts-table-body">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <div id="morescoPageControls" style="display: none; margin-top: 1rem; display: none; gap: 0.75rem; align-items: center; justify-content: space-between;">
            <span id="morescoPageInfo" style="font-size: 0.8rem; color: var(--text-light);"></span>
            <div style="display: flex; gap: 0.5rem;">
                <button id="btnPrevPage" class="btn btn-secondary" onclick="changeMorescoPage(-1)" style="font-size: 0.8rem; padding: 0.35rem 0.8rem;">&#8592; Prev</button>
                <button id="btnNextPage" class="btn btn-secondary" onclick="changeMorescoPage(1)" style="font-size: 0.8rem; padding: 0.35rem 0.8rem;">Next &#8594;</button>
            </div>
        </div>
    </div>

    <!-- Groups Section -->
    <div>
        <div class="header" style="margin-bottom: 1rem;">
            <h2>Groups</h2>
            <button class="btn btn-primary btn-add-entity" onclick="openGroupModal()">
                <i class="fa-solid fa-plus"></i> New Group
            </button>
        </div>
        <div class="card" style="padding: 0;">
            <div class="scrollable-container">
                <ul id="groups-list" style="list-style: none; padding: 0;">
                    <!-- Populated by JS -->
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Contact Modal -->
<div id="contactModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 400px; max-width: 90%;">
        <h3 id="contactModalTitle">Add New Contact</h3>
        <form id="contactForm" class="mt-4">
            <input type="hidden" name="id" id="contactId">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" id="contactName" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone_number" id="contactPhone" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" id="contactEmail" class="form-control">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('contactModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Group Modal -->
<div id="groupModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 400px; max-width: 90%;">
        <h3 id="groupModalTitle">Add New Group</h3>
        <form id="groupForm" class="mt-4">
            <input type="hidden" name="id" id="groupId">
            <div class="form-group">
                <label class="form-label">Name</label>
                <input type="text" name="name" id="groupName" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="groupDescription" class="form-control"></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeModal('groupModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Group View Modal -->
<div id="groupViewModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 600px; max-width: 90%; max-height: 90vh; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9;">
            <div>
                <h3 id="groupViewTitle" style="margin:0;">Group Details</h3>
                <div id="groupViewSub" style="font-size: 0.875rem; color: var(--text-light);">---</div>
            </div>
            <button class="btn" style="padding: 0.5rem;" onclick="closeModal('groupViewModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div style="flex-grow: 1; overflow-y: auto; padding: 1.5rem 0;">
            <!-- Add Member Search -->
            <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <label class="form-label" style="font-size: 0.8125rem;">Quick Add Member</label>
                <div style="display: flex; gap: 0.5rem;">
                    <div style="position: relative; flex-grow: 1;">
                        <input type="text" id="memberSearch" class="form-control" placeholder="Search by name or phone..." oninput="searchPotentialMembers(this.value)">
                        <div id="memberSearchResults" class="card" style="display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 20; padding: 0.5rem; margin-top: 2px; box-shadow: var(--shadow-lg);">
                            <!-- Results here -->
                        </div>
                    </div>
                </div>
            </div>

            <div id="groupViewContent">
                <!-- Populated by JS -->
            </div>
        </div>
    </div>
</div>
<!-- MORESCO Group Members Modal -->
<div id="morescoGroupModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 640px; max-width: 95%; max-height: 88vh; display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9;">
            <div>
                <h3 id="morescoGroupModalTitle" style="margin: 0; color: var(--moresco-blue);">Group Members</h3>
                <div id="morescoGroupModalSub" style="font-size: 0.8rem; color: var(--text-light); margin-top: 0.2rem;"></div>
            </div>
            <button class="btn" style="padding: 0.5rem;" onclick="closeModal('morescoGroupModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- Filter input -->
        <div style="padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9;">
            <div style="position: relative;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--text-light); font-size: 0.85rem;"></i>
                <input type="text" id="morescoGroupMemberSearch" placeholder="Filter by name, account #, or phone..."
                    style="width: 100%; padding: 0.45rem 0.75rem 0.45rem 2.25rem; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); font-size: 0.83rem; box-sizing: border-box;"
                    oninput="filterMorescoGroupMembers()">
            </div>
        </div>

        <!-- Members list -->
        <div id="morescoGroupMembersList" style="flex-grow: 1; overflow-y: auto; padding: 0;">
            <div style="text-align: center; padding: 2rem; color: var(--text-light);"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Utils
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
        if (id === 'contactModal') {
            document.getElementById('contactForm').reset();
            document.getElementById('contactId').value = '';
            document.getElementById('contactModalTitle').innerText = 'Add New Contact';
        }
        if (id === 'groupModal') {
            document.getElementById('groupForm').reset();
            document.getElementById('groupId').value = '';
            document.getElementById('groupModalTitle').innerText = 'Add New Group';
        }
    }

    // Modal Helpers
    function openContactModal(contact = null) {
        if (contact) {
            document.getElementById('contactModalTitle').innerText = 'Edit Contact';
            document.getElementById('contactId').value = contact.id;
            document.getElementById('contactName').value = contact.name;
            document.getElementById('contactPhone').value = contact.phone_number;
            document.getElementById('contactEmail').value = contact.email || '';
        } else {
            document.getElementById('contactModalTitle').innerText = 'Add New Contact';
            document.getElementById('contactForm').reset();
            document.getElementById('contactId').value = '';
        }
        openModal('contactModal');
    }

    function openGroupModal(group = null) {
        if (group) {
            document.getElementById('groupModalTitle').innerText = 'Edit Group';
            document.getElementById('groupId').value = group.id;
            document.getElementById('groupName').value = group.name;
            document.getElementById('groupDescription').value = group.description || '';
        } else {
            document.getElementById('groupModalTitle').innerText = 'Add New Group';
            document.getElementById('groupForm').reset();
            document.getElementById('groupId').value = '';
        }
        openModal('groupModal');
    }

    // State
    let currentSource = 'app';
    let allContacts = [];
    let allGroups = [];
    let selectedContactIds = new Set();
    let morescoOffset = 0;
    let morescoTotal = 0;
    const MORESCO_PER_PAGE = 100;
    let morescoSearchTimeout = null;

    function debouncedMorescoSearch() {
        clearTimeout(morescoSearchTimeout);
        morescoSearchTimeout = setTimeout(() => {
            morescoOffset = 0;
            loadContacts();
        }, 400);
    }

    function changeMorescoPage(direction) {
        const newOffset = morescoOffset + direction * MORESCO_PER_PAGE;
        if (newOffset < 0) return;
        if (newOffset >= morescoTotal) return;
        morescoOffset = newOffset;
        loadContacts();
    }

    function setSource(source) {
        currentSource = source;
        morescoOffset = 0;
        
        // Update tabs UX
        document.querySelectorAll('.source-tab').forEach(tab => {
            tab.style.borderBottomColor = 'transparent';
            tab.style.color = 'var(--text-light)';
            tab.style.fontWeight = '500';
        });
        
        const activeTab = document.getElementById(`tab-${source}`);
        if(activeTab) {
            activeTab.style.borderBottomColor = 'var(--primary-color)';
            activeTab.style.color = 'var(--primary-color)';
            activeTab.style.fontWeight = '600';
        }

        // MORESCO-specific UI toggles
        const isMoresco = source === 'moresco';
        const addBtns = document.querySelectorAll('.btn-add-entity');
        addBtns.forEach(btn => btn.style.display = isMoresco ? 'none' : 'inline-flex');
        document.getElementById('sourceWarning').style.display = isMoresco ? 'block' : 'none';
        document.getElementById('morescoSearchBar').style.display = isMoresco ? 'block' : 'none';
        document.getElementById('morescoPageControls').style.display = isMoresco ? 'flex' : 'none';
        document.querySelectorAll('.col-extra').forEach(el => el.style.display = isMoresco ? '' : 'none');
        document.getElementById('th-actions').style.display = isMoresco ? 'none' : '';
        document.getElementById('th-checkbox').style.display = isMoresco ? 'none' : '';
        document.getElementById('selectAllContacts').style.display = isMoresco ? 'none' : '';

        // Reload data
        loadContacts();
        loadGroups();
    }

    async function loadContacts() {
        const tbody = document.getElementById('contacts-table-body');
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 2rem; color: var(--text-light);"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</td></tr>`;

        if (currentSource === 'moresco') {
            const search = document.getElementById('morescoSearchInput')?.value || '';
            const endpoint = `/contacts?source=moresco&per_page=${MORESCO_PER_PAGE}&offset=${morescoOffset}${search ? '&search=' + encodeURIComponent(search) : ''}`;
            const response = await fetchAPI(endpoint);
            allContacts = response.data || [];
            morescoTotal = response.total || 0;

            // Update pagination info
            const from = morescoOffset + 1;
            const to = Math.min(morescoOffset + MORESCO_PER_PAGE, morescoTotal);
            document.getElementById('morescoPageInfo').innerText = `Showing ${from}–${to} of ${morescoTotal.toLocaleString()} members`;
            document.getElementById('btnPrevPage').disabled = morescoOffset === 0;
            document.getElementById('btnNextPage').disabled = (morescoOffset + MORESCO_PER_PAGE) >= morescoTotal;

            if (allContacts.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; padding: 2rem; color: var(--text-light);">No MORESCO members found.</td></tr>`;
                return;
            }

            tbody.innerHTML = allContacts.map(c => `
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="display:none;"></td>
                    <td style="color: var(--text-light); font-weight: 600; font-size: 0.8rem;">${c.id}</td>
                    <td style="font-weight: 500;">${c.name || '-'}</td>
                    <td style="white-space: nowrap;">${c.phone_number || '-'}</td>
                    <td class="col-email" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.email || ''}">${c.email || '-'}</td>
                    <td class="col-extra" style="font-size: 0.8rem; color: var(--text-light);">${c.service_area || '-'}</td>
                    <td class="col-extra">
                        <span class="badge ${c.status === 'Active' ? 'badge-success' : 'badge-muted'}">
                            ${c.status || 'Unknown'}
                        </span>
                    </td>
                    <td style="display:none;"></td>
                </tr>
            `).join('');
            return;
        }

        // App contacts
        allContacts = await fetchAPI(`/contacts?source=${currentSource}`);
        
        if (allContacts.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-light);">No contacts found for this source.</td></tr>`;
            clearSelection();
            return;
        }

        tbody.innerHTML = allContacts.map(c => `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="text-align: center;">
                    <input type="checkbox" class="contact-checkbox" value="${c.id}" onchange="toggleContactSelection(this)">
                </td>
                <td style="color: var(--text-light); font-weight: 600;">#${c.id}</td>
                <td style="font-weight: 500;">${c.name}</td>
                <td style="white-space: nowrap;">${c.phone_number}</td>
                <td class="col-email" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.email || ''}">${c.email || '-'}</td>
                <td class="col-extra" style="display:none;"></td>
                <td class="col-extra" style="display:none;"></td>
                <td style="text-align: center;">
                    <div style="display: flex; gap: 0.25rem; justify-content: center;">
                        <button class="btn btn-icon" style="color: var(--moresco-blue);" onclick='openContactModal(${JSON.stringify(c).replace(/'/g, "&apos;")})'>
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn btn-icon" style="color: var(--danger-color);" onclick="deleteContact(${c.id})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
        clearSelection();
    }

    async function loadGroups() {
        const list = document.getElementById('groups-list');

        if (currentSource === 'moresco') {
            // Fetch all three MORESCO group types in parallel
            const [serviceAreas, municipalities, barangays] = await Promise.all([
                fetchAPI('/groups?source=moresco'),
                fetchAPI('/groups?source=moresco_municipality'),
                fetchAPI('/groups?source=moresco_barangay'),
            ]);

            // allGroups = service areas only (for bulk-select compat, kept simple)
            allGroups = serviceAreas;

            let html = '';

            // ── Service Areas ───────────────────────────────────────────────
            if (serviceAreas.length > 0) {
                html += groupSectionHeader('Service Areas', serviceAreas.length);
                html += serviceAreas.map(g => morescoGroupRow(g, 'sa')).join('');
            }

            // ── Municipalities ──────────────────────────────────────────────
            if (municipalities.length > 0) {
                html += groupSectionHeader('Municipalities', municipalities.length);
                html += municipalities.map(g => morescoGroupRow(g, 'municipality')).join('');
            }

            // ── Barangays (collapsible) ──────────────────────────────────────
            if (barangays.length > 0) {
                html += `
                <li style="padding: 0.5rem 0.75rem; border-bottom: 1px solid #f1f5f9; background: var(--item-hover); display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleContactsBarangayList()">
                    <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--moresco-blue); letter-spacing: 0.05em;">
                        <i class="fa-solid fa-map-pin" style="margin-right: 0.35rem;"></i>Barangays
                        <span style="font-weight: 400; color: var(--text-light);">(${barangays.length})</span>
                    </span>
                    <span id="brgyToggleIcon" style="font-size: 0.75rem; color: var(--text-light);">▼ expand</span>
                </li>
                <div id="barangayGroupRows" style="display: none;">
                    ${barangays.map(g => morescoGroupRow(g, 'barangay')).join('')}
                </div>`;
            }

            if (html === '') {
                html = `<li style="text-align: center; padding: 2rem; color: var(--text-light);">No MORESCO groups found.</li>`;
            }

            list.innerHTML = html;
            updateBulkSelect();
            return;
        }

        // ── App groups ───────────────────────────────────────────────────────
        allGroups = await fetchAPI(`/groups?source=${currentSource}`);

        if (allGroups.length === 0) {
            list.innerHTML = `<li style="text-align: center; padding: 2rem; color: var(--text-light);">No groups found for this source.</li>`;
            updateBulkSelect();
            return;
        }

        list.innerHTML = allGroups.map(g => {
            const count = g.member_count ?? g.contacts_count ?? 0;
            return `
            <li class="table-dense" style="padding: 0.6rem 0.75rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="color: var(--text-light); font-weight: 600; min-width: 30px;">#${g.id}</span>
                    <div>
                        <strong style="cursor: pointer; color: var(--moresco-blue);" onclick="viewGroup(${g.id})">${g.name}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-light);">${count} members</div>
                    </div>
                </div>
                <div style="display: flex; gap: 0.25rem;">
                    <button class="btn btn-icon" style="color: var(--moresco-blue);" onclick='openGroupModal(${JSON.stringify(g).replace(/'/g, "&apos;")})'>
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-icon" style="color: var(--danger-color);" onclick="deleteGroup(${g.id})">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </li>`;
        }).join('');

        updateBulkSelect();
    }

    function groupSectionHeader(label, count) {
        return `<li style="padding: 0.5rem 0.75rem; border-bottom: 1px solid #f1f5f9; background: var(--item-hover);">
            <span style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--moresco-blue); letter-spacing: 0.05em;">
                <i class="fa-solid fa-layer-group" style="margin-right: 0.35rem;"></i>${label}
                <span style="font-weight: 400; color: var(--text-light);">(${count})</span>
            </span>
        </li>`;
    }

    function morescoGroupRow(g, type) {
        const count = g.member_count ?? 0;
        const encodedId   = encodeURIComponent(g.id).replace(/'/g, "&#39;");
        const escapedName = (g.name || '').replace(/'/g, "&#39;");
        return `
        <li class="table-dense" style="padding: 0.6rem 0.75rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; cursor: pointer;"
            onclick="viewMorescoGroup('${type}', '${g.id.replace(/'/g, "\\'") }', '${escapedName}')">
            <div>
                <strong style="color: var(--moresco-blue);">${g.name}</strong>
                <div style="font-size: 0.75rem; color: var(--text-light);">${count} members</div>
            </div>
            <span class="badge badge-info" style="white-space: nowrap;">
                <i class="fa-solid fa-eye" style="margin-right: 2px;"></i> View
            </span>
        </li>`;
    }

    function toggleContactsBarangayList() {
        const rows = document.getElementById('barangayGroupRows');
        const icon = document.getElementById('brgyToggleIcon');
        if (!rows) return;
        const isOpen = rows.style.display !== 'none';
        rows.style.display = isOpen ? 'none' : 'block';
        if (icon) icon.textContent = isOpen ? '▼ expand' : '▲ collapse';
    }



    function updateBulkSelect() {
        // Update bulk select
        const bulkSelect = document.getElementById('bulkGroupSelect');
        bulkSelect.innerHTML = '<option value="">Add to Group...</option>' + 
            allGroups.map(g => `<option value="${g.id}">${g.name}</option>`).join('');
    }

    // -- Selection Logic --
    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.contact-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = master.checked;
            toggleContactSelection(cb);
        });
    }

    function toggleContactSelection(cb) {
        if (cb.checked) {
            selectedContactIds.add(cb.value);
        } else {
            selectedContactIds.delete(cb.value);
            document.getElementById('selectAllContacts').checked = false;
        }
        updateBulkBar();
    }

    function updateBulkBar() {
        const bar = document.getElementById('bulkActionsBar');
        const count = document.getElementById('selectedCount');
        count.innerText = selectedContactIds.size;
        bar.style.display = selectedContactIds.size > 0 ? 'flex' : 'none';
    }

    function clearSelection() {
        selectedContactIds.clear();
        document.getElementById('selectAllContacts').checked = false;
        document.querySelectorAll('.contact-checkbox').forEach(cb => cb.checked = false);
        updateBulkBar();
    }

    async function applyBulkAddToGroup() {
        const groupId = document.getElementById('bulkGroupSelect').value;
        if (!groupId) { alert('Please select a group'); return; }

        try {
            await fetchAPI(`/groups/${groupId}/contacts`, {
                method: 'POST',
                body: JSON.stringify({ contact_ids: Array.from(selectedContactIds) })
            });
            alert('Contacts added successfully!');
            clearSelection();
            loadGroups();
        } catch (err) {
            alert('Failed to add contacts: ' + err.message);
        }
    }

    // -- Group View & Searching --
    let currentViewingGroupId = null;

    async function viewGroup(id) {
        currentViewingGroupId = id;
        const group = await fetchAPI(`/groups/${id}`);
        document.getElementById('groupViewTitle').innerText = group.name;
        document.getElementById('groupViewSub').innerText = `${group.contacts.length} members • ${group.description || 'No description'}`;
        
        const content = document.getElementById('groupViewContent');
        let html = `<h4>Active Members:</h4>`;
        
        if (group.contacts.length === 0) {
            html += `<p style="color: var(--text-light); margin-top: 1rem; text-align:center;">No members in this group yet. Use the search above to add some!</p>`;
        } else {
            html += `<ul style="list-style: none; padding: 0; margin-top: 1rem; border: 1px solid #f1f5f9; border-radius: 8px;">`;
            group.contacts.forEach(c => {
                html += `<li style="padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: 600; font-size: 0.875rem;">${c.name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-light);">${c.phone_number}</div>
                    </div>
                    <button class="btn btn-icon" style="color: var(--danger-color);" title="Remove from Group" onclick="removeFromGroup(${group.id}, ${c.id})">
                        <i class="fa-solid fa-user-minus"></i>
                    </button>
                </li>`;
            });
            html += `</ul>`;
        }
        
        content.innerHTML = html;
        openModal('groupViewModal');
    }

    function searchPotentialMembers(query) {
        const resultsDiv = document.getElementById('memberSearchResults');
        if (!query || query.length < 1) {
            resultsDiv.style.display = 'none';
            return;
        }

        const filtered = allContacts.filter(c => 
            (c.name.toLowerCase().includes(query.toLowerCase()) || c.phone_number.includes(query))
        ).slice(0, 5); // Limit results

        if (filtered.length === 0) {
            resultsDiv.innerHTML = '<div style="padding: 0.5rem; font-size: 0.8125rem; color: var(--text-light);">No contacts found</div>';
        } else {
            resultsDiv.innerHTML = filtered.map(c => `
                <div style="padding: 0.5rem; border-bottom: 1px solid #f8fafc; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" 
                     class="hover-bg" onclick="addSingleToGroup(${c.id})">
                    <div>
                        <div style="font-weight: 600; font-size: 0.8125rem;">${c.name}</div>
                        <div style="font-size: 0.7rem; color: var(--text-light);">${c.phone_number}</div>
                    </div>
                    <i class="fa-solid fa-plus-circle" style="color: var(--primary-color);"></i>
                </div>
            `).join('');
        }
        resultsDiv.style.display = 'block';
    }

    async function addSingleToGroup(contactId) {
        if (!currentViewingGroupId) return;
        try {
            await fetchAPI(`/groups/${currentViewingGroupId}/contacts`, {
                method: 'POST',
                body: JSON.stringify({ contact_ids: [contactId] })
            });
            document.getElementById('memberSearch').value = '';
            document.getElementById('memberSearchResults').style.display = 'none';
            await viewGroup(currentViewingGroupId);
            loadGroups();
        } catch (err) {
            alert('Failed to add member');
        }
    }

    async function removeFromGroup(groupId, contactId) {
        if (!confirm('Remove this contact from the group?')) return;
        try {
            await fetchAPI(`/groups/${groupId}/contacts/${contactId}`, { method: 'DELETE' });
            viewGroup(groupId);
            loadGroups();
        } catch (err) {
            alert('Failed to remove contact');
        }
    }

    // Actions
    document.getElementById('contactForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        const id = data.id;
        delete data.id;
        
        if (!id) {
            data.source = currentSource;
        }

        try {
            if (id) {
                await fetchAPI(`/contacts/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
            } else {
                await fetchAPI('/contacts', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
            }
            closeModal('contactModal');
            loadContacts();
        } catch (err) {
            alert('Failed to save contact');
        }
    });

    document.getElementById('groupForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        const id = data.id;
        delete data.id;
        
        if (!id) {
            data.source = currentSource;
        }

        try {
            if (id) {
                await fetchAPI(`/groups/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
            } else {
                await fetchAPI('/groups', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
            }
            closeModal('groupModal');
            loadGroups();
        } catch (err) {
            alert('Failed to save group');
        }
    });

    async function deleteContact(id) {
        if (!confirm('Are you sure you want to delete this contact?')) return;
        try {
            await fetchAPI(`/contacts/${id}`, { method: 'DELETE' });
            loadContacts();
        } catch (err) {
            alert('Failed to delete contact: ' + err.message);
        }
    }

    async function deleteGroup(id) {
        if (!confirm('Are you sure you want to delete this group?')) return;
        try {
            await fetchAPI(`/groups/${id}`, { method: 'DELETE' });
            loadGroups();
        } catch (err) {
            alert('Failed to delete group: ' + err.message);
        }
    }

    // ── MORESCO Group Members Modal ──────────────────────────────────────────
    let morescoGroupMembersCache = [];

    async function viewMorescoGroup(type, id, label) {
        document.getElementById('morescoGroupModalTitle').textContent = label;
        document.getElementById('morescoGroupModalSub').textContent = 'Loading members...';
        document.getElementById('morescoGroupMemberSearch').value = '';
        document.getElementById('morescoGroupMembersList').innerHTML =
            `<div style="text-align:center;padding:2rem;color:var(--text-light);"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>`;
        openModal('morescoGroupModal');

        let endpoint = '/contacts?source=moresco';
        if (type === 'sa')          endpoint += `&sa_code=${encodeURIComponent(id)}`;
        if (type === 'municipality') endpoint += `&municipality=${encodeURIComponent(id)}`;
        if (type === 'barangay')    endpoint += `&barangay=${encodeURIComponent(id)}`;

        try {
            const members = await fetchAPI(endpoint);
            morescoGroupMembersCache = Array.isArray(members) ? members : (members.data || []);
            document.getElementById('morescoGroupModalSub').textContent =
                `${morescoGroupMembersCache.length.toLocaleString()} member${morescoGroupMembersCache.length !== 1 ? 's' : ''}`;
            renderMorescoGroupMembers(morescoGroupMembersCache);
        } catch (e) {
            document.getElementById('morescoGroupMembersList').innerHTML =
                `<div style="text-align:center;padding:2rem;color:#ef4444;">Failed to load members.</div>`;
        }
    }

    function renderMorescoGroupMembers(list) {
        const container = document.getElementById('morescoGroupMembersList');
        if (list.length === 0) {
            container.innerHTML = `<div style="text-align:center;padding:2rem;color:var(--text-light);">No members found.</div>`;
            return;
        }
        container.innerHTML = `<table style="width:100%;border-collapse:collapse;">
            <thead style="position:sticky;top:0;background:white;z-index:1;">
                <tr style="text-align:left;border-bottom:2px solid #e2e8f0;font-size:0.75rem;color:var(--text-light);text-transform:uppercase;">
                    <th style="padding:0.5rem 1rem;">Name</th>
                    <th style="padding:0.5rem;">Acct #</th>
                    <th style="padding:0.5rem;">Phone</th>
                </tr>
            </thead>
            <tbody>
                ${list.map(m => `
                <tr class="moresco-member-row" style="border-bottom:1px solid #f1f5f9;font-size:0.83rem;"
                    data-search="${(m.name||'').toLowerCase()} ${(m.id||'')} ${(m.phone_number||'')}">
                    <td style="padding:0.55rem 1rem;font-weight:500;">${m.name || '-'}</td>
                    <td style="padding:0.55rem;color:var(--moresco-blue);font-weight:600;font-size:0.78rem;white-space:nowrap;">${m.id || '-'}</td>
                    <td style="padding:0.55rem;white-space:nowrap;">${m.phone_number || '-'}</td>
                </tr>`).join('')}
            </tbody>
        </table>`;
    }

    function filterMorescoGroupMembers() {
        const q = document.getElementById('morescoGroupMemberSearch').value.toLowerCase();
        document.querySelectorAll('.moresco-member-row').forEach(row => {
            row.style.display = row.dataset.search.includes(q) ? '' : 'none';
        });
    }
    // ────────────────────────────────────────────────────────────────────────

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        setSource('app');
    });
</script>
@endpush
