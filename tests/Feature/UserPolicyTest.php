<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_defaults_to_examinee(): void
    {
        $user = User::factory()->create();

        $this->assertSame(Role::Examinee, $user->fresh()->role);
    }

    public function test_role_cannot_be_mass_assigned(): void
    {
        $user = new User;
        $user->fill(['name' => 'Eve', 'role' => 'admin']);

        $this->assertNull($user->role);
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('view', $target));
        $this->assertTrue($admin->can('create', User::class));
        $this->assertTrue($admin->can('update', $target));
        $this->assertTrue($admin->can('changeRole', $target));
        $this->assertTrue($admin->can('delete', $target));
    }

    public function test_admin_cannot_demote_or_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('changeRole', $admin));
        $this->assertFalse($admin->can('delete', $admin));
    }

    public function test_instructor_and_examinee_cannot_manage_users(): void
    {
        $target = User::factory()->create();

        foreach ([User::factory()->instructor()->create(), User::factory()->create()] as $user) {
            $this->assertFalse($user->can('viewAny', User::class));
            $this->assertFalse($user->can('view', $target));
            $this->assertFalse($user->can('create', User::class));
            $this->assertFalse($user->can('update', $target));
            $this->assertFalse($user->can('changeRole', $target));
            $this->assertFalse($user->can('delete', $target));
        }
    }
}
