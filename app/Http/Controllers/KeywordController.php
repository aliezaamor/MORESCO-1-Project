<?php

namespace App\Http\Controllers;

use App\Models\Keyword;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    public function index()
    {
        return Keyword::with('parent')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
            'reply_content' => 'required|string',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:keywords,id',
            'action_type' => 'nullable|string',
            'action_data' => 'nullable|array',
        ]);

        if (empty($validated['action_type'])) {
            $validated['action_type'] = 'static';
        }

        $keyword = Keyword::create($validated);

        $this->logUserActivity("Created keyword: {$keyword->keyword}");

        return $keyword;
    }

    public function show(Keyword $keyword)
    {
        return $keyword->load('parent', 'children');
    }

    public function update(Request $request, Keyword $keyword)
    {
        $validated = $request->validate([
            'keyword' => 'sometimes|required|string|max:255',
            'reply_content' => 'sometimes|required|string',
            'is_active' => 'boolean',
            'parent_id' => 'nullable|exists:keywords,id',
            'action_type' => 'nullable|string',
            'action_data' => 'nullable|array',
        ]);

        if (array_key_exists('action_type', $validated) && empty($validated['action_type'])) {
            $validated['action_type'] = 'static';
        }

        $keyword->update($validated);

        $this->logUserActivity("Updated keyword: {$keyword->keyword}");

        return $keyword;
    }

    public function destroy(Keyword $keyword)
    {
        $name = $keyword->keyword;
        $keyword->delete();

        $this->logUserActivity("Deleted keyword: {$name}");

        return response()->noContent();
    }
}
