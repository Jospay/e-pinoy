<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Store feedback from landing page modal
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'user_type' => 'required|string', 
            'rating' => 'required|numeric|min:1|max:5',
            'description' => 'required|string',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Decrypt user_type
        try {
            $userTypeId = Crypt::decryptString($request->user_type);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid user type'], 400);
        }

        // Handle avatar upload
        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $feedback = Feedback::create([
            'name' => $request->name,
            'user_type_id' => $userTypeId,
            'rating' => $request->rating,
            'description' => $request->description,
            'avatar' => $avatarPath,
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'feedback' => $feedback->load('userType:id,name')
        ]);
    }
}
