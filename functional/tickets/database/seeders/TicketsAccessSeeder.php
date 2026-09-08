<?php

namespace Functional\Tickets\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the permissions the ticket perimeters read, the roles that group them,
 * and one user per profile.
 *
 * Roles group, permissions authorize: no code outside this seeder ever names a
 * role. Perimeters check permissions only.
 */
class TicketsAccessSeeder extends Seeder
{
    public const PERMISSION_VIEW_OWN = 'view own tickets';

    public const PERMISSION_VIEW_ASSIGNED = 'view assigned tickets';

    public const PERMISSION_VIEW_ALL = 'view all tickets';

    public const PERMISSION_CREATE = 'create tickets';

    public const PERMISSION_ASSIGN = 'assign tickets';

    public const PERMISSION_CLOSE = 'close tickets';

    public const DEMO_REQUESTER_EMAIL = 'requester@support.test';

    public const DEMO_TECHNICIAN_EMAIL = 'technician@support.test';

    public const DEMO_MANAGER_EMAIL = 'manager@support.test';

    /**
     * Which permissions each role groups.
     *
     * @var array<string, list<string>>
     */
    private const ROLES = [
        'requester' => [
            self::PERMISSION_CREATE,
            self::PERMISSION_VIEW_OWN,
        ],
        'technician' => [
            self::PERMISSION_VIEW_ASSIGNED,
        ],
        'manager' => [
            self::PERMISSION_VIEW_ALL,
            self::PERMISSION_ASSIGN,
            self::PERMISSION_CLOSE,
        ],
    ];

    /**
     * One demo account per profile, so the scoping can be verified end to end.
     *
     * @var array<string, array{name: string, email: string}>
     */
    private const DEMO_USERS = [
        'requester' => ['name' => 'Demo Requester', 'email' => self::DEMO_REQUESTER_EMAIL],
        'technician' => ['name' => 'Demo Technician', 'email' => self::DEMO_TECHNICIAN_EMAIL],
        'manager' => ['name' => 'Demo Manager', 'email' => self::DEMO_MANAGER_EMAIL],
    ];

    public function run(): void
    {
        foreach (self::permissions() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        foreach (self::DEMO_USERS as $role => $attributes) {
            $user = User::firstWhere('email', $attributes['email'])
                ?? User::factory()->create($attributes);

            $user->syncRoles([$role]);
        }

        /** Spatie caches the permission map; a stale cache makes every check fail. */
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            self::PERMISSION_VIEW_OWN,
            self::PERMISSION_VIEW_ASSIGNED,
            self::PERMISSION_VIEW_ALL,
            self::PERMISSION_CREATE,
            self::PERMISSION_ASSIGN,
            self::PERMISSION_CLOSE,
        ];
    }
}
