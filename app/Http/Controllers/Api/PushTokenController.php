<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushToken;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'push_token' => 'required|string',
            'device_type' => 'nullable|string',
            'user_type' => 'nullable|string',
        ]);

        $user = $request->user();

        PushToken::updateOrCreate(
            ['push_token' => $request->push_token],
            [
                'user_id' => $user?->id,
                'device_type' => $request->device_type ?? 'android',
                'user_type' => $request->user_type ?? 'admin',
                'active' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Token registrado correctamente',
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'push_token' => 'required|string',
        ]);

        PushToken::where('push_token', $request->push_token)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token eliminado',
        ]);
    }
}
