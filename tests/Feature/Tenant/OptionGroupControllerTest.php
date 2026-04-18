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

class OptionGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $admin;

    private User $staff;

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
    }

    public function test_tenant_admin_can_list_option_groups(): void
    {
        OptionGroup::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/option-groups');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'required',
                        'min_select',
                        'max_select',
                        'sort_order',
                        'is_active',
                        'options',
                    ],
                ],
            ]);
    }

    public function test_option_groups_include_options(): void
    {
        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Option::factory()->count(3)->create([
            'option_group_id' => $optionGroup->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/tenant/option-groups');

        $response->assertStatus(200);

        $data = $response->json('data.0');
        $this->assertCount(3, $data['options']);
    }

    public function test_tenant_admin_can_create_option_group(): void
    {
        $optionGroupData = [
            'name' => 'サイズ',
            'required' => true,
            'min_select' => 1,
            'max_select' => 1,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/option-groups', $optionGroupData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'サイズ',
                    'required' => true,
                    'min_select' => 1,
                    'max_select' => 1,
                ],
            ]);

        $this->assertDatabaseHas('option_groups', [
            'tenant_id' => $this->tenant->id,
            'name' => 'サイズ',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::OptionGroupCreated->value,
        ]);
    }

    public function test_create_option_group_assigns_next_sort_order_when_not_provided(): void
    {
        OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 1,
        ]);
        OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/option-groups', [
                'name' => 'トッピング',
                'required' => false,
                'min_select' => 0,
                'max_select' => 2,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('option_groups', [
            'tenant_id' => $this->tenant->id,
            'name' => 'トッピング',
            'sort_order' => 3,
        ]);
    }

    public function test_min_select_must_be_less_than_or_equal_to_max_select(): void
    {
        $optionGroupData = [
            'name' => 'サイズ',
            'min_select' => 3,
            'max_select' => 2,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/tenant/option-groups', $optionGroupData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['min_select']);
    }

    public function test_tenant_admin_can_update_option_group(): void
    {
        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '旧名前',
        ]);

        $updateData = [
            'name' => '新名前',
            'required' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/option-groups/{$optionGroup->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => '新名前',
                    'required' => true,
                ],
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditAction::OptionGroupUpdated->value,
        ]);
    }

    public function test_tenant_admin_can_delete_option_group(): void
    {
        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Option::factory()->count(2)->create([
            'option_group_id' => $optionGroup->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/tenant/option-groups/{$optionGroup->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('option_groups', ['id' => $optionGroup->id]);
        $this->assertDatabaseMissing('options', ['option_group_id' => $optionGroup->id]);
    }

    public function test_tenant_staff_cannot_create_option_group(): void
    {
        $optionGroupData = [
            'name' => 'サイズ',
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/tenant/option-groups', $optionGroupData);

        $response->assertStatus(403);
    }

    public function test_cannot_view_other_tenant_option_group(): void
    {
        $otherTenant = Tenant::factory()->create();
        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/tenant/option-groups/{$optionGroup->id}");

        $response->assertStatus(404);
    }

    public function test_cannot_update_other_tenant_option_group(): void
    {
        $otherTenant = Tenant::factory()->create();
        $optionGroup = OptionGroup::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->patchJson("/api/tenant/option-groups/{$optionGroup->id}", [
                'name' => '新名前',
            ]);

        $response->assertStatus(404);
    }
}
