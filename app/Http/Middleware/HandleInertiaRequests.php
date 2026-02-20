<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;
use Illuminate\Support\Str;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing('roles', 'permissions');
            $hasFrontdeskAdminRole = $user->roles
                ->pluck('name')
                ->filter()
                ->map(function ($name) {
                    $normalized = Str::of((string) $name)
                        ->trim()
                        ->lower()
                        ->replaceMatches('/[\s-]+/', '_')
                        ->replaceMatches('/[^a-z0-9_]/', '')
                        ->toString();

                    if ($normalized === \App\Enum\RoleEnum::FRONTDESK_ADMIN->value) {
                        return true;
                    }

                    $compact = str_replace('_', '', $normalized);
                    $hasFrontChunk = str_contains($compact, 'front') || str_contains($compact, 'fron');
                    $hasDeskChunk = str_contains($compact, 'desk') || str_contains($compact, 'destk');
                    $hasAdminChunk = str_contains($compact, 'admin');

                    return $hasFrontChunk && $hasDeskChunk && $hasAdminChunk;
                })
                ->contains(true);

            $user->setAttribute(
                'has_frontdesk_admin_role',
                $hasFrontdeskAdminRole || $user->hasRole(\App\Enum\RoleEnum::FRONTDESK_ADMIN->value) || $user->hasRole('FRONTDESK_ADMIN')
            );
        }

        return [
            ...parent::share($request),
            'auth' => [
              'user' => $user,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => function () use ($request) {
              return [
                  'success' => $request->session()->get('success'),
                  'error' => $request->session()->get('error'),
              ];
            }
        ];
    }
}
