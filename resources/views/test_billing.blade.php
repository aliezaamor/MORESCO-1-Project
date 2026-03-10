@extends('layouts.app')

@section('title', 'SMS Billing Data Tester')

@section('content')
<div class="test-billing-container" style="background-color: #f8f9fa; font-family: 'Inter', sans-serif;">
    <style>
        .test-billing-container .card { border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .test-billing-container .pre-wrap { white-space: pre-wrap; background: #212529; color: #00ff00; padding: 15px; border-radius: 8px; font-size: 14px;}
        .test-billing-container .json-block { background: #f1f3f5; padding: 10px; border-radius: 6px; font-family: monospace; font-size: 13px; max-height: 400px; overflow-y: auto;}
    </style>
    
    <div class="row justify-content-center py-4">
        <div class="col-md-10">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <form method="GET" action="{{ url('/test-billing') }}">
                        <div class="input-group mb-3" style="display: flex;">
                            <input type="text" name="account" class="form-control form-control-lg" style="flex: 1; padding: 10px; font-size: 1.1rem; border: 1px solid #ced4da; border-radius: 6px 0 0 6px;" placeholder="Enter Member ID (e.g. 50560)" value="{{ request('account') }}" required>
                            <button class="btn btn-primary px-4" type="submit" style="border-radius: 0 6px 6px 0; background-color: var(--primary-color); border: none; color: white; padding: 0 20px; font-weight: 600;">Fetch Data</button>
                        </div>
                    </form>
                </div>
            </div>

                @if(request('account'))
                    @if(isset($error))
                        <div class="alert alert-danger">{{ $error }}</div>
                    @else
                        <!-- SMS Preview -->
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0">Parsed Data (What the user receives)</h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-sm-4 fw-bold">Member Name</div>
                                    <div class="col-sm-8">{{ $member['name'] ?? 'Not Found in vw_members_list' }}</div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-sm-4 fw-bold">Mapped Account No(s)</div>
                                    <div class="col-sm-8">{{ implode(', ', $mapped) }}</div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-group list-group-flush border rounded mb-3">
                                            <li class="list-group-item bg-light fw-bold">Billing Details</li>
                                            <li class="list-group-item d-flex justify-content-between"><span>Bill Amount</span> <span class="fw-bold">{{ $billing['bill_amount'] }}</span></li>
                                            <li class="list-group-item d-flex justify-content-between"><span>Billing Period</span> <span class="fw-bold">{{ $billing['billing_period'] }}</span></li>
                                            <li class="list-group-item d-flex justify-content-between"><span>Due Date</span> <span class="fw-bold">{{ $billing['due_date'] }}</span></li>
                                            <li class="list-group-item d-flex justify-content-between"><span>Running Balance</span> <span class="fw-bold">{{ $billing['balance'] }}</span></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-group list-group-flush border rounded mb-3">
                                            <li class="list-group-item bg-light fw-bold">Payment Details</li>
                                            <li class="list-group-item d-flex justify-content-between"><span>Last Payment</span> <span class="fw-bold">{{ $billing['last_payment_amount'] }}</span></li>
                                            <li class="list-group-item d-flex justify-content-between"><span>Payment Date</span> <span class="fw-bold">{{ $billing['last_payment_date'] }}</span></li>
                                            <li class="list-group-item d-flex justify-content-between"><span>OR Number</span> <span class="fw-bold">{{ $billing['or_number'] }}</span></li>
                                            <li class="list-group-item d-flex justify-content-between"><span>Account Status</span> <span class="fw-bold">{{ $billing['account_status'] }}</span></li>
                                        </ul>
                                    </div>
                                    <div class="col-md-12">
                                        <ul class="list-group list-group-flush border rounded mb-3">
                                            <li class="list-group-item bg-light fw-bold">Active Outage Details</li>
                                            @if($outage)
                                                <li class="list-group-item d-flex justify-content-between"><span>Outage Type</span> <span class="fw-bold">{{ strtoupper($outage['type']) }}</span></li>
                                                <li class="list-group-item d-flex justify-content-between"><span>Work Name</span> <span class="fw-bold">{{ $outage['work_name'] }}</span></li>
                                                <li class="list-group-item d-flex justify-content-between"><span>Status</span> <span class="fw-bold">{{ $outage['work_status'] }}</span></li>
                                                <li class="list-group-item d-flex justify-content-between"><span>Interruption Type</span> <span class="fw-bold">{{ $outage['power_interruption'] }}</span></li>
                                                <li class="list-group-item d-flex justify-content-between"><span>Date Logged</span> <span class="fw-bold">{{ $outage['date_created'] }}</span></li>
                                            @else
                                                <li class="list-group-item text-center text-muted">No active individual or area-wide outages.</li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Raw Data -->
                        <div class="card">
                            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Raw Database Readout</h6>
                                <small>For debugging purposes</small>
                            </div>
                            <div class="card-body">
                                <h6>vw_members_list</h6>
                                <div class="json-block mb-4">{{ json_encode($member_raw, JSON_PRETTY_PRINT) }}</div>
                                
                                <h6>vw_AccountTransactions (Latest 5 matching padded IDs)</h6>
                                <div class="json-block mb-4">{{ json_encode($ledger, JSON_PRETTY_PRINT) }}</div>
                                
                                <h6>VW_ACCOUNTS_METER_READING (Latest 5 matching padded IDs)</h6>
                                <div class="json-block">{{ json_encode($metering, JSON_PRETTY_PRINT) }}</div>
                            </div>
                        </div>
                    @endif
                @endif
        </div>
    </div>
</div>
@endsection
