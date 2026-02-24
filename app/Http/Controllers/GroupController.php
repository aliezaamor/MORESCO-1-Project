<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        return Group::withCount('contacts')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        return Group::create($validated);
    }

    public function show(Group $group)
    {
        return $group->load('contacts');
    }

    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->update($validated);

        return $group;
    }

    public function destroy(Group $group)
    {
        $group->delete();
        return response()->noContent();
    }

    public function addContacts(Request $request, Group $group)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id'
        ]);

        $group->contacts()->syncWithoutDetaching($validated['contact_ids']);
        
        return response()->json([
            'message' => count($validated['contact_ids']) . ' contacts added to group',
            'group' => $group->load('contacts')
        ]);
    }

    public function removeContact(Group $group, $contactId)
    {
        $group->contacts()->detach($contactId);
        return response()->json(['message' => 'Contact removed from group']);
    }
}
