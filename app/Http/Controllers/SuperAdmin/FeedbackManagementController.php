<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;
use Inertia\Inertia;

class FeedbackManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $feedbacks = Feedback::with('userType:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(10) 
            ->through(fn($fb) => [
                'id' => $fb->id,
                'name' => $fb->name,
                'avatar' => $fb->avatar,
                'rating' => $fb->rating,
                'is_active' => $fb->is_active,
                'description' => $fb->description,
                'user_type' => $fb->userType ? [
                    'id' => $fb->userType->id,
                    'name' => $fb->userType->name,
                ] : null,
                'created_at' => $fb->created_at->toDateTimeString(),
            ]);

        return Inertia::render('super-admin/feedback/Index', [
            'feedbacks' => $feedbacks,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $feedback = Feedback::findOrFail($id);
        
        $feedback->delete(); 

        return back()->with('success', 'Feedback deleted successfully.');
    }
    
    public function toggleActive($id) 
    { 
        $feedback = Feedback::findOrFail($id); 
        $feedback->is_active = !$feedback->is_active; 
        $feedback->save(); 

        // return response()->json([ 'message' => 'Feedback status updated successfully', 'feedback' => $feedback->load('userType:id,name') ]); 
        return back()->with('success', 'Feedback status updated successfully.');
    }
}
