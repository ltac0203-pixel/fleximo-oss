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
            'cart_id.required' => 'カートIDは必須です。',
            'cart_id.integer' => 'カートIDは数値で指定してください。',
            'cart_id.exists' => '指定されたカートは存在しません。',
            'payment_method.required' => '決済方法は必須です。',
            'payment_method.string' => '決済方法は文字列で指定してください。',
            'payment_method.enum' => '無効な決済方法です。',
            'card_id.integer' => 'カードIDは数値で指定してください。',
            'card_id.exists' => '指定されたカードは存在しません。',
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
