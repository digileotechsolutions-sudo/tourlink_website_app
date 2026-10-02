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
        $result = $callbackService->handle($request->json()->all());

        if ($result === 'verification_pending') {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Payment status could not be verified.'], 503);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
