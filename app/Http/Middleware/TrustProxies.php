<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Only trust proxies explicitly configured for this deployment.
     * Example: TRUSTED_PROXIES=172.18.0.10,10.0.0.0/8
     */
    protected $proxies;

    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    public function handle($request, Closure $next)
    {
        $configured = trim((string) env('TRUSTED_PROXIES', ''));
        if ($configured !== '') {
            $this->proxies = $configured === '*'
                ? '*'
                : array_filter(array_map('trim', explode(',', $configured)));
        }

        return parent::handle($request, $next);
    }
}
