<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberRequest;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $q = Member::query()->orderBy('name');

        if ($search = $request->query('q')) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        }

        $perPage = (int) $request->query('per_page', 25);
        return $q->paginate($perPage);
    }

    public function store(MemberRequest $request)
    {
        $payload = $request->validated();
        // default points to 0 if not provided
        $payload['points'] = $payload['points'] ?? 0;
        $member = Member::create($payload);

        return response()->json($member, 201);
    }

    public function show(Member $member)
    {
        // load relations if needed
        $member->load('pointsHistory');
        return $member;
    }

    public function update(MemberRequest $request, Member $member)
    {
        $payload = $request->validated();

        // If points change here, record history (optional)
        if (isset($payload['points']) && $payload['points'] != $member->points) {
            $delta = (int) $payload['points'] - (int) $member->points;
            DB::transaction(function () use ($member, $payload, $delta) {
                $member->update($payload);
                // create points history record
                $member->pointsHistory()->create([
                    'order_id' => null,
                    'points_change' => $delta,
                    'reason' => 'Admin adjustment',
                ]);
            });
        } else {
            $member->update($payload);
        }

        return $member->fresh();
    }

    public function destroy(Member $member)
    {
        // optional: prevent delete if member has orders
        if ($member->orders()->exists()) {
            return response()->json(['message' => 'Member has orders — cannot delete.'], 422);
        }

        $member->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
