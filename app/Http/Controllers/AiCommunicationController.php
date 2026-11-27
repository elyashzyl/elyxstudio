<?php

namespace App\Http\Controllers;

use App\Services\AiCommunicationDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiCommunicationController extends Controller
{
    public function draft(Request $request, AiCommunicationDraftService $service): JsonResponse
    {
        if (is_string($request->input('prompt'))) {
            $request->merge(['prompt' => trim($request->input('prompt'))]);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:announcement,notification,todo',
            'prompt' => 'required|string|max:4000',
        ]);

        try {
            $draft = $service->draft($validated['type'], $validated['prompt']);
        } catch (ValidationException $exception) {
            return response()->json([
                'ok' => false,
                'message' => 'The AI returned an invalid draft. Please try again or clarify your prompt.',
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($draft === null) {
            return response()->json([
                'ok' => false,
                'message' => 'AI drafting is currently unavailable. Please try again later or ask an administrator to check the AI provider settings.',
            ], 503);
        }

        return response()->json(['ok' => true, 'draft' => $draft]);
    }
}
