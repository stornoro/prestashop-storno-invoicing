<?php

/**
 * Copyright since 2024 Storno
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/MIT
 *
 * @author    Storno <support@storno.ro>
 * @copyright Since 2024 Storno
 * @license   https://opensource.org/licenses/MIT MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class StornoInvoicingWebhookModuleFrontController extends ModuleFrontController
{
    /** @var bool Suppress page layout — return raw JSON response */
    public $contentOnly = true;

    /** @var bool Force HTTPS */
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();
        $this->handleWebhook();
    }

    private function handleWebhook()
    {
        // Only accept POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respond(405, ['error' => 'Method not allowed']);

            return;
        }

        $payload = file_get_contents('php://input');
        if (empty($payload)) {
            $this->respond(400, ['error' => 'Empty payload']);

            return;
        }

        // Verify HMAC signature
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
        $secret = Configuration::get('STORNO_WEBHOOK_SECRET');

        if (!$secret) {
            PrestaShopLogger::addLog('Storno webhook: No secret configured', 3);
            $this->respond(500, ['error' => 'Webhook not configured']);

            return;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expected, $signature)) {
            PrestaShopLogger::addLog('Storno webhook: Invalid signature', 2);
            $this->respond(401, ['error' => 'Invalid signature']);

            return;
        }

        // Parse event
        $event = json_decode($payload, true);
        if (!$event || !isset($event['event']) || !isset($event['data'])) {
            $this->respond(400, ['error' => 'Invalid payload']);

            return;
        }

        $eventType = $event['event'];
        $data = $event['data'];
        $invoiceId = $data['id'] ?? null;

        if (!$invoiceId) {
            $this->respond(200, ['status' => 'ignored', 'reason' => 'no invoice id']);

            return;
        }

        // Find the matching order
        $mapping = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'storno_invoices
             WHERE storno_invoice_id = "' . pSQL($invoiceId) . '"'
        );

        if (!$mapping) {
            // Could be an invoice not created via PrestaShop
            $this->respond(200, ['status' => 'ignored', 'reason' => 'no matching order']);

            return;
        }

        $orderId = (int) $mapping['id_order'];

        switch ($eventType) {
            case 'invoice.issued':
                $this->updateInvoiceStatus($invoiceId, 'issued', $data);
                $this->logEvent($orderId, 'Invoice issued: ' . ($data['number'] ?? ''));
                break;

            case 'invoice.validated':
                $this->updateInvoiceStatus($invoiceId, 'validated', $data);
                $this->logEvent($orderId, 'Invoice validated by ANAF');
                break;

            case 'invoice.rejected':
                $errors = $data['anafValidationErrors'] ?? 'Unknown error';
                if (is_array($errors)) {
                    $errors = json_encode($errors);
                }
                $this->updateInvoiceStatus($invoiceId, 'rejected', $data, $errors);
                $this->logEvent($orderId, 'Invoice REJECTED by ANAF: ' . $errors);
                break;

            case 'invoice.paid':
                $this->updateInvoiceStatus($invoiceId, 'paid', $data);
                $this->logEvent($orderId, 'Invoice marked as paid in Storno');
                break;

            case 'payment.created':
                $amount = $data['amount'] ?? '';
                $this->logEvent($orderId, 'Payment received: ' . $amount . ' ' . ($data['currency'] ?? ''));
                break;

            default:
                $this->logEvent($orderId, 'Webhook event: ' . $eventType);
                break;
        }

        $this->respond(200, ['status' => 'ok']);
    }

    private function updateInvoiceStatus($invoiceId, $status, $data, $error = '')
    {
        $update = [
            'storno_status' => pSQL($status),
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        if (!empty($data['number'])) {
            $update['storno_invoice_number'] = pSQL($data['number']);
        }

        if ($error) {
            $update['storno_error'] = pSQL(mb_substr($error, 0, 500));
        } else {
            $update['storno_error'] = '';
        }

        Db::getInstance()->update(
            'storno_invoices',
            $update,
            'storno_invoice_id = "' . pSQL($invoiceId) . '"'
        );
    }

    private function logEvent($orderId, $message)
    {
        // Log to PrestaShop system log
        PrestaShopLogger::addLog(
            'Storno: ' . $message,
            1,
            null,
            'Order',
            $orderId
        );

        // Also log to our webhook log table
        Db::getInstance()->insert('storno_webhook_log', [
            'id_order' => (int) $orderId,
            'event' => pSQL(mb_substr($message, 0, 255)),
            'date_add' => date('Y-m-d H:i:s'),
        ]);
    }

    private function respond($code, $body)
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($body);
        exit;
    }
}
