<?php

use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

test('book configuration routes and tables are removed', function () {
    expect(Route::has('admin.book-config.index'))->toBeFalse()
        ->and(Route::has('admin.book-config.books.store'))->toBeFalse()
        ->and(Route::has('admin.book-config.inventory.store'))->toBeFalse()
        ->and(Schema::hasTable('ref_books'))->toBeFalse()
        ->and(Schema::hasTable('book_inventory'))->toBeFalse();
});

test('admin dashboard no longer links to book configuration', function () {
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);
    $admin = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'admin.books.removed',
        'email' => 'admin.books.removed@anhs.local',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get('/admin/book-configuration')
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Book Configuration')
        ->assertDontSee('admin.book-config.index');
});
