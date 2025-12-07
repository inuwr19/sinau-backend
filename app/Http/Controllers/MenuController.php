<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * GET /api/menu
     * optional query: category (slug), search (name)
     */
    public function index(Request $request)
    {
        $q = MenuItem::query()->with('category');

        if ($request->filled('category')) {
            $q->whereHas('category', function ($qb) use ($request) {
                $qb->where('slug', $request->category);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $q->where('name', 'like', "%{$search}%");
        }

        // Only available items by default
        if ($request->boolean('available', true)) {
            $q->where('is_available', true);
        }

        $items = $q->orderBy('name')->get();

        return response()->json($items);
    }

    public function show($id)
    {
        $item = MenuItem::with('category')->findOrFail($id);
        return response()->json($item);
    }

    public function categories()
    {
        return response()->json(Category::orderBy('name')->get());
    }
}
