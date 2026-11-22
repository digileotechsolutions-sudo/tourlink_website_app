<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiRouteMiddlewareTest extends TestCase
{
    public function test_mpesa_stk_endpoint_uses_api_token_authentication(): void
    {
        $route = Route::getRoutes()->getByName('payments.mpesa.stk');

        $this->assertNotNull($route);
        $middleware = $route->getAction('middleware');
        $this->assertContains('api', $middleware);
        $this->assertContains('auth:sanctum', $middleware);

        $this->postJson('/api/payments/mpesa/stk')
            ->assertUnauthorized();
    }
}
