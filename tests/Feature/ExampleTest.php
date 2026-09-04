<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_map_page_renders_a_valid_sidebar(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="sidebar"', false);
        $response->assertSee('function showSidebar', false);
        $response->assertSee('function closeSidebar', false);
    }
}
