@extends('layouts.app')

@section('title', 'Contacts & Groups')

@section('content')
<div class="grid-2" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Contacts Section -->
    <div>
        <div class="header" style="margin-bottom: 1rem;">
            <h2>Contacts</h2>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn btn-primary" onclick="openContactModal()">
                    <i class="fa-solid fa-plus"></i> New Contact
                </button>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div id="bulkActionsBar" class="card" style="display: none; padding: 0.75rem 1.5rem; margin-bottom: 1rem; background: #f0f7ff; border: 1px solid #cce3ff; flex-direction: row; align-items: center; justify-content: space-between;">
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

        <div class="card" style="padding: 0;">
            <div class="scrollable-container">
                <table class="table-dense" style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                        <tr style="text-align: left; border-bottom: 2px solid #e2e8f0;">
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="selectAllContacts" onclick="toggleSelectAll(this)">
                            </th>
                            <th style="width: 40px;">ID</th>
                            <th style="width: 200px;">Name</th>
                            <th style="width: 150px;">Phone</th>
                            <th>Email</th>
                            <th style="width: 100px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="contacts-table-body">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Groups Section -->
    <div>
        <div class="header" style="margin-bottom: 1rem;">
            <h2>Groups</h2>
            <button class="btn btn-primary" onclick="openGroupModal()">
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
                <button type="button" class="btn" onclick="closeModal('contactModal')" style="background: #e2e8f0;">Cancel</button>
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
                <button type="button" class="btn" onclick="closeModal('groupModal')" style="background: #e2e8f0;">Cancel</button>
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

    // Load Data
    let allContacts = [];
    let allGroups = [];
    let selectedContactIds = new Set();

    async function loadContacts() {
        allContacts = await fetchAPI('/contacts');
        const tbody = document.getElementById('contacts-table-body');
        tbody.innerHTML = allContacts.map(c => `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="text-align: center;">
                    <input type="checkbox" class="contact-checkbox" value="${c.id}" onchange="toggleContactSelection(this)">
                </td>
                <td style="color: var(--text-light); font-weight: 600;">#${c.id}</td>
                <td style="font-weight: 500;">${c.name}</td>
                <td style="white-space: nowrap;">${c.phone_number}</td>
                <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.email || ''}">${c.email || '-'}</td>
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
        allGroups = await fetchAPI('/groups');
        const list = document.getElementById('groups-list');
        list.innerHTML = allGroups.map(g => `
            <li class="table-dense" style="padding: 0.6rem 0.75rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="color: var(--text-light); font-weight: 600; min-width: 30px;">#${g.id}</span>
                    <div>
                        <strong style="cursor: pointer; color: var(--moresco-blue);" onclick="viewGroup(${g.id})">${g.name}</strong>
                        <div style="font-size: 0.75rem; color: var(--text-light);">${g.contacts_count} members</div>
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
            </li>
        `).join('');

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

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        loadContacts();
        loadGroups();
    });
</script>
@endpush
