<?php

namespace App\Http\Middleware;

use App\Services\RequestTrace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TraceTaskRequest
{
    public function __construct(private RequestTrace $trace) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('tasks', 'tasks/*')) {
            return $next($request);
        }

        $this->trace->begin($request);
        $response = $next($request);
        $this->trace->finish($request, $response);

        return $response;
    }
}
