<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $q = Branch::query()->orderBy('name');
        if ($search = $request->query('q')) {
            $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
        }
        $perPage = (int) $request->query('per_page', 25);
        return $q->paginate($perPage);
    }

    public function store(BranchRequest $request)
    {
        $branch = Branch::create($request->validated());
        return response()->json($branch, 201);
    }

    public function show(Branch $branch)
    {
        return $branch;
    }

    public function update(BranchRequest $request, Branch $branch)
    {
        $branch->update($request->validated());
        return $branch;
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
