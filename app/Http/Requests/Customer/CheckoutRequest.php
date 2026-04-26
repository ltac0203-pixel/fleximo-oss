<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Scopes\TenantScope;
use App\Rules\CardOwnershipRule;
use App\Rules\CartOwnershipRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    private ?Cart $cart = null;

    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    public function rules(): array
    {
        return [
            'cart_id' => ['required', 'integer', 'exists:carts,id', new CartOwnershipRule],
            'payment_method' => ['required', 'string', Rule::enum(PaymentMethod::class)],
            'card_id' => [
                'nullable',
                'integer',
                'exists:fincode_cards,id',
                new CardOwnershipRule($this->integer('cart_id')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cart_id.required' => __('validation.custom.cart_id_required'),
            'cart_id.integer' => __('validation.custom.cart_id_integer'),
            'cart_id.exists' => __('validation.custom.cart_id_exists'),
            'payment_method.required' => __('validation.custom.payment_method_required'),
            'payment_method.string' => __('validation.custom.payment_method_required'),
            'payment_method.enum' => __('validation.custom.payment_method_in'),
            'card_id.integer' => __('validation.custom.card_id_integer'),
            'card_id.exists' => __('validation.custom.card_id_required'),
        ];
    }

    public function getCart(): Cart
    {
        if ($this->cart === null) {
            $this->cart = Cart::withoutGlobalScope(TenantScope::class)
                ->withFullRelations()
                ->findOrFail($this->input('cart_id'));
        }

        return $this->cart;
    }

    public function getPaymentMethod(): PaymentMethod
    {
        return PaymentMethod::from($this->input('payment_method'));
    }

    public function getCardId(): ?int
    {
        $cardId = $this->input('card_id');

        return $cardId !== null ? (int) $cardId : null;
    }
}
