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

require_once dirname(__FILE__) . '/classes/StornoApi.php';

class StornoInvoicing extends Module
{
    /** All configuration keys used by this module */
    public const CONFIG_KEYS = [
        // Connection
        'STORNO_API_URL',
        'STORNO_API_KEY',
        'STORNO_COMPANY_ID',
        // Invoice behavior
        'STORNO_TRIGGER_STATUS',
        'STORNO_AUTO_ISSUE',
        'STORNO_AUTO_APPLY_VAT_RULES',
        'STORNO_DOCUMENT_SERIES_ID',
        'STORNO_INVOICE_LANGUAGE',
        'STORNO_PAYMENT_TERM_DAYS',
        'STORNO_DEFAULT_VAT_RATE',
        'STORNO_SHIPPING_VAT_RATE',
        'STORNO_DEFAULT_UNIT',
        // Line descriptions
        'STORNO_SHIPPING_LABEL',
        'STORNO_DISCOUNT_LABEL',
        'STORNO_WRAPPING_LABEL',
        // Invoice content
        'STORNO_INVOICE_NOTES',
        'STORNO_INTERNAL_NOTE_FORMAT',
        // Payment method mapping
        'STORNO_PM_WIREPAYMENT',
        'STORNO_PM_CHECKPAYMENT',
        'STORNO_PM_CASHONDELIVERY',
        'STORNO_PM_STRIPE',
        'STORNO_PM_PAYPAL',
        'STORNO_PM_DEFAULT',
        // Webhook
        'STORNO_WEBHOOK_SECRET',
        'STORNO_WEBHOOK_ID',
    ];

    public function __construct()
    {
        $this->name = 'stornoinvoicing';
        $this->tab = 'billing_invoicing';
        $this->version = '1.1.0';
        $this->author = 'Storno';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => '9.99.99'];
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

        include dirname(__FILE__) . '/sql/install.php';

        // Set defaults
        Configuration::updateValue('STORNO_AUTO_ISSUE', 1);
        Configuration::updateValue('STORNO_AUTO_APPLY_VAT_RULES', 1);
        Configuration::updateValue('STORNO_PAYMENT_TERM_DAYS', 30);
        Configuration::updateValue('STORNO_DEFAULT_VAT_RATE', 21);
        Configuration::updateValue('STORNO_SHIPPING_VAT_RATE', 21);
        Configuration::updateValue('STORNO_DEFAULT_UNIT', 'buc');
        Configuration::updateValue('STORNO_SHIPPING_LABEL', 'Transport');
        Configuration::updateValue('STORNO_DISCOUNT_LABEL', 'Discount');
        Configuration::updateValue('STORNO_WRAPPING_LABEL', 'Ambalare cadou');
        Configuration::updateValue('STORNO_INTERNAL_NOTE_FORMAT', 'PrestaShop #{reference}');
        Configuration::updateValue('STORNO_PM_WIREPAYMENT', 'bank_transfer');
        Configuration::updateValue('STORNO_PM_CHECKPAYMENT', 'cheque');
        Configuration::updateValue('STORNO_PM_CASHONDELIVERY', 'cash');
        Configuration::updateValue('STORNO_PM_STRIPE', 'card');
        Configuration::updateValue('STORNO_PM_PAYPAL', 'card');
        Configuration::updateValue('STORNO_PM_DEFAULT', 'other');

        return $this->registerHook('actionValidateOrder')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('displayAdminOrder')
            && $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        include dirname(__FILE__) . '/sql/uninstall.php';

        foreach (self::CONFIG_KEYS as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    // ─── Configuration Page ──────────────────────────────────────

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitStornoConnection')) {
            $output .= $this->saveConnectionSettings();
        }
        if (Tools::isSubmit('submitStornoInvoice')) {
            $output .= $this->saveInvoiceSettings();
        }
        if (Tools::isSubmit('submitStornoLabels')) {
            $output .= $this->saveLabelSettings();
        }
        if (Tools::isSubmit('submitStornoPayments')) {
            $output .= $this->savePaymentSettings();
        }
        if (Tools::isSubmit('submitStornoRegisterWebhook')) {
            $output .= $this->registerWebhook();
        }
        if (Tools::isSubmit('submitStornoTestConnection')) {
            $output .= $this->testConnection();
        }

        return $output
            . $this->renderConnectionForm()
            . $this->renderInvoiceForm()
            . $this->renderLabelForm()
            . $this->renderPaymentForm()
            . $this->renderActionButtons();
    }

    private function saveConnectionSettings()
    {
        $requiredFields = [
            'STORNO_API_URL' => $this->l('API URL is required.'),
            'STORNO_API_KEY' => $this->l('API Key is required.'),
            'STORNO_COMPANY_ID' => $this->l('Company UUID is required.'),
        ];
        foreach ($requiredFields as $key => $errorMsg) {
            if (empty(Tools::getValue($key))) {
                return $this->displayError($errorMsg);
            }
        }

        Configuration::updateValue('STORNO_API_URL', Tools::getValue('STORNO_API_URL'));
        Configuration::updateValue('STORNO_API_KEY', Tools::getValue('STORNO_API_KEY'));
        Configuration::updateValue('STORNO_COMPANY_ID', Tools::getValue('STORNO_COMPANY_ID'));

        return $this->displayConfirmation($this->l('Connection settings saved.'));
    }

    private function saveInvoiceSettings()
    {
        Configuration::updateValue('STORNO_TRIGGER_STATUS', (int) Tools::getValue('STORNO_TRIGGER_STATUS'));
        Configuration::updateValue('STORNO_AUTO_ISSUE', (int) Tools::getValue('STORNO_AUTO_ISSUE'));
        Configuration::updateValue('STORNO_AUTO_APPLY_VAT_RULES', (int) Tools::getValue('STORNO_AUTO_APPLY_VAT_RULES'));
        Configuration::updateValue('STORNO_DOCUMENT_SERIES_ID', Tools::getValue('STORNO_DOCUMENT_SERIES_ID'));
        Configuration::updateValue('STORNO_INVOICE_LANGUAGE', Tools::getValue('STORNO_INVOICE_LANGUAGE'));
        Configuration::updateValue('STORNO_PAYMENT_TERM_DAYS', (int) Tools::getValue('STORNO_PAYMENT_TERM_DAYS'));
        Configuration::updateValue('STORNO_DEFAULT_VAT_RATE', (float) Tools::getValue('STORNO_DEFAULT_VAT_RATE'));
        Configuration::updateValue('STORNO_SHIPPING_VAT_RATE', (float) Tools::getValue('STORNO_SHIPPING_VAT_RATE'));
        Configuration::updateValue('STORNO_DEFAULT_UNIT', Tools::getValue('STORNO_DEFAULT_UNIT'));
        Configuration::updateValue('STORNO_INVOICE_NOTES', Tools::getValue('STORNO_INVOICE_NOTES'));
        Configuration::updateValue('STORNO_INTERNAL_NOTE_FORMAT', Tools::getValue('STORNO_INTERNAL_NOTE_FORMAT'));

        return $this->displayConfirmation($this->l('Invoice settings saved.'));
    }

    private function saveLabelSettings()
    {
        Configuration::updateValue('STORNO_SHIPPING_LABEL', Tools::getValue('STORNO_SHIPPING_LABEL'));
        Configuration::updateValue('STORNO_DISCOUNT_LABEL', Tools::getValue('STORNO_DISCOUNT_LABEL'));
        Configuration::updateValue('STORNO_WRAPPING_LABEL', Tools::getValue('STORNO_WRAPPING_LABEL'));

        return $this->displayConfirmation($this->l('Label settings saved.'));
    }

    private function savePaymentSettings()
    {
        Configuration::updateValue('STORNO_PM_WIREPAYMENT', Tools::getValue('STORNO_PM_WIREPAYMENT'));
        Configuration::updateValue('STORNO_PM_CHECKPAYMENT', Tools::getValue('STORNO_PM_CHECKPAYMENT'));
        Configuration::updateValue('STORNO_PM_CASHONDELIVERY', Tools::getValue('STORNO_PM_CASHONDELIVERY'));
        Configuration::updateValue('STORNO_PM_STRIPE', Tools::getValue('STORNO_PM_STRIPE'));
        Configuration::updateValue('STORNO_PM_PAYPAL', Tools::getValue('STORNO_PM_PAYPAL'));
        Configuration::updateValue('STORNO_PM_DEFAULT', Tools::getValue('STORNO_PM_DEFAULT'));

        return $this->displayConfirmation($this->l('Payment method mapping saved.'));
    }

    private function testConnection()
    {
        try {
            $api = $this->getApi();
            $response = $api->listCompanies();
            $companies = $response['data'] ?? $response;
            $names = array_map(function ($c) { return $c['name'] ?? $c['id']; }, $companies);

            return $this->displayConfirmation(
                $this->l('Connection successful! Companies: ') . implode(', ', $names)
            );
        } catch (Exception $e) {
            return $this->displayError($this->l('Connection failed: ') . $e->getMessage());
        }
    }

    private function registerWebhook()
    {
        try {
            $api = $this->getApi();
            $webhookUrl = $this->context->link->getModuleLink($this->name, 'webhook', [], true);

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
                $this->l('Webhook registered at: ') . $webhookUrl
            );
        } catch (Exception $e) {
            return $this->displayError($this->l('Webhook registration failed: ') . $e->getMessage());
        }
    }

    // ─── Form: Connection ────────────────────────────────────────

    private function renderConnectionForm()
    {
        $fields = [
            'form' => [
                'legend' => ['title' => $this->l('API Connection'), 'icon' => 'icon-plug'],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('API URL'),
                        'name' => 'STORNO_API_URL',
                        'desc' => $this->l('Your self-hosted Storno instance URL (e.g. https://invoices.yourdomain.com).') . ' <a href="https://docs.storno.ro/getting-started/self-hosting" target="_blank">' . $this->l('Setup guide') . '</a>',
                        'required' => true,
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Key'),
                        'name' => 'STORNO_API_KEY',
                        'desc' => $this->l('API token starting with af_... Create it in Storno → Settings → API Keys.') . ' <a href="https://docs.storno.ro/api-reference/api-keys/create" target="_blank">' . $this->l('How to create an API key') . '</a> | <a href="https://docs.storno.ro/api-reference/api-keys/scopes" target="_blank">' . $this->l('Required scopes') . '</a>',
                        'required' => true,
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Company UUID'),
                        'name' => 'STORNO_COMPANY_ID',
                        'desc' => $this->l('UUID of the company to issue invoices for. Find it in Storno → Settings → Company.') . ' <a href="https://docs.storno.ro/api-reference/companies/list" target="_blank">' . $this->l('Companies documentation') . '</a>',
                        'required' => true,
                        'class' => 'fixed-width-xxl',
                    ],
                ],
                'submit' => ['title' => $this->l('Save Connection')],
            ],
        ];

        return $this->buildHelper('submitStornoConnection', $fields, [
            'STORNO_API_URL' => Configuration::get('STORNO_API_URL'),
            'STORNO_API_KEY' => Configuration::get('STORNO_API_KEY'),
            'STORNO_COMPANY_ID' => Configuration::get('STORNO_COMPANY_ID'),
        ]);
    }

    // ─── Form: Invoice Settings ──────────────────────────────────

    private function renderInvoiceForm()
    {
        $orderStatuses = OrderState::getOrderStates($this->context->language->id);
        $statusOptions = [];
        foreach ($orderStatuses as $s) {
            $statusOptions[] = ['id' => $s['id_order_state'], 'name' => $s['name']];
        }

        // Fetch document series from API (cached on page load)
        $seriesOptions = [['id' => '', 'name' => $this->l('-- Default (auto) --')]];
        try {
            $api = $this->getApi();
            $series = $api->listDocumentSeries();
            foreach ($series as $s) {
                $label = ($s['prefix'] ?? '') . ' — ' . ($s['name'] ?? $s['id']);
                $seriesOptions[] = ['id' => $s['id'], 'name' => $label];
            }
        } catch (Exception $e) {
            // API not configured yet — show only default
        }

        $languageOptions = [
            ['id' => '', 'name' => $this->l('-- Default (from Storno) --')],
            ['id' => 'ro', 'name' => 'Romana'],
            ['id' => 'en', 'name' => 'English'],
            ['id' => 'de', 'name' => 'Deutsch'],
            ['id' => 'fr', 'name' => 'Francais'],
        ];

        $fields = [
            'form' => [
                'legend' => ['title' => $this->l('Invoice Settings'), 'icon' => 'icon-file-text-o'],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Trigger on Order Status'),
                        'name' => 'STORNO_TRIGGER_STATUS',
                        'desc' => $this->l('The invoice is created when the order reaches this status (e.g. Shipped). Nothing is sent to Storno until then.'),
                        'options' => ['query' => $statusOptions, 'id' => 'id', 'name' => 'name'],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Auto-issue invoices'),
                        'name' => 'STORNO_AUTO_ISSUE',
                        'desc' => $this->l('Automatically issue the invoice after creation: assigns a number from the series, generates PDF and XML. e-Factura submission to ANAF is controlled from the company settings in Storno.') . ' <a href="https://docs.storno.ro/concepts/document-lifecycle" target="_blank">' . $this->l('Invoice lifecycle') . '</a>',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Auto-apply VAT rules'),
                        'name' => 'STORNO_AUTO_APPLY_VAT_RULES',
                        'desc' => $this->l('Storno automatically applies VAT rules: reverse charge (0% for EU clients with valid VIES), OSS rates for intra-community sales, and exemption for non-EU exports.') . ' <a href="https://docs.storno.ro/concepts/einvoice-integration" target="_blank">' . $this->l('e-Factura integration') . '</a>',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Document Series'),
                        'name' => 'STORNO_DOCUMENT_SERIES_ID',
                        'desc' => $this->l('Invoice numbering series. Leave default to use the company default series from Storno.') . ' <a href="https://docs.storno.ro/concepts/series-numbering" target="_blank">' . $this->l('How series work') . '</a>',
                        'options' => ['query' => $seriesOptions, 'id' => 'id', 'name' => 'name'],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Invoice Language'),
                        'name' => 'STORNO_INVOICE_LANGUAGE',
                        'desc' => $this->l('Language for the generated invoice PDF. Leave default to use the language from Storno.') . ' <a href="https://docs.storno.ro/api-reference/invoices/create" target="_blank">' . $this->l('Invoice creation docs') . '</a>',
                        'options' => ['query' => $languageOptions, 'id' => 'id', 'name' => 'name'],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Payment Term (days)'),
                        'name' => 'STORNO_PAYMENT_TERM_DAYS',
                        'desc' => $this->l('Number of days from issue date to due date. Example: 30 = due 30 days after issuing.'),
                        'class' => 'fixed-width-sm',
                        'suffix' => $this->l('days'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Default VAT Rate (%)'),
                        'name' => 'STORNO_DEFAULT_VAT_RATE',
                        'desc' => $this->l('Default VAT rate when a PrestaShop product has no tax configured. Used as fallback. The final VAT may be overridden by automatic rules (reverse charge, OSS).') . ' <a href="https://docs.storno.ro/api-reference/vat-rates/list" target="_blank">' . $this->l('VAT rates in Storno') . '</a>',
                        'class' => 'fixed-width-sm',
                        'suffix' => '%',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Shipping VAT Rate (%)'),
                        'name' => 'STORNO_SHIPPING_VAT_RATE',
                        'desc' => $this->l('VAT rate applied to the shipping line. Only applies when shipping cost is greater than 0.'),
                        'class' => 'fixed-width-sm',
                        'suffix' => '%',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Default Unit of Measure'),
                        'name' => 'STORNO_DEFAULT_UNIT',
                        'desc' => $this->l('Unit of measure shown on invoice lines. Examples: buc (pieces), kg, ora (hours), m, l, set.'),
                        'class' => 'fixed-width-md',
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Invoice Notes'),
                        'name' => 'STORNO_INVOICE_NOTES',
                        'desc' => $this->l('Public notes shown on every invoice (e.g. bank details, thank you message). Leave empty to use default notes from Storno.'),
                        'rows' => 3,
                        'cols' => 60,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Internal Note Format'),
                        'name' => 'STORNO_INTERNAL_NOTE_FORMAT',
                        'desc' => $this->l('Internal note format (visible only in Storno, not on the invoice). Use {reference} for order reference and {id} for order ID. Example: PrestaShop #{reference}'),
                        'class' => 'fixed-width-xxl',
                    ],
                ],
                'submit' => ['title' => $this->l('Save Invoice Settings')],
            ],
        ];

        return $this->buildHelper('submitStornoInvoice', $fields, [
            'STORNO_TRIGGER_STATUS' => $this->getConfigOrDefault('STORNO_TRIGGER_STATUS', 2),
            'STORNO_AUTO_ISSUE' => Configuration::get('STORNO_AUTO_ISSUE'),
            'STORNO_AUTO_APPLY_VAT_RULES' => Configuration::get('STORNO_AUTO_APPLY_VAT_RULES'),
            'STORNO_DOCUMENT_SERIES_ID' => Configuration::get('STORNO_DOCUMENT_SERIES_ID'),
            'STORNO_INVOICE_LANGUAGE' => Configuration::get('STORNO_INVOICE_LANGUAGE'),
            'STORNO_PAYMENT_TERM_DAYS' => $this->getConfigOrDefault('STORNO_PAYMENT_TERM_DAYS', 30),
            'STORNO_DEFAULT_VAT_RATE' => $this->getConfigOrDefault('STORNO_DEFAULT_VAT_RATE', '21'),
            'STORNO_SHIPPING_VAT_RATE' => $this->getConfigOrDefault('STORNO_SHIPPING_VAT_RATE', '21'),
            'STORNO_DEFAULT_UNIT' => $this->getConfigOrDefault('STORNO_DEFAULT_UNIT', 'buc'),
            'STORNO_INVOICE_NOTES' => Configuration::get('STORNO_INVOICE_NOTES'),
            'STORNO_INTERNAL_NOTE_FORMAT' => $this->getConfigOrDefault('STORNO_INTERNAL_NOTE_FORMAT', 'PrestaShop #{reference}'),
        ]);
    }

    // ─── Form: Line Labels ───────────────────────────────────────

    private function renderLabelForm()
    {
        $fields = [
            'form' => [
                'legend' => ['title' => $this->l('Invoice Line Labels'), 'icon' => 'icon-tag'],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Shipping Line'),
                        'name' => 'STORNO_SHIPPING_LABEL',
                        'desc' => $this->l('Description for the shipping cost line on the invoice. Appears as a separate line when the order has delivery costs.'),
                        'class' => 'fixed-width-xl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Discount Line'),
                        'name' => 'STORNO_DISCOUNT_LABEL',
                        'desc' => $this->l('Description for the discount line on the invoice. Appears as a negative line when the order has discounts (cart rules, vouchers).'),
                        'class' => 'fixed-width-xl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Gift Wrapping Line'),
                        'name' => 'STORNO_WRAPPING_LABEL',
                        'desc' => $this->l('Description for the gift wrapping line. Only appears when the customer selected gift wrapping.'),
                        'class' => 'fixed-width-xl',
                    ],
                ],
                'submit' => ['title' => $this->l('Save Labels')],
            ],
        ];

        return $this->buildHelper('submitStornoLabels', $fields, [
            'STORNO_SHIPPING_LABEL' => $this->getConfigOrDefault('STORNO_SHIPPING_LABEL', 'Transport'),
            'STORNO_DISCOUNT_LABEL' => $this->getConfigOrDefault('STORNO_DISCOUNT_LABEL', 'Discount'),
            'STORNO_WRAPPING_LABEL' => $this->getConfigOrDefault('STORNO_WRAPPING_LABEL', 'Ambalare cadou'),
        ]);
    }

    // ─── Form: Payment Method Mapping ────────────────────────────

    private function renderPaymentForm()
    {
        $paymentOptions = [
            ['id' => 'bank_transfer', 'name' => $this->l('Bank Transfer')],
            ['id' => 'cash', 'name' => $this->l('Cash')],
            ['id' => 'card', 'name' => $this->l('Card')],
            ['id' => 'cheque', 'name' => $this->l('Cheque')],
            ['id' => 'other', 'name' => $this->l('Other')],
        ];

        $makeSelect = function ($label, $name, $desc) use ($paymentOptions) {
            return [
                'type' => 'select',
                'label' => $this->l($label),
                'name' => $name,
                'desc' => $this->l($desc),
                'options' => ['query' => $paymentOptions, 'id' => 'id', 'name' => 'name'],
            ];
        };

        $fields = [
            'form' => [
                'legend' => ['title' => $this->l('Payment Method Mapping'), 'icon' => 'icon-credit-card'],
                'description' => $this->l('Each PrestaShop payment module is mapped to a Storno payment method. This appears on the invoice as the "Payment method" field.'),
                'input' => [
                    $makeSelect('Wire Payment (ps_wirepayment)', 'STORNO_PM_WIREPAYMENT', 'Bank transfer module'),
                    $makeSelect('Check Payment (ps_checkpayment)', 'STORNO_PM_CHECKPAYMENT', 'Check payment module'),
                    $makeSelect('Cash on Delivery (ps_cashondelivery)', 'STORNO_PM_CASHONDELIVERY', 'Cash on delivery module'),
                    $makeSelect('Stripe', 'STORNO_PM_STRIPE', 'Stripe payment module'),
                    $makeSelect('PayPal', 'STORNO_PM_PAYPAL', 'PayPal payment module'),
                    $makeSelect('Default (other modules)', 'STORNO_PM_DEFAULT', 'Fallback for any unrecognized payment module'),
                ],
                'submit' => ['title' => $this->l('Save Payment Mapping')],
            ],
        ];

        return $this->buildHelper('submitStornoPayments', $fields, [
            'STORNO_PM_WIREPAYMENT' => $this->getConfigOrDefault('STORNO_PM_WIREPAYMENT', 'bank_transfer'),
            'STORNO_PM_CHECKPAYMENT' => $this->getConfigOrDefault('STORNO_PM_CHECKPAYMENT', 'cheque'),
            'STORNO_PM_CASHONDELIVERY' => $this->getConfigOrDefault('STORNO_PM_CASHONDELIVERY', 'cash'),
            'STORNO_PM_STRIPE' => $this->getConfigOrDefault('STORNO_PM_STRIPE', 'card'),
            'STORNO_PM_PAYPAL' => $this->getConfigOrDefault('STORNO_PM_PAYPAL', 'card'),
            'STORNO_PM_DEFAULT' => $this->getConfigOrDefault('STORNO_PM_DEFAULT', 'other'),
        ]);
    }

    // ─── Action Buttons ──────────────────────────────────────────

    private function renderActionButtons()
    {
        $this->context->smarty->assign([
            'storno_webhook_registered' => (bool) Configuration::get('STORNO_WEBHOOK_SECRET'),
            'storno_form_action' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'storno_docs_url' => 'https://docs.storno.ro/concepts/webhooks-events',
        ]);

        return $this->display(__FILE__, 'views/templates/admin/actions.tpl');
    }

    // ─── Form Helper ─────────────────────────────────────────────

    private function buildHelper($submitAction, $formDef, $values)
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->submit_action = $submitAction;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->fields_value = $values;

        return $helper->generateForm([$formDef]);
    }

    // ─── Hooks ───────────────────────────────────────────────────

    public function hookActionValidateOrder($params)
    {
        $triggerStatus = (int) Configuration::get('STORNO_TRIGGER_STATUS');

        if ($triggerStatus && $triggerStatus > 0) {
            return;
        }

        $this->processOrder($params['order']);
    }

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

    public function hookDisplayAdminOrder($params)
    {
        $orderId = (int) ($params['id_order'] ?? 0);
        if (!$orderId) {
            return '';
        }

        $mapping = Db::getInstance()->getRow(
            'SELECT * FROM ' . _DB_PREFIX_ . 'storno_invoices WHERE id_order = ' . $orderId
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

    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('configure') === $this->name) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
        }
    }

    // ─── Invoice Processing ──────────────────────────────────────

    private function processOrder(Order $order)
    {
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
                3, null, 'Order', $order->id
            );

            return;
        }

        try {
            $api = $this->getApi();

            $clientId = $this->ensureClient($api, $order);
            $lines = $this->buildInvoiceLines($order);

            $paymentTermDays = (int) $this->getConfigOrDefault('STORNO_PAYMENT_TERM_DAYS', 30);
            $currency = new Currency($order->id_currency);

            $internalNote = $this->formatInternalNote($order);

            $invoiceData = [
                'clientId' => $clientId,
                'issueDate' => date('Y-m-d'),
                'dueDate' => date('Y-m-d', strtotime('+' . $paymentTermDays . ' days')),
                'currency' => $currency->iso_code,
                'paymentMethod' => $this->mapPaymentMethod($order->module),
                'internalNote' => $internalNote,
                'orderNumber' => $order->reference,
                'idempotencyKey' => 'ps-' . $order->reference . '-' . $order->id,
                'lines' => $lines,
            ];

            // Optional fields — only send if configured
            if (Configuration::get('STORNO_AUTO_APPLY_VAT_RULES')) {
                $invoiceData['autoApplyVatRules'] = true;
            }

            $seriesId = Configuration::get('STORNO_DOCUMENT_SERIES_ID');
            if ($seriesId) {
                $invoiceData['documentSeriesId'] = $seriesId;
            }

            $language = Configuration::get('STORNO_INVOICE_LANGUAGE');
            if ($language) {
                $invoiceData['language'] = $language;
            }

            $notes = Configuration::get('STORNO_INVOICE_NOTES');
            if ($notes) {
                $invoiceData['notes'] = $notes;
            }

            $result = $api->createInvoice($invoiceData);
            $invoiceId = $result['invoice']['id'];
            $invoiceNumber = $result['invoice']['number'] ?? '';
            $status = 'draft';

            // Auto-issue if enabled
            // e-Factura submission is handled by the Storno backend
            // based on the company's efacturaDelayHours setting.
            if (Configuration::get('STORNO_AUTO_ISSUE')) {
                $issueResult = $api->issueInvoice($invoiceId);
                $invoiceNumber = $issueResult['number'] ?? $invoiceNumber;
                $status = 'issued';
            }

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
                1, null, 'Order', $order->id
            );
        } catch (Exception $e) {
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
                'Storno: Failed for order #' . $order->reference . ': ' . $e->getMessage(),
                3, null, 'Order', $order->id
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

        if ($address->company) {
            if ($address->vat_number) {
                $clientData['vatCode'] = $address->vat_number;
                $clientData['isVatPayer'] = true;
            }
            if ($address->dni) {
                $clientData['cui'] = $address->dni;
            }
        }

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
        $defaultVat = (float) $this->getConfigOrDefault('STORNO_DEFAULT_VAT_RATE', 21);
        $unit = $this->getConfigOrDefault('STORNO_DEFAULT_UNIT', 'buc');

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
                'unitOfMeasure' => $unit,
            ];

            if (!empty($product['product_reference'])) {
                $line['productCode'] = $product['product_reference'];
            }

            $lines[] = $line;
        }

        // Shipping
        $shippingCost = (float) $order->total_shipping_tax_excl;
        if ($shippingCost > 0) {
            $shippingVat = (float) $this->getConfigOrDefault('STORNO_SHIPPING_VAT_RATE', 21);
            $lines[] = [
                'description' => $this->getConfigOrDefault('STORNO_SHIPPING_LABEL', 'Transport'),
                'quantity' => 1,
                'unitPrice' => round($shippingCost, 4),
                'vatRate' => $shippingVat,
                'unitOfMeasure' => $unit,
            ];
        }

        // Discounts
        $totalDiscounts = (float) $order->total_discounts_tax_excl;
        if ($totalDiscounts > 0) {
            $lines[] = [
                'description' => $this->getConfigOrDefault('STORNO_DISCOUNT_LABEL', 'Discount'),
                'quantity' => -1,
                'unitPrice' => round($totalDiscounts, 4),
                'vatRate' => $defaultVat,
                'unitOfMeasure' => $unit,
            ];
        }

        // Gift wrapping
        $wrappingCost = (float) $order->total_wrapping_tax_excl;
        if ($wrappingCost > 0) {
            $lines[] = [
                'description' => $this->getConfigOrDefault('STORNO_WRAPPING_LABEL', 'Ambalare cadou'),
                'quantity' => 1,
                'unitPrice' => round($wrappingCost, 4),
                'vatRate' => $defaultVat,
                'unitOfMeasure' => $unit,
            ];
        }

        return $lines;
    }

    private function mapPaymentMethod(string $module): string
    {
        $map = [
            'ps_wirepayment' => $this->getConfigOrDefault('STORNO_PM_WIREPAYMENT', 'bank_transfer'),
            'ps_checkpayment' => $this->getConfigOrDefault('STORNO_PM_CHECKPAYMENT', 'cheque'),
            'ps_cashondelivery' => $this->getConfigOrDefault('STORNO_PM_CASHONDELIVERY', 'cash'),
            'stripe' => $this->getConfigOrDefault('STORNO_PM_STRIPE', 'card'),
            'paypal' => $this->getConfigOrDefault('STORNO_PM_PAYPAL', 'card'),
        ];

        return $map[$module] ?? $this->getConfigOrDefault('STORNO_PM_DEFAULT', 'other');
    }

    private function formatInternalNote(Order $order): string
    {
        $format = $this->getConfigOrDefault('STORNO_INTERNAL_NOTE_FORMAT', 'PrestaShop #{reference}');

        return str_replace(
            ['{reference}', '{id}'],
            [$order->reference, $order->id],
            $format
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Get a config value, returning $default only when the key does not exist.
     * Preserves falsy values like 0, '0', '' that the user intentionally set.
     */
    private function getConfigOrDefault(string $key, $default)
    {
        $val = Configuration::get($key);

        return $val !== false ? $val : $default;
    }

    private function getApi(): StornoApi
    {
        return new StornoApi(
            Configuration::get('STORNO_API_URL'),
            Configuration::get('STORNO_API_KEY'),
            Configuration::get('STORNO_COMPANY_ID')
        );
    }
}
