<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// @mixin \App\Models\TenantApplication
class TenantApplicationResource extends JsonResource
{
    // リソースを配列に変換する。
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user()?->isAdmin();

        return [
            'id' => $this->id,
            'application_code' => $this->application_code,
            'applicant_name' => $this->applicant_name,
            'applicant_email' => $this->applicant_email,
            'applicant_phone' => $this->applicant_phone,
            'tenant_name' => $this->tenant_name,
            'tenant_address' => $this->tenant_address,
            'business_type' => $this->business_type->value,
            'business_type_label' => $this->business_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'rejection_reason' => $this->rejection_reason,
            'internal_notes' => $this->when((bool) $isAdmin, $this->internal_notes),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ]),
            'created_tenant' => $this->whenLoaded('createdTenant', fn () => [
                'id' => $this->createdTenant->id,
                'name' => $this->createdTenant->name,
                'slug' => $this->createdTenant->slug,
            ]),
            'can_start_review' => $this->canStartReview(),
            'can_be_approved' => $this->canBeApproved(),
            'can_be_rejected' => $this->canBeRejected(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
