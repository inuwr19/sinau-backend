<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    public function index(Request $request)
    {
        $q = MenuItem::with('category')->orderBy('name');

        if ($search = $request->query('q')) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        $perPage = (int) $request->query('per_page', 25);
        $result = $q->paginate($perPage);

        return response()->json($result);
    }

    public function store(MenuItemRequest $request)
    {
        $data = $request->validated();

        // handle image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('menu_images', 'public');
            // store accessible url
            $data['image_url'] = Storage::url($path);
        }

        $data['is_available'] = $data['is_available'] ?? true;

        $menu = MenuItem::create($data);

        return response()->json($menu, 201);
    }

    public function show(MenuItem $menuItem)
    {
        $menuItem->load('category');
        return $menuItem;
    }

    public function update(MenuItemRequest $request, MenuItem $menuItem)
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $menuItem, &$data) {
            if ($request->hasFile('image')) {
                // delete old file if stored in /storage
                if ($menuItem->image_url) {
                    // try to resolve path from image_url
                    $parsed = parse_url($menuItem->image_url, PHP_URL_PATH);
                    if ($parsed) {
                        // remove leading /storage/ from path for storage disk
                        $filePath = preg_replace('#^/storage/#', '', $parsed);
                        if ($filePath && Storage::disk('public')->exists($filePath)) {
                            Storage::disk('public')->delete($filePath);
                        }
                    }
                }

                $path = $request->file('image')->store('menu_images', 'public');
                $data['image_url'] = Storage::url($path);
            }

            $menuItem->update($data);
        });

        return response()->json($menuItem->fresh());
    }

    public function destroy(MenuItem $menuItem)
    {
        // delete image from storage if present
        if ($menuItem->image_url) {
            $parsed = parse_url($menuItem->image_url, PHP_URL_PATH);
            if ($parsed) {
                $filePath = preg_replace('#^/storage/#', '', $parsed);
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
        }

        $menuItem->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
