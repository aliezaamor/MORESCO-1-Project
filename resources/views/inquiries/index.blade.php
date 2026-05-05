@extends('layouts.app')

@section('title', 'Consumer Inquiries Log')

@section('content')
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; background: transparent; border-bottom: 2px solid var(--border-color); padding: 1.5rem;">
        <div>
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--text-color);">Inquiries from MORESCO Database</h2>
            <p style="margin: 5px 0 0; font-size: 0.9rem; color: var(--text-light);">
                Tracking <strong>{{ number_format($total) }}</strong> records from external system
            </p>
        </div>
        <div class="actions" style="display: flex; gap: 1rem; align-items: center;">
            <div style="position: relative;">
                <input type="text" id="inquirySearch" placeholder="Search account or name..." class="form-control" style="padding-left: 2.5rem; width: 300px; border-radius: 20px;" value="{{ $search }}">
                <i class="fa-solid fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
            </div>
            <button class="btn btn-secondary" onclick="window.location.href='{{ route('inquiries.index') }}'" style="display: flex; align-items: center; gap: 0.5rem; border-radius: 8px;">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh
            </button>
        </div>
    </div>

    <div style="background: var(--surface-color); border-bottom: 1px solid var(--border-color); padding: 0 1.5rem; display: flex; gap: 2rem;">
        <a href="{{ route('inquiries.index', ['status' => 'new', 'search' => $search]) }}" style="padding: 1rem 0; color: {{ $status === 'new' ? 'var(--primary-color)' : 'var(--text-light)' }}; font-weight: 600; text-decoration: none; border-bottom: 3px solid {{ $status === 'new' ? 'var(--primary-color)' : 'transparent' }}; transition: all 0.2s;">
            <i class="fa-solid fa-inbox" style="margin-right: 0.5rem;"></i>New Inquiries
        </a>
        <a href="{{ route('inquiries.index', ['status' => 'processed', 'search' => $search]) }}" style="padding: 1rem 0; color: {{ $status === 'processed' ? 'var(--primary-color)' : 'var(--text-light)' }}; font-weight: 600; text-decoration: none; border-bottom: 3px solid {{ $status === 'processed' ? 'var(--primary-color)' : 'transparent' }}; transition: all 0.2s;">
            <i class="fa-solid fa-check-double" style="margin-right: 0.5rem;"></i>Processed
        </a>
        <a href="{{ route('inquiries.index', ['status' => 'all', 'search' => $search]) }}" style="padding: 1rem 0; color: {{ $status === 'all' ? 'var(--primary-color)' : 'var(--text-light)' }}; font-weight: 600; text-decoration: none; border-bottom: 3px solid {{ $status === 'all' ? 'var(--primary-color)' : 'transparent' }}; transition: all 0.2s;">
            <i class="fa-solid fa-list-ul" style="margin-right: 0.5rem;"></i>All Records
        </a>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-container" style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: separate; border-spacing: 0;">
                <thead style="background: var(--item-hover);">
                    <tr>
                        <th style="padding: 1rem; width: 40px; text-align: center; border-bottom: 2px solid var(--border-color);"></th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-light); border-bottom: 2px solid var(--border-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Date</th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-light); border-bottom: 1px solid var(--border-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Account</th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-light); border-bottom: 1px solid var(--border-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Consumer Name</th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-light); border-bottom: 1px solid var(--border-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Type</th>
                        <th style="padding: 1rem; text-align: center; color: var(--text-light); border-bottom: 1px solid var(--border-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 1rem; text-align: right; color: var(--text-light); border-bottom: 1px solid var(--border-color); font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inquiries as $inq)
                        @php
                            // Smart name logic: hide First Name if identical to Last Name
                            $fullName = $inq['last_name'];
                            if (strtoupper(trim($inq['first_name'])) !== strtoupper(trim($inq['last_name']))) {
                                $fullName .= ', ' . $inq['first_name'];
                            }
                        @endphp
                        <tr style="transition: background 0.2s;" 
                            onmouseover="this.style.background='var(--item-hover)'" 
                            onmouseout="this.style.background='transparent'">
                            <td style="padding: 0.75rem 1rem; text-align: center; border-bottom: 1px solid var(--border-color);">
                                @if($inq['status_id'] == 1)
                                    <form action="{{ route('inquiries.process', $inq['id']) }}" method="POST" style="margin:0;" id="form-process-{{ $inq['id'] }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="checkbox" title="Mark as Processed" onclick="handleCheckboxProcess(event, this, {{ $inq['id'] }})" style="cursor: pointer; width: 1.1rem; height: 1.1rem; accent-color: #10b981;">
                                    </form>
                                @else
                                    <i class="fa-solid fa-check" style="color: #10b981; font-size: 1.1rem;" title="Processed"></i>
                                @endif
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; white-space: nowrap; color: var(--text-light);">
                                {{ $inq['date'] }}
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color);">
                                <span style="font-family: monospace; font-size: 0.9rem; color: var(--text-light);">#{{ $inq['account_no'] }}</span>
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color);">
                                <div style="font-weight: 600; color: var(--primary-color); font-size: 0.95rem; text-transform: uppercase;">{{ $fullName }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-light);">{{ $inq['phone'] }}</div>
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color);">
                                <span style="font-size: 0.85rem; color: var(--text-light);">{{ $inq['type'] }}</span>
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); text-align: center;">
                                @if($inq['status_id'] == 1)
                                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.6rem; background: #fff3cd; color: #856404; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">
                                        NEW
                                    </span>
                                @else
                                    <span style="padding: 0.2rem 0.6rem; background: #d4edda; color: #155724; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">
                                        PROCESSED
                                    </span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; align-items: center;">
                                    @if($inq['status_id'] != 1)
                                        <form action="{{ route('inquiries.reopen', $inq['id']) }}" method="POST" style="margin:0;" id="form-reopen-{{ $inq['id'] }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" class="btn btn-icon btn-sm" onclick="confirmAction('Undo Status?', 'This will move the inquiry back to New. Are you sure?', '#f59e0b', 'Yes, undo it', 'form-reopen-{{ $inq['id'] }}')" title="Undo / Mark as New" style="color: #f59e0b;">
                                                <i class="fa-solid fa-arrow-rotate-left"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <button class="btn btn-icon btn-sm" onclick='inspectInquiry(@json($inq))' title="View Full Message" style="color: var(--primary-color);">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 4rem; color: var(--text-light);">
                                <div style="opacity: 0.2; font-size: 3rem; margin-bottom: 1rem;"><i class="fa-solid fa-folder-open"></i></div>
                                No inquiries found in the database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lastPage > 1)
            <div style="padding: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 1rem; border-top: 2px solid var(--border-color);">
                @php
                    $prevUrl = "?page=" . ($page - 1);
                    $nextUrl = "?page=" . ($page + 1);
                    if(!empty($search)) {
                        $prevUrl .= "&search=" . urlencode($search);
                        $nextUrl .= "&search=" . urlencode($search);
                    }
                    $prevUrl .= "&status=" . urlencode($status);
                    $nextUrl .= "&status=" . urlencode($status);
                @endphp
                <a href="{{ $prevUrl }}" class="btn btn-secondary btn-sm {{ $page <= 1 ? 'disabled' : '' }}" style="border-radius: 8px;">
                    <i class="fa-solid fa-chevron-left" style="margin-right: 0.5rem;"></i> Previous
                </a>
                
                <div style="font-size: 0.9rem; color: var(--text-light);">
                    Page <strong style="color: var(--text-color);">{{ $page }}</strong> of <strong>{{ $lastPage }}</strong>
                </div>

                <a href="{{ $nextUrl }}" class="btn btn-secondary btn-sm {{ $page >= $lastPage ? 'disabled' : '' }}" style="border-radius: 8px;">
                    Next <i class="fa-solid fa-chevron-right" style="margin-left: 0.5rem;"></i>
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Inquiry Details Modal -->
<div class="modal-overlay" id="inquiryModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; z-index: 2000; backdrop-filter: blur(4px);">
    <div class="modal" style="width: 600px; max-width: 95%; background: var(--surface-color); border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); border: 1px solid var(--border-color); display: flex; flex-direction: column; max-height: 90vh;">
        <div class="modal-header" style="padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--item-hover); flex-shrink: 0;">
            <h3 style="margin: 0; display: flex; align-items: center; gap: 0.75rem; color: var(--text-color); font-size: 1.25rem;">
                <i class="fa-solid fa-clipboard-list" style="color: var(--primary-color);"></i>
                Inquiry Details
            </h3>
            <button onclick="closeInquiryModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--text-light); cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 2rem; overflow-y: auto;">
            <div id="modalContent">
                <!-- Data injected here -->
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.5); opacity: 0.5; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* Dynamic Badges based on type */
    .inq-badge-online-payment { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .inq-badge-outage-report { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .inq-badge-billing-inquiry { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .inq-badge-general-inquiry { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .inq-badge-others { background: #e9ecef; color: #6c757d; }

    .disabled { pointer-events: none; opacity: 0.5; }

    /* Ensure SweetAlert pops up over the inquiry details modal */
    .swal2-container {
        z-index: 9999 !important;
    }
</style>

<script>
    document.getElementById('inquirySearch')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const val = e.target.value;
            window.location.href = `{{ route('inquiries.index') }}?search=${encodeURIComponent(val)}`;
        }
    });

    function inspectInquiry(inq) {
        const modal = document.getElementById('inquiryModal');
        const content = document.getElementById('modalContent');
        
        // Build clean identity string
        let name = inq.last_name;
        if (inq.first_name.toUpperCase() !== inq.last_name.toUpperCase()) {
            name += ', ' + inq.first_name;
        }

        content.innerHTML = `
            <div style="display: flex; gap: 1.5rem; align-items: flex-start; margin-bottom: 2rem;">
                <div style="width: 64px; height: 64px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; flex-shrink: 0;">
                    ${inq.last_name.charAt(0)}
                </div>
                <div>
                    <h4 style="margin: 0 0 0.25rem; font-size: 1.25rem; color: var(--text-color);">${name}</h4>
                    <div style="color: var(--text-light); font-size: 0.9rem; display: flex; align-items: center; gap: 1rem;">
                        <span><i class="fa-solid fa-id-card" style="margin-right: 0.4rem;"></i>${inq.account_no || 'No Account'}</span>
                        <span><i class="fa-solid fa-phone" style="margin-right: 0.4rem;"></i>${inq.phone}</span>
                    </div>
                </div>
            </div>

            <div style="background: var(--background-color); border: 1px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: var(--primary-color); text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 0.05em;">Message Body</label>
                <div style="font-size: 1.1rem; color: var(--text-color); line-height: 1.6; white-space: pre-wrap; max-height: 300px; overflow-y: auto; padding-right: 10px;">${inq.inquiry}</div>
            </div>

            ${inq.action_taken ? `
            <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #3b82f6; text-transform: uppercase; margin-bottom: 0.75rem; letter-spacing: 0.05em;">Reply Sent</label>
                <div style="font-size: 1.1rem; color: var(--text-color); line-height: 1.6; white-space: pre-wrap; max-height: 200px; overflow-y: auto; padding-right: 10px;">${inq.action_taken}</div>
            </div>
            ` : ''}

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-light); text-transform: uppercase; margin-bottom: 0.4rem;">Received At</label>
                    <div style="font-weight: 600; color: var(--text-color);">${inq.date}</div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.7rem; font-weight: 700; color: var(--text-light); text-transform: uppercase; margin-bottom: 0.4rem;">Service Address</label>
                    <div style="font-weight: 600; color: var(--text-color); font-size: 0.85rem;">${inq.address || 'N/A'}</div>
                </div>
            </div>

            ${inq.status_id == 1 ? `
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <form action="{{ url('/inquiries') }}/${inq.id}/process" method="POST" style="margin: 0;" id="modal-form-process-${inq.id}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="button" class="btn btn-primary" style="padding: 0.6rem 2rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem;" onclick="confirmAction('Mark as Processed?', 'Are you sure you want to mark this inquiry as processed?', '#10b981', 'Yes, process it', 'modal-form-process-${inq.id}')">
                        <i class="fa-solid fa-check"></i> Mark as Processed
                    </button>
                </form>
            </div>
            ` : `
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <form action="{{ url('/inquiries') }}/${inq.id}/reopen" method="POST" style="margin: 0;" id="modal-form-reopen-${inq.id}">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="PATCH">
                    <button type="button" class="btn" style="background: transparent; color: #f59e0b; border: 1px solid #f59e0b; padding: 0.6rem 2rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem;" onclick="confirmAction('Undo Status?', 'This will move the inquiry back to New. Are you sure?', '#f59e0b', 'Yes, undo it', 'modal-form-reopen-${inq.id}')">
                        <i class="fa-solid fa-arrow-rotate-left"></i> Undo / Mark as New
                    </button>
                </form>
            </div>
            `}

            <div id="historyContainer" style="margin-top: 2rem; border-top: 2px dashed var(--border-color); padding-top: 1.5rem;">
                <div style="text-align: center; color: var(--text-light); padding: 1rem;">
                    <i class="fa-solid fa-spinner fa-spin" style="margin-right: 0.5rem;"></i> Loading previous records...
                </div>
            </div>
        `;
        
        modal.style.display = 'flex';

        fetch(`{{ url('/inquiries/history') }}?account=${encodeURIComponent(inq.account_no || '')}&phone=${encodeURIComponent(inq.phone || '')}&exclude=${inq.id}`)
            .then(res => res.json())
            .then(history => {
                const hContainer = document.getElementById('historyContainer');
                if (!history || history.length === 0) {
                    hContainer.innerHTML = '';
                    hContainer.style.display = 'none';
                    return;
                }

                let historyHtml = `
                    <h5 style="margin: 0 0 1rem; color: var(--text-color); font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-clock-rotate-left" style="color: var(--primary-color);"></i>
                        Previous Records (${history.length})
                    </h5>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                `;

                history.forEach(record => {
                    const statusBadge = record.status_id == 1 
                        ? `<span style="padding: 0.2rem 0.5rem; background: #fff3cd; color: #856404; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">NEW</span>`
                        : `<span style="padding: 0.2rem 0.5rem; background: #d4edda; color: #155724; border-radius: 4px; font-size: 0.65rem; font-weight: 700;">PROCESSED</span>`;

                    historyHtml += `
                        <div style="background: var(--background-color); border: 1px solid var(--border-color); border-radius: 8px; padding: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-light);"><i class="fa-solid fa-calendar-days" style="margin-right: 0.4rem;"></i>${record.date} &nbsp; • &nbsp; ${record.type}</div>
                                <div>${statusBadge}</div>
                            </div>
                            <div style="font-size: 0.95rem; color: var(--text-color); line-height: 1.5; white-space: pre-wrap;">${record.inquiry}</div>
                            ${record.action_taken ? `
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--primary-color);">
                                    <span style="font-weight: 700; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; display: block; margin-bottom: 0.2rem;">Reply Sent</span>
                                    <div style="color: var(--text-color); white-space: pre-wrap;">${record.action_taken}</div>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });

                historyHtml += `</div>`;
                hContainer.innerHTML = historyHtml;
            })
            .catch(err => {
                console.error("Failed to load history", err);
                document.getElementById('historyContainer').innerHTML = '';
            });
    }

    function closeInquiryModal() {
        document.getElementById('inquiryModal').style.display = 'none';
    }

    // Close on background click
    window.onclick = function(event) {
        const modal = document.getElementById('inquiryModal');
        if (event.target == modal) {
            closeInquiryModal();
        }
    }

    function confirmAction(title, text, confirmColor, confirmText, formId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#64748b',
                confirmButtonText: confirmText
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        } else {
            if (confirm(text)) {
                document.getElementById(formId).submit();
            }
        }
    }

    function handleCheckboxProcess(event, checkbox, id) {
        event.preventDefault(); // Prevent immediate checking
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Mark as Processed?',
                text: "Are you sure you want to mark this inquiry as processed?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, process it'
            }).then((result) => {
                if (result.isConfirmed) {
                    checkbox.checked = true;
                    checkbox.disabled = true;
                    document.getElementById('form-process-' + id).submit();
                }
            });
        } else {
            if (confirm("Are you sure you want to mark this inquiry as processed?")) {
                checkbox.checked = true;
                checkbox.disabled = true;
                document.getElementById('form-process-' + id).submit();
            }
        }
    }
</script>
@endsection
