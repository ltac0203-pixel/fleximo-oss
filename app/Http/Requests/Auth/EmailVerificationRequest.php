<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class EmailVerificationRequest extends FormRequest
{
    protected ?User $verificationUser = null;

    public function authorize(): bool
    {
        $this->verificationUser = User::find($this->route('id'));

        if (! $this->verificationUser) {
            return false;
        }

        // ログイン中の別ユーザーが他人のメールを認証することを防ぐ
        if ($this->user() && $this->user()->isNot($this->verificationUser)) {
            return false;
        }

        return hash_equals(
            sha1($this->verificationUser->getEmailForVerification()),
            (string) $this->route('hash')
        );
    }

    public function rules(): array
    {
        return [];
    }

    public function userToVerify(): User
    {
        return $this->verificationUser;
    }
}
