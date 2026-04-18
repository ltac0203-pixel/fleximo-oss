<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
