<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Seo\PublicPageSeoFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    public function __construct(
        private readonly PublicPageSeoFactory $publicPageSeoFactory
    ) {}

    public function __invoke(): RedirectResponse|Response
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            ...$this->publicPageSeoFactory->welcome(),
        ]);
    }
}
