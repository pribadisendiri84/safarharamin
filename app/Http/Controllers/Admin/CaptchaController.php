<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminCaptcha;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptchaController extends Controller
{
    public function __invoke(Request $request, AdminCaptcha $captcha): Response
    {
        return response($captcha->image($request), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }
}
