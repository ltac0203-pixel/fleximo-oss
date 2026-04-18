<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\AuditAction;
use App\Enums\TenantUserRole;
use App\Enums\UserRole;
use App\Models\Option;
use App\Models\OptionGroup;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $staff;

    private OptionGroup $optionGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->admin = User::factory()->create([
            'role' => UserRole::TenantAdmin,
        ]);
        TenantUser::factory()->create([
            'user_id' => $this->admin->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Admin,
        ]);

        $this->staff = User::factory()->create([
            'role' => UserRole::TenantStaff,
        ]);
        TenantUser::factory()->create([
            'user_id' => $this->staff->id,
            'tenant_id' => $this->tenant->id,
            'role' => TenantUserRole::Staff,
        ]);

        $this->optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_tenant_admin_can_list_options(): void
    {
        Option::factory()->count(3)->create([
            'option_group_id' => $this->optionGroup->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/option-groups/{$this->optionGroup->id}/options");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'price', 'sort_order', 'is_active'],
                ],
            ]);
    }

    public function test_tenant_staff_can_list_options(): void
    {
        Option::factory()->count(2)->create([
            'option_group_id' => $this->optionGroup->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson("/api/tenant/option-groups/{$this->optionGroup->id}/options");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_tenant_admin_can_create_option(): void
    {
        $optionData = [
            'name' => 'S',
            'price' => 0,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson("/api/tenant/option-groups/{$this->optionGroup->id}/options", $optionData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'S',
                    'price' => 0,
                ],
            ]);

        $this->assertDatabaseHas('options', [
            'option_group_id' => $this->optionGroup->id,
            'name' => 'S',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::OptionCreated->value,
        ]);
    }

    public function test_create_option_assigns_next_sort_order_when_not_provided(): void
    {
        Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
            'sort_order' => 1,
        ]);
        Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/tenant/option-groups/{$this->optionGroup->id}/options", [
                'name' => 'L',
                'price' => 100,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('options', [
            'option_group_id' => $this->optionGroup->id,
            'name' => 'L',
            'sort_order' => 3,
        ]);
    }

    public function test_option_price_has_max_limit(): void
    {
        $optionData = [
            'name' => 'S',
            'price' => 1000000,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson("/api/tenant/option-groups/{$this->optionGroup->id}/options", $optionData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_tenant_admin_can_update_option(): void
    {
        $option = Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
            'name' => '旧名前',
            'price' => 100,
        ]);

        $updateData = [
            'name' => '新名前',
            'price' => 200,
        ];

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/option-groups/{$this->optionGroup->id}/options/{$option->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => '新名前',
                    'price' => 200,
                ],
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::OptionUpdated->value,
        ]);
    }

    public function test_tenant_admin_can_delete_option(): void
    {
        $option = Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/option-groups/{$this->optionGroup->id}/options/{$option->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('options', ['id' => $option->id]);
    }

    public function test_tenant_staff_cannot_create_option(): void
    {
        $optionData = [
            'name' => 'S',
            'price' => 0,
        ];

        $response = $this->actingAs($this->staff)
            ->postJson("/api/tenant/option-groups/{$this->optionGroup->id}/options", $optionData);

        $response->assertStatus(403);
    }

    public function test_tenant_staff_cannot_update_option(): void
    {
        $option = Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->patchJson("/api/tenant/option-groups/{$this->optionGroup->id}/options/{$option->id}", [
                'name' => '変更不可',
            ]);

        $response->assertStatus(403);
    }

    public function test_tenant_staff_cannot_delete_option(): void
    {
        $option = Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->deleteJson("/api/tenant/option-groups/{$this->optionGroup->id}/options/{$option->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('options', ['id' => $option->id]);
    }

    public function test_cannot_access_other_tenant_option_group(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOptionGroup = OptionGroup::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/option-groups/{$otherOptionGroup->id}/options");

        $response->assertStatus(404);
    }

    public function test_cannot_update_option_when_option_group_path_is_mismatched(): void
    {
        $otherOptionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $otherOption = Option::factory()->create([
            'option_group_id' => $otherOptionGroup->id,
            'name' => '別グループのオプション',
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/option-groups/{$this->optionGroup->id}/options/{$otherOption->id}", [
                'name' => '更新されない',
            ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('options', [
            'id' => $otherOption->id,
            'name' => '別グループのオプション',
        ]);
    }

    public function test_cannot_delete_option_when_option_group_path_is_mismatched(): void
    {
        $otherOptionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $otherOption = Option::factory()->create([
            'option_group_id' => $otherOptionGroup->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/option-groups/{$this->optionGroup->id}/options/{$otherOption->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('options', ['id' => $otherOption->id]);
    }

    public function test_cannot_update_other_tenant_option(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherOptionGroup = OptionGroup::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);
        $otherOption = Option::factory()->create([
            'option_group_id' => $otherOptionGroup->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/option-groups/{$otherOptionGroup->id}/options/{$otherOption->id}", [
                'name' => '新名前',
            ]);

        $response->assertStatus(404);
    }

    public function test_options_are_ordered_by_sort_order(): void
    {
        Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
            'name' => 'Third',
            'sort_order' => 3,
        ]);
        Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
            'name' => 'First',
            'sort_order' => 1,
        ]);
        Option::factory()->create([
            'option_group_id' => $this->optionGroup->id,
            'name' => 'Second',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/option-groups/{$this->optionGroup->id}/options");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('First', $data[0]['name']);
        $this->assertEquals('Second', $data[1]['name']);
        $this->assertEquals('Third', $data[2]['name']);
    }
}
