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
    <div class="card" style="width: 450px; max-width: 90%;">
        <h3 id="keywordModalTitle">Add New Keyword</h3>
        <form id="keywordForm" class="mt-4">
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
                <label class="form-label">Auto-Reply Content</label>
                <textarea name="reply_content" id="keywordContent" class="form-control" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="is_active" id="keywordActive" value="1" checked> Active
                </label>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                <button type="button" class="btn" onclick="closeModal('keywordModal')" style="background: #e2e8f0;">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
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
        } else {
            document.getElementById('keywordModalTitle').innerText = 'Add New Keyword';
            document.getElementById('keywordForm').reset();
            document.getElementById('keywordId').value = '';
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
                    ${k.parent ? `<span class="badge" style="background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0;">${k.parent.keyword}</span>` : '<span style="opacity: 0.3;">-</span>'}
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
        const data = Object.fromEntries(formData);
        data.is_active = formData.get('is_active') ? 1 : 0;
        if (!data.parent_id) delete data.parent_id;
        
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
