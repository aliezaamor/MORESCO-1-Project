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
        ]);

        return Keyword::create($validated);
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
        ]);

        $keyword->update($validated);

        return $keyword;
    }

    public function destroy(Keyword $keyword)
    {
        $keyword->delete();
        return response()->noContent();
    }
}
