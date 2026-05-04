@extends('layouts.app')

@section('title', 'User Manual')

@section('content')
<div class="card" style="max-width: 900px; margin: 0 auto; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
    <div class="card-header" style="background: transparent; border-bottom: 2px solid var(--border-color); padding: 2rem;">
        <h2 style="margin: 0; font-size: 1.8rem; font-weight: 700; color: var(--text-color);">
            <i class="fa-solid fa-book-open" style="color: var(--primary-color); margin-right: 0.5rem;"></i>
            MORESCO-1 SMS Management System
        </h2>
        <p style="margin: 5px 0 0; color: var(--text-light); font-size: 1rem;">Official User Guide</p>
    </div>
    
    <div class="card-body" style="padding: 2.5rem; line-height: 1.7; color: var(--text-color); font-size: 1.05rem;">
        <p>Welcome to the MORESCO-1 SMS Management System! This guide is designed to help you navigate the system and efficiently manage consumer text messages, dispatch advisories, and monitor automated replies.</p>
        <p>Please use the sidebar navigation on the left to access the different modules in the exact sequence outlined below.</p>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 2rem 0;">

        <h3 style="color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem;">
            <i class="fa-solid fa-gauge"></i> 1. Dashboard
        </h3>
        <p>When you log in, you are greeted by the <strong>Dashboard</strong>. This serves as your command center, providing a quick summary of system activity, recent incoming messages, and overall SMS traffic.</p>
        <ul style="padding-left: 1.5rem;">
            <li><strong>Action:</strong> Use this page to get a high-level overview of the day's operations.</li>
        </ul>

        <h3 style="color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem;">
            <i class="fa-solid fa-address-book"></i> 2. Contacts & Groups
        </h3>
        <p>This module allows you to manage the people and service areas you communicate with.</p>
        <ul style="padding-left: 1.5rem;">
            <li><strong>Contacts:</strong> Search for specific consumers based on their Name, Account Number, or Phone Number as synced from the MORESCO database.</li>
            <li><strong>Groups:</strong> View the different service areas, municipalities, and barangays for targeted mass texting.</li>
        </ul>

        <h3 style="color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem;">
            <i class="fa-solid fa-clipboard-question"></i> 3. Consumer Inquiries
        </h3>
        <p>This is the log where all incoming texts starting with <code>REPORT</code> or <code>CONCERN</code> are recorded for your action.</p>
        <ul style="padding-left: 1.5rem;">
            <li><strong>Viewing New Inquiries:</strong> By default, you will be on the <strong>New Inquiries</strong> tab. These are reports that just arrived.</li>
            <li><strong>Inspecting Details:</strong> Click the <i class="fa-solid fa-magnifying-glass"></i> icon to open the details. You will see the clean message body and the exact automated text the system replied with.</li>
            <li><strong>Marking as Processed:</strong> Once an inquiry has been endorsed to the relevant department, click the blue <strong>"Mark as Processed"</strong> button. A confirmation prompt will appear to prevent accidental clicks.</li>
            <li><strong>Undo / Reopen:</strong> If you accidentally marked an inquiry as processed, go to the "Processed" tab and click the orange <strong>Undo</strong> <i class="fa-solid fa-arrow-rotate-left" style="color: #f59e0b;"></i> button to move it back.</li>
        </ul>

        <h3 style="color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem;">
            <i class="fa-solid fa-envelope"></i> 4. Messages
        </h3>
        <p>This dropdown menu handles all your outbound communications.</p>
        <ul style="padding-left: 1.5rem;">
            <li><strong>Sending an Individual Message:</strong> Select "Individual Notification", search for a specific consumer, type your message, and dispatch it directly to their phone.</li>
            <li><strong>Sending a Broadcast (Mass Text):</strong> Select "Broadcast Messages" to send advisories to entire Municipalities, Barangays, or Service Areas.</li>
            <li><strong>Scheduling:</strong> You can check the "Schedule Send" box to pick a specific Date and Time for a planned interruption notice to go out automatically.</li>
        </ul>

        <h3 style="color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem;">
            <i class="fa-solid fa-keyboard"></i> 5. Keywords
        </h3>
        <p>This dropdown menu allows you to manage the automated bot responses.</p>
        <ul style="padding-left: 1.5rem;">
            <li><strong>Manage Keywords:</strong> Depending on your access level, you can view or edit the predefined templates the system uses when consumers text specific keywords like <code>BILL</code>, <code>STATUS</code>, or <code>HELP</code>.</li>
            <li><strong>Keyword History:</strong> View the log of what consumers are texting in (Incoming) and what the system automatically replied (Outgoing). Look for the <span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">[SENT]</span> or <span style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; font-weight: bold;">[FAILED]</span> badges to confirm delivery.</li>
        </ul>

        <h3 style="color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem;">
            <i class="fa-solid fa-chart-line"></i> 6. SMS Activity Monitor
        </h3>
        <p>This tool is used to monitor the health and limits of the SMS gateway.</p>
        <ul style="padding-left: 1.5rem;">
            <li><strong>Action:</strong> Check this page to see if any users are sending too many messages in a short time. If a user is temporarily blocked by the system's anti-spam filter, you can unblock them from here.</li>
        </ul>

        <h3 style="color: var(--primary-color); display: flex; align-items: center; gap: 0.5rem; margin-top: 2rem;">
            <i class="fa-solid fa-comments"></i> 7. Message Threads
        </h3>
        <p>This module provides a conversation-style view.</p>
        <ul style="padding-left: 1.5rem;">
            <li><strong>Action:</strong> Use this to view a continuous, threaded history of back-and-forth SMS interactions with specific phone numbers or accounts, giving you full context of their concerns.</li>
        </ul>

    </div>
</div>
@endsection
