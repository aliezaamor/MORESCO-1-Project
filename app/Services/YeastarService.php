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
        $this->defaultPort = env('YEASTAR_DEFAULT_ROUTE_PORT', '2');
    }

    public function sendSms(string $destination, string $content, string $gsmPort = null)
    {
        $portUsed = $gsmPort ?? $this->defaultPort;
        $url = "http://{$this->host}:{$this->port}/cgi/WebCGI";
        $queryString = "1500101=account={$this->username}&password={$this->password}&port={$portUsed}&destination={$destination}&content=" . urlencode($content);

        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'ignore_errors' => true
                ]
            ]);

            $responseBody = @file_get_contents("{$url}?{$queryString}", false, $context);
            Log::info("Yeastar outbound SMS to {$destination}: " . $responseBody);
            return $responseBody && str_contains($responseBody, 'Success');
        } catch (\Exception $e) {
            Log::error("Yeastar SMS send failed to {$destination}: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSms(int $gsmPort, int $index)
    {
        $amiPort = env('YEASTAR_API_PORT', 5038);
        $socket = @fsockopen($this->host, $amiPort, $errno, $errstr, 5);
        
        if (!$socket) {
            Log::error("Yeastar AMI Connection for deletion failed: {$errstr} ({$errno})");
            return false;
        }

        try {
            fwrite($socket, "Action: Login\r\n");
            fwrite($socket, "Username: {$this->username}\r\n");
            fwrite($socket, "Secret: {$this->password}\r\n");
            fwrite($socket, "\r\n");
            
            fwrite($socket, "Action: smscommand\r\n");
            fwrite($socket, "Command: sms delete {$gsmPort} {$index}\r\n");
            fwrite($socket, "\r\n");

            fwrite($socket, "Action: Logoff\r\n\r\n");
            
            Log::info("Yeastar SMS delete command sent for Port: {$gsmPort}, Index: {$index}");
            return true;
        } catch (\Exception $e) {
            Log::error("Yeastar SMS deletion failed: " . $e->getMessage());
            return false;
        } finally {
            fclose($socket);
        }
    }
}
