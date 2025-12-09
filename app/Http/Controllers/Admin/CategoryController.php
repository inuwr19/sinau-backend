<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = Category::query()->orderBy('name');

        if ($search = $request->query('q')) {
            $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%");
        }

        $perPage = (int) $request->query('per_page', 25);

        return $q->paginate($perPage);
    }

    public function store(CategoryRequest $request)
    {
        $payload = $request->validated();
        $category = Category::create($payload);
        return response()->json($category, 201);
    }

    public function show(Category $category)
    {
        return $category;
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        return $category;
    }

    public function destroy(Category $category)
    {
        // optional: prevent delete if has menu items
        if ($category->menuItems()->exists()) {
            return response()->json(['message' => 'Category has menu items. Remove or reassign them first.'], 422);
        }

        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
