<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
                            {--name= : 管理者の名前}
                            {--email= : 管理者のメールアドレス}';

    protected $description = 'システム管理者ユーザーを作成します';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('管理者の名前を入力してください');
        $email = $this->option('email') ?? $this->ask('メールアドレスを入力してください');

        // メールアドレスの一意性はDBスキーマでも保証されるが、CLI上で分かりやすいエラーメッセージを返すために事前チェックする
        if (User::where('email', $email)->exists()) {
            $this->error("メールアドレス {$email} は既に使用されています");

            return Command::FAILURE;
        }

        // secretメソッドを使い、端末上にパスワードが表示されないようにする
        $password = $this->secret('パスワードを入力してください（最小8文字）');
        $passwordConfirmation = $this->secret('パスワードを再入力してください');

        if ($password !== $passwordConfirmation) {
            $this->error('パスワードが一致しません');

            return Command::FAILURE;
        }

        // FormRequestが使えないCLIコンテキストのため、手動でバリデーションを実行する
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return Command::FAILURE;
        }

        // 管理者は高権限のため、誤操作を防ぐために作成前に内容確認を求める
        $this->info("\n以下の内容で管理者を作成します：");
        $this->table(
            ['項目', '値'],
            [
                ['名前', $name],
                ['メールアドレス', $email],
                ['ロール', 'システム管理者 (admin)'],
            ]
        );

        if (! $this->confirm('作成してよろしいですか？')) {
            $this->info('キャンセルしました');

            return Command::SUCCESS;
        }

        // role, is_active, email_verified_at は$fillableから除外されているため、直接属性代入で設定する
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // Userモデルのcastsで自動ハッシュ化
        ]);
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->role = UserRole::Admin;
        $user->save();

        $this->info("\n管理者を作成しました！");
        $this->info("ID: {$user->id}");
        $this->info("メール: {$user->email}");
        $this->info("\n{$user->email} でログインできます");

        return Command::SUCCESS;
    }
}
