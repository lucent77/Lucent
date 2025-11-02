<?php
/**
 * Evolution Web Portal API Client
 * Handles all communication with Evolution Web Portal V18
 */

namespace App\Services;

use App\Core\Logger;

class EvolutionClient
{
    private array $config;
    private Logger $logger;
    private int $retryCount = 0;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->logger = new Logger($config['logging']['path'] ?? __DIR__ . '/../../logs', 'evolution');
    }

    /**
     * Account Login - Authenticate with Evolution Portal
     */
    public function accountLogin(): array
    {
        $xml = $this->buildXml([
            'username' => $this->config['evolution']['username'],
            'password' => $this->config['evolution']['password']
        ]);

        return $this->request('account_login', $xml);
    }

    /**
     * Get case list for date range
     */
    public function getCaseList(string $startDate, string $endDate): array
    {
        $xml = $this->buildXml([
            'username' => $this->config['evolution']['username'],
            'password' => $this->config['evolution']['password'],
            'startdate' => $startDate,
            'enddate' => $endDate
        ]);

        return $this->request('cases_caselist', $xml);
    }

    /**
     * Get detailed case information
     */
    public function getCaseInformation(string $caseNumber): array
    {
        $xml = $this->buildXml([
            'username' => $this->config['evolution']['username'],
            'password' => $this->config['evolution']['password'],
            'casenumber' => $caseNumber
        ]);

        return $this->request('case_caseinformation', $xml);
    }

    /**
     * Get case notes
     */
    public function getCaseNotes(string $caseNumber): array
    {
        $xml = $this->buildXml([
            'username' => $this->config['evolution']['username'],
            'password' => $this->config['evolution']['password'],
            'casenumber' => $caseNumber
        ]);

        return $this->request('case_noteget', $xml);
    }

    /**
     * Add note to case
     */
    public function addCaseNote(string $caseNumber, string $note): array
    {
        $xml = $this->buildXml([
            'username' => $this->config['evolution']['username'],
            'password' => $this->config['evolution']['password'],
            'casenumber' => $caseNumber,
            'note' => $note
        ]);

        return $this->request('case_noteadd', $xml);
    }

    /**
     * Get case image list
     */
    public function getCaseImages(string $caseNumber): array
    {
        $xml = $this->buildXml([
            'username' => $this->config['evolution']['username'],
            'password' => $this->config['evolution']['password'],
            'casenumber' => $caseNumber
        ]);

        return $this->request('case_imagelist', $xml);
    }

    /**
     * Make HTTP request to Evolution Portal
     */
    private function request(string $event, string $xmlBody): array
    {
        if (!$this->config['evolution']['enabled']) {
            return [
                'success' => false,
                'message' => 'Evolution Portal integration is disabled',
                'data' => null
            ];
        }

        $url = $this->config['evolution']['base_url'] . '/?event=' . $event;
        $timeout = $this->config['evolution']['timeout'] ?? 30;
        $retryAttempts = $this->config['evolution']['retry_attempts'] ?? 3;
        $retryDelay = $this->config['evolution']['retry_delay'] ?? 2;

        $this->logger->info("Evolution API Request: $event", ['url' => $url]);

        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $ch = curl_init();

                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $xmlBody,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/xml',
                        'Content-Length: ' . strlen($xmlBody)
                    ],
                    CURLOPT_SSL_VERIFYPEER => $this->config['evolution']['verify_ssl'] ?? true,
                    CURLOPT_SSL_VERIFYHOST => 2
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);

                curl_close($ch);

                if ($response === false) {
                    throw new \RuntimeException("cURL Error: $error");
                }

                if ($httpCode !== 200) {
                    throw new \RuntimeException("HTTP Error: $httpCode");
                }

                // Parse XML response
                $parsedResponse = $this->parseXmlResponse($response);

                $this->logger->info("Evolution API Response: $event", [
                    'http_code' => $httpCode,
                    'attempt' => $attempt
                ]);

                // Log raw data if enabled
                if ($this->config['import']['log_raw_data'] ?? true) {
                    $this->logger->debug("Raw Request XML", ['xml' => $xmlBody]);
                    $this->logger->debug("Raw Response XML", ['xml' => $response]);
                }

                return [
                    'success' => true,
                    'message' => 'Request successful',
                    'data' => $parsedResponse,
                    'raw_request' => $xmlBody,
                    'raw_response' => $response,
                    'http_code' => $httpCode
                ];

            } catch (\Exception $e) {
                $this->logger->error("Evolution API Error (Attempt $attempt/$retryAttempts): " . $e->getMessage(), [
                    'event' => $event,
                    'url' => $url
                ]);

                if ($attempt < $retryAttempts) {
                    // Exponential backoff
                    $delay = $retryDelay * pow(2, $attempt - 1);
                    $this->logger->info("Retrying in {$delay} seconds...");
                    sleep($delay);
                    continue;
                }

                // All retries failed
                return [
                    'success' => false,
                    'message' => 'Evolution API request failed: ' . $e->getMessage(),
                    'data' => null,
                    'error' => $e->getMessage(),
                    'attempts' => $attempt
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Maximum retry attempts reached',
            'data' => null
        ];
    }

    /**
     * Build XML request body
     */
    private function buildXml(array $params): string
    {
        $xml = new \SimpleXMLElement('<request/>');

        foreach ($params as $key => $value) {
            $xml->addChild($key, htmlspecialchars($value));
        }

        return $xml->asXML();
    }

    /**
     * Parse XML response to array
     */
    private function parseXmlResponse(string $xmlString): array
    {
        try {
            // Suppress XML parsing errors
            libxml_use_internal_errors(true);

            $xml = simplexml_load_string($xmlString);

            if ($xml === false) {
                $errors = libxml_get_errors();
                libxml_clear_errors();

                $errorMessages = array_map(function($error) {
                    return $error->message;
                }, $errors);

                throw new \RuntimeException('XML Parse Error: ' . implode(', ', $errorMessages));
            }

            // Convert SimpleXML to array
            return $this->xmlToArray($xml);

        } catch (\Exception $e) {
            $this->logger->error('Failed to parse XML response: ' . $e->getMessage());

            return [
                'error' => 'Failed to parse response',
                'message' => $e->getMessage(),
                'raw' => $xmlString
            ];
        }
    }

    /**
     * Convert SimpleXML object to array
     */
    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $json = json_encode($xml);
        return json_decode($json, true);
    }

    /**
     * Test connection to Evolution Portal
     */
    public function testConnection(): array
    {
        $this->logger->info('Testing Evolution Portal connection...');

        try {
            $result = $this->accountLogin();

            if ($result['success']) {
                $data = $result['data'] ?? [];

                if (isset($data['authenticated']) && $data['authenticated'] === true) {
                    $this->logger->info('Evolution Portal connection successful');

                    return [
                        'success' => true,
                        'message' => 'Connection successful',
                        'is_admin' => $data['is_admin'] ?? false,
                        'user_functions' => $data['userfunctions'] ?? []
                    ];
                }
            }

            $this->logger->warning('Evolution Portal authentication failed');

            return [
                'success' => false,
                'message' => 'Authentication failed',
                'data' => $result['data'] ?? null
            ];

        } catch (\Exception $e) {
            $this->logger->error('Evolution Portal connection test failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generic request method for custom events
     */
    public function customRequest(string $event, array $params = []): array
    {
        $params = array_merge([
            'username' => $this->config['evolution']['username'],
            'password' => $this->config['evolution']['password']
        ], $params);

        $xml = $this->buildXml($params);

        return $this->request($event, $xml);
    }
}
