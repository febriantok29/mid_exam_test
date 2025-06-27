<?php

namespace App\Http\Utilities;

use Illuminate\Support\Facades\Http;
use Exception;

enum ApiMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}

class ApiClient
{
    // ------------------
    // Class properties
    // ------------------
    protected string $baseUrl;
    protected array $queries = [];
    protected array $parameters = [];
    protected array $headers = [];
    protected ?string $token = null;

    protected static array $globalHeaders = [];
    protected static bool $debugMode = true;

    // ------------------
    // Constructor & Static Methods
    // ------------------

    public function __construct()
    {
        // For local development, ensure we use port 8000
        $appUrl = config('app.url');
        if (app()->environment('local') && parse_url($appUrl, PHP_URL_HOST) === 'localhost') {
            $appUrl = 'http://localhost:8001';
        }

        $this->baseUrl = $appUrl . '/api';
    }

    public static function create(): self
    {
        return new self();
    }

    public static function addGlobalHeader(string $key, string $value): void
    {
        self::$globalHeaders[$key] = $value;
    }

    public static function addGlobalHeaders(array $headers): void
    {
        self::$globalHeaders = array_merge(self::$globalHeaders, $headers);
    }

    public static function clearGlobalHeaders(): void
    {
        self::$globalHeaders = [];
    }

    // ------------------
    // Builder methods
    // ------------------

    public function withToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function withQuery(string $key, string $value): self
    {
        $this->queries[$key] = $value;
        return $this;
    }

    public function withQueries(array $queries): self
    {
        $this->queries = array_merge($this->queries, $queries);
        return $this;
    }

    public function withParameter(string $key, string $value): self
    {
        $this->parameters[$key] = $value;
        return $this;
    }

    public function withParameters(array $parameters): self
    {
        $this->parameters = array_merge($this->parameters, $parameters);
        return $this;
    }

    public function withHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    // ------------------
    // URL & Headers Building
    // ------------------

    protected function buildHeaders(): array
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return array_merge($headers, $this->headers, self::$globalHeaders);
    }

    protected function buildUrl(string $endpoint): string
    {
        try {
            // Replace path parameters if any
            if (!empty($this->parameters)) {
                foreach ($this->parameters as $key => $value) {
                    $endpoint = str_replace(":{$key}", $value, $endpoint);
                }
            }

            $url = "{$this->baseUrl}/{$endpoint}";

            // Add query parameters if any
            if (!empty($this->queries)) {
                $queryString = http_build_query($this->queries);
                $url .= str_contains($url, '?') ? "&{$queryString}" : "?{$queryString}";
            }

            return $url;
        } catch (Exception $e) {
            throw new Exception("Failed to build URL: {$e->getMessage()}");
        }
    }

    // ------------------
    // Request Handling
    // ------------------

    protected function request(ApiMethod $method, string $endpoint, ?array $body = null): array
    {
        try {
            $url = $this->buildUrl($endpoint);
            $headers = $this->buildHeaders();

            // Log request if debug mode is on
            if (self::$debugMode) {
                $this->logRequest($method, $url, $headers, $body);
            }

            $response = Http::withHeaders($headers);

            // Execute request based on method
            $response = match ($method) {
                ApiMethod::GET => $response->get($url),
                ApiMethod::POST => $response->post($url, $body),
                ApiMethod::PUT => $response->put($url, $body),
                ApiMethod::PATCH => $response->patch($url, $body),
                ApiMethod::DELETE => $response->delete($url, $body),
            };

            // Log response if debug mode is on
            if (self::$debugMode) {
                $this->logResponse($response);
            }

            $response_body = $response->json();

            if (isset($response_body['error'])) {
                $this->handleErrorResponse($response);
            }

            return $response->json();
        } catch (Exception $e) {
            throw new Exception($this->getAppropriateErrorMessage($e));
        }
    }

    protected function handleErrorResponse($response): void
    {
        $status = $response->status();
        $body = $response->json();

        $message = $body['message'] ?? ($body['error'] ?? $this->getDefaultErrorMessage($status));

        throw new Exception($message, $status);
    }

    protected function getDefaultErrorMessage(int $status): string
    {
        return match ($status) {
            400 => 'Permintaan tidak valid.',
            401 => 'Tidak memiliki izin akses.',
            403 => 'Akses ditolak.',
            404 => 'Data tidak ditemukan.',
            422 => 'Data yang dikirim tidak valid.',
            429 => 'Terlalu banyak permintaan.',
            default => 'Terjadi kesalahan pada server.',
        };
    }

    protected function getAppropriateErrorMessage(Exception $e): string
    {
        if (str_contains($e->getMessage(), 'cURL error 6')) {
            return 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
        }

        if (str_contains($e->getMessage(), 'Connection timed out')) {
            return 'Waktu koneksi habis. Silakan coba lagi.';
        }

        return $e->getMessage();
    }

    // ------------------
    // Public Request Methods
    // ------------------

    public function call(ApiMethod $method, string $endpoint, ?array $body = null): array
    {
        return $this->request($method, $endpoint, $body);
    }

    // ------------------
    // Logging Methods
    // ------------------

    protected function logRequest(ApiMethod $method, string $url, array $headers, ?array $body): void
    {
        $log = "\n🔍 API Request:\n";
        $log .= "URL: {$method->value} {$url}\n";
        $log .= 'Headers: ' . json_encode($headers, JSON_PRETTY_PRINT) . "\n";

        if ($body) {
            $log .= 'Body: ' . json_encode($body, JSON_PRETTY_PRINT) . "\n";
        }

        error_log($log);
    }

    protected function logResponse($response): void
    {
        $log = "\n📡 API Response:\n";
        $log .= "Status: {$response->status()}\n";
        $log .= 'Body: ' . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";

        error_log($log);
    }
}
