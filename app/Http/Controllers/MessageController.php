<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Message::with('recipients.contact')->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'type' => 'required|in:individual,broadcast',
            'contact_id' => 'required_if:type,individual|nullable|exists:contacts,id',
            'group_id' => 'required_if:type,broadcast|nullable|exists:groups,id',
        ]);

        $message = Message::create([
            'content' => $validated['content'],
            'type' => $validated['type'],
            'user_id' => auth()->id(),
        ]);

        if ($validated['type'] === 'individual') {
            $message->recipients()->create([
                'contact_id' => $validated['contact_id'],
                'status' => 'sent', // Simulated send
            ]);
        } elseif ($validated['type'] === 'broadcast') {
            $group = \App\Models\Group::find($validated['group_id']);
            foreach ($group->contacts as $contact) {
                $message->recipients()->create([
                    'contact_id' => $contact->id,
                    'status' => 'sent', // Simulated send
                ]);
            }
        }

        $this->logUserActivity("Sent {$validated['type']} message");

        return response()->json([
            'message' => 'Message sent successfully',
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
