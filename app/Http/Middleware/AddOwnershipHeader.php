<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyematkan header identitas pengembangan dan hak cipta pada setiap respons HTTP.
 *
 * @author AAA-05
 * @copyright Copyright (c) 2026 AAA-05
 */
class AddOwnershipHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Developed-By', 'AAA-05');
        $response->headers->set('X-Copyright', '2026 AAA-05');

        return $response;
    }
}
