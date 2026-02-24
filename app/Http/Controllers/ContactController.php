<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return Contact::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:contacts,phone_number|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        return Contact::create($validated);
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

        return $contact;
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return response()->noContent();
    }
}
