<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class UserAlreadyAssignedToTenantException extends Exception
{
    public function __construct(
        public readonly User $user,
        string $message = '',
        int $code = 0,
        ?Exception $previous = null
    ) {
        if (empty($message)) {
            $message = 'このユーザーは既に別のテナントに所属しています。1ユーザーは1テナントにのみ所属できます。';
        }

        parent::__construct($message, $code, $previous);
    }

    public function render()
    {
        Log::warning('ユーザーが既に別テナントに所属', [
            'user_id' => $this->user->id,
            'current_tenant_id' => $this->user->tenantUser?->tenant_id,
        ]);

        return response()->json([
            'error' => 'USER_ALREADY_ASSIGNED',
            'message' => $this->getMessage(),
        ], 409);
    }
}
