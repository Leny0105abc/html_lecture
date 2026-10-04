<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireStudentPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->role === 'student' && $user->must_change_password
            && ! $request->routeIs('student-password.edit', 'user-password.update', 'logout')) {
            return redirect()->route('student-password.edit');
        }

        return $next($request);
    }
}
