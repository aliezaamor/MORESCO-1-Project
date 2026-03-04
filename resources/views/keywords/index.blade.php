@extends('layouts.app')

@section('title', 'Auto-Reply Keywords')

@section('content')
<div class="header" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
    <h2>Managed Keywords</h2>
    <button class="btn btn-primary" onclick="openKeywordModal()">
        <i class="fa-solid fa-plus"></i> New Keyword
    </button>
</div>

<div class="card" style="padding: 0;">
    <div class="scrollable-container" style="max-height: 400px;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                <tr style="text-align: left; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 1rem;">Keyword</th>
                    <th style="padding: 1rem;">Menu Parent</th>
                    <th style="padding: 1rem;">Reply Message</th>
                    <th style="padding: 1rem;">Status</th>
                    <th style="padding: 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="keywords-table-body">
                <!-- Populated by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Keyword Modal -->
<div id="keywordModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1001;">
    <div class="card" style="width: 450px; max-width: 90%; max-height: 90vh; display: flex; flex-direction: column;">
        <h3 id="keywordModalTitle">Add New Keyword</h3>
        <div style="overflow-y: auto; overflow-x: hidden; padding-right: 10px; margin-top: 1rem; flex-grow: 1;">
            <form id="keywordForm">
                <input type="hidden" name="id" id="keywordId">
                <div class="form-group">
                    <label class="form-label">Keyword / Option</label>
                    <input type="text" name="keyword" id="keywordKey" class="form-control" placeholder="e.g., BILL or 1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Parent Menu (Optional)</label>
                    <select name="parent_id" id="keywordParent" class="form-control">
                        <option value="">None (Top-Level)</option>
                    </select>
                    <small style="color: var(--text-light); font-size: 0.75rem;">If this is an option for another menu (like '1' for BILL), select the parent here.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Action Type</label>
                    <select name="action_type" id="keywordActionType" class="form-control" onchange="toggleActionFields()">
                        <option value="static">Static Reply (Standard single reply)</option>
                        <option value="billing_info">Billing Info (Has Balance / No Balance)</option>
                        <option value="due_date_info">Due Date Info (Has Due Date / Settled)</option>
                        <option value="payment_history">Payment History (Record Found / No Record)</option>
                        <option value="account_status">Account Status (Active / For Disconnection / Disconnected)</option>
                        <option value="advisory_info">Advisory Info (Active Advisory / No Advisory)</option>
                        <option value="outage_report">Outage Report (Request Location / Reported Successfully / Invalid Location / Already Reported)</option>
                        <option value="events_info">Events Info (Has Event / No Event)</option>
                    </select>
                    <small style="color: var(--text-light); font-size: 0.75rem;">Select the behavior for this keyword.</small>
                </div>

                <div id="dynamicFieldsContainer">
                    <div class="form-group">
                        <label class="form-label">Auto-Reply Content <small>(Fallback/Default)</small></label>
                        <textarea name="reply_content" id="keywordContent" class="form-control" rows="4" required></textarea>
                    </div>
                    <div id="actionDataInputs">
                        <!-- Dynamic fields will be injected here via JS -->
                    </div>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="keywordActive" value="1" checked> Active
                    </label>
                </div>
            </form>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
            <button type="button" class="btn" onclick="closeModal('keywordModal')" style="background: #e2e8f0;">Cancel</button>
            <button type="submit" form="keywordForm" class="btn btn-primary">Save</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let allKeywords = [];

    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
        if (id === 'keywordModal') {
            document.getElementById('keywordForm').reset();
            document.getElementById('keywordId').value = '';
            document.getElementById('keywordModalTitle').innerText = 'Add New Keyword';
        }
    }

    const actionConfig = {
        'static': [],
        'billing_info': [
            { key: 'has_balance', label: 'Reply (Has Outstanding Balance)' },
            { key: 'no_balance', label: 'Reply (No Balance)' }
        ],
        'due_date_info': [
            { key: 'has_due', label: 'Reply (Has Pending Due Date)' },
            { key: 'settled', label: 'Reply (Account Settled)' }
        ],
        'payment_history': [
            { key: 'record_found', label: 'Reply (Record Found)' },
            { key: 'no_record', label: 'Reply (No Recent Record)' }
        ],
        'account_status': [
            { key: 'active', label: 'Reply (Status: Active)' },
            { key: 'for_disconnection', label: 'Reply (Status: For Disconnection)' },
            { key: 'disconnected', label: 'Reply (Status: Disconnected)' }
        ],
        'advisory_info': [
            { key: 'active_advisory', label: 'Reply (Active Advisory Found)' },
            { key: 'no_advisory', label: 'Reply (No Active Advisory)' }
        ],
        'outage_report': [
            { key: 'request_location', label: 'Reply (Request Location Info)' },
            { key: 'reported_success', label: 'Reply (Reported Successfully)' },
            { key: 'invalid_location', label: 'Reply (Location Not Recognized)' },
            { key: 'already_reported', label: 'Reply (Outage Already Reported)' }
        ],
        'events_info': [
            { key: 'has_event', label: 'Reply (Upcoming Event Found)' },
            { key: 'no_event', label: 'Reply (No Upcoming Events)' }
        ]
    };

    function toggleActionFields(actionData = null) {
        const type = document.getElementById('keywordActionType').value;
        const container = document.getElementById('actionDataInputs');
        const defaultReplyContent = document.getElementById('keywordContent');
        
        container.innerHTML = '';
        
        if (type === 'static') {
            defaultReplyContent.parentElement.style.display = 'block';
            defaultReplyContent.required = true;
        } else {
            defaultReplyContent.parentElement.style.display = 'none';
            defaultReplyContent.required = false;
            defaultReplyContent.value = 'Dynamic Response'; // Need to pass validation
            
            const fields = actionConfig[type] || [];
            fields.forEach(field => {
                const value = actionData ? (actionData[field.key] || '') : '';
                container.innerHTML += `
                    <div class="form-group mt-3" style="border-left: 3px solid var(--moresc-blue); padding-left: 10px; margin-bottom: 1rem;">
                        <label class="form-label">${field.label}</label>
                        <textarea name="action_data[${field.key}]" class="form-control" rows="3" required>${value}</textarea>
                    </div>
                `;
            });
        }
    }

    function openKeywordModal(k = null) {
        // Populate parents dropdown (exclude self when editing)
        const parentSelect = document.getElementById('keywordParent');
        parentSelect.innerHTML = '<option value="">None (Top-Level)</option>' + 
            allKeywords
                .filter(pk => !k || pk.id != k.id)
                .map(pk => `<option value="${pk.id}">${pk.keyword}</option>`)
                .join('');

        if (k) {
            document.getElementById('keywordModalTitle').innerText = 'Edit Keyword';
            document.getElementById('keywordId').value = k.id;
            document.getElementById('keywordKey').value = k.keyword;
            document.getElementById('keywordContent').value = k.reply_content;
            document.getElementById('keywordActive').checked = k.is_active;
            document.getElementById('keywordParent').value = k.parent_id || '';
            document.getElementById('keywordActionType').value = k.action_type || 'static';
            toggleActionFields(k.action_data);
        } else {
            document.getElementById('keywordModalTitle').innerText = 'Add New Keyword';
            document.getElementById('keywordForm').reset();
            document.getElementById('keywordId').value = '';
            document.getElementById('keywordActionType').value = 'static';
            toggleActionFields();
        }
        openModal('keywordModal');
    }

    async function loadKeywords() {
        allKeywords = await fetchAPI('/keywords');
        const tbody = document.getElementById('keywords-table-body');
        tbody.innerHTML = allKeywords.map(k => `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 1rem; font-weight: 600;">
                    ${k.parent ? '<i class="fa-solid fa-turn-up fa-rotate-90" style="margin-right: 0.5rem; color: #cbd5e1;"></i>' : ''}
                    ${k.keyword}
                </td>
                <td style="padding: 1rem; color: var(--text-light); font-size: 0.85rem;">
                    ${k.parent ? `<span class="badge" style="background:var(--border-color); color:var(--text-color); border:1px solid var(--border-color); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">${k.parent.keyword}</span>` : '<span style="opacity: 0.3;">-</span>'}
                </td>
                <td style="padding: 1rem; white-space: pre-wrap;">${k.reply_content}</td>
                <td style="padding: 1rem;">
                    <span class="badge" style="background: ${k.is_active ? 'var(--success-color)' : 'var(--text-light)'}; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">
                        ${k.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td style="padding: 1rem; text-align: right; display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button class="btn" style="color: var(--moresc-blue); padding: 0.25rem;" onclick='openKeywordModal(${JSON.stringify(k).replace(/'/g, "&apos;")})'>
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn" style="color: var(--danger-color); padding: 0.25rem;" onclick="deleteKeyword(${k.id})">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    document.getElementById('keywordForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Custom serialization to handle nested action_data array notation correctly
        const data = { action_data: {} };
        for (const [key, value] of formData.entries()) {
            if (key.startsWith('action_data[')) {
                const nestedKey = key.match(/\[(.*?)\]/)[1];
                data.action_data[nestedKey] = value;
            } else {
                data[key] = value;
            }
        }

        data.is_active = formData.get('is_active') ? 1 : 0;
        if (!data.parent_id) delete data.parent_id;
        if (data.action_type === 'static') data.action_data = null;
        
        const id = data.id;
        delete data.id;

        try {
            if (id) {
                await fetchAPI(`/keywords/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify(data)
                });
            } else {
                await fetchAPI('/keywords', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
            }
            closeModal('keywordModal');
            loadKeywords();
        } catch (err) {
            alert('Failed to save keyword: ' + err.message);
        }
    });

    async function deleteKeyword(id) {
        if (!confirm('Are you sure you want to delete this keyword?')) return;
        await fetchAPI(`/keywords/${id}`, { method: 'DELETE' });
        loadKeywords();
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadKeywords();
    });
</script>
@endpush
