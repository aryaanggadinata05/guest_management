<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnershipAttributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_contains_ownership_headers_meta_tags_and_footer(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Developed-By', 'AAA-05');
        $response->assertHeader('X-Copyright', '2026 AAA-05');
        $response->assertSee('<meta name="author" content="AAA-05">', false);
        $response->assertSee('<meta name="copyright" content="Copyright (c) 2026 AAA-05. All rights reserved.">', false);
        $response->assertSee('Guest Management System', false);
        $response->assertSee('AAA-05', false);
    }

    public function test_scan_page_contains_ownership_headers_meta_tags_and_footer(): void
    {
        $petugas = User::factory()->create([
            'role' => 'register',
            'is_active' => true,
        ]);

        $response = $this->actingAs($petugas)->get('/scan');

        $response->assertOk();
        $response->assertHeader('X-Developed-By', 'AAA-05');
        $response->assertHeader('X-Copyright', '2026 AAA-05');
        $response->assertSee('<meta name="author" content="AAA-05">', false);
        $response->assertSee('<meta name="copyright" content="Copyright (c) 2026 AAA-05. All rights reserved.">', false);
        $response->assertSee('Guest Management System', false);
        $response->assertSee('AAA-05', false);
    }

    public function test_manage_settings_page_displays_system_ownership_info_card(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/pengaturan-tampilan');

        $response->assertOk();
        $response->assertHeader('X-Developed-By', 'AAA-05');
        $response->assertHeader('X-Copyright', '2026 AAA-05');
        $response->assertSee('Guest Management System v1.0', false);
        $response->assertSee('AAA-05 — Proprietary Software', false);
        $response->assertSee('Guest Management System &bull; AAA-05', false);
    }
}
