<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        // When source=moresco, return service area groups from the external SQL Server
        if ($request->source === 'moresco') {
            $service = app(\App\Services\MorescoDbService::class);
            return response()->json($service->getServiceAreaGroups());
        }

        if ($request->source === 'moresco_municipality') {
            $service = app(\App\Services\MorescoDbService::class);
            return response()->json($service->getMunicipalityGroups());
        }

        if ($request->source === 'moresco_barangay') {
            $service = app(\App\Services\MorescoDbService::class);
            return response()->json($service->getBarangayGroups());
        }

        $query = Group::withCount('contacts');
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }
        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group = Group::create($validated);

        $this->logUserActivity("Created group: {$group->name}");

        return $group;
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

        $this->logUserActivity("Updated group: {$group->name}");

        return $group;
    }

    public function destroy(Group $group)
    {
        $name = $group->name;
        $group->delete();

        $this->logUserActivity("Deleted group: {$name}");

        return response()->noContent();
    }

    public function addContacts(Request $request, Group $group)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id'
        ]);

        $group->contacts()->syncWithoutDetaching($validated['contact_ids']);
        
        $count = count($validated['contact_ids']);
        $this->logUserActivity("Added {$count} contacts to group: {$group->name}");

        return response()->json([
            'message' => count($validated['contact_ids']) . ' contacts added to group',
            'group' => $group->load('contacts')
        ]);
    }

    public function removeContact(Group $group, $contactId)
    {
        $group->contacts()->detach($contactId);
        
        $this->logUserActivity("Removed contact from group: {$group->name}");

        return response()->json(['message' => 'Contact removed from group']);
    }
}
