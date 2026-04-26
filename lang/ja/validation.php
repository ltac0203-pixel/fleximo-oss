<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーション言語ライン
    |--------------------------------------------------------------------------
    |
    | バリデータが既定で使用するエラーメッセージです。size 系のように
    | 複数バリエーションを持つルールもあります。プロジェクトの文体に
    | 合わせて自由に書き換えて構いません。
    |
    */

    'accepted' => ':attribute を承認してください。',
    'accepted_if' => ':other が :value のとき、:attribute を承認してください。',
    'active_url' => ':attribute は有効な URL 形式で入力してください。',
    'after' => ':attribute には :date より後の日付を入力してください。',
    'after_or_equal' => ':attribute には :date 以降の日付を入力してください。',
    'alpha' => ':attribute はアルファベットのみで入力してください。',
    'alpha_dash' => ':attribute はアルファベット・数字・ダッシュ・アンダースコアのみで入力してください。',
    'alpha_num' => ':attribute はアルファベットと数字のみで入力してください。',
    'any_of' => ':attribute の値が正しくありません。',
    'array' => ':attribute は配列で指定してください。',
    'ascii' => ':attribute は半角英数字と記号のみで入力してください。',
    'before' => ':attribute には :date より前の日付を入力してください。',
    'before_or_equal' => ':attribute には :date 以前の日付を入力してください。',
    'between' => [
        'array' => ':attribute は :min 個から :max 個の間で指定してください。',
        'file' => ':attribute は :min KB から :max KB の間で指定してください。',
        'numeric' => ':attribute は :min から :max の間で指定してください。',
        'string' => ':attribute は :min 文字から :max 文字の間で入力してください。',
    ],
    'boolean' => ':attribute は true または false で指定してください。',
    'can' => ':attribute に許可されていない値が含まれています。',
    'confirmed' => ':attribute と確認用の値が一致しません。',
    'contains' => ':attribute に必須の値が含まれていません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attribute は有効な日付で入力してください。',
    'date_equals' => ':attribute は :date と同じ日付を入力してください。',
    'date_format' => ':attribute は :format 形式で入力してください。',
    'decimal' => ':attribute は小数点以下 :decimal 桁で入力してください。',
    'declined' => ':attribute は拒否してください。',
    'declined_if' => ':other が :value のとき、:attribute は拒否してください。',
    'different' => ':attribute と :other は別の値を指定してください。',
    'digits' => ':attribute は :digits 桁で入力してください。',
    'digits_between' => ':attribute は :min から :max 桁で入力してください。',
    'dimensions' => ':attribute の画像サイズが正しくありません。',
    'distinct' => ':attribute に重複した値があります。',
    'doesnt_contain' => ':attribute に次のいずれも含めないでください: :values',
    'doesnt_end_with' => ':attribute は次のいずれかで終わらないようにしてください: :values',
    'doesnt_start_with' => ':attribute は次のいずれかで始まらないようにしてください: :values',
    'email' => ':attribute は有効なメールアドレス形式で入力してください。',
    'encoding' => ':attribute は :encoding でエンコードされている必要があります。',
    'ends_with' => ':attribute は次のいずれかで終わってください: :values',
    'enum' => '選択された :attribute は無効です。',
    'exists' => '選択された :attribute は無効です。',
    'extensions' => ':attribute は次のいずれかの拡張子にしてください: :values',
    'file' => ':attribute はファイルを指定してください。',
    'filled' => ':attribute に値を入力してください。',
    'gt' => [
        'array' => ':attribute は :value 個より多く指定してください。',
        'file' => ':attribute は :value KB より大きいファイルを指定してください。',
        'numeric' => ':attribute は :value より大きい値を指定してください。',
        'string' => ':attribute は :value 文字より多く入力してください。',
    ],
    'gte' => [
        'array' => ':attribute は :value 個以上を指定してください。',
        'file' => ':attribute は :value KB 以上のファイルを指定してください。',
        'numeric' => ':attribute は :value 以上の値を指定してください。',
        'string' => ':attribute は :value 文字以上で入力してください。',
    ],
    'hex_color' => ':attribute は有効な16進カラーコードで指定してください。',
    'image' => ':attribute は画像ファイルを指定してください。',
    'in' => '選択された :attribute は無効です。',
    'in_array' => ':attribute は :other に含まれている必要があります。',
    'in_array_keys' => ':attribute には次のキーのいずれかを含めてください: :values',
    'integer' => ':attribute は整数で指定してください。',
    'ip' => ':attribute は有効な IP アドレスで指定してください。',
    'ipv4' => ':attribute は有効な IPv4 アドレスで指定してください。',
    'ipv6' => ':attribute は有効な IPv6 アドレスで指定してください。',
    'json' => ':attribute は有効な JSON 文字列で指定してください。',
    'list' => ':attribute はリスト形式で指定してください。',
    'lowercase' => ':attribute は小文字で入力してください。',
    'lt' => [
        'array' => ':attribute は :value 個より少なく指定してください。',
        'file' => ':attribute は :value KB より小さいファイルを指定してください。',
        'numeric' => ':attribute は :value より小さい値を指定してください。',
        'string' => ':attribute は :value 文字より少なく入力してください。',
    ],
    'lte' => [
        'array' => ':attribute は :value 個以下で指定してください。',
        'file' => ':attribute は :value KB 以下のファイルを指定してください。',
        'numeric' => ':attribute は :value 以下の値を指定してください。',
        'string' => ':attribute は :value 文字以下で入力してください。',
    ],
    'mac_address' => ':attribute は有効な MAC アドレスで指定してください。',
    'max' => [
        'array' => ':attribute は :max 個以下で指定してください。',
        'file' => ':attribute は :max KB 以下にしてください。',
        'numeric' => ':attribute は :max 以下にしてください。',
        'string' => ':attribute は :max 文字以下で入力してください。',
    ],
    'max_digits' => ':attribute は :max 桁以下で入力してください。',
    'mimes' => ':attribute は次のファイル形式を指定してください: :values',
    'mimetypes' => ':attribute は次のファイル形式を指定してください: :values',
    'min' => [
        'array' => ':attribute は :min 個以上を指定してください。',
        'file' => ':attribute は :min KB 以上にしてください。',
        'numeric' => ':attribute は :min 以上にしてください。',
        'string' => ':attribute は :min 文字以上で入力してください。',
    ],
    'min_digits' => ':attribute は :min 桁以上で入力してください。',
    'missing' => ':attribute は指定しないでください。',
    'missing_if' => ':other が :value のとき、:attribute は指定しないでください。',
    'missing_unless' => ':other が :value でない限り、:attribute は指定しないでください。',
    'missing_with' => ':values が指定されているとき、:attribute は指定しないでください。',
    'missing_with_all' => ':values がすべて指定されているとき、:attribute は指定しないでください。',
    'multiple_of' => ':attribute は :value の倍数で指定してください。',
    'not_in' => '選択された :attribute は無効です。',
    'not_regex' => ':attribute の形式が正しくありません。',
    'numeric' => ':attribute は数値で指定してください。',
    'password' => [
        'letters' => ':attribute には少なくとも1文字のアルファベットを含めてください。',
        'mixed' => ':attribute には大文字と小文字を少なくとも1文字ずつ含めてください。',
        'numbers' => ':attribute には少なくとも1つの数字を含めてください。',
        'symbols' => ':attribute には少なくとも1つの記号を含めてください。',
        'uncompromised' => '指定された :attribute は漏洩したパスワードに含まれます。別のものを設定してください。',
    ],
    'present' => ':attribute を指定してください。',
    'present_if' => ':other が :value のとき、:attribute を指定してください。',
    'present_unless' => ':other が :value でない限り、:attribute を指定してください。',
    'present_with' => ':values が指定されているとき、:attribute も指定してください。',
    'present_with_all' => ':values がすべて指定されているとき、:attribute も指定してください。',
    'prohibited' => ':attribute は指定できません。',
    'prohibited_if' => ':other が :value のとき、:attribute は指定できません。',
    'prohibited_if_accepted' => ':other を承認したとき、:attribute は指定できません。',
    'prohibited_if_declined' => ':other を拒否したとき、:attribute は指定できません。',
    'prohibited_unless' => ':other が :values に含まれない限り、:attribute は指定できません。',
    'prohibits' => ':attribute は :other を指定できなくします。',
    'regex' => ':attribute の形式が正しくありません。',
    'required' => ':attribute は必須項目です。',
    'required_array_keys' => ':attribute には次のキーをすべて含めてください: :values',
    'required_if' => ':other が :value のとき、:attribute は必須です。',
    'required_if_accepted' => ':other を承認したとき、:attribute は必須です。',
    'required_if_declined' => ':other を拒否したとき、:attribute は必須です。',
    'required_unless' => ':other が :values に含まれない限り、:attribute は必須です。',
    'required_with' => ':values が指定されているとき、:attribute は必須です。',
    'required_with_all' => ':values がすべて指定されているとき、:attribute は必須です。',
    'required_without' => ':values が指定されていないとき、:attribute は必須です。',
    'required_without_all' => ':values がいずれも指定されていないとき、:attribute は必須です。',
    'same' => ':attribute と :other は一致している必要があります。',
    'size' => [
        'array' => ':attribute は :size 個で指定してください。',
        'file' => ':attribute は :size KB で指定してください。',
        'numeric' => ':attribute は :size を指定してください。',
        'string' => ':attribute は :size 文字で入力してください。',
    ],
    'starts_with' => ':attribute は次のいずれかで始まってください: :values',
    'string' => ':attribute は文字列で指定してください。',
    'timezone' => ':attribute は有効なタイムゾーンを指定してください。',
    'unique' => ':attribute は既に使用されています。',
    'uploaded' => ':attribute のアップロードに失敗しました。',
    'uppercase' => ':attribute は大文字で入力してください。',
    'url' => ':attribute は有効な URL 形式で入力してください。',
    'ulid' => ':attribute は有効な ULID で指定してください。',
    'uuid' => ':attribute は有効な UUID で指定してください。',

    /*
    |--------------------------------------------------------------------------
    | カスタムバリデーション言語ライン
    |--------------------------------------------------------------------------
    |
    | FormRequest::messages() からは __('validation.custom.<key>') 形式で
    | このセクション配下のキーを参照する。属性名×ルールではなく
    | 個別キー（cart_id_required 等）で集約しているのは、複合ルールや
    | サービス層由来のメッセージも同居させるため。
    |
    */

    'custom' => [
        // FormRequest::messages() で参照される個別メッセージ
        'cart_id_required' => 'カートIDは必須です。',
        'cart_id_integer' => 'カートIDは整数で指定してください。',
        'cart_id_exists' => '指定されたカートが見つかりません。',

        'menu_item_id_required' => 'メニュー商品を選択してください。',
        'menu_item_id_integer' => 'メニュー商品IDは整数で指定してください。',
        'menu_item_id_exists' => '指定されたメニュー商品が見つかりません。',

        'quantity_required' => '数量は必須です。',
        'quantity_integer' => '数量は整数で指定してください。',
        'quantity_min' => '数量は1以上で指定してください。',
        'quantity_max' => '数量が上限を超えています。',

        'option_ids_array' => 'オプションは配列で指定してください。',
        'option_ids_integer' => 'オプションIDは整数で指定してください。',
        'option_ids_exists' => '指定されたオプションが見つかりません。',

        'cart_item_id_required' => 'カート商品IDは必須です。',
        'cart_item_id_integer' => 'カート商品IDは整数で指定してください。',
        'cart_item_id_exists' => '指定されたカート商品が見つかりません。',

        'payment_method_required' => '決済方法を選択してください。',
        'payment_method_in' => '対応していない決済方法が指定されました。',

        'card_id_required' => 'カードを選択してください。',
        'card_id_integer' => 'カードIDは整数で指定してください。',
        'card_token_required' => 'カードトークンが取得できませんでした。お手数ですが再度カード情報をご入力ください。',
        'card_token_string' => 'カードトークンの形式が正しくありません。',

        'order_id_required' => '注文IDは必須です。',
        'order_id_integer' => '注文IDは整数で指定してください。',
        'order_id_exists' => '指定された注文が見つかりません。',

        'tenant_id_required' => 'テナントIDは必須です。',
        'tenant_id_integer' => 'テナントIDは整数で指定してください。',
        'tenant_id_exists' => '指定されたテナントが見つかりません。',

        'access_id_required' => '決済セッションが見つかりません。お手数ですが再度お試しください。',
        'access_id_string' => '決済セッション形式が正しくありません。',

        'three_ds_status_required' => '3Dセキュアの結果が取得できませんでした。',
        'three_ds_status_string' => '3Dセキュア結果の形式が正しくありません。',

        'email_required' => 'メールアドレスは必須です。',
        'email_email' => '有効なメールアドレスを入力してください。',
        'email_string' => 'メールアドレスは文字列で入力してください。',
        'email_max' => 'メールアドレスは255文字以下で入力してください。',
        'email_unique' => 'このメールアドレスは既に登録されています。',

        'password_required' => 'パスワードは必須です。',
        'password_string' => 'パスワードは文字列で入力してください。',
        'password_confirmed' => 'パスワードと確認用パスワードが一致しません。',
        'password_current' => '現在のパスワードが正しくありません。',

        'name_required' => 'お名前は必須です。',
        'name_string' => 'お名前は文字列で入力してください。',
        'name_max' => 'お名前は255文字以下で入力してください。',

        'page_integer' => 'ページ番号は整数で指定してください。',
        'page_min' => 'ページ番号は1以上で指定してください。',

        'per_page_integer' => '1ページあたりの件数は整数で指定してください。',
        'per_page_min' => '1ページあたりの件数は1以上で指定してください。',
        'per_page_max' => '1ページあたりの件数が上限を超えています。',

        'status_required' => 'ステータスを選択してください。',
        'status_in' => '指定されたステータスは無効です。',

        'tenant_unavailable' => '指定されたテナントは現在ご利用いただけません。',
        'menu_item_not_in_tenant' => '指定された商品はこのテナントには存在しません。',
        'option_not_in_menu_item' => 'この商品に紐付いていないオプションが選択されています。',

        'payment_id_required' => '決済IDは必須です。',
        'payment_id_integer' => '決済IDは整数で指定してください。',
        'payment_id_invalid' => '指定された決済は無効です。',

        'token_required' => 'カードトークンは必須です。',
        'token_string' => 'カードトークンの形式が正しくありません。',

        'param_required' => '認証パラメータは必須です。',
        'param_string' => '認証パラメータは文字列で指定してください。',
    ],

    /*
    |--------------------------------------------------------------------------
    | カスタム属性名
    |--------------------------------------------------------------------------
    |
    | バリデーションメッセージ内の :attribute 部分を、内部キー名から
    | ユーザーに分かりやすい表記に置き換える。
    |
    */

    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => '確認用パスワード',
        'name' => 'お名前',
        'cart_id' => 'カート',
        'cart_item_id' => 'カート商品',
        'menu_item_id' => 'メニュー商品',
        'option_ids' => 'オプション',
        'option_ids.*' => 'オプション',
        'quantity' => '数量',
        'payment_method' => '決済方法',
        'card_id' => 'カード',
        'card_token' => 'カードトークン',
        'order_id' => '注文',
        'tenant_id' => 'テナント',
        'access_id' => '決済セッション',
        'three_ds_status' => '3Dセキュア結果',
        'page' => 'ページ番号',
        'per_page' => '1ページあたりの件数',
        'status' => 'ステータス',
    ],

];
