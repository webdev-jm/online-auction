<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @var array<string, array{module: string, description: string}>
     */
    private array $permissions = [
        'settings manage' => [
            'module' => 'Settings',
            'description' => 'Allow user to change auction settings, terms and notifications.',
        ],
        'auction winner manage' => [
            'module' => 'Auctions',
            'description' => 'Allow user to record payment, release and forfeit for auction winners.',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $name => $attributes) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                $attributes
            );
        }

        $superadmin = Role::where('name', 'superadmin')->first();
        $superadmin?->givePermissionTo(array_keys($this->permissions));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', array_keys($this->permissions))->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
