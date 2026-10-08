<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportBasketballCourtsTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_page_loads_when_no_courts_exist(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
