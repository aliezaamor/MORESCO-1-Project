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
        $this->host = config('yeastar.host', '10.209.80.8');
        $this->port = config('yeastar.port', '80');
        $this->username = config('yeastar.username', 'apiuser');
        $this->password = config('yeastar.password', 'apipass');
        $this->defaultPort = config('yeastar.default_route_port', 2);
    }

    public function sendSms(string $destination, string $content, string $gsmPort = null)
    {
        $rawPort  = $gsmPort ?? $this->defaultPort;
        $spanMap  = config('yeastar.gsm_span_map', []);
        $portUsed = $spanMap[(int) $rawPort] ?? $rawPort;
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
            stream_set_timeout($socket, 2);
            fwrite($socket, "Action: Login\r\n");
            fwrite($socket, "Username: {$this->username}\r\n");
            fwrite($socket, "Secret: {$this->password}\r\n");
            fwrite($socket, "\r\n");
            usleep(500000); // Wait 500ms for login

            fwrite($socket, "Action: smscommand\r\n");
            fwrite($socket, "Command: sms delete {$gsmPort} {$index}\r\n");
            fwrite($socket, "\r\n");
            usleep(200000); // Wait 200ms for command processing

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
