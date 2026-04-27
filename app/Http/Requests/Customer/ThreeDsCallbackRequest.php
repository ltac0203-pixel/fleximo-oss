<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Models\Payment;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class ThreeDsCallbackRequest extends FormRequest
{
    private ?Payment $payment = null;

    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'integer'],
            'param' => ['required', 'string'],
            'event' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_id.required' => __('validation.custom.payment_id_required'),
            'payment_id.integer' => __('validation.custom.payment_id_integer'),
            'param.required' => __('validation.custom.param_required'),
            'param.string' => __('validation.custom.param_string'),
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
                $validator->errors()->add('payment_id', __('validation.custom.payment_id_invalid'));

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
                'payment_id' => __('validation.custom.payment_id_invalid'),
            ]);
        }

        return $this->payment;
    }

    public function getParam(): string
    {
        return $this->input('param');
    }

    public function getEvent(): ?string
    {
        return $this->input('event');
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

    // 3DS コールバックはテナント文脈を持たず戻るため、Payment の所有確認は order.user_id に寄せる。
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
