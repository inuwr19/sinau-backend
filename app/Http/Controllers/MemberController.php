<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    /**
     * Search member by phone (exact) or partial
     * GET /api/members/search?phone=081...
     */
    public function search(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phone = preg_replace('/\D+/', '', $request->phone);

        $member = Member::where('phone', $phone)->orWhere('phone', 'like', "%{$phone}%")->first();

        if (!$member) {
            return response()->json(null, 204); // no content
        }

        return response()->json($member);
    }

    /**
     * Register new member from POS
     * POST /api/members
     * body: name, phone
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'phone' => 'required|string|max:30|unique:members,phone',
        ]);

        $phone = preg_replace('/\D+/', '', $request->phone);

        $member = Member::create([
            'name' => $request->name,
            'phone' => $phone,
            'points' => 0,
        ]);

        return response()->json($member, 201);
    }
}
