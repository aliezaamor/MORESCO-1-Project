@extends('layouts.app')

@section('title', 'User Activity Log')

@section('content')
    <div class="card" style="padding: 1.5rem;">
        <div style="overflow-x: auto; overflow-y: auto; max-height: 65vh; border: 1px solid var(--border-color); border-radius: 6px;">
            <table style="width:100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead style="position: sticky; top: 0; background-color: var(--card-bg, #ffffff); z-index: 10;">
                    <tr style="text-align: left; border-bottom: 2px solid var(--border-color); color: var(--text-color);">
                        <th style="padding: 0.75rem 0.5rem; font-weight: 600;">#</th>
                        <th style="padding: 0.75rem 0.5rem; font-weight: 600;">User</th>
                        <th style="padding: 0.75rem 0.5rem; font-weight: 600;">Activity</th>
                        <th style="padding: 0.75rem 0.5rem; font-weight: 600;">IP Address</th>
                        <th style="padding: 0.75rem 0.5rem; font-weight: 600;">Date/Time</th>
                        <th style="padding: 0.75rem 0.5rem; font-weight: 600;">User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $act)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 1rem 0.5rem; vertical-align: middle;">{{ $act->id }}</td>
                            <td style="padding: 1rem 0.5rem; vertical-align: middle;">
                                @if($act->user)
                                    <div style="font-weight: 500;">{{ $act->user->name }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-light);">{{ $act->user->username }}</div>
                                @else
                                    <span style="color: var(--text-light); font-style: italic;">Unknown User</span>
                                @endif
                            </td>
                            <td style="padding: 1rem 0.5rem; vertical-align: middle;">
                                <span style="background-color: var(--background-color); padding: 0.25rem 0.5rem; border-radius: 4px; border: 1px solid var(--border-color); font-size: 0.85rem;">
                                    {{ $act->activity }}
                                </span>
                            </td>
                            <td style="padding: 1rem 0.5rem; vertical-align: middle; font-family: monospace; font-size: 0.85rem; color: var(--text-light);">{{ $act->ip_address }}</td>
                            <td style="padding: 1rem 0.5rem; vertical-align: middle;">
                                <div>{{ \Carbon\Carbon::parse($act->created_at)->format('M d, Y') }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-light);">{{ \Carbon\Carbon::parse($act->created_at)->format('h:i A') }}</div>
                            </td>
                            <td style="padding: 1rem 0.5rem; vertical-align: middle; font-size: 0.75rem; color: var(--text-light); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $act->user_agent }}">
                                {{ Str::limit($act->user_agent, 40) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-light);">
                                <i class="fa-solid fa-clipboard-list" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                                <p>No activity records found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
            {{ $activities->links() }}
        </div>
    </div>
@endsection
