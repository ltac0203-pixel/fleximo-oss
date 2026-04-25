--
-- データベース: `xs946644_fleximo`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `analytics_cache`
--

CREATE TABLE `analytics_cache` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'テナントID（NULLはプラットフォーム全体）',
  `metric_type` varchar(50) NOT NULL COMMENT 'メトリクスタイプ',
  `date` date NOT NULL COMMENT '集計日',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '集計データ' CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '実行ユーザー',
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '対象テナント',
  `action` varchar(100) NOT NULL COMMENT 'アクション名',
  `auditable_type` varchar(255) DEFAULT NULL COMMENT '対象モデルクラス',
  `auditable_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '対象モデルID',
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '変更前の値' CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '変更後の値' CHECK (json_valid(`new_values`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '追加メタデータ' CHECK (json_valid(`metadata`)),
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IPアドレス',
  `user_agent` text DEFAULT NULL COMMENT 'ユーザーエージェント',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'ユーザー',
  `tenant_id` bigint(20) UNSIGNED NOT NULL COMMENT '対象テナント',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` smallint(5) UNSIGNED NOT NULL DEFAULT 1 COMMENT '数量',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `cart_item_options`
--

CREATE TABLE `cart_item_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_item_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `option_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `favorite_tenants`
--

CREATE TABLE `favorite_tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `fincode_cards`
--

CREATE TABLE `fincode_cards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fincode_customer_id` bigint(20) UNSIGNED NOT NULL,
  `fincode_card_id` varchar(100) NOT NULL,
  `card_no_display` varchar(20) NOT NULL,
  `brand` varchar(20) DEFAULT NULL,
  `expire` varchar(10) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `fincode_customers`
--

CREATE TABLE `fincode_customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `fincode_customer_id` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `hourly_order_stats`
--

CREATE TABLE `hourly_order_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL COMMENT '集計日',
  `hour` tinyint(3) UNSIGNED NOT NULL COMMENT '時間（0-23）',
  `order_count` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '注文数',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00 COMMENT '売上合計',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `idempotency_keys`
--

CREATE TABLE `idempotency_keys` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `key` varchar(36) NOT NULL COMMENT 'Idempotency key (UUIDv4)',
  `route_name` varchar(100) NOT NULL COMMENT 'Route name for scoping',
  `request_method` varchar(10) NOT NULL COMMENT 'HTTP method',
  `request_hash` varchar(64) NOT NULL COMMENT 'SHA-256 hash of request',
  `response_body` longtext DEFAULT NULL COMMENT 'Cached response body',
  `response_status` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'Cached response status',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Idempotency expiration time',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `menu_categories`
--

CREATE TABLE `menu_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'カテゴリ名',
  `slug` varchar(150) NOT NULL COMMENT 'URLスラッグ',
  `description` text DEFAULT NULL COMMENT '説明',
  `image_url` varchar(500) DEFAULT NULL COMMENT '画像URL',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '表示順',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `menu_items`
--

CREATE TABLE `menu_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT '商品名',
  `description` text DEFAULT NULL COMMENT '説明',
  `price` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '価格（税込）',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `is_sold_out` tinyint(1) NOT NULL DEFAULT 0 COMMENT '売切フラグ',
  `available_days` tinyint(3) UNSIGNED NOT NULL DEFAULT 127 COMMENT '販売曜日ビットマスク',
  `available_from` time DEFAULT NULL COMMENT '販売開始時刻',
  `available_until` time DEFAULT NULL COMMENT '販売終了時刻',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '表示順',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `allergens` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '特定原材料8品目ビットマスク',
  `allergen_advisories` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '推奨表示20品目ビットマスク',
  `allergen_note` text DEFAULT NULL COMMENT 'アレルゲン自由記述（コンタミ注意等）',
  `nutrition_info` longtext DEFAULT NULL COMMENT '栄養成分JSON（energy,protein,fat,carbohydrate,salt）'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `menu_item_categories`
--

CREATE TABLE `menu_item_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `menu_category_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'カテゴリ内表示順',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `menu_item_option_groups`
--

CREATE TABLE `menu_item_option_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `option_group_id` bigint(20) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '表示順',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `options`
--

CREATE TABLE `options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `option_group_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL COMMENT 'テナントID',
  `name` varchar(100) NOT NULL COMMENT 'オプション名（例: Mサイズ、Lサイズ）',
  `price` int(11) NOT NULL DEFAULT 0 COMMENT '追加価格（マイナス値可）',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '表示順',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `option_groups`
--

CREATE TABLE `option_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'グループ名（例: サイズ、トッピング）',
  `required` tinyint(1) NOT NULL DEFAULT 0 COMMENT '必須選択フラグ',
  `min_select` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最小選択数',
  `max_select` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '最大選択数',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT '表示順',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '注文者',
  `tenant_id` bigint(20) UNSIGNED NOT NULL COMMENT '注文先テナント',
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '決済ID',
  `order_code` char(4) NOT NULL COMMENT '表示用注文番号（例: A123）',
  `business_date` date NOT NULL COMMENT '営業日',
  `status` varchar(20) NOT NULL DEFAULT 'pending_payment' COMMENT '注文ステータス',
  `total_amount` int(10) UNSIGNED NOT NULL COMMENT '合計金額',
  `paid_at` timestamp NULL DEFAULT NULL COMMENT '決済完了日時',
  `accepted_at` timestamp NULL DEFAULT NULL COMMENT '受付日時',
  `in_progress_at` timestamp NULL DEFAULT NULL COMMENT '調理開始日時',
  `ready_at` timestamp NULL DEFAULT NULL COMMENT '準備完了日時',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '完了日時',
  `cancelled_at` timestamp NULL DEFAULT NULL COMMENT 'キャンセル日時',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '元メニュー商品ID（参照用）',
  `name` varchar(255) NOT NULL COMMENT '商品名（スナップショット）',
  `price` int(10) UNSIGNED NOT NULL COMMENT '単価（スナップショット）',
  `quantity` smallint(5) UNSIGNED NOT NULL COMMENT '数量',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `order_item_options`
--

CREATE TABLE `order_item_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `option_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '元オプションID（参照用）',
  `name` varchar(100) NOT NULL COMMENT 'オプション名（スナップショット）',
  `price` int(11) NOT NULL COMMENT '追加価格（スナップショット）',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `order_number_sequences`
--

CREATE TABLE `order_number_sequences` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `business_date` date NOT NULL COMMENT '営業日',
  `last_sequence` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '最終シーケンス番号',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(50) NOT NULL DEFAULT 'fincode' COMMENT '決済プロバイダー',
  `method` varchar(20) NOT NULL COMMENT '決済方法',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '決済ステータス',
  `amount` int(10) UNSIGNED NOT NULL COMMENT '決済金額',
  `fincode_id` varchar(100) DEFAULT NULL COMMENT 'fincode決済ID',
  `fincode_access_id` varchar(100) DEFAULT NULL COMMENT 'fincodeアクセスID',
  `fincode_customer_id` varchar(255) DEFAULT NULL COMMENT 'fincode顧客ID',
  `redirect_url` varchar(500) DEFAULT NULL COMMENT 'リダイレクトURL（PayPay/3Dセキュア）',
  `tds_trans_result` varchar(50) DEFAULT NULL COMMENT '3DS認証結果',
  `tds_challenge_url` varchar(500) DEFAULT NULL COMMENT '3DSチャレンジURL',
  `fincode_card_id` varchar(100) DEFAULT NULL COMMENT 'fincode カードID',
  `error_code` varchar(50) DEFAULT NULL COMMENT 'エラーコード',
  `error_message` text DEFAULT NULL COMMENT 'エラーメッセージ',
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '追加メタデータ' CHECK (json_valid(`metadata`)),
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '決済完了日時',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `tenants`
--

CREATE TABLE `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'テナント名',
  `slug` varchar(100) NOT NULL COMMENT 'URLスラッグ',
  `email` varchar(255) NOT NULL COMMENT '連絡先メール',
  `phone` varchar(20) DEFAULT NULL COMMENT '電話番号',
  `address` varchar(255) DEFAULT NULL COMMENT '住所',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
  `is_order_paused` tinyint(1) NOT NULL DEFAULT 0 COMMENT '注文受付一時停止フラグ',
  `order_paused_at` timestamp NULL DEFAULT NULL COMMENT '注文受付一時停止開始日時',
  `is_approved` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'テナント承認フラグ',
  `status` enum('active','inactive','suspended','pending','rejected') DEFAULT 'active',
  `fincode_shop_id` varchar(255) DEFAULT NULL COMMENT 'fincode テナントショップID（マルチテナント決済用）',
  `platform_fee_rate_bps` smallint(5) UNSIGNED DEFAULT NULL COMMENT 'プラットフォーム手数料率（bps）',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `tenant_business_hours`
--

CREATE TABLE `tenant_business_hours` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `weekday` tinyint(3) UNSIGNED NOT NULL COMMENT '曜日 (0=日..6=土)',
  `open_time` time NOT NULL COMMENT '開店時間',
  `close_time` time NOT NULL COMMENT '閉店時間',
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '表示順',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `tenant_applications`
--

CREATE TABLE `tenant_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `application_code` varchar(20) NOT NULL COMMENT '申し込み番号（TAPP-XXXXXXXX）',
  `applicant_name` varchar(100) NOT NULL COMMENT '申請者名',
  `applicant_email` varchar(255) NOT NULL COMMENT '申請者メール',
  `applicant_phone` varchar(20) NOT NULL COMMENT '申請者電話番号',
  `tenant_name` varchar(100) NOT NULL COMMENT '店舗名',
  `tenant_address` varchar(255) DEFAULT NULL COMMENT '住所',
  `business_type` varchar(50) NOT NULL COMMENT '業種',
  `status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'ステータス',
  `rejection_reason` text DEFAULT NULL COMMENT '却下理由',
  `internal_notes` text DEFAULT NULL COMMENT '管理者メモ',
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT '審査者',
  `reviewed_at` timestamp NULL DEFAULT NULL COMMENT '審査日時',
  `created_tenant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '作成されたテナントID',
  `applicant_user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '申請者ユーザーID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `tenant_users`
--

CREATE TABLE `tenant_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff' COMMENT 'テナント内ロール',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT '氏名',
  `email` varchar(255) NOT NULL COMMENT 'メールアドレス',
  `email_verified_at` timestamp NULL DEFAULT NULL COMMENT 'メール認証日時',
  `password` varchar(255) NOT NULL COMMENT 'パスワードハッシュ',
  `role` varchar(255) NOT NULL DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `account_status` varchar(20) NOT NULL DEFAULT 'active' COMMENT 'アカウントステータス (active/suspended/banned)',
  `account_status_reason` text DEFAULT NULL COMMENT 'ステータス変更理由',
  `account_status_changed_at` timestamp NULL DEFAULT NULL COMMENT 'ステータス変更日時',
  `account_status_changed_by` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ステータス変更者',
  `phone` varchar(20) DEFAULT NULL COMMENT '電話番号',
  `last_login_at` timestamp NULL DEFAULT NULL COMMENT '最終ログイン日時',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `webhook_logs`
--

CREATE TABLE `webhook_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '対象テナント',
  `provider` varchar(50) NOT NULL COMMENT 'プロバイダー（fincode）',
  `fincode_id` varchar(100) DEFAULT NULL COMMENT 'fincode固有ID（冪等性チェック用）',
  `event_type` varchar(100) NOT NULL COMMENT 'イベントタイプ',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '受信ペイロード' CHECK (json_valid(`payload`)),
  `processed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '処理済みフラグ',
  `processed_at` timestamp NULL DEFAULT NULL COMMENT '処理日時',
  `error_message` text DEFAULT NULL COMMENT 'エラーメッセージ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) UNSIGNED NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `analytics_cache`
--
ALTER TABLE `analytics_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `analytics_cache_unique` (`tenant_id`,`metric_type`,`date`),
  ADD KEY `analytics_cache_tenant_metric_index` (`tenant_id`,`metric_type`),
  ADD KEY `analytics_cache_date_index` (`date`);

--
-- テーブルのインデックス `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_user_id_index` (`user_id`),
  ADD KEY `audit_logs_tenant_id_index` (`tenant_id`),
  ADD KEY `audit_logs_action_index` (`action`),
  ADD KEY `audit_logs_auditable_index` (`auditable_type`,`auditable_id`),
  ADD KEY `audit_logs_created_at_index` (`created_at`);

--
-- テーブルのインデックス `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- テーブルのインデックス `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

-- テーブルのインデックス `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `carts_user_tenant_unique` (`user_id`,`tenant_id`),
  ADD KEY `carts_tenant_id_index` (`tenant_id`);

--
-- テーブルのインデックス `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_items_cart_id_index` (`cart_id`),
  ADD KEY `cart_items_menu_item_id_index` (`menu_item_id`),
  ADD KEY `cart_items_tenant_id_index` (`tenant_id`);

--
-- テーブルのインデックス `cart_item_options`
--
ALTER TABLE `cart_item_options`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cart_item_options_unique` (`cart_item_id`,`option_id`),
  ADD KEY `cart_item_options_option_id_index` (`option_id`),
  ADD KEY `cart_item_options_tenant_id_index` (`tenant_id`);

--
-- テーブルのインデックス `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- テーブルのインデックス `favorite_tenants`
--
ALTER TABLE `favorite_tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `favorite_tenants_user_id_tenant_id_unique` (`user_id`,`tenant_id`),
  ADD KEY `favorite_tenants_tenant_id_index` (`tenant_id`);

--
-- テーブルのインデックス `fincode_cards`
--
ALTER TABLE `fincode_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fincode_cards_fincode_customer_id_foreign` (`fincode_customer_id`),
  ADD KEY `fincode_cards_fincode_card_id_index` (`fincode_card_id`);

--
-- テーブルのインデックス `fincode_customers`
--
ALTER TABLE `fincode_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fincode_customers_user_id_tenant_id_unique` (`user_id`,`tenant_id`),
  ADD KEY `fincode_customers_tenant_id_foreign` (`tenant_id`),
  ADD KEY `fincode_customers_fincode_customer_id_index` (`fincode_customer_id`);

--
-- テーブルのインデックス `hourly_order_stats`
--
ALTER TABLE `hourly_order_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hourly_order_stats_unique` (`tenant_id`,`date`,`hour`),
  ADD KEY `hourly_order_stats_tenant_date_index` (`tenant_id`,`date`);

--
-- テーブルのインデックス `idempotency_keys`
--
ALTER TABLE `idempotency_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idempotency_keys_user_id_key_route_name_unique` (`user_id`,`key`,`route_name`),
  ADD KEY `idempotency_keys_key_route_name_index` (`key`,`route_name`),
  ADD KEY `idempotency_keys_expires_at_index` (`expires_at`);

--
-- テーブルのインデックス `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- テーブルのインデックス `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_categories_tenant_slug_unique` (`tenant_id`,`slug`),
  ADD KEY `menu_categories_sort_order_index` (`tenant_id`,`sort_order`),
  ADD KEY `menu_categories_is_active_index` (`tenant_id`,`is_active`);

--
-- テーブルのインデックス `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_items_sort_order_index` (`tenant_id`,`sort_order`),
  ADD KEY `menu_items_tenant_id_index` (`tenant_id`),
  ADD KEY `menu_items_tenant_id_is_active_index` (`tenant_id`,`is_active`);

--
-- テーブルのインデックス `menu_item_categories`
--
ALTER TABLE `menu_item_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_item_categories_unique` (`menu_item_id`,`menu_category_id`),
  ADD KEY `menu_item_categories_category_id_foreign` (`menu_category_id`);

--
-- テーブルのインデックス `menu_item_option_groups`
--
ALTER TABLE `menu_item_option_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_item_option_groups_unique` (`menu_item_id`,`option_group_id`),
  ADD KEY `menu_item_option_groups_option_group_id_foreign` (`option_group_id`);

--
-- テーブルのインデックス `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`);

--
-- テーブルのインデックス `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `options_option_group_id_index` (`option_group_id`),
  ADD KEY `options_option_group_id_is_active_index` (`option_group_id`,`is_active`),
  ADD KEY `options_tenant_id_index` (`tenant_id`),
  ADD KEY `options_tenant_id_is_active_index` (`tenant_id`,`is_active`);

--
-- テーブルのインデックス `option_groups`
--
ALTER TABLE `option_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `option_groups_tenant_id_index` (`tenant_id`),
  ADD KEY `option_groups_tenant_id_is_active_index` (`tenant_id`,`is_active`);

--
-- テーブルのインデックス `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_tenant_business_code_unique` (`tenant_id`,`business_date`,`order_code`),
  ADD KEY `orders_status_index` (`tenant_id`,`status`,`updated_at`),
  ADD KEY `orders_payment_id_foreign` (`payment_id`),
  ADD KEY `orders_tenant_id_created_at_index` (`tenant_id`,`created_at`),
  ADD KEY `orders_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `orders_tenant_business_date_status_index` (`tenant_id`,`business_date`,`status`),
  ADD KEY `orders_created_at_index` (`created_at`),
  ADD KEY `orders_business_date_status_index` (`business_date`,`status`),
  ADD KEY `orders_tenant_user_business_date_index` (`tenant_id`,`user_id`,`business_date`);

--
-- テーブルのインデックス `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_index` (`order_id`),
  ADD KEY `order_items_tenant_id_index` (`tenant_id`);

--
-- テーブルのインデックス `order_item_options`
--
ALTER TABLE `order_item_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_item_options_order_item_id_index` (`order_item_id`),
  ADD KEY `order_item_options_tenant_id_index` (`tenant_id`),
  ADD KEY `order_item_options_option_id_foreign` (`option_id`);

--
-- テーブルのインデックス `order_number_sequences`
--
ALTER TABLE `order_number_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number_sequences_unique` (`tenant_id`,`business_date`);

--
-- テーブルのインデックス `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_order_id_index` (`order_id`),
  ADD KEY `payments_tenant_id_index` (`tenant_id`);

--
-- テーブルのインデックス `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- テーブルのインデックス `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- テーブルのインデックス `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenants_slug_unique` (`slug`),
  ADD KEY `tenants_is_active_index` (`is_active`),
  ADD KEY `tenants_fincode_shop_id_index` (`fincode_shop_id`),
  ADD KEY `idx_tenants_status` (`status`);

--
-- テーブルのインデックス `tenant_business_hours`
--
ALTER TABLE `tenant_business_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_business_hours_weekday_sort_index` (`tenant_id`,`weekday`,`sort_order`);

--
-- テーブルのインデックス `tenant_applications`
--
ALTER TABLE `tenant_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenant_applications_application_code_unique` (`application_code`),
  ADD KEY `tenant_applications_status_index` (`status`),
  ADD KEY `tenant_applications_created_at_index` (`created_at`),
  ADD KEY `tenant_applications_reviewed_by_foreign` (`reviewed_by`),
  ADD KEY `tenant_applications_created_tenant_id_foreign` (`created_tenant_id`),
  ADD KEY `tenant_applications_applicant_user_id_index` (`applicant_user_id`);

--
-- テーブルのインデックス `tenant_users`
--
ALTER TABLE `tenant_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenant_users_tenant_user_unique` (`tenant_id`,`user_id`),
  ADD KEY `tenant_users_user_id_foreign` (`user_id`);

--
-- テーブルのインデックス `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`),
  ADD KEY `users_account_status_index` (`account_status`);

--
-- テーブルのインデックス `webhook_logs`
--
ALTER TABLE `webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `webhook_logs_provider_event_fincode_unique` (`provider`,`event_type`,`fincode_id`),
  ADD KEY `webhook_logs_tenant_id_index` (`tenant_id`),
  ADD KEY `webhook_logs_processed_index` (`processed`),
  ADD KEY `webhook_logs_created_at_index` (`created_at`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `analytics_cache`
--
ALTER TABLE `analytics_cache`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- テーブルの AUTO_INCREMENT `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `cart_item_options`
--
ALTER TABLE `cart_item_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `favorite_tenants`
--
ALTER TABLE `favorite_tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `fincode_cards`
--
ALTER TABLE `fincode_cards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `fincode_customers`
--
ALTER TABLE `fincode_customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `hourly_order_stats`
--
ALTER TABLE `hourly_order_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `idempotency_keys`
--
ALTER TABLE `idempotency_keys`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- テーブルの AUTO_INCREMENT `menu_categories`
--
ALTER TABLE `menu_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `menu_item_categories`
--
ALTER TABLE `menu_item_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `menu_item_option_groups`
--
ALTER TABLE `menu_item_option_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- テーブルの AUTO_INCREMENT `options`
--
ALTER TABLE `options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `option_groups`
--
ALTER TABLE `option_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `order_item_options`
--
ALTER TABLE `order_item_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `order_number_sequences`
--
ALTER TABLE `order_number_sequences`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- テーブルの AUTO_INCREMENT `tenant_business_hours`
--
ALTER TABLE `tenant_business_hours`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `tenant_applications`
--
ALTER TABLE `tenant_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- テーブルの AUTO_INCREMENT `tenant_users`
--
ALTER TABLE `tenant_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- テーブルの AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- テーブルの AUTO_INCREMENT `webhook_logs`
--
ALTER TABLE `webhook_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `analytics_cache`
--
ALTER TABLE `analytics_cache`
  ADD CONSTRAINT `analytics_cache_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `cart_item_options`
--
ALTER TABLE `cart_item_options`
  ADD CONSTRAINT `cart_item_options_cart_item_id_foreign` FOREIGN KEY (`cart_item_id`) REFERENCES `cart_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_item_options_option_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `options` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_item_options_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `fincode_cards`
--
ALTER TABLE `fincode_cards`
  ADD CONSTRAINT `fincode_cards_fincode_customer_id_foreign` FOREIGN KEY (`fincode_customer_id`) REFERENCES `fincode_customers` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `fincode_customers`
--
ALTER TABLE `fincode_customers`
  ADD CONSTRAINT `fincode_customers_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fincode_customers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `favorite_tenants`
--
ALTER TABLE `favorite_tenants`
  ADD CONSTRAINT `favorite_tenants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorite_tenants_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `hourly_order_stats`
--
ALTER TABLE `hourly_order_stats`
  ADD CONSTRAINT `hourly_order_stats_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `idempotency_keys`
--
ALTER TABLE `idempotency_keys`
  ADD CONSTRAINT `idempotency_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `menu_categories`
--
ALTER TABLE `menu_categories`
  ADD CONSTRAINT `menu_categories_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `menu_item_categories`
--
ALTER TABLE `menu_item_categories`
  ADD CONSTRAINT `menu_item_categories_category_id_foreign` FOREIGN KEY (`menu_category_id`) REFERENCES `menu_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_item_categories_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `menu_item_option_groups`
--
ALTER TABLE `menu_item_option_groups`
  ADD CONSTRAINT `menu_item_option_groups_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_item_option_groups_option_group_id_foreign` FOREIGN KEY (`option_group_id`) REFERENCES `option_groups` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `options_option_group_id_foreign` FOREIGN KEY (`option_group_id`) REFERENCES `option_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `options_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `option_groups`
--
ALTER TABLE `option_groups`
  ADD CONSTRAINT `option_groups_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `order_item_options`
--
ALTER TABLE `order_item_options`
  ADD CONSTRAINT `order_item_options_option_id_foreign` FOREIGN KEY (`option_id`) REFERENCES `options` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_item_options_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_item_options_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `order_number_sequences`
--
ALTER TABLE `order_number_sequences`
  ADD CONSTRAINT `order_number_sequences_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `tenant_applications`
--
ALTER TABLE `tenant_applications`
  ADD CONSTRAINT `tenant_applications_applicant_user_id_foreign` FOREIGN KEY (`applicant_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tenant_applications_created_tenant_id_foreign` FOREIGN KEY (`created_tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tenant_applications_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `tenant_business_hours`
--
ALTER TABLE `tenant_business_hours`
  ADD CONSTRAINT `tenant_business_hours_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `tenant_users`
--
ALTER TABLE `tenant_users`
  ADD CONSTRAINT `tenant_users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_account_status_changed_by_foreign` FOREIGN KEY (`account_status_changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- テーブルの制約 `webhook_logs`
--
ALTER TABLE `webhook_logs`
  ADD CONSTRAINT `webhook_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL;
COMMIT;
