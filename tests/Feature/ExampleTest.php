<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Panel admin ada di "/" dan butuh login: tamu diarahkan ke halaman login.
     */
    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_halaman_login_bisa_dibuka(): void
    {
        $this->get('/login')->assertOk();
    }
}
