<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YeastarService
{
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $defaultPort;

    public function __construct()
    {
        $this->host = env('YEASTAR_HOST', '10.209.80.8');
        $this->port = env('YEASTAR_PORT', '80');
        $this->username = env('YEASTAR_API_USERNAME', 'apiuser');
        $this->password = env('YEASTAR_API_PASSWORD', 'apipass');
        $this->defaultPort = env('YEASTAR_DEFAULT_ROUTE_PORT', '2'); // Which GSM port to use for sending, if known
    }

    /**
     * Send an SMS via the Yeastar TG Series HTTP CGI Api
     * Default URL structure for TG models: http://[IP]:[PORT]/cgi/WebCGI?11401&destination=[NUMBER]&port=[PORT]&content=[MESSAGE]&username=[USER]&password=[PASS]
     */
    public function sendSms(string $destination, string $content, string $gsmPort = null)
    {
        $portUsed = $gsmPort ?? $this->defaultPort;
        
        $url = "http://{$this->host}:{$this->port}/cgi/WebCGI";
        
        // Older TG Firmwares require the parameters stacked on the 1500101= key directly
        $queryString = "1500101=account={$this->username}&password={$this->password}&port={$portUsed}&destination={$destination}&content=" . urlencode($content);

        try {
            // Some older Yeastar firmware returns malformed HTTP headers that crack Guzzle/cURL.
            // Using raw stream context safely grabs the body while ignoring bad headers.
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'ignore_errors' => true // Don't throw PHP errors on 400/500
                ]
            ]);

            $responseBody = @file_get_contents("{$url}?{$queryString}", false, $context);
            
            Log::info("Yeastar outbound SMS to {$destination}: " . $responseBody);
            
            // Yeastar normally returns something like "Action: smssend\r\nStatus: Success"
            return $responseBody && str_contains($responseBody, 'Success');
        } catch (\Exception $e) {
            Log::error("Yeastar SMS send failed to {$destination}: " . $e->getMessage());
            return false;
        }
    }
}
