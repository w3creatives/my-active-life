<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ManualEntryController extends Controller
{
    /**
     * Get the current manual entry setting for the user
     */
    public function get(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = json_decode($user->settings ?? '{}', true);

        $manualEntryGlobal = $settings['manual_entry_populates_all_events'] ?? false;

        return response()->json([
            'manual_entry_global' => $manualEntryGlobal,
        ]);
    }

    /**
     * Update the manual entry setting for the user
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'manual_entry_global' => 'required|boolean',
        ]);

        $user = $request->user();
        $settings = json_decode($user->settings ?? '{}', true);

        $settings['manual_entry_populates_all_events'] = $request->manual_entry_global;

        $user->settings = json_encode($settings);
        $user->save();

        return response()->json([
            'message' => 'Manual entry settings updated successfully',
            'manual_entry_global' => $request->manual_entry_global,
        ]);
    }
}
