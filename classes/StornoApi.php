<?php
/**
 * Storno API Client for PrestaShop
 *
 * Handles all communication with the Storno REST API.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class StornoApi
{
    private $baseUrl;
    private $apiKey;
    private $companyId;

    public function __construct($baseUrl, $apiKey, $companyId)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->companyId = $companyId;
    }

    /**
     * Generic API request
     *
     * @param string     $method   HTTP method (GET, POST, PUT, DELETE)
     * @param string     $endpoint API endpoint path
     * @param array|null $body     Request body (JSON-encoded)
     *
     * @return array Decoded response
     *
     * @throws RuntimeException On API error
     */
    public function request($method, $endpoint, $body = null)
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization: ' . $this->apiKey,
            'X-Company: ' . $this->companyId,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: StornoPrestaShop/1.0',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException('cURL error: ' . $curlError);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $message = 'HTTP ' . $httpCode;
            if (isset($decoded['message'])) {
                $message .= ': ' . $decoded['message'];
            } elseif (isset($decoded['detail'])) {
                $message .= ': ' . $decoded['detail'];
            } elseif (isset($decoded['violations'])) {
                $violations = [];
                foreach ($decoded['violations'] as $v) {
                    $violations[] = ($v['propertyPath'] ?? '') . ': ' . ($v['message'] ?? '');
                }
                $message .= ': ' . implode('; ', $violations);
            }
            throw new \RuntimeException($message);
        }

        return $decoded ?: [];
    }

    /**
     * Raw GET request returning response body (for PDF downloads)
     */
    public function requestRaw($endpoint)
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->apiKey,
                'X-Company: ' . $this->companyId,
                'User-Agent: StornoPrestaShop/1.0',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException('cURL error: ' . $curlError);
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException('HTTP ' . $httpCode . ' downloading resource');
        }

        return $response;
    }

    // ─── Companies ───────────────────────────────────────────────

    public function listCompanies()
    {
        return $this->request('GET', '/api/v1/companies');
    }

    // ─── Document Series ────────────────────────────────────────

    public function listDocumentSeries()
    {
        return $this->request('GET', '/api/v1/document-series');
    }

    // ─── Clients ─────────────────────────────────────────────────

    public function createClient($data)
    {
        return $this->request('POST', '/api/v1/clients', $data);
    }

    public function getClient($id)
    {
        return $this->request('GET', '/api/v1/clients/' . $id);
    }

    public function anafLookup($cui)
    {
        return $this->request('GET', '/api/v1/clients/anaf-lookup?cui=' . urlencode($cui));
    }

    // ─── Invoices ────────────────────────────────────────────────

    public function createInvoice($data)
    {
        return $this->request('POST', '/api/v1/invoices', $data);
    }

    public function getInvoice($id)
    {
        return $this->request('GET', '/api/v1/invoices/' . $id);
    }

    public function issueInvoice($id)
    {
        return $this->request('POST', '/api/v1/invoices/' . $id . '/issue');
    }

    public function submitInvoice($id)
    {
        return $this->request('POST', '/api/v1/invoices/' . $id . '/submit');
    }

    public function downloadPdf($id)
    {
        return $this->requestRaw('/api/v1/invoices/' . $id . '/pdf');
    }

    // ─── Webhooks ────────────────────────────────────────────────

    public function createWebhook($data)
    {
        return $this->request('POST', '/api/v1/webhooks', $data);
    }

    public function deleteWebhook($id)
    {
        return $this->request('DELETE', '/api/v1/webhooks/' . $id);
    }
}
