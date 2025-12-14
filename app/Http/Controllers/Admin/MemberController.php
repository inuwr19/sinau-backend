<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberRequest;
use App\Models\Member;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        // Kalau mau pakai pagination:
        $members = Member::orderBy('name')->paginate(20);
        return response()->json($members);

        // $members = Member::orderBy('name')->get();

        // return response()->json($members);
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json($member);
    }

    public function store(MemberRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Normalisasi nomor HP
        $data['phone'] = preg_replace('/\D+/', '', $data['phone']);

        // Points default 0 kalau tidak dikirim
        if (!isset($data['points'])) {
            $data['points'] = 0;
        }

        $member = Member::create($data);

        return response()->json($member, 201);
    }

    public function update(MemberRequest $request, Member $member): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/\D+/', '', $data['phone']);
        }

        $member->update($data);

        return response()->json($member);
    }

    public function destroy(Member $member): JsonResponse
    {
        $member->delete();

        return response()->json(null, 204);
    }
}
