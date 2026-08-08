<?php

namespace Tests\Feature;

use App\Enums\RecitationStatus;
use App\Enums\StaffRole;
use App\Models\User;
use App\Policies\RecitationPolicy;
use App\Policies\ReciterPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCatalogData;
use Tests\Concerns\CreatesStaffUsers;
use Tests\TestCase;

class BulkDeletePolicyTest extends TestCase
{
    use CreatesCatalogData;
    use CreatesStaffUsers;
    use RefreshDatabase;

    public function test_staff_can_delete_any_for_bulk_actions(): void
    {
        $admin = $this->makeStaffUser(StaffRole::Admin);
        $production = $this->makeStaffUser(StaffRole::Production);

        $this->assertTrue((new ReciterPolicy)->deleteAny($admin));
        $this->assertTrue((new ReciterPolicy)->deleteAny($production));
        $this->assertTrue((new RecitationPolicy)->deleteAny($admin));
        $this->assertTrue((new RecitationPolicy)->deleteAny($production));
    }

    public function test_listener_cannot_delete_any(): void
    {
        /** @var User $listener */
        $listener = $this->makeUser();

        $this->assertFalse((new ReciterPolicy)->deleteAny($listener));
        $this->assertFalse((new RecitationPolicy)->deleteAny($listener));
    }

    public function test_admin_delete_policy_blocks_approved_recitations(): void
    {
        $admin = $this->makeStaffUser(StaffRole::Admin);
        $reciter = $this->makeReciter();
        $approved = $this->makeRecitation($reciter, status: RecitationStatus::Approved);
        $draft = $this->makeRecitation(
            $reciter,
            $this->makeSurah(['number' => 2]),
            RecitationStatus::Draft,
            ['created_by' => $admin->id],
        );

        $policy = new RecitationPolicy;

        $this->assertFalse($policy->delete($admin, $approved));
        $this->assertTrue($policy->delete($admin, $draft));
    }
}
