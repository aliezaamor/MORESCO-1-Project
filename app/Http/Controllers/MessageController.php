<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Services\YeastarService;
use App\Services\MorescoDbService;
use App\Models\Contact;

class MessageController extends Controller
{
    protected $yeastarService;

    public function __construct(YeastarService $yeastarService)
    {
        $this->yeastarService = $yeastarService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Message::with('recipients.contact')->latest();

        if ($request->has('type')) {
            $query->where(function($q) use ($request) {
                $q->where('type', $request->type);
                
                // Keep individual incoming messages in the "Individual Notification" tab
                if ($request->type === 'individual' && (!$request->has('scheduled') || !$request->scheduled)) {
                    $q->orWhere('type', 'incoming');
                }
                
                // Keep keyword incoming messages in the "Keyword History" tab
                if ($request->type === 'auto_reply') {
                    $q->orWhere('type', 'incoming_keyword');
                }
            });
        }

        if ($request->has('scheduled') && $request->scheduled) {
            $query->where('is_scheduled', 1);
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content'             => 'required|string',
            'type'                => 'required|in:individual,broadcast',
            // App contact (local DB)
            'contact_id'          => 'nullable|exists:contacts,id',
            // MORESCO consumer (external DB — phone + name passed directly)
            'moresco_phone'       => 'nullable|string|max:30',
            'moresco_name'        => 'nullable|string|max:255',
            // App groups (local DB)
            'group_ids'           => 'nullable|array',
            'group_ids.*'         => 'exists:groups,id',
            // MORESCO service area groups (sa_codes from external DB)
            'moresco_group_codes'    => 'nullable|array',
            'moresco_group_codes.*'  => 'string',
            // MORESCO municipality groups
            'moresco_municipalities'   => 'nullable|array',
            'moresco_municipalities.*' => 'string',
            // MORESCO barangay groups (format: "Municipality|Barangay")
            'moresco_barangays'       => 'nullable|array',
            'moresco_barangays.*'     => 'string',
            'category'            => 'nullable|in:MCO CONTACTS,ADVISORY,OUTAGE,EVENTS',
            'is_scheduled'        => 'boolean',
            'scheduled_at'        => 'required_if:is_scheduled,true|nullable|date',
            'no_reply'            => 'boolean',
        ]);

        // Individual notification requires either a local contact OR a MORESCO phone
        if ($validated['type'] === 'individual') {
            if (empty($validated['contact_id']) && empty($validated['moresco_phone'])) {
                return response()->json(['message' => 'A contact or MORESCO phone number is required for individual messages.'], 422);
            }
        }

        $message = Message::create([
            'content'      => $validated['content'],
            'type'         => $validated['type'],
            'user_id'      => auth()->id(),
            'category'     => $validated['category'] ?? 'ADVISORY',
            'is_scheduled' => $validated['is_scheduled'] ?? false,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'no_reply'     => $validated['no_reply'] ?? true,
        ]);

        $dispatchCount = 0;
        $successCount  = 0;
        $contactsToText = [];
        $gsmPortToUse   = null;

        if ($validated['type'] === 'individual') {
            $gsmPortToUse = config('yeastar.port_individual', 2);

            if (!empty($validated['moresco_phone'])) {
                // MORESCO consumer — find or create a local stub so the FK is satisfied
                $phone = preg_replace('/[^0-9+]/', '', $validated['moresco_phone']);
                $contact = Contact::firstOrCreate(
                    ['phone_number' => $phone],
                    [
                        'name'   => $validated['moresco_name'] ?? 'MORESCO Consumer',
                        'source' => 'moresco',
                    ]
                );
                $contactsToText[] = $contact;
            } else {
                $contact = Contact::find($validated['contact_id']);
                if ($contact) {
                    $contactsToText[] = $contact;
                }
            }
        }
        elseif ($validated['type'] === 'broadcast') {
            $gsmPortToUse = config('yeastar.port_broadcast', 1);
            $uniqueContacts = collect();

            // --- App groups (local MySQL) ---
            if (!empty($validated['group_ids'])) {
                $groups = \App\Models\Group::with('contacts')
                    ->whereIn('id', $validated['group_ids'])
                    ->get();
                foreach ($groups as $group) {
                    foreach ($group->contacts as $contact) {
                        $uniqueContacts->push($contact);
                    }
                }
            }

            // --- MORESCO service area groups (external SQL Server) ---
            if (!empty($validated['moresco_group_codes'])) {
                $morescoService = app(MorescoDbService::class);
                foreach ($validated['moresco_group_codes'] as $saCode) {
                    $members = $morescoService->getMembersBySaCode($saCode);
                    foreach ($members as $member) {
                        $phone = preg_replace('/[^0-9+]/', '', $member['phone_number'] ?? '');
                        if (!$phone) continue;
                        $contact = Contact::firstOrCreate(
                            ['phone_number' => $phone],
                            ['name' => $member['name'] ?? 'MORESCO Consumer', 'source' => 'moresco']
                        );
                        $uniqueContacts->push($contact);
                    }
                }
            }

            // --- MORESCO municipality groups ---
            if (!empty($validated['moresco_municipalities'])) {
                $morescoService = app(MorescoDbService::class);
                foreach ($validated['moresco_municipalities'] as $municipality) {
                    $members = $morescoService->getMembersByMunicipality($municipality);
                    foreach ($members as $member) {
                        $phone = preg_replace('/[^0-9+]/', '', $member['phone_number'] ?? '');
                        if (!$phone) continue;
                        $contact = Contact::firstOrCreate(
                            ['phone_number' => $phone],
                            ['name' => $member['name'] ?? 'MORESCO Consumer', 'source' => 'moresco']
                        );
                        $uniqueContacts->push($contact);
                    }
                }
            }

            // --- MORESCO barangay groups (Municipality|Barangay) ---
            if (!empty($validated['moresco_barangays'])) {
                $morescoService = app(MorescoDbService::class);
                foreach ($validated['moresco_barangays'] as $groupId) {
                    $members = $morescoService->getMembersByBarangay($groupId);
                    foreach ($members as $member) {
                        $phone = preg_replace('/[^0-9+]/', '', $member['phone_number'] ?? '');
                        if (!$phone) continue;
                        $contact = Contact::firstOrCreate(
                            ['phone_number' => $phone],
                            ['name' => $member['name'] ?? 'MORESCO Consumer', 'source' => 'moresco']
                        );
                        $uniqueContacts->push($contact);
                    }
                }
            }

            // De-duplicate by phone number across both sources
            $contactsToText = $uniqueContacts->unique('phone_number')->values()->all();
        }

        $isFirst = true;
        foreach ($contactsToText as $contact) {
            $status = 'pending';

            if (!$message->is_scheduled) {
                // Add 3-second gap for broadcasts after the first message
                if ($validated['type'] === 'broadcast') {
                    if (!$isFirst) {
                        sleep(3);
                    }
                    $isFirst = false;
                }

                // Determine destination number (cleaning it up if necessary)
                $destination = preg_replace('/[^0-9+]/', '', $contact->phone_number);

                // Try sending via Yeastar using the specifically mapped port
                $sent = $this->yeastarService->sendSms($destination, $validated['content'], $gsmPortToUse);
                $dispatchCount++;
                if ($sent) {
                    $successCount++;
                }

                $status = $sent ? 'sent' : 'failed';
            }

            $message->recipients()->create([
                'contact_id' => $contact->id,
                'status'     => $status,
            ]);
        }

        $logMsg = "Stored {$validated['type']} message";
        if ($message->is_scheduled) {
            $logMsg .= " (Scheduled)";
        } else {
            $logMsg .= " (Sent: {$successCount}/{$dispatchCount})";
        }
        $this->logUserActivity($logMsg);

        $responseMsg = $message->is_scheduled
            ? 'Message scheduled successfully'
            : ($dispatchCount > 0 ? "Message sent to {$successCount} of {$dispatchCount} recipients" : 'Message sent successfully');

        return response()->json([
            'message' => $responseMsg,
            'data'    => $message->load('recipients'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */

    public function show(string $id)
    {
    //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $adminUser = \App\Models\User::where('username', 'admin')->orWhere('role', 'admin')->first();

        if (!$adminUser || !\Illuminate\Support\Facades\Hash::check($request->password, $adminUser->password)) {
            return response()->json(['message' => 'Incorrect admin password.'], 403);
        }

        $message = Message::findOrFail($id);
        
        // Log the activity
        $this->logUserActivity("Deleted message ID: {$id}");

        $message->delete();

        return response()->json(['message' => 'Message deleted successfully.']);
    }
}
