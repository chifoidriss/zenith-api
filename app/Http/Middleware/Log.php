<?php

namespace App\Http\Middleware;

use App\Models\Log as ModelsLog;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Log
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        ModelsLog::create([
            'user_id' => auth()->id(),
            'url' => request()->fullUrl(),
            'header' => json_encode(request()->header())
        ]);

        $user = User::find(auth()->id());
        $user->update(['last_activity_at' => now()]);

        return $next($request);
    }
}
