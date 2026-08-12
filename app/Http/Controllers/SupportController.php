<?php

namespace App\Http\Controllers;

use App\Models\Support;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    // Display a listing of the resource
    public function index()
    {
        $supports = Support::paginate(10); // Adjust the pagination as needed
        return view('supports.index', compact('supports'));
    }

    // Show the form for creating a new resource
    public function create()
    {
        return view('supports.create'); // Create a view for the form
    }

    // Store a newly created resource in storage
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        Support::create($request->all());

        return redirect()->route('supports.index')->with('success', 'Support created successfully.');
    }

    // Show the form for editing the specified resource
    public function edit(Support $support)
    {
        return view('supports.edit', compact('support')); // Create a view for the edit form
    }

    // Update the specified resource in storage
    public function update(Request $request, Support $support)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:supports,email,' . $support->id,
        ]);
    
        $support->update($request->all());
    
        return redirect()->route('supports.index')->with('success', 'Soporte actualizado exitosamente.');
    }
    

    // Remove the specified resource from storage
    public function destroy(Support $support)
    {
        $support->delete();

        return redirect()->route('supports.index')->with('success', 'Support deleted successfully.');
    }
}
