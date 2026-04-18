<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditAction: string
{
    // 認証
    case Login = 'auth.login';
    case Logout = 'auth.logout';
    case LoginFailed = 'auth.login_failed';
    case PasswordReset = 'auth.password_reset';

    // テナント
    case TenantCreated = 'tenant.created';
    case TenantUpdated = 'tenant.updated';
    case TenantSuspended = 'tenant.suspended';
    case TenantDeleted = 'tenant.deleted';
    case OrderPaused = 'tenant.order_paused';
    case OrderResumed = 'tenant.order_resumed';

    // 注文
    case OrderCancelled = 'order.cancelled';
    case OrderRefunded = 'order.refunded';

    // KDS（キッチンディスプレイシステム）
    case KdsOrderStatusChanged = 'kds.order.status_changed';

    // ユーザー
    case UserSuspended = 'user.suspended';
    case UserDeleted = 'user.deleted';

    // テナントユーザー
    case TenantUserAssigned = 'tenant_user.assigned';
    case TenantUserRemoved = 'tenant_user.removed';
    case TenantUserRoleChanged = 'tenant_user.role_changed';

    // スタッフ管理
    case StaffCreated = 'staff.created';
    case StaffUpdated = 'staff.updated';
    case StaffDeleted = 'staff.deleted';

    // 決済設定
    case PaymentSettingsUpdated = 'payment_settings.updated';

    // 決済カード
    case CardDeleted = 'card.deleted';

    // メニューカテゴリ
    case MenuCategoryCreated = 'menu_category.created';
    case MenuCategoryUpdated = 'menu_category.updated';
    case MenuCategoryDeleted = 'menu_category.deleted';
    case MenuCategoryReordered = 'menu_category.reordered';

    // メニュー商品
    case MenuItemCreated = 'menu_item.created';
    case MenuItemUpdated = 'menu_item.updated';
    case MenuItemDeleted = 'menu_item.deleted';
    case MenuItemSoldOutToggled = 'menu_item.sold_out_toggled';

    // オプショングループ
    case OptionGroupCreated = 'option_group.created';
    case OptionGroupUpdated = 'option_group.updated';
    case OptionGroupDeleted = 'option_group.deleted';

    // オプション
    case OptionCreated = 'option.created';
    case OptionUpdated = 'option.updated';
    case OptionDeleted = 'option.deleted';

    // テナント申し込み
    case TenantApplicationCreated = 'tenant_application.created';
    case TenantApplicationReviewStarted = 'tenant_application.review_started';
    case TenantApplicationApproved = 'tenant_application.approved';
    case TenantApplicationRejected = 'tenant_application.rejected';

    // 顧客管理
    case CustomerSuspended = 'customer.suspended';
    case CustomerBanned = 'customer.banned';
    case CustomerReactivated = 'customer.reactivated';
    case CustomerDataExported = 'customer.data_exported';

    // 異常ログイン検知
    case SuspiciousLoginIpChange = 'auth.suspicious_login.ip_change';
    case SuspiciousLoginFrequency = 'auth.suspicious_login.frequency';
    case SuspiciousLoginNewDevice = 'auth.suspicious_login.new_device';

    // 監査アクションの表示ラベルを取得する。
    public function label(): string
    {
        return match ($this) {
            self::Login => 'ログイン',
            self::Logout => 'ログアウト',
            self::LoginFailed => 'ログイン失敗',
            self::PasswordReset => 'パスワードリセット',
            self::TenantCreated => 'テナント作成',
            self::TenantUpdated => 'テナント更新',
            self::TenantSuspended => 'テナント停止',
            self::TenantDeleted => 'テナント削除',
            self::OrderPaused => '注文受付一時停止',
            self::OrderResumed => '注文受付再開',
            self::OrderCancelled => '注文キャンセル',
            self::OrderRefunded => '注文返金',
            self::KdsOrderStatusChanged => 'KDS注文ステータス変更',
            self::UserSuspended => 'ユーザー停止',
            self::UserDeleted => 'ユーザー削除',
            self::TenantUserAssigned => 'テナントユーザー割り当て',
            self::TenantUserRemoved => 'テナントユーザー削除',
            self::TenantUserRoleChanged => 'テナントユーザーロール変更',
            self::StaffCreated => 'スタッフ作成',
            self::StaffUpdated => 'スタッフ更新',
            self::StaffDeleted => 'スタッフ削除',
            self::PaymentSettingsUpdated => '決済設定更新',
            self::CardDeleted => 'カード削除',
            self::MenuCategoryCreated => 'カテゴリ作成',
            self::MenuCategoryUpdated => 'カテゴリ更新',
            self::MenuCategoryDeleted => 'カテゴリ削除',
            self::MenuCategoryReordered => 'カテゴリ並び替え',
            self::MenuItemCreated => '商品作成',
            self::MenuItemUpdated => '商品更新',
            self::MenuItemDeleted => '商品削除',
            self::MenuItemSoldOutToggled => '売り切れ切替',
            self::OptionGroupCreated => 'オプショングループ作成',
            self::OptionGroupUpdated => 'オプショングループ更新',
            self::OptionGroupDeleted => 'オプショングループ削除',
            self::OptionCreated => 'オプション作成',
            self::OptionUpdated => 'オプション更新',
            self::OptionDeleted => 'オプション削除',
            self::TenantApplicationCreated => 'テナント申し込み作成',
            self::TenantApplicationReviewStarted => 'テナント申し込み審査開始',
            self::TenantApplicationApproved => 'テナント申し込み承認',
            self::TenantApplicationRejected => 'テナント申し込み却下',
            self::CustomerSuspended => '顧客一時停止',
            self::CustomerBanned => '顧客BAN',
            self::CustomerReactivated => '顧客再有効化',
            self::CustomerDataExported => '顧客データエクスポート',
            self::SuspiciousLoginIpChange => '不審なログイン（IP変化）',
            self::SuspiciousLoginFrequency => '不審なログイン（高頻度）',
            self::SuspiciousLoginNewDevice => '不審なログイン（新規デバイス）',
        };
    }

    // 監査アクションの全値を取得する。

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
