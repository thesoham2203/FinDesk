<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Http\Middleware\EnsureUserHasRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

describe('EnsureUserHasRole Middleware', function (): void {
    it('allows user with required role to pass through', function (): void {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserHasRole();
        $response = $middleware->handle($request, fn () => response('Success'), 'admin');

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->getContent())->toBe('Success');
    });

    it('allows user with one of multiple required roles', function (): void {
        $user = User::factory()->create(['role' => UserRole::Manager]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserHasRole();
        $response = $middleware->handle(
            $request,
            fn () => response('Success'),
            'manager',
            'admin'
        );

        expect($response->getContent())->toBe('Success');
    });

    it('denies user without required role', function (): void {
        $user = User::factory()->create(['role' => UserRole::Employee]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserHasRole();

        expect(
            fn () => $middleware->handle(
                $request,
                fn () => response('Success'),
                'admin'
            )
        )->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('denies request with no authenticated user', function (): void {
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = new EnsureUserHasRole();

        expect(
            fn () => $middleware->handle(
                $request,
                fn () => response('Success'),
                'admin'
            )
        )->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('denies accountant role from admin-only route', function (): void {
        $user = User::factory()->create(['role' => UserRole::Accountant]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserHasRole();

        expect(
            fn () => $middleware->handle(
                $request,
                fn () => response('Success'),
                'admin'
            )
        )->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('is case sensitive for role matching', function (): void {
        $user = User::factory()->create(['role' => UserRole::Admin]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new EnsureUserHasRole();

        expect(
            fn () => $middleware->handle(
                $request,
                fn () => response('Success'),
                'ADMIN' // Wrong case
            )
        )->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });
});
