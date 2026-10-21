<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminPageTest extends TestCase
{
    public function test_admin_page_has_manage_users_heading_and_no_corrupted_script_markup(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(200)
            ->assertSee('Manage Users')
            ->assertSee('Add User')
            ->assertDontSee('tbody.addEventListener');
    }
}
