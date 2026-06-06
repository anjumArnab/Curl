<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Outgoing Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds the API tester will wait for a response from
    | a target API before aborting the request.
    |
    */

    'request_timeout' => (int) env('CURL_REQUEST_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Blocked Hosts (SSRF guard)
    |--------------------------------------------------------------------------
    |
    | The tester executes requests server-side via the Laravel HTTP Client, so
    | it can reach any host the server can. Because the whole point is to test
    | user-specified (often localhost) APIs this defaults to permissive. List
    | hostnames here to refuse outgoing requests to them. This is a self-hosted
    | trust boundary — tighten it for multi-tenant / public deployments.
    |
    */

    'blocked_hosts' => array_filter(
        explode(',', (string) env('CURL_BLOCKED_HOSTS', ''))
    ),

    /*
    |--------------------------------------------------------------------------
    | Response Body Storage Limit
    |--------------------------------------------------------------------------
    |
    | Maximum number of bytes of a response body to persist in request history.
    |
    */

    'max_stored_response_bytes' => (int) env('CURL_MAX_STORED_RESPONSE_BYTES', 256 * 1024),

];
