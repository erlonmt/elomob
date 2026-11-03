<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | The proxies allowed to tell the client address in X-Forwarded-For, as
    | addresses or ranges separated by commas: the Next.js proxy and, in
    | Docker, the nginx in front of it. Rate limits count each client by
    | that address. Left empty, no proxy is trusted.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
