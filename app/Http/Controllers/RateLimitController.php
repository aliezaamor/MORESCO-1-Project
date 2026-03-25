<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\RateLimitService;
use Illuminate\Http\Request;

class RateLimitController extends Controller
{
    public function __construct(protected RateLimitService $rateLimiter) {}

    /**
     * Show the Activity Monitor page.
     */
    public function index()
    {
        return view('messages.activity');
    }

    /**
     * Return JSON data for the live Activity Monitor table.
     */
    public function data(Request $request)
    {
        return response()->json($this->rateLimiter->getActivityData($request->query('date')));
    }

    /**
     * Staff action: manually reset (unblock) a contact's rate limit.
     */
    public function unblock(Contact $contact)
    {
        $this->rateLimiter->reset($contact);

        return response()->json(['success' => true, 'message' => "Rate limit for {$contact->name} has been reset."]);
    }
}
