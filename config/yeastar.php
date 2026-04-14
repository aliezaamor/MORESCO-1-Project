<?php

return [
    'host'     => env('YEASTAR_HOST', '10.209.80.8'),
    'port'     => env('YEASTAR_PORT', 80),
    'api_port' => env('YEASTAR_API_PORT', 5038),
    'username' => env('YEASTAR_API_USERNAME', 'apiuser'),
    'password' => env('YEASTAR_API_PASSWORD', 'apipass'),

    // GsmSpan values for outgoing SMS routing (mapped to WebCGI ports via gsm_span_map below).
    // Keyword: GsmSpan 2 (TM/Globe) → WebCGI port 1
    // Individual/Broadcast: GsmSpan 3 (Smart/TNT) → WebCGI port 2
    'port_keyword'      => (int) env('YEASTAR_PORT_KEYWORD', 2),
    'port_individual'   => (int) env('YEASTAR_PORT_INDIVIDUAL', 3),
    'port_broadcast'    => (int) env('YEASTAR_PORT_BROADCAST', 3),
    'default_route_port'=> (int) env('YEASTAR_DEFAULT_ROUTE_PORT', 1),

    // Mapping from AMI GsmSpan (used in incoming webhooks) → WebCGI port (used for outgoing SMS).
    // These two numbering systems are different: GsmSpan is the physical AMI slot number,
    // while WebCGI port follows the Yeastar admin UI "Port 1 / Port 2" labels.
    // Example: GsmSpan 2 (Globe/TM) → WebCGI port 1, GsmSpan 3 (Smart/TNT) → WebCGI port 2
    'gsm_span_map' => array_filter([
        (int) env('YEASTAR_TM_GSMSPAN',  2) => (int) env('YEASTAR_TM_PORT',  1),
        (int) env('YEASTAR_TNT_GSMSPAN', 3) => (int) env('YEASTAR_TNT_PORT', 2),
    ]),
];
