<?php

namespace Tests\Feature;

use App\AccountApprovalStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountApprovalRequiresOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_approve_an_account_until_email_otp_is_verified(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $user = User::factory()->unverified()->create([
            'role' => 'TRAVELER',
            'approval_status' => AccountApprovalStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'approval_status' => AccountApprovalStatus::Approved->value,
                'account_status' => $user->account_status->value,
                'verification_level' => $user->verification_level->value,
            ])
            ->assertSessionHasErrors('approval_status');

        $this->assertSame(AccountApprovalStatus::Pending, $user->fresh()->approval_status);
    }

    public function test_changing_email_resets_approval_until_the_new_email_is_verified(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $user = User::factory()->create([
            'role' => 'TRAVELER',
            'approval_status' => AccountApprovalStatus::Approved,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => 'new-'.$user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'approval_status' => AccountApprovalStatus::Approved->value,
                'account_status' => $user->account_status->value,
                'verification_level' => $user->verification_level->value,
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->email_verified_at);
        $this->assertSame(AccountApprovalStatus::Pending, $user->approval_status);
    }
}
