<?php
/**
 * CREODENT Integrated Web Operations System
 * Evolution Web Portal V18 API Client
 *
 * This service handles all communication with the Evolution Web Portal
 * using XML POST requests as documented in Evolution_Web_Portal_V18_Summary.md
 *
 * Supported Events:
 * - account_login: Test connection and validate credentials
 * - cases_caselist: Get list of cases for a date range
 * - case_caseinformation: Get detailed information for a specific case
 * - case_noteget: Get notes for a case
 * - case_noteadd: Add a note to a case
 * - case_imagelist: Get list of images for a case
 */

declare(strict_types=1);

class EvolutionClient {
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout;
    private int $maxRetries;
    private int $retryDelay;

    /**
     * Constructor
     */
    public function __construct() {
        $config = config('evolution');

        if (!$config['enabled']) {
            throw new Exception('Evolution Web Portal integration is disabled');
        }

        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->timeout = $config['timeout'];
        $this->maxRetries = $config['max_retries'];
        $this->retryDelay = $config['retry_delay'];
    }

    /**
     * Call an Evolution Web Portal event
     *
     * @param string $eventName The event to call (e.g., 'cases_caselist')
     * @param array $params Additional parameters for the event
     * @return array Parsed response data
     * @throws Exception
     */
    public function callEvent(string $eventName, array $params = []): array {
        // Build XML request
        $xmlRequest = $this->buildXmlRequest($eventName, $params);

        // Log the request
        $this->logRequest($eventName, $xmlRequest);

        // Make HTTP request with retry logic
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $xmlResponse = $this->sendRequest($eventName, $xmlRequest);
                $parsedResponse = $this->parseXmlResponse($xmlResponse);

                // Log successful response
                $this->logResponse($eventName, $xmlResponse, 'success');

                return $parsedResponse;

            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    // Wait before retrying
                    sleep($this->retryDelay * $attempt);
                }
            }
        }

        // All retries failed, log error and throw
        $this->logResponse($eventName, $lastException->getMessage(), 'error');
        throw new Exception('Evolution API call failed after ' . $this->maxRetries . ' attempts: ' . $lastException->getMessage());
    }

    /**
     * Build XML request based on event type
     *
     * @param string $eventName
     * @param array $params
     * @return string XML string
     */
    private function buildXmlRequest(string $eventName, array $params): string {
        // Always include username and password
        $params['username'] = $this->username;
        $params['password'] = $this->password;

        // Create XML document
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request/>');

        // Add all parameters as child elements
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                // Handle nested arrays
                $child = $xml->addChild($key);
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }

        return $xml->asXML();
    }

    /**
     * Convert array to XML recursively
     *
     * @param array $data
     * @param SimpleXMLElement $xml
     */
    private function arrayToXml(array $data, SimpleXMLElement $xml): void {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }

    /**
     * Send HTTP POST request to Evolution portal
     *
     * @param string $eventName
     * @param string $xmlRequest
     * @return string XML response
     * @throws Exception
     */
    private function sendRequest(string $eventName, string $xmlRequest): string {
        $url = $this->baseUrl . '/?event=' . urlencode($eventName);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xmlRequest,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Content-Length: ' . strlen($xmlRequest),
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new Exception('cURL error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('HTTP error ' . $httpCode . ': ' . $response);
        }

        return $response;
    }

    /**
     * Parse XML response into PHP array
     *
     * @param string $xmlResponse
     * @return array
     * @throws Exception
     */
    private function parseXmlResponse(string $xmlResponse): array {
        try {
            $xml = new SimpleXMLElement($xmlResponse);
            return $this->xmlToArray($xml);
        } catch (Exception $e) {
            throw new Exception('Failed to parse XML response: ' . $e->getMessage());
        }
    }

    /**
     * Convert SimpleXMLElement to array recursively
     *
     * @param SimpleXMLElement $xml
     * @return array
     */
    private function xmlToArray(SimpleXMLElement $xml): array {
        $array = [];

        foreach ($xml->children() as $element) {
            $name = $element->getName();

            if ($element->count() > 0) {
                // Has children, recurse
                $array[$name] = $this->xmlToArray($element);
            } else {
                // Leaf node
                $array[$name] = (string)$element;
            }
        }

        return $array;
    }

    /**
     * Log request for debugging and audit
     *
     * @param string $eventName
     * @param string $xmlRequest
     */
    private function logRequest(string $eventName, string $xmlRequest): void {
        // Remove password from log
        $safeXml = preg_replace('/<password>.*?<\/password>/', '<password>***</password>', $xmlRequest);

        error_log("Evolution API Request [$eventName]: " . $safeXml);
    }

    /**
     * Log response for debugging and audit
     *
     * @param string $eventName
     * @param string $xmlResponse
     * @param string $status
     */
    private function logResponse(string $eventName, string $xmlResponse, string $status): void {
        error_log("Evolution API Response [$eventName] [$status]: " . substr($xmlResponse, 0, 500));
    }

    /**
     * Test connection to Evolution portal
     *
     * @return bool
     */
    public function testConnection(): bool {
        try {
            $result = $this->callEvent('account_login', []);
            return isset($result['success']) && $result['success'] === 'true';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get list of cases for a date range
     *
     * @param string $startDate Format: YYYY-MM-DD
     * @param string $endDate Format: YYYY-MM-DD
     * @return array
     */
    public function getCaseList(string $startDate, string $endDate): array {
        return $this->callEvent('cases_caselist', [
            'startdate' => $startDate,
            'enddate' => $endDate,
        ]);
    }

    /**
     * Get detailed information for a specific case
     *
     * @param string $caseNumber
     * @return array
     */
    public function getCaseInformation(string $caseNumber): array {
        return $this->callEvent('case_caseinformation', [
            'casenumber' => $caseNumber,
        ]);
    }

    /**
     * Get notes for a case
     *
     * @param string $caseNumber
     * @return array
     */
    public function getCaseNotes(string $caseNumber): array {
        return $this->callEvent('case_noteget', [
            'casenumber' => $caseNumber,
        ]);
    }

    /**
     * Add a note to a case
     *
     * @param string $caseNumber
     * @param string $note
     * @param string $noteType
     * @return array
     */
    public function addCaseNote(string $caseNumber, string $note, string $noteType = 'General'): array {
        return $this->callEvent('case_noteadd', [
            'casenumber' => $caseNumber,
            'note' => $note,
            'notetype' => $noteType,
        ]);
    }

    /**
     * Get list of images for a case
     *
     * @param string $caseNumber
     * @return array
     */
    public function getCaseImages(string $caseNumber): array {
        return $this->callEvent('case_imagelist', [
            'casenumber' => $caseNumber,
        ]);
    }
}
