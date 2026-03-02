<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use App\Services\YeastarService;
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
                
                // Keep incoming messages strictly in the "Individual Notification" tab
                if ($request->type === 'individual' && (!$request->has('scheduled') || !$request->scheduled)) {
                    $q->orWhere('type', 'incoming');
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
            'content' => 'required|string',
            'type' => 'required|in:individual,broadcast',
            'contact_id' => 'required_if:type,individual|nullable|exists:contacts,id',
            'group_ids' => 'required_if:type,broadcast|nullable|array',
            'group_ids.*' => 'exists:groups,id',
            'category' => 'required_if:type,broadcast|nullable|in:MCO CONTACTS,ADVISORY,OUTAGE,EVENTS',
            'is_scheduled' => 'boolean',
            'scheduled_at' => 'required_if:is_scheduled,true|nullable|date',
            'no_reply' => 'boolean',
        ]);

        $message = Message::create([
            'content' => $validated['content'],
            'type' => $validated['type'],
            'user_id' => auth()->id(),
            'category' => $validated['category'] ?? 'ADVISORY',
            'is_scheduled' => $validated['is_scheduled'] ?? false,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'no_reply' => $validated['no_reply'] ?? true,
        ]);

        $dispatchCount = 0;
        $successCount = 0;

        $contactsToText = [];
        $gsmPortToUse = null;

        if ($validated['type'] === 'individual') {
            $gsmPortToUse = env('YEASTAR_PORT_INDIVIDUAL', 1);
            $contact = Contact::find($validated['contact_id']);
            if ($contact) {
                $contactsToText[] = $contact;
            }
        }
        elseif ($validated['type'] === 'broadcast') {
            $gsmPortToUse = env('YEASTAR_PORT_BROADCAST', 2);
            $groups = \App\Models\Group::with('contacts')->whereIn('id', $validated['group_ids'])->get();

            // Get unique contacts across groups
            $uniqueContacts = collect();
            foreach ($groups as $group) {
                foreach ($group->contacts as $contact) {
                    $uniqueContacts->push($contact);
                }
            }
            $contactsToText = $uniqueContacts->unique('id')->values()->all();
        }

        foreach ($contactsToText as $contact) {
            $status = 'pending';

            if (!$message->is_scheduled) {
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
                'status' => $status,
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
            'data' => $message->load('recipients'),
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
    public function destroy(string $id)
    {
    //
    }
}
