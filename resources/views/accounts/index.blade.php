@extends('layouts.app')

@section('title', 'Accounts Master List (MORESCO System)')

@push('header_actions')
    <div style="font-size: 0.9em; color: var(--text-light); display: flex; align-items: center; gap: 0.5rem; background: rgba(59, 130, 246, 0.1); padding: 0.5rem 1rem; border-radius: 20px;">
        <i class="fa-solid fa-cloud" style="color: var(--primary-color);"></i>
        Live Data
    </div>
@endpush

@section('content')

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="font-size: 1.25rem; font-weight: 600; color: var(--text-color); margin: 0;">External Accounts</h2>
        <div style="display: flex; gap: 1rem;">
            <div style="position: relative;">
                <input type="text" id="accountsSearch" placeholder="Search account or name..." class="form-control" style="padding-left: 2.5rem; width: 300px;">
                <i class="fa-solid fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
            </div>
        </div>
    </div>

    <!-- Accounts Table (MORESCO SQL Server) -->
    <div class="table-container" style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
        <table class="table-dense" id="accountsTable" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid var(--border-color); background: var(--item-hover);">
                    <th style="padding: 0.75rem 1rem;">Member ID</th>
                    <th style="padding: 0.75rem 1rem;">Member Name</th>
                    <th style="padding: 0.75rem 1rem;">Status</th>
                    <th style="padding: 0.75rem 1rem;">Service Area</th>
                    <th style="padding: 0.75rem 1rem; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody id="accountsTableBody">
                <!-- Data loaded via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
        <div style="color: var(--text-light); font-size: 0.875rem;" id="accountsPaginationInfo">
            Showing <span id="accountsStartIdx" style="font-weight: 600; color: var(--text-color);">0</span> to <span id="accountsEndIdx" style="font-weight: 600; color: var(--text-color);">0</span> of <span id="accountsTotalCount" style="font-weight: 600; color: var(--text-color);">0</span> entries
        </div>
        <div style="display: flex; gap: 0.5rem;" id="accountsPaginationControls">
            <button class="btn btn-secondary" onclick="changeAccountPage('prev')" id="accountsPrevBtn" disabled style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                <i class="fa-solid fa-chevron-left"></i> Previous
            </button>
            <button class="btn btn-secondary" onclick="changeAccountPage('next')" id="accountsNextBtn" disabled style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                Next <i class="fa-solid fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Account Details Modal -->
<div class="modal-overlay" id="accountDetailsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1001; padding: 1rem; flex-direction: column;">
    <div class="modal" style="width: 700px; max-width: 95%; max-height: 90vh; display: flex; flex-direction: column; background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);">
        <div class="modal-header" style="background: var(--item-hover); padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 0.75rem; color: var(--text-color); font-size: 1.25rem;">
                <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary-color);"></i> 
                Account Details
            </h3>
            <button class="modal-close" onclick="closeAccountDetailsModal()" style="background: none; border: none; color: var(--text-color); font-size: 1.5rem; cursor: pointer; opacity: 0.6; transition: opacity 0.2s; line-height: 1;">&times;</button>
        </div>
        <div class="modal-body" style="background: transparent; padding: 1.5rem; position: relative; color: var(--text-color); overflow-y: auto;">
            
            <div id="accountLoading" style="position: absolute; inset: 0; background: var(--surface-color); opacity: 0.9; z-index: 10; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; border-radius: 8px;">
                <div class="spinner" style="width: 40px; height: 40px; border: 4px solid var(--border-color); border-top-color: var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite;"></div>
                <div style="font-weight: 600; color: var(--primary-color);">Fetching Live External Data...</div>
            </div>

            <div id="accountContent" style="display: none; height: 100%;">
                <!-- Identity -->
                <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem;">
                    <div style="width: 50px; height: 50px; flex-shrink: 0; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; font-weight: bold;" id="mcInitials">
                        AA
                    </div>
                    <div>
                        <div id="mcName" style="font-weight: 600; font-size: 1.1rem; color: var(--text-color);">John Doe</div>
                        <div style="font-size: 0.85rem; color: var(--text-light);">Member ID: <strong id="mcMemberId" style="color: var(--text-color);">0000</strong></div>
                    </div>
                </div>

                <div id="memberAccountsList" style="max-height: 50vh; overflow-y: auto; padding-right: 0.5rem; display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Accounts injected here -->
                </div>

            </div>
        </div>
        <div class="modal-footer" style="padding: 1.25rem; border-top: 1px solid var(--border-color); background: var(--item-hover); border-radius: 0 0 12px 12px; display: flex; justify-content: center;">
            <button class="btn btn-primary" onclick="window.location.href='/simulator'" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 2rem; font-weight: 600; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);">
                <i class="fa-solid fa-comment-sms"></i> Text Simulator
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>

<script>
    let accountCurrentPage = 1;
    let accountPerPage = 100;
    let accountTotalCount = 0;
    let accountDebounceTimer = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadAccounts();

        document.getElementById('accountsSearch').addEventListener('input', (e) => {
            clearTimeout(accountDebounceTimer);
            accountDebounceTimer = setTimeout(() => {
                accountCurrentPage = 1;
                loadAccounts();
            }, 400);
        });
    });

    async function loadAccounts() {
        const tbody = document.getElementById('accountsTableBody');
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-light);"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading external data...</td></tr>`;
        
        const searchQuery = document.getElementById('accountsSearch').value;
        const offset = (accountCurrentPage - 1) * accountPerPage;
        
        try {
            const url = `{{ route('accounts.data') }}?search=${encodeURIComponent(searchQuery)}&per_page=${accountPerPage}&offset=${offset}`;
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                const text = await response.text();
                console.error("HTTP Error " + response.status + ":", text);
                alert("HTTP " + response.status + ": " + text.substring(0, 500));
                throw new Error('Failed to fetch external accounts');
            }
            
            const reqData = await response.json();
            const rows = reqData.data;
            accountTotalCount = reqData.total;
            
            updateAccountPagination();
            renderAccountsTable(rows);
            
        } catch (error) {
            console.error('Error:', error);
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--danger-color); padding: 2rem;">Error loading data from MORESCO SQL Server</td></tr>`;
        }
    }

    function renderAccountsTable(rows) {
        const tbody = document.getElementById('accountsTableBody');
        if (rows.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-light); padding: 2rem;">No accounts found</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(acc => `
            <tr style="border-bottom: 1px solid var(--border-color);">
                <td style="padding: 0.75rem 1rem; font-family: monospace; font-size: 0.9rem; color: var(--text-light);">#${escapeHtml(acc.member_id || '-')}</td>
                <td style="padding: 0.75rem 1rem; font-weight: 600; color: var(--moresco-blue);">${escapeHtml(acc.MemberName || '-')}</td>
                <td style="padding: 0.75rem 1rem;">
                    <span class="badge ${getStatusBadge(acc.membershipstatus)}">
                        ${escapeHtml(acc.membershipstatus || 'UNKNOWN')}
                    </span>
                </td>
                <td style="padding: 0.75rem 1rem; color: var(--text-light); font-size: 0.85rem;">${escapeHtml(acc.service_area || '-')}</td>
                <td style="padding: 0.75rem 1rem; text-align: right;">
                    <button class="btn btn-icon" title="Inspect Accounts" onclick="inspectAccount('${escapeHtml(acc.member_id)}')" style="color: var(--moresco-blue);">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async function inspectAccount(memberId) {
        document.getElementById('accountDetailsModal').style.display = 'flex';
        document.getElementById('accountContent').style.display = 'none';
        document.getElementById('accountLoading').style.display = 'flex';

        try {
            const response = await fetch(`/accounts/${encodeURIComponent(memberId)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) throw new Error('API Error');
            const data = await response.json();

            // Populate Member Identity
            const mem = data.member;
            if (mem && mem.name) {
                document.getElementById('mcName').textContent = mem.name;
                document.getElementById('mcInitials').textContent = mem.name.substring(0, 2).toUpperCase();
            } else {
                document.getElementById('mcName').textContent = 'Unknown Consumer';
                document.getElementById('mcInitials').textContent = '??';
            }
            document.getElementById('mcMemberId').textContent = memberId;

            // Render all accounts
            const listEl = document.getElementById('memberAccountsList');
            let accountsHtml = '';

            if (!data.accounts || data.accounts.length === 0) {
                accountsHtml = '<div style="color: var(--text-light); text-align: center; padding: 2rem;">No accounts found for this member.</div>';
            } else {
                data.accounts.forEach((acc) => {
                    const bill = acc.billing || {};
                    const out = acc.outage || {};
                    
                    const status = (bill.account_status || 'UNKNOWN').toUpperCase();
                    let badgeStyles = 'background: rgba(100, 116, 139, 0.1); color: #64748b; border: 1px solid rgba(100, 116, 139, 0.2);';
                    if (status.includes('ACTIVE')) {
                        badgeStyles = 'background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);'; // Emerald/Success
                    } else if (status.includes('DISCONNECT')) {
                        badgeStyles = 'background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);'; // Red/Danger
                    }

                    let outageHtml = '';
                    if (out && out.work_status && out.work_status !== 'N/A') {
                        const outLocation = out.location ? escapeHtml(out.location) : 'Unknown Location';
                        const outRemarks = out.remarks ? escapeHtml(out.remarks) : '';
                        
                        outageHtml = `
                            <div style="margin-top: 1rem; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 8px; padding: 1rem;">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <i class="fa-solid fa-triangle-exclamation" style="color: #ef4444; font-size: 1.25rem; margin-top: 0.1rem;"></i>
                                    <div>
                                        <div style="font-weight: 600; color: #ef4444; margin-bottom: 0.25rem;">Active Outage Detected</div>
                                        <div style="font-size: 0.85rem; color: var(--text-color); margin-bottom: 0.25rem;">
                                            <strong>${escapeHtml(out.work_name)}</strong> &mdash; Status: <span style="color: #ef4444; font-weight: 600;">${escapeHtml(out.work_status)}</span>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--text-light);">
                                            <i class="fa-solid fa-location-dot" style="margin-right: 0.2rem;"></i> <strong>Location:</strong> <span style="color: #ef4444;">${outLocation}</span>
                                        </div>
                                        ${outRemarks ? `<div style="font-size: 0.8rem; color: var(--text-light); margin-top: 0.25rem; opacity: 0.8;"><em>Remarks: ${outRemarks}</em></div>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    accountsHtml += `
                    <div style="border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem; background: var(--background-color);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--text-color);">
                                <i class="fa-solid fa-plug" style="color: var(--primary-color);"></i>
                                Account: <span style="font-family: monospace;">${escapeHtml(acc.account_no)}</span>
                            </h4>
                            <span style="padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; ${badgeStyles}">
                                ${status}
                            </span>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <!-- Billing Widget -->
                            <div style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);">
                                <div style="background: var(--item-hover); padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--border-color); font-weight: 600; font-size: 0.8rem; display: flex; justify-content: space-between; color: var(--text-color);">
                                    <span><i class="fa-solid fa-file-invoice" style="margin-right: 0.3rem;"></i> Latest Bill</span>
                                    <span style="color: var(--primary-color);">${escapeHtml(bill.billing_period || '-')}</span>
                                </div>
                                <div style="padding: 0.75rem;">
                                    <div style="font-size: 1.25rem; font-weight: bold; color: var(--text-color); margin-bottom: 0.25rem;">${escapeHtml(bill.bill_amount || '0.00')}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-light);"><i class="fa-solid fa-bolt" style="margin-right: 0.2rem; color: var(--primary-color);"></i> Reading Date: <span style="font-weight: 500; color: var(--text-color);">${escapeHtml(bill.reading_date || '-')}</span></div>
                                    <div style="font-size: 0.75rem; color: var(--text-light); margin-bottom: 0.2rem;"><i class="fa-regular fa-calendar" style="margin-right: 0.2rem;"></i> Due: <span style="color: var(--danger-color); font-weight: 500;">${escapeHtml(bill.due_date || '-')}</span></div>
                                </div>
                                <div style="padding: 0.4rem 0.75rem; border-top: 1px dashed var(--border-color); background: var(--item-hover); font-size: 0.75rem; display: flex; justify-content: space-between; color: var(--text-light);">
                                    <span>Running Balance:</span>
                                    <span style="font-weight: 600; color: var(--text-color);">${escapeHtml(bill.balance || '0.00')}</span>
                                </div>
                            </div>

                            <!-- Payment Widget -->
                            <div style="background: var(--surface-color); border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);">
                                <div style="background: var(--item-hover); padding: 0.5rem 0.75rem; border-bottom: 1px solid var(--border-color); font-weight: 600; font-size: 0.8rem; color: var(--text-color);">
                                    <i class="fa-solid fa-receipt" style="margin-right: 0.3rem;"></i> Last Payment
                                </div>
                                <div style="padding: 0.75rem;">
                                    <div style="font-size: 1.25rem; font-weight: bold; color: #10b981; margin-bottom: 0.25rem;">${escapeHtml(bill.last_payment_amount || '0.00')}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-light);"><i class="fa-regular fa-calendar-check" style="margin-right: 0.2rem;"></i> ${escapeHtml(bill.last_payment_date || '-')}</div>
                                </div>
                                <div style="padding: 0.4rem 0.75rem; border-top: 1px dashed var(--border-color); background: var(--item-hover); font-size: 0.75rem; display: flex; justify-content: space-between; color: var(--text-light);">
                                    <span>OR Number:</span>
                                    <span style="font-weight: 600; font-family: monospace; color: var(--text-color);">${escapeHtml(bill.or_number || '-')}</span>
                                </div>
                            </div>
                        </div>
                        ${outageHtml}
                    </div>
                    `;
                });
            }

            listEl.innerHTML = accountsHtml;

            document.getElementById('accountLoading').style.display = 'none';
            document.getElementById('accountContent').style.display = 'flex';
            document.getElementById('accountContent').style.flexDirection = 'column';

        } catch (error) {
            console.error(error);
            alert('Failed to load member/account details from MORESCO SQL Server.');
            document.getElementById('accountDetailsModal').style.display = 'none';
        }
    }

    function closeAccountDetailsModal() {
        document.getElementById('accountDetailsModal').style.display = 'none';
    }

    function getStatusBadge(status) {
        if (!status) return 'badge-secondary';
        const s = status.toLowerCase();
        if (s.includes('member')) return 'badge-success';
        if (s.includes('applicant')) return 'badge-warning';
        if (s.includes('disconnect')) return 'badge-danger';
        return 'badge-secondary';
    }

    function changeAccountPage(direction) {
        if (direction === 'prev' && accountCurrentPage > 1) {
            accountCurrentPage--;
            loadAccounts();
        } else if (direction === 'next' && (accountCurrentPage * accountPerPage) < accountTotalCount) {
            accountCurrentPage++;
            loadAccounts();
        }
    }

    function updateAccountPagination() {
        const start = accountTotalCount === 0 ? 0 : ((accountCurrentPage - 1) * accountPerPage) + 1;
        const end = Math.min(accountCurrentPage * accountPerPage, accountTotalCount);
        
        document.getElementById('accountsStartIdx').textContent = start;
        document.getElementById('accountsEndIdx').textContent = end;
        document.getElementById('accountsTotalCount').textContent = accountTotalCount;
        
        document.getElementById('accountsPrevBtn').disabled = accountCurrentPage === 1;
        document.getElementById('accountsNextBtn').disabled = end >= accountTotalCount;
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
</script>
@endsection
