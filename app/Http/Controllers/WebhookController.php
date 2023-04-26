<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * 消息控制器
 *
 * @author southwan
 */
class WebhookController
{
    
    public function message(Request $request): array
    {
        Log::info(json_encode($request::all(), JSON_PARTIAL_OUTPUT_ON_ERROR));
        
        return [];
    }
    
}