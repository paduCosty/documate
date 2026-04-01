<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password')
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->dump(); // This will show the response

        $this->assertAuthenticated();
    }
}
