<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class FinalizePaymentRequest extends FormRequest
{
    private const INVALID_PAYMENT_MESSAGE = '指定された決済は無効です。';

    private ?Payment $payment = null;

    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'integer'],
            'token' => ['nullable', 'string'],
            'save_card' => ['nullable', 'boolean'],
            'save_as_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required' => '決済IDは必須です。',
            'payment_id.integer' => '決済IDは数値で指定してください。',
            'token.string' => 'カードトークンは文字列で指定してください。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $payment = $this->ownedPaymentQuery()->find($this->input('payment_id'));

            if (! $payment) {
                $validator->errors()->add('payment_id', self::INVALID_PAYMENT_MESSAGE);

                return;
            }

            $this->payment = $payment;
        });
    }

    public function getPayment(): Payment
    {
        if ($this->payment === null) {
            $this->payment = $this->ownedPaymentQuery()
                ->find($this->input('payment_id'));
        }

        if ($this->payment === null) {
            throw ValidationException::withMessages([
                'payment_id' => self::INVALID_PAYMENT_MESSAGE,
            ]);
        }

        return $this->payment;
    }

    public function getToken(): ?string
    {
        return $this->input('token');
    }

    public function getSaveCard(): bool
    {
        return (bool) $this->input('save_card', false);
    }

    public function getSaveAsDefault(): bool
    {
        return (bool) $this->input('save_as_default', false);
    }

    /**
     * @return Builder<Payment>
     */
    private function paymentQuery(): Builder
    {
        return Payment::withoutGlobalScope(TenantScope::class)
            ->with([
                'tenant',
                'order' => fn ($query) => $query->withoutGlobalScope(TenantScope::class),
            ]);
    }

    // 顧客の決済完了フローはテナント配下 URL ではないため、TenantScope ではなく order.user_id で所有確認する。
    /**
     * @return Builder<Payment>
     */
    private function ownedPaymentQuery(): Builder
    {
        $authId = $this->user()?->getAuthIdentifier();
        $authUserId = is_numeric($authId) ? (int) $authId : null;

        if ($authUserId === null) {
            return $this->paymentQuery()->whereKey(-1);
        }

        return $this->paymentQuery()
            ->whereHas('order', fn (Builder $query) => $query
                ->withoutGlobalScope(TenantScope::class)
                ->where('user_id', $authUserId));
    }
}
