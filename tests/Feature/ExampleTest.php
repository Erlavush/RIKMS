<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_agency_admin_can_view_dashboard_and_repository(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Agency Research Dashboard');

        $this->actingAs($user)
            ->get('/repository')
            ->assertOk()
            ->assertSee('Showing 1-6 of 38 documents')
            ->assertSee('Cybersecurity data science');
    }
}
