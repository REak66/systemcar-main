<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id'       => $request->user()->id,
                    'name'     => $request->user()->name,
                    'username' => $request->user()->username,
                    'email'    => $request->user()->email,
                    'role'     => $request->user()->role,
                ] : null,
            ],
            'flash' => [
                'success'        => fn () => $request->session()->get('success'),
                'error'          => fn () => $request->session()->get('error'),
                'import_success' => fn () => $request->session()->get('import_success'),
                'import_failed'  => fn () => $request->session()->get('import_failed'),
                'import_errors'  => fn () => $request->session()->get('import_errors'),
            ],
            'ziggy' => fn () => [...(new Ziggy)->toArray(), 'location' => $request->url()],
        ];
    }
}
