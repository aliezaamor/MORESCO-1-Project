@extends('layouts.app')

@section('title', 'Consumer Inquiries Log')

@section('content')
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; font-size: 1.25rem;">Inquiries from MORESCO Database</h2>
            <p style="margin: 5px 0 0; font-size: 0.875rem; color: var(--text-light);">
                Total Records: <strong>{{ number_format($total) }}</strong>
            </p>
        </div>
        <div class="actions">
            <!-- Optional refresh button -->
            <button class="btn btn-secondary" onclick="window.location.reload()">
                <i class="fa-solid fa-sync" style="margin-right: 0.5rem;"></i> Refresh
            </button>
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Account</th>
                        <th>Consumer Name</th>
                        <th>Address</th>
                        <th>Inquiry Details</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inquiries as $inq)
                        <tr>
                            <td style="white-space: nowrap; font-size: 0.85rem;">
                                {{ $inq['date'] }}
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary-color);">
                                    {{ $inq['account_no'] }}
                                </span>
                            </td>
                            <td style="font-weight: 500;">
                                {{ $inq['last_name'] }}, {{ $inq['first_name'] }}
                                <div style="font-size: 0.75rem; color: var(--text-light); font-weight: 400;">
                                    {{ $inq['phone'] }}
                                </div>
                            </td>
                            <td style="font-size: 0.85rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $inq['address'] }}">
                                {{ $inq['address'] }}
                            </td>
                            <td style="font-size: 0.85rem; max-width: 300px;">
                                {{ $inq['inquiry'] }}
                            </td>
                            <td>
                                <span class="badge" style="background: #e9ecef; color: #495057;">
                                    {{ $inq['type'] }}
                                </span>
                            </td>
                            <td>
                                @if($inq['status_id'] == 1)
                                    <span class="status-pill warning" style="background: #fff3cd; color: #856404;">New</span>
                                @else
                                    <span class="status-pill success">Processed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-light);">
                                <i class="fa-solid fa-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.3;"></i>
                                No inquiries found in the database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lastPage > 1)
            <div class="pagination" style="padding: 1.5rem; display: flex; justify-content: center; gap: 0.5rem; border-top: 1px solid var(--border-color);">
                @if($page > 1)
                    <a href="?page={{ $page - 1 }}" class="btn btn-secondary btn-sm">Previous</a>
                @endif
                
                <span style="display: flex; align-items: center; padding: 0 1rem; font-size: 0.9rem;">
                    Page {{ $page }} of {{ $lastPage }}
                </span>

                @if($page < $lastPage)
                    <a href="?page={{ $page + 1 }}" class="btn btn-secondary btn-sm">Next</a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
