<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Show the form to create a new group.
     */
    public function create()
    {
        return view('admin.create-group');
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:groups',
            'description' => 'nullable|string',
        ]);

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Auto‑attach the admin who created it
        $group->users()->attach(auth()->id(), ['has_agreed_rules' => true]);

        return redirect()->route('dashboard')
            ->with('success', "Group '{$group->name}' created successfully!");
    }
}