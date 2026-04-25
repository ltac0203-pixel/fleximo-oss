<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\TenantDetailResource;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_resource_returns_correct_structure(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Cafe',
            'slug' => 'test-cafe',
            'address' => 'Tokyo Shibuya 1-1-1',
            'email' => 'test@example.com',
            'phone' => '03-1234-5678',
        ]);
        $tenant->load('businessHours');

        $request = Request::create('/api/tenants', 'GET');
        $resource = new TenantResource($tenant);
        $response = $resource->toArray($request);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('slug', $response);
        $this->assertArrayHasKey('address', $response);
        $this->assertArrayHasKey('today_business_hours', $response);
        $this->assertArrayHasKey('is_open', $response);

        $this->assertArrayNotHasKey('email', $response);
        $this->assertArrayNotHasKey('phone', $response);
    }

    public function test_tenant_resource_returns_correct_values(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Cafe',
            'slug' => 'test-cafe',
            'address' => 'Tokyo Shibuya 1-1-1',
        ]);
        $tenant->load('businessHours');

        $request = Request::create('/api/tenants', 'GET');
        $resource = new TenantResource($tenant);
        $response = $resource->toArray($request);

        $this->assertEquals('Test Cafe', $response['name']);
        $this->assertEquals('test-cafe', $response['slug']);
        $this->assertEquals('Tokyo Shibuya 1-1-1', $response['address']);
        $this->assertIsArray($response['today_business_hours']);
    }

    public function test_tenant_resource_handles_no_business_hours(): void
    {
        $tenant = Tenant::factory()->create();
        $tenant->load('businessHours');

        $request = Request::create('/api/tenants', 'GET');
        $resource = new TenantResource($tenant);
        $response = $resource->toArray($request);

        $this->assertIsArray($response['today_business_hours']);
        $this->assertEmpty($response['today_business_hours']);
    }

    public function test_tenant_resource_omits_business_hours_keys_when_relation_not_loaded(): void
    {
        // businessHours リレーションが未 load の場合、is_open / today_business_hours キーは
        // 含まれないことを検証する（lazy loading を防ぎ、フロント側で undefined 判定可能にする）
        $tenant = Tenant::factory()->create();

        $request = Request::create('/api/tenants', 'GET');
        $resource = new TenantResource($tenant);
        $response = $resource->toArray($request);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayNotHasKey('is_open', $response);
        $this->assertArrayNotHasKey('today_business_hours', $response);
    }

    public function test_tenant_detail_resource_includes_contact_information(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Cafe',
            'email' => 'test@example.com',
            'phone' => '03-1234-5678',
        ]);

        $request = Request::create('/api/tenants', 'GET');
        $resource = new TenantDetailResource($tenant);
        $response = $resource->toArray($request);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('slug', $response);

        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('phone', $response);
        $this->assertEquals('test@example.com', $response['email']);
        $this->assertEquals('03-1234-5678', $response['phone']);
    }

    public function test_tenant_detail_resource_handles_empty_contact_info(): void
    {
        $tenant = Tenant::factory()->create([
            'email' => 'placeholder@example.com',
            'phone' => null,
        ]);

        $request = Request::create('/api/tenants', 'GET');
        $resource = new TenantDetailResource($tenant);
        $response = $resource->toArray($request);

        $this->assertEquals('placeholder@example.com', $response['email']);
        $this->assertNull($response['phone']);
    }
}
