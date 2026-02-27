<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Message::with('recipients.contact')->latest();

        if ($request->has('type')) {
            $query->where('type', $request->type);
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

        // Only create recipients if NOT scheduled (send immediately)
        if (!$message->is_scheduled) {
            if ($validated['type'] === 'individual') {
                $message->recipients()->create([
                    'contact_id' => $validated['contact_id'],
                    'status' => 'sent',
                ]);
            }
            elseif ($validated['type'] === 'broadcast') {
                $contactIds = [];
                $groups = \App\Models\Group::with('contacts')->whereIn('id', $validated['group_ids'])->get();

                foreach ($groups as $group) {
                    foreach ($group->contacts as $contact) {
                        $contactIds[] = $contact->id;
                    }
                }

                // Remove duplicates in case a contact is in multiple groups
                foreach (array_unique($contactIds) as $contactId) {
                    $message->recipients()->create([
                        'contact_id' => $contactId,
                        'status' => 'sent',
                    ]);
                }
            }
        }

        $this->logUserActivity("Stored {$validated['type']} message" . ($message->is_scheduled ? " (Scheduled)" : ""));

        return response()->json([
            'message' => $message->is_scheduled ? 'Message scheduled successfully' : 'Message sent successfully',
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
