<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Seo\PublicPageSeoFactory;
use Inertia\Inertia;
use Inertia\Response;

class ForBusinessController extends Controller
{
    public function __construct(
        private readonly PublicPageSeoFactory $publicPageSeoFactory
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('ForBusiness/Index', [
            ...$this->publicPageSeoFactory->forBusiness(),
        ]);
    }
}
