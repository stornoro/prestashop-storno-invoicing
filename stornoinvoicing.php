<?php
/**
 * Storno Invoicing - PrestaShop Module
 *
 * Automatically creates and manages invoices via the Storno API
 * when orders are placed in PrestaShop.
 *
 * @author  Storno <support@storno.ro>
 * @license MIT
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/classes/StornoApi.php';

class StornoInvoicing extends Module
{
    public function __construct()
    {
        $this->name = 'stornoinvoicing';
        $this->tab = 'billing_invoicing';
        $this->version = '1.0.0';
        $this->author = 'Storno';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => '8.99.99'];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Storno Invoicing');
        $this->description = $this->l('Automatic invoice generation and e-Factura submission via Storno API.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall? All invoice mapping data will be lost.');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        // Create database tables
        include dirname(__FILE__) . '/sql/install.php';

        // Register hooks
        return $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('displayAdminOrder')
            && $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        include dirname(__FILE__) . '/sql/uninstall.php';

        Configuration::deleteByName('STORNO_API_URL');
        Configuration::deleteByName('STORNO_API_KEY');
        Configuration::deleteByName('STORNO_COMPANY_ID');
        Configuration::deleteByName('STORNO_WEBHOOK_SECRET');
        Configuration::deleteByName('STORNO_WEBHOOK_ID');
        Configuration::deleteByName('STORNO_AUTO_ISSUE');
        Configuration::deleteByName('STORNO_DEFAULT_VAT_RATE');
        Configuration::deleteByName('STORNO_SHIPPING_VAT_RATE');
        Configuration::deleteByName('STORNO_TRIGGER_STATUS');

        return parent::uninstall();
    }

    // ─── Configuration Page ──────────────────────────────────────

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitStornoSettings')) {
            $output .= $this->postProcess();
        }

        if (Tools::isSubmit('submitStornoRegisterWebhook')) {
            $output .= $this->registerWebhook();
        }

        if (Tools::isSubmit('submitStornoTestConnection')) {
            $output .= $this->testConnection();
        }

        return $output . $this->renderForm();
    }

    private function postProcess()
    {
        $fields = [
            'STORNO_API_URL' => 'API URL',
            'STORNO_API_KEY' => 'API Key',
            'STORNO_COMPANY_ID' => 'Company UUID',
        ];

        foreach ($fields as $key => $label) {
            $value = Tools::getValue($key);
            if (empty($value)) {
                return $this->displayError($this->l($label . ' is required.'));
            }
        }

        Configuration::updateValue('STORNO_API_URL', Tools::getValue('STORNO_API_URL'));
        Configuration::updateValue('STORNO_API_KEY', Tools::getValue('STORNO_API_KEY'));
        Configuration::updateValue('STORNO_COMPANY_ID', Tools::getValue('STORNO_COMPANY_ID'));
        Configuration::updateValue('STORNO_AUTO_ISSUE', (int) Tools::getValue('STORNO_AUTO_ISSUE'));
        Configuration::updateValue('STORNO_DEFAULT_VAT_RATE', (float) Tools::getValue('STORNO_DEFAULT_VAT_RATE'));
        Configuration::updateValue('STORNO_SHIPPING_VAT_RATE', (float) Tools::getValue('STORNO_SHIPPING_VAT_RATE'));
        Configuration::updateValue('STORNO_TRIGGER_STATUS', (int) Tools::getValue('STORNO_TRIGGER_STATUS'));

        return $this->displayConfirmation($this->l('Settings saved successfully.'));
    }

    private function testConnection()
    {
        try {
            $api = $this->getApi();
            $api->listCompanies();
            return $this->displayConfirmation($this->l('Connection successful! API key is valid.'));
        } catch (\Exception $e) {
            return $this->displayError($this->l('Connection failed: ') . $e->getMessage());
        }
    }

    private function registerWebhook()
    {
        try {
            $api = $this->getApi();
            $webhookUrl = Context::getContext()->link->getModuleLink(
                $this->name,
                'webhook',
                [],
                true
            );

            $result = $api->createWebhook([
                'url' => $webhookUrl,
                'events' => [
                    'invoice.issued',
                    'invoice.validated',
                    'invoice.rejected',
                    'invoice.paid',
                    'payment.created',
                ],
                'description' => 'PrestaShop - ' . Configuration::get('PS_SHOP_NAME'),
                'isActive' => true,
            ]);

            Configuration::updateValue('STORNO_WEBHOOK_SECRET', $result['secret']);
            Configuration::updateValue('STORNO_WEBHOOK_ID', $result['uuid']);

            return $this->displayConfirmation(
                $this->l('Webhook registered successfully at: ') . $webhookUrl
            );
        } catch (\Exception $e) {
            return $this->displayError($this->l('Webhook registration failed: ') . $e->getMessage());
        }
    }

    private function renderForm()
    {
        $orderStatuses = OrderState::getOrderStates($this->context->language->id);
        $statusOptions = [];
        foreach ($orderStatuses as $status) {
            $statusOptions[] = [
                'id' => $status['id_order_state'],
                'name' => $status['name'],
            ];
        }

        $fields_form = [
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Storno API Settings'),
                        'icon' => 'icon-cogs',
                    ],
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->l('API URL'),
                            'name' => 'STORNO_API_URL',
                            'desc' => $this->l('Your Storno instance URL, e.g. https://factura.domeniu.ro'),
                            'required' => true,
                            'class' => 'fixed-width-xxl',
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('API Key'),
                            'name' => 'STORNO_API_KEY',
                            'desc' => $this->l('API token starting with af_... (created in Storno → Settings → API Keys)'),
                            'required' => true,
                            'class' => 'fixed-width-xxl',
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Company UUID'),
                            'name' => 'STORNO_COMPANY_ID',
                            'desc' => $this->l('The UUID of the company to create invoices for.'),
                            'required' => true,
                            'class' => 'fixed-width-xxl',
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save Settings'),
                    ],
                ],
            ],
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Invoice Settings'),
                        'icon' => 'icon-file-text-o',
                    ],
                    'input' => [
                        [
                            'type' => 'select',
                            'label' => $this->l('Trigger on Order Status'),
                            'name' => 'STORNO_TRIGGER_STATUS',
                            'desc' => $this->l('Create the Storno invoice when the order reaches this status. Default: Payment accepted.'),
                            'options' => [
                                'query' => $statusOptions,
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        [
                            'type' => 'switch',
                            'label' => $this->l('Auto-issue invoices'),
                            'name' => 'STORNO_AUTO_ISSUE',
                            'desc' => $this->l('Automatically issue the invoice (assign number, generate PDF/XML) after creation. e-Factura submission is handled by Storno based on the company settings (Settings → e-Factura delay).'),
                            'is_bool' => true,
                            'values' => [
                                ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                                ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Default VAT Rate (%)'),
                            'name' => 'STORNO_DEFAULT_VAT_RATE',
                            'desc' => $this->l('Fallback VAT rate if product tax rate is not set.'),
                            'class' => 'fixed-width-sm',
                            'suffix' => '%',
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Shipping VAT Rate (%)'),
                            'name' => 'STORNO_SHIPPING_VAT_RATE',
                            'desc' => $this->l('VAT rate applied to shipping costs.'),
                            'class' => 'fixed-width-sm',
                            'suffix' => '%',
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save Settings'),
                    ],
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = 'submitStornoSettings';
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper->fields_value = [
            'STORNO_API_URL' => Configuration::get('STORNO_API_URL'),
            'STORNO_API_KEY' => Configuration::get('STORNO_API_KEY'),
            'STORNO_COMPANY_ID' => Configuration::get('STORNO_COMPANY_ID'),
            'STORNO_AUTO_ISSUE' => Configuration::get('STORNO_AUTO_ISSUE'),
            'STORNO_DEFAULT_VAT_RATE' => Configuration::get('STORNO_DEFAULT_VAT_RATE') ?: '21',
            'STORNO_SHIPPING_VAT_RATE' => Configuration::get('STORNO_SHIPPING_VAT_RATE') ?: '21',
            'STORNO_TRIGGER_STATUS' => Configuration::get('STORNO_TRIGGER_STATUS') ?: 2,
        ];

        $output = $helper->generateForm($fields_form);

        // Add action buttons
        $output .= $this->renderActionButtons();

        return $output;
    }

    private function renderActionButtons()
    {
        $webhookStatus = Configuration::get('STORNO_WEBHOOK_SECRET')
            ? '<span class="badge badge-success">' . $this->l('Registered') . '</span>'
            : '<span class="badge badge-warning">' . $this->l('Not registered') . '</span>';

        $currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $token = Tools::getAdminTokenLite('AdminModules');

        return '
        <div class="panel">
            <div class="panel-heading"><i class="icon-link"></i> ' . $this->l('Actions') . '</div>
            <div class="form-wrapper">
                <div class="row">
                    <div class="col-lg-6">
                        <p><strong>' . $this->l('Webhook Status:') . '</strong> ' . $webhookStatus . '</p>
                        <form method="post" action="' . $currentIndex . '&token=' . $token . '">
                            <button type="submit" name="submitStornoRegisterWebhook" class="btn btn-default">
                                <i class="icon-refresh"></i> ' . $this->l('Register Webhook') . '
                            </button>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <p><strong>' . $this->l('Test your API connection:') . '</strong></p>
                        <form method="post" action="' . $currentIndex . '&token=' . $token . '">
                            <button type="submit" name="submitStornoTestConnection" class="btn btn-default">
                                <i class="icon-check"></i> ' . $this->l('Test Connection') . '
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>';
    }

    // ─── Hooks ───────────────────────────────────────────────────

    /**
     * Create Storno invoice when order is validated (if trigger = actionValidateOrder)
     */
    public function hookActionValidateOrder($params)
    {
        $triggerStatus = (int) Configuration::get('STORNO_TRIGGER_STATUS');

        // Status 0 or not set means trigger on order validation directly
        if ($triggerStatus && $triggerStatus > 0) {
            return;
        }

        $this->processOrder($params['order']);
    }

    /**
     * Create Storno invoice when order reaches the configured status
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        $triggerStatus = (int) Configuration::get('STORNO_TRIGGER_STATUS');
        $newStatus = (int) $params['newOrderStatus']->id;

        if (!$triggerStatus || $newStatus !== $triggerStatus) {
            return;
        }

        $order = new Order((int) $params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        $this->processOrder($order);
    }

    /**
     * Display Storno invoice info on admin order page
     */
    public function hookDisplayAdminOrder($params)
    {
        $orderId = (int) ($params['id_order'] ?? 0);
        if (!$orderId) {
            return '';
        }

        $mapping = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'storno_invoices
             WHERE id_order = ' . $orderId
        );

        if (!$mapping) {
            return '';
        }

        $this->context->smarty->assign([
            'storno_invoice_id' => $mapping['storno_invoice_id'],
            'storno_invoice_number' => $mapping['storno_invoice_number'],
            'storno_status' => $mapping['storno_status'],
            'storno_url' => Configuration::get('STORNO_API_URL'),
            'storno_error' => $mapping['storno_error'],
            'date_add' => $mapping['date_add'],
        ]);

        return $this->display(__FILE__, 'views/templates/hook/admin_order.tpl');
    }

    /**
     * Add CSS in back office
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        }
    }

    // ─── Invoice Processing ──────────────────────────────────────

    private function processOrder(Order $order)
    {
        // Check if invoice already exists for this order
        $existing = Db::getInstance()->getValue(
            'SELECT storno_invoice_id FROM ' . _DB_PREFIX_ . 'storno_invoices
             WHERE id_order = ' . (int) $order->id
        );

        if ($existing) {
            return;
        }

        if (!Configuration::get('STORNO_API_KEY') || !Configuration::get('STORNO_COMPANY_ID')) {
            PrestaShopLogger::addLog(
                'Storno: Cannot create invoice - module not configured',
                3,
                null,
                'Order',
                $order->id
            );
            return;
        }

        try {
            $api = $this->getApi();

            // 1. Create or find client
            $clientId = $this->ensureClient($api, $order);

            // 2. Build invoice lines
            $lines = $this->buildInvoiceLines($order);

            // 3. Create invoice
            $currency = new Currency($order->id_currency);
            $invoiceData = [
                'clientId' => $clientId,
                'issueDate' => date('Y-m-d'),
                'dueDate' => date('Y-m-d', strtotime('+30 days')),
                'currency' => $currency->iso_code,
                'paymentMethod' => $this->mapPaymentMethod($order->module),
                'internalNote' => 'PrestaShop #' . $order->reference,
                'orderNumber' => $order->reference,
                'idempotencyKey' => 'ps-' . $order->reference . '-' . $order->id,
                'lines' => $lines,
            ];

            $result = $api->createInvoice($invoiceData);
            $invoiceId = $result['invoice']['id'];
            $invoiceNumber = $result['invoice']['number'] ?? '';
            $status = 'draft';

            // 4. Auto-issue if enabled
            // e-Factura submission is handled automatically by the Storno backend
            // based on the company's efacturaDelayHours setting.
            if (Configuration::get('STORNO_AUTO_ISSUE')) {
                $issueResult = $api->issueInvoice($invoiceId);
                $invoiceNumber = $issueResult['number'] ?? $invoiceNumber;
                $status = 'issued';
            }

            // Save mapping
            Db::getInstance()->insert('storno_invoices', [
                'id_order' => (int) $order->id,
                'storno_invoice_id' => pSQL($invoiceId),
                'storno_invoice_number' => pSQL($invoiceNumber),
                'storno_status' => pSQL($status),
                'storno_error' => '',
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ]);

            PrestaShopLogger::addLog(
                'Storno: Invoice ' . $invoiceNumber . ' created for order #' . $order->reference,
                1,
                null,
                'Order',
                $order->id
            );
        } catch (\Exception $e) {
            // Save error for admin visibility
            Db::getInstance()->insert('storno_invoices', [
                'id_order' => (int) $order->id,
                'storno_invoice_id' => '',
                'storno_invoice_number' => '',
                'storno_status' => 'error',
                'storno_error' => pSQL(mb_substr($e->getMessage(), 0, 500)),
                'date_add' => date('Y-m-d H:i:s'),
                'date_upd' => date('Y-m-d H:i:s'),
            ]);

            PrestaShopLogger::addLog(
                'Storno: Failed to create invoice for order #' . $order->reference . ': ' . $e->getMessage(),
                3,
                null,
                'Order',
                $order->id
            );
        }
    }

    private function ensureClient(StornoApi $api, Order $order): string
    {
        $customer = new Customer($order->id_customer);
        $address = new Address($order->id_address_invoice);
        $country = new Country($address->id_country);

        $clientData = [
            'name' => $address->company ?: trim($customer->firstname . ' ' . $customer->lastname),
            'type' => $address->company ? 'company' : 'individual',
            'email' => $customer->email,
            'address' => trim($address->address1 . ($address->address2 ? ', ' . $address->address2 : '')),
            'city' => $address->city,
            'country' => $country->iso_code,
            'postalCode' => $address->postcode,
        ];

        if ($address->phone) {
            $clientData['phone'] = $address->phone;
        } elseif ($address->phone_mobile) {
            $clientData['phone'] = $address->phone_mobile;
        }

        // Company-specific fields
        if ($address->company) {
            if ($address->vat_number) {
                $clientData['vatCode'] = $address->vat_number;
                $clientData['isVatPayer'] = true;
            }
            if ($address->dni) {
                $clientData['cui'] = $address->dni;
            }
        }

        // State/county
        if ($address->id_state) {
            $state = new State($address->id_state);
            if (Validate::isLoadedObject($state)) {
                $clientData['county'] = $state->name;
            }
        }

        $result = $api->createClient($clientData);

        return $result['client']['id'];
    }

    private function buildInvoiceLines(Order $order): array
    {
        $lines = [];
        $defaultVat = (float) (Configuration::get('STORNO_DEFAULT_VAT_RATE') ?: 21);

        foreach ($order->getProductsDetail() as $product) {
            $vatRate = (float) $product['tax_rate'];
            if ($vatRate <= 0) {
                $vatRate = $defaultVat;
            }

            $line = [
                'description' => $product['product_name'],
                'quantity' => (float) $product['product_quantity'],
                'unitPrice' => round((float) $product['unit_price_tax_excl'], 4),
                'vatRate' => $vatRate,
                'unitOfMeasure' => 'buc',
            ];

            if (!empty($product['product_reference'])) {
                $line['productCode'] = $product['product_reference'];
            }

            $lines[] = $line;
        }

        // Shipping as a separate line
        $shippingCost = (float) $order->total_shipping_tax_excl;
        if ($shippingCost > 0) {
            $shippingVat = (float) (Configuration::get('STORNO_SHIPPING_VAT_RATE') ?: 21);
            $lines[] = [
                'description' => 'Transport',
                'quantity' => 1,
                'unitPrice' => round($shippingCost, 4),
                'vatRate' => $shippingVat,
                'unitOfMeasure' => 'buc',
            ];
        }

        // Discounts (cart rules) as negative lines
        $totalDiscounts = (float) $order->total_discounts_tax_excl;
        if ($totalDiscounts > 0) {
            $lines[] = [
                'description' => 'Discount',
                'quantity' => 1,
                'unitPrice' => -round($totalDiscounts, 4),
                'vatRate' => $defaultVat,
                'unitOfMeasure' => 'buc',
            ];
        }

        // Wrapping costs
        $wrappingCost = (float) $order->total_wrapping_tax_excl;
        if ($wrappingCost > 0) {
            $lines[] = [
                'description' => 'Ambalare cadou',
                'quantity' => 1,
                'unitPrice' => round($wrappingCost, 4),
                'vatRate' => $defaultVat,
                'unitOfMeasure' => 'buc',
            ];
        }

        return $lines;
    }

    private function mapPaymentMethod(string $module): string
    {
        $map = [
            'ps_wirepayment' => 'bank_transfer',
            'ps_checkpayment' => 'cheque',
            'ps_cashondelivery' => 'cash',
            'stripe' => 'card',
            'paypal' => 'card',
            'ps_eventspayment' => 'card',
        ];

        return $map[$module] ?? 'other';
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function getApi(): StornoApi
    {
        return new StornoApi(
            Configuration::get('STORNO_API_URL'),
            Configuration::get('STORNO_API_KEY'),
            Configuration::get('STORNO_COMPANY_ID')
        );
    }
}
