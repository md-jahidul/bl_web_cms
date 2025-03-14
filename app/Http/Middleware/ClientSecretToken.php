<?php

namespace App\Http\Middleware;

use App\Exceptions\RequestUnauthorizedException;
use App\Exceptions\SecreteTokenExpireException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ClientSecretToken
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     * @throws RequestUnauthorizedException
     * @throws SecreteTokenExpireException
     */
    public function handle($request, Closure $next)
    {
        if ($this->validateToken($request)){
            return $next($request);
        }
        throw new RequestUnauthorizedException();
    }

    /**
     * @throws SecreteTokenExpireException
     */
    public function validateToken($request)
    {
        try {
            $clientSecurityToken = $request->header('client-security-token');
            $clientSecurityTokenArr = explode('=', $clientSecurityToken);
            $redisKey = "al_api_security_key:" . $clientSecurityTokenArr[0];
            $secretToken = $clientSecurityTokenArr[1];

            $cacheData = Redis::get($redisKey);
            $cacheData = json_decode($cacheData, true);

            if (!$cacheData) {
                throw new SecreteTokenExpireException();
            }

            if (hash_equals($cacheData['secret_key'], $secretToken)) {
                Redis::del($redisKey);
                return true;
            }
        } catch (\Exception $exception){

        }
    }
}
