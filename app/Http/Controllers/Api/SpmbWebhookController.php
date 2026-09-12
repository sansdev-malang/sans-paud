<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SpmbIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SpmbWebhookController extends Controller
{
    protected SpmbIntegrationService $service;

    public function __construct(SpmbIntegrationService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle incoming SPMB Webhook request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('X-Spmb-Signature');
        $event = $request->header('X-Spmb-Event', 'unknown');
        $rawPayload = $request->getContent();

        // 1. Verify webhook signature if secret configured
        $secret = Setting::get('spmb_webhook_secret');
        if (!empty($secret)) {
            if (!$this->service->verifyWebhookSignature($rawPayload, $signature)) {
                Log::warning('SPMB Webhook signature mismatch', [
                    'received_sig' => $signature,
                    'ip' => $request->ip(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid webhook signature.',
                ], 401);
            }
        }

        // 2. Parse payload
        $payload = $request->all();
        if (empty($payload)) {
            $payload = json_decode($rawPayload, true) ?? [];
        }

        try {
            $result = $this->service->processWebhookEvent($event, $payload);
            return response()->json([
                'success' => true,
                'result' => $result,
            ], 200);
        } catch (\Exception $e) {
            Log::error('SPMB Webhook processing error: ' . $e->getMessage(), [
                'event' => $event,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Internal webhook error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
