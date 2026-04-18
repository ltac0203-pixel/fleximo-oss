<?php

declare(strict_types=1);

namespace App\Services\Fincode;

use App\Enums\PaymentMethod;
use Illuminate\Support\Str;

class FincodeClient
{
    private const IDEMPOTENCY_HEADER = 'Idempotency-Key';

    private const PAYMENT_METHOD_LUMP_SUM = '1';

    private const JOB_CODE_CAPTURE = 'CAPTURE';

    private const REDIRECT_TYPE_STANDARD = '1';

    private const TDS_TYPE_V2 = '2';

    private const DEFAULT_FLAG_ON = '1';

    private const DEFAULT_FLAG_OFF = '0';

    private string $baseUrl;

    private ?string $apiKey;

    public function __construct(private FincodeRequestExecutor $requestExecutor)
    {
        $isProduction = config('fincode.is_production');
        $this->baseUrl = $isProduction
            ? config('fincode.api_url')
            : config('fincode.test_api_url');
        $this->apiKey = config('fincode.api_key');
    }

    public function createCardPayment(array $params): FincodePaymentResponse
    {
        return $this->createPayment(PaymentMethod::Card, $params);
    }

    public function executeCardPayment(string $paymentId, array $params): FincodePaymentResponse
    {
        $requestData = [
            'pay_type' => PaymentMethod::Card->toFincodePayType(),
            'access_id' => $params['access_id'],
            'token' => $params['token'],
            'method' => self::PAYMENT_METHOD_LUMP_SUM,
        ];

        $response = $this->request(
            'PUT',
            "/payments/{$paymentId}",
            $requestData,
            $params['tenant_shop_id'] ?? null
        );

        return FincodePaymentResponse::fromArray($response);
    }

    public function createPayPayPayment(array $params): FincodePaymentResponse
    {
        return $this->createPayment(PaymentMethod::PayPay, $params);
    }

    private function createPayment(PaymentMethod $paymentMethod, array $params): FincodePaymentResponse
    {
        $requestData = [
            'pay_type' => $paymentMethod->toFincodePayType(),
            'job_code' => self::JOB_CODE_CAPTURE,
            'amount' => (string) $params['amount'],
        ];

        // fincode 側の決済ログとアプリ側の Order を紐付けるため、order_id を client_field_1 に埋め込む
        if (isset($params['order_id'])) {
            $requestData['client_field_1'] = (string) $params['order_id'];
        }

        if (isset($params['order_description'])) {
            $requestData['order_description'] = $params['order_description'];
        }

        $idempotencyKey = $this->resolveCreatePaymentIdempotencyKey($params);

        $response = $this->request(
            'POST',
            '/payments',
            $requestData,
            $params['tenant_shop_id'] ?? null,
            [self::IDEMPOTENCY_HEADER => $idempotencyKey]
        );

        return FincodePaymentResponse::fromArray($response);
    }

    public function executePayPayPayment(string $paymentId, array $params): FincodePaymentResponse
    {
        $requestData = [
            'pay_type' => PaymentMethod::PayPay->toFincodePayType(),
            'access_id' => $params['access_id'],
            'redirect_url' => $params['redirect_url'],
            'redirect_type' => self::REDIRECT_TYPE_STANDARD,
        ];

        if (isset($params['customer_id'])) {
            $requestData['customer_id'] = $params['customer_id'];
        }

        if (isset($params['user_agent'])) {
            $requestData['user_agent'] = $params['user_agent'];
        }

        $response = $this->request(
            'PUT',
            "/payments/{$paymentId}",
            $requestData,
            $params['tenant_shop_id'] ?? null
        );

        return FincodePaymentResponse::fromArray($response);
    }

    public function getPayment(string $paymentId, ?string $tenantShopId = null, ?string $payType = null): FincodePaymentResponse
    {
        $response = $this->request('GET', "/payments/{$paymentId}", ['pay_type' => $payType ?? PaymentMethod::Card->toFincodePayType()], $tenantShopId);

        return FincodePaymentResponse::fromArray($response);
    }

    public function executeCardPaymentFor3ds(string $paymentId, array $params): FincodePaymentResponse
    {
        $requestData = [
            'pay_type' => PaymentMethod::Card->toFincodePayType(),
            'access_id' => $params['access_id'],
            'method' => self::PAYMENT_METHOD_LUMP_SUM,
            'tds_type' => self::TDS_TYPE_V2,
            'tds2_type' => self::TDS_TYPE_V2,
        ];

        // 新規カードと保存済みカードで認証パラメータが異なるため、カードの種別を判定して動的に構築する
        if (isset($params['token'])) {
            $requestData['token'] = $params['token'];
        }

        if (isset($params['customer_id']) && isset($params['card_id'])) {
            $requestData['customer_id'] = $params['customer_id'];
            $requestData['card_id'] = $params['card_id'];
        } elseif (isset($params['customer_id'])) {
            $requestData['customer_id'] = $params['customer_id'];
        }

        // 3DS認証イベント通知先とブラウザリダイレクト先を指定する
        if (isset($params['tds2_ret_url'])) {
            $requestData['tds2_ret_url'] = $params['tds2_ret_url'];
            $requestData['return_url'] = $params['tds2_ret_url'];
        }

        return FincodePaymentResponse::fromArray(
            $this->request('PUT', "/payments/{$paymentId}", $requestData, $params['tenant_shop_id'] ?? null)
        );
    }

    public function createCardPaymentWith3ds(array $params): FincodePaymentResponse
    {
        $requestData = [
            'pay_type' => PaymentMethod::Card->toFincodePayType(),
            'job_code' => self::JOB_CODE_CAPTURE,
            'amount' => (string) $params['amount'],
            'tds_type' => self::TDS_TYPE_V2,
            'tds2_type' => self::TDS_TYPE_V2,
        ];

        if (isset($params['tds2_ret_url'])) {
            $requestData['tds2_ret_url'] = $params['tds2_ret_url'];
            $requestData['return_url'] = $params['tds2_ret_url'];
        }

        if (isset($params['order_id'])) {
            $requestData['client_field_1'] = (string) $params['order_id'];
        }

        $idempotencyKey = $this->resolveCreatePaymentIdempotencyKey($params);

        $response = $this->request(
            'POST',
            '/payments',
            $requestData,
            $params['tenant_shop_id'] ?? null,
            [self::IDEMPOTENCY_HEADER => $idempotencyKey]
        );

        return FincodePaymentResponse::fromArray($response);
    }

    public function execute3dsAuthentication(string $accessId, ?string $param = null, ?string $tenantShopId = null, ?string $customerId = null, ?string $cardId = null): FincodePaymentResponse
    {
        $requestData = [];
        if ($customerId !== null && $cardId !== null) {
            $requestData['customer_id'] = $customerId;
            $requestData['card_id'] = $cardId;
        } else {
            $requestData['param'] = $param;
        }

        $response = $this->request(
            'PUT',
            "/secure2/{$accessId}",
            $requestData,
            $tenantShopId
        );

        return FincodePaymentResponse::fromArray($response);
    }

    public function get3dsAuthenticationResult(string $accessId, ?string $tenantShopId = null): FincodePaymentResponse
    {
        $response = $this->request('GET', "/secure2/{$accessId}", [], $tenantShopId);

        return FincodePaymentResponse::fromArray($response);
    }

    public function executePaymentAfter3ds(string $paymentId, string $accessId, ?string $tenantShopId = null): FincodePaymentResponse
    {
        $requestData = [
            'pay_type' => PaymentMethod::Card->toFincodePayType(),
            'access_id' => $accessId,
        ];

        $response = $this->request(
            'PUT',
            "/payments/{$paymentId}/secure",
            $requestData,
            $tenantShopId
        );

        return FincodePaymentResponse::fromArray($response);
    }

    public function createCustomer(array $params): FincodeCustomerResponse
    {
        $requestData = [];

        if (isset($params['name'])) {
            $requestData['name'] = $params['name'];
        }
        if (isset($params['email'])) {
            $requestData['email'] = $params['email'];
        }

        $response = $this->request(
            'POST',
            '/customers',
            $requestData,
            $params['tenant_shop_id'] ?? null
        );

        return FincodeCustomerResponse::fromArray($response);
    }

    public function getCustomer(string $customerId, ?string $tenantShopId = null): FincodeCustomerResponse
    {
        $response = $this->request('GET', "/customers/{$customerId}", [], $tenantShopId);

        return FincodeCustomerResponse::fromArray($response);
    }

    public function registerCard(string $customerId, array $params): FincodeCardResponse
    {
        $requestData = [
            'token' => $params['token'],
        ];

        if (isset($params['default_flag'])) {
            $requestData['default_flag'] = $params['default_flag'] ? self::DEFAULT_FLAG_ON : self::DEFAULT_FLAG_OFF;
        }

        $response = $this->request(
            'POST',
            "/customers/{$customerId}/cards",
            $requestData,
            $params['tenant_shop_id'] ?? null
        );

        return FincodeCardResponse::fromArray($response);
    }

    public function getCards(string $customerId, ?string $tenantShopId = null): array
    {
        $response = $this->request('GET', "/customers/{$customerId}/cards", [], $tenantShopId);

        $cards = [];
        $list = $response['list'] ?? [];
        foreach ($list as $cardData) {
            $cards[] = FincodeCardResponse::fromArray($cardData);
        }

        return $cards;
    }

    public function deleteCard(string $customerId, string $cardId, ?string $tenantShopId = null): bool
    {
        $this->request('DELETE', "/customers/{$customerId}/cards/{$cardId}", [], $tenantShopId);

        return true;
    }

    private function request(
        string $method,
        string $endpoint,
        array $data = [],
        ?string $tenantShopId = null,
        array $extraHeaders = []
    ): array {
        return $this->requestExecutor->execute(
            $method,
            $this->baseUrl.$endpoint,
            $endpoint,
            $this->buildHeaders($tenantShopId, $extraHeaders),
            $data
        );
    }

    private function buildHeaders(?string $tenantShopId, array $extraHeaders): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$this->getApiKey(),
            'Content-Type' => 'application/json',
        ];

        // マルチテナント対応: Tenant-Shop-Idヘッダーを追加
        if ($tenantShopId !== null) {
            $headers['Tenant-Shop-Id'] = $tenantShopId;
        }

        if ($extraHeaders !== []) {
            $headers = array_merge($headers, $extraHeaders);
        }

        return $headers;
    }

    private function getApiKey(): string
    {
        if ($this->apiKey === null) {
            throw new FincodeApiException(null, [], 'Fincode API key is not configured');
        }

        return $this->apiKey;
    }

    private function resolveCreatePaymentIdempotencyKey(array $params): string
    {
        $explicitKey = $params['idempotency_key'] ?? null;
        if (is_string($explicitKey) && trim($explicitKey) !== '') {
            return trim($explicitKey);
        }

        $paymentId = $params['payment_id'] ?? null;
        if ($paymentId !== null) {
            return $this->createDeterministicUuidFromString('fincode-payment-'.$paymentId);
        }

        return (string) Str::uuid();
    }

    private function createDeterministicUuidFromString(string $source): string
    {
        $hash = hash('sha256', $source);

        $segment1 = substr($hash, 0, 8);
        $segment2 = substr($hash, 8, 4);
        $segment3 = substr($hash, 12, 4);
        $segment4 = substr($hash, 16, 4);
        $segment5 = substr($hash, 20, 12);

        // UUIDv4 形式に整形して外部APIの受け入れ互換性を高める
        $segment3 = '4'.substr($segment3, 1);
        $variantNibble = dechex((hexdec($segment4[0]) & 0x3) | 0x8);
        $segment4 = $variantNibble.substr($segment4, 1);

        return strtolower("{$segment1}-{$segment2}-{$segment3}-{$segment4}-{$segment5}");
    }
}
