<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\MorescoDbService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        // Serve MORESCO System contacts directly from the external SQL Server
        if ($request->source === 'moresco') {
            $search  = $request->get('search');
            $service = app(MorescoDbService::class);

            // picker=1 → flat array for message-form dropdowns (no pagination wrapper)
            if ($request->get('picker') == '1') {
                $perPage = (int) $request->get('per_page', 200);
                $offset  = (int) $request->get('offset', 0);
                return response()->json($service->getMembers($search, $perPage, $offset));
            }

            // Default: paginated response for the Contacts page
            $perPage = (int) $request->get('per_page', 100);
            $offset  = (int) $request->get('offset', 0);
            return response()->json([
                'data'  => $service->getMembers($search, $perPage, $offset),
                'total' => $service->countMembers($search),
            ]);
        }

        // App contacts — local DB
        $query = Contact::query();
        if ($request->has('source')) {
            $query->where('source', $request->source);
        }
        return $query->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:contacts,phone_number|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $contact = Contact::create($validated);

        $this->logUserActivity("Created contact: {$contact->name}");

        return $contact;
    }

    public function show(Contact $contact)
    {
        return $contact->load('groups');
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'sometimes|required|string|unique:contacts,phone_number,' . $contact->id . '|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $contact->update($validated);

        $this->logUserActivity("Updated contact: {$contact->name}");

        return $contact;
    }

    public function destroy(Contact $contact)
    {
        $name = $contact->name;
        $contact->delete();

        $this->logUserActivity("Deleted contact: {$name}");

        return response()->noContent();
    }
}
