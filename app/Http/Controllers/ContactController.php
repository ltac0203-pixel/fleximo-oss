<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ContactFormRequest;
use App\Mail\ContactFormMail;
use App\Support\Seo\PublicPageSeoFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        private readonly PublicPageSeoFactory $publicPageSeoFactory
    ) {}

    // お問い合わせフォームを表示する
    public function index(): Response
    {
        return Inertia::render('Contact/Index', [
            ...$this->publicPageSeoFactory->contact(),
        ]);
    }

    // お問い合わせを送信する
    public function store(ContactFormRequest $request): RedirectResponse
    {
        // ハニーポットチェック（botはこのフィールドに値を入れがち）
        if ($request->filled('website')) {
            // スパムの可能性が高いが、成功のふりをして終了
            return redirect()
                ->route('contact.index')
                ->with('success', 'お問い合わせを送信しました。');
        }

        $validated = $request->validated();

        try {
            Mail::to(config('mail.from_addresses.contact.address'))
                ->queue(new ContactFormMail(
                    senderName: $validated['name'],
                    senderEmail: $validated['email'],
                    contactSubject: $validated['subject'],
                    contactMessage: $validated['message'],
                ));
        } catch (\Throwable $e) {
            Log::error('お問い合わせメールのキュー追加に失敗しました', [
                'subject' => $validated['subject'],
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);

            return redirect()
                ->route('contact.index')
                ->with('error', 'メールの送信に失敗しました。しばらく時間をおいて再度お試しください。');
        }

        return redirect()
            ->route('contact.index')
            ->with('success', 'お問い合わせを送信しました。担当者からご連絡いたします。');
    }
}
