<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\CardRegistrationException;
use PHPUnit\Framework\TestCase;

class CardRegistrationExceptionTest extends TestCase
{
    public function test_returns_user_friendly_message_for_invalid_card_number(): void
    {
        $exception = new CardRegistrationException('E01100101');

        $this->assertEquals('カード番号が正しくありません。', $exception->getUserMessage());
    }

    public function test_returns_user_friendly_message_for_invalid_expiry(): void
    {
        $exception = new CardRegistrationException('E01100102');

        $this->assertEquals('カード有効期限が正しくありません。', $exception->getUserMessage());
    }

    public function test_returns_user_friendly_message_for_invalid_cvv(): void
    {
        $exception = new CardRegistrationException('E01100103');

        $this->assertEquals('セキュリティコードが正しくありません。', $exception->getUserMessage());
    }

    public function test_returns_user_friendly_message_for_card_not_available(): void
    {
        $exception = new CardRegistrationException('E01100201');

        $this->assertEquals('このカードは利用できません。別のカードをお試しください。', $exception->getUserMessage());
    }

    public function test_returns_user_friendly_message_for_expired_card(): void
    {
        $exception = new CardRegistrationException('E01100202');

        $this->assertEquals('カードの有効期限が切れています。', $exception->getUserMessage());
    }

    public function test_returns_user_friendly_message_for_invalid_token(): void
    {
        $exception = new CardRegistrationException('E01200101');

        $this->assertEquals('カードトークンが無効です。もう一度お試しください。', $exception->getUserMessage());
    }

    public function test_returns_default_message_for_unknown_error_code(): void
    {
        $exception = new CardRegistrationException('E99999999', 'カスタムエラー');

        $this->assertEquals('カスタムエラー', $exception->getUserMessage());
    }

    public function test_returns_default_message_when_no_error_code(): void
    {
        $exception = new CardRegistrationException(null, 'カード操作に失敗しました');

        $this->assertEquals('カード操作に失敗しました', $exception->getUserMessage());
    }

    public function test_stores_fincode_error_code_internally(): void
    {
        $exception = new CardRegistrationException('E01100101');

        $this->assertEquals('E01100101', $exception->fincodeErrorCode);
    }

    public function test_token_consumed_defaults_to_false(): void
    {
        $exception = new CardRegistrationException('E01100101');

        $this->assertFalse($exception->tokenConsumed);
    }

    public function test_token_consumed_can_be_set_to_true(): void
    {
        $exception = new CardRegistrationException('E01100101', 'カード登録に失敗しました。', tokenConsumed: true);

        $this->assertTrue($exception->tokenConsumed);
    }
}
