<?php

namespace Tests\Unit;

use App\Support\AdminListReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminListReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_valid_packages_index_return_url(): void
    {
        $index = route('admin.packages.index');
        $return = $index.'?page=2&sort=title&dir=asc';

        $this->assertSame($return, AdminListReturn::resolve($return));
    }

    public function test_it_falls_back_for_untrusted_return_url(): void
    {
        $this->assertSame(
            route('admin.packages.index'),
            AdminListReturn::resolve('https://evil.test/admin/packages')
        );

        $this->assertSame(
            route('admin.packages.index'),
            AdminListReturn::resolve(route('admin.dashboard'))
        );
    }
}
