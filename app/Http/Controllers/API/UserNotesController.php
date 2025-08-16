<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserNotesController extends BaseController
{
    public function index(Request $request): JsonResponse
    {

        $user = $request->user();

        return $this->sendResponse(['note' => $user->notes], 'User Note saved');
    }

    public function store(Request $request): JsonResponse
    {

        $request->validate([
            'note' => 'required|max:1000',
        ]);

        $user = $request->user();

        $user->fill(['notes' => $request->note])->save();

        return $this->sendResponse([], 'User Note');
    }
}
