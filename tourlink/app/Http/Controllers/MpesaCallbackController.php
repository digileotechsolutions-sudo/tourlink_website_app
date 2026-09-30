<?php

namespace App\Http\Controllers;

use App\Services\Payments\MpesaCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;

class MpesaCallbackController extends Controller
{
    /** @throws JsonException */
    public function __invoke(Request $request, MpesaCallbackService $callbackService): JsonResponse
    {
        $callbackService->handle($request->json()->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
