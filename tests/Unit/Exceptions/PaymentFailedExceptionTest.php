<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\PaymentFailedException;
use PHPUnit\Framework\TestCase;

class PaymentFailedExceptionTest extends TestCase
{
    public function test_returns_user_message_for_invalid_card_number(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100101');
        $this->assertEquals('カード番号が正しくありません。', $e->getUserMessage());
    }

    public function test_returns_user_message_for_invalid_expiry(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100102');
        $this->assertEquals('カード有効期限が正しくありません。', $e->getUserMessage());
    }

    public function test_returns_user_message_for_invalid_cvv(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100103');
        $this->assertEquals('セキュリティコードが正しくありません。', $e->getUserMessage());
    }

    public function test_returns_user_message_for_invalid_card_holder(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100104');
        $this->assertEquals('カード名義人が正しくありません。', $e->getUserMessage());
    }

    public function test_returns_user_message_for_card_not_available(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100201');
        $this->assertEquals('カードが利用できません。別のカードをお試しください。', $e->getUserMessage());
    }

    public function test_returns_user_message_for_expired_card(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100202');
        $this->assertEquals('カードの有効期限が切れています。', $e->getUserMessage());
    }

    public function test_returns_user_message_for_limit_exceeded(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100203');
        $this->assertEquals('ご利用限度額を超えています。', $e->getUserMessage());
    }

    public function test_returns_user_message_for_payment_cancelled(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100301');
        $this->assertEquals('決済がキャンセルされました。', $e->getUserMessage());
    }

    public function test_returns_user_message_for_payment_rejected(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100302');
        $this->assertEquals('決済が拒否されました。', $e->getUserMessage());
    }

    public function test_returns_default_message_for_unknown_error_code(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E99999999');
        $this->assertEquals('決済処理に失敗しました', $e->getUserMessage());
    }

    public function test_returns_custom_message_when_provided(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: null, message: 'カスタムエラー');
        $this->assertEquals('カスタムエラー', $e->getUserMessage());
    }

    public function test_stores_fincode_error_code(): void
    {
        $e = new PaymentFailedException(fincodeErrorCode: 'E01100101');
        $this->assertEquals('E01100101', $e->fincodeErrorCode);
    }

    public function test_payment_defaults_to_null(): void
    {
        $e = new PaymentFailedException;
        $this->assertNull($e->payment);
    }

    public function test_fincode_error_code_defaults_to_null(): void
    {
        $e = new PaymentFailedException;
        $this->assertNull($e->fincodeErrorCode);
    }
}
