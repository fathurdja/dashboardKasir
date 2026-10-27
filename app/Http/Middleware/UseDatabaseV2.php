<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class UseDatabaseV2
{
    /**
     * Switch default database connection to mysql_v2 for api-v2 routes.
     */
    public function handle(Request $request, Closure $next)
    {
        Config::set('database.default', 'mysql_v2');
        DB::setDefaultConnection('mysql_v2');

        try {
            return $next($request);
        } finally {
            // Restore back to mysql
            Config::set('database.default', 'mysql');
            DB::setDefaultConnection('mysql');
        }
    }
}
