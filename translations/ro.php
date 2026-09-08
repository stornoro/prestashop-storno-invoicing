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
global $_MODULE;
$_MODULE = [];

// ─── Module metadata ─────────────────────────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Storno Invoicing')] = 'Storno Facturare';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Automatic invoice generation and e-Factura submission via Storno API.')] = 'Generare automata de facturi si trimitere la e-Factura prin API-ul Storno.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Are you sure you want to uninstall? All invoice mapping data will be lost.')] = 'Sunteti sigur ca doriti dezinstalarea? Toate datele de mapare a facturilor vor fi pierdute.';

// ─── Save confirmations ───────────────────────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Connection settings saved.')] = 'Setarile de conexiune au fost salvate.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Invoice settings saved.')] = 'Setarile de facturare au fost salvate.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Label settings saved.')] = 'Etichetele au fost salvate.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Payment method mapping saved.')] = 'Maparea metodelor de plata a fost salvata.';

// ─── Connection test / webhook results ───────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Connection successful! Companies: ')] = 'Conexiune reusita! Companii:';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Connection failed: ')] = 'Conexiune esuata:';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Webhook registered at: ')] = 'Webhook inregistrat la:';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Webhook registration failed: ')] = 'Inregistrarea webhook-ului a esuat:';

// ─── Validation errors ────────────────────────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('API URL is required.')] = 'API URL este obligatoriu.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('API Key is required.')] = 'Cheia API este obligatorie.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Company UUID is required.')] = 'UUID-ul Companiei este obligatoriu.';

// ─── Connection form ──────────────────────────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('API Connection')] = 'Conexiune API';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('API URL')] = 'API URL';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Your self-hosted Storno instance URL (e.g. https://invoices.yourdomain.com).')] = 'URL-ul instantei Storno self-hosted (ex: https://factura.domeniu.ro).';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Setup guide')] = 'Ghid instalare';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('API Key')] = 'Cheie API';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('API token starting with af_... Create it in Storno → Settings → API Keys.')] = 'Token-ul API care incepe cu af_... Creati-l din Storno → Setari → Chei API.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('How to create an API key')] = 'Cum creez o cheie API';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Required scopes')] = 'Scopuri necesare';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Company UUID')] = 'UUID Companie';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('UUID of the company to issue invoices for. Find it in Storno → Settings → Company.')] = 'UUID-ul companiei pentru care se emit facturi. Il gasiti in Storno → Setari → Companie.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Companies documentation')] = 'Documentatie companii';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Save Connection')] = 'Salveaza Conexiunea';

// ─── Invoice settings form ────────────────────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Invoice Settings')] = 'Setari Facturare';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Trigger on Order Status')] = 'Declanseaza la Statusul Comenzii';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('The invoice is created when the order reaches this status (e.g. Shipped). Nothing is sent to Storno until then.')] = 'Factura se creeaza cand comanda ajunge la acest status (ex: Expediata). Pana atunci, nu se trimite nimic catre Storno.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Auto-issue invoices')] = 'Emitere automata facturi';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Automatically issue the invoice after creation: assigns a number from the series, generates PDF and XML. e-Factura submission to ANAF is controlled from the company settings in Storno.')] = 'Emite factura automat dupa creare: ii atribuie numar din serie, genereaza PDF si XML. Trimiterea la e-Factura ANAF este controlata din setarile companiei in Storno.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Invoice lifecycle')] = 'Ciclul de viata al facturii';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Yes')] = 'Da';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('No')] = 'Nu';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Auto-apply VAT rules')] = 'Aplica automat regulile TVA';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Storno automatically applies VAT rules: reverse charge (0% for EU clients with valid VIES), OSS rates for intra-community sales, and exemption for non-EU exports.')] = 'Storno aplica automat regulile de TVA: taxare inversa (0% pentru clienti EU cu VIES valid), rate OSS pentru vanzari intracomunitare, si scutire la export non-EU.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('e-Factura integration')] = 'Integrare e-Factura';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Document Series')] = 'Serie Documente';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Invoice numbering series. Leave default to use the company default series from Storno.')] = 'Seria de numerotare a facturilor. Lasati implicit pentru a folosi seria implicita a companiei din Storno.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('How series work')] = 'Cum functioneaza seriile';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('-- Default (auto) --')] = '-- Implicit (auto) --';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Invoice Language')] = 'Limba Facturii';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Language for the generated invoice PDF. Leave default to use the language from Storno.')] = 'Limba in care se genereaza PDF-ul facturii. Lasati implicit pentru a folosi limba din Storno.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Invoice creation docs')] = 'Documentatie creare factura';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('-- Default (from Storno) --')] = '-- Implicit (din Storno) --';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Payment Term (days)')] = 'Termen de Plata (zile)';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Number of days from issue date to due date. Example: 30 = due 30 days after issuing.')] = 'Numarul de zile de la data emiterii pana la scadenta. Exemplu: 30 = scadenta la 30 de zile de la emitere.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('days')] = 'zile';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Default VAT Rate (%)')] = 'Rata TVA Implicita (%)';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Default VAT rate when a PrestaShop product has no tax configured. Used as fallback. The final VAT may be overridden by automatic rules (reverse charge, OSS).')] = 'Rata TVA implicita cand un produs din PrestaShop nu are taxa configurata. Se foloseste ca fallback. TVA-ul final poate fi suprascris de regulile automate (taxare inversa, OSS).';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('VAT rates in Storno')] = 'Rate TVA in Storno';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Shipping VAT Rate (%)')] = 'Rata TVA Transport (%)';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('VAT rate applied to the shipping line. Only applies when shipping cost is greater than 0.')] = 'Rata TVA aplicata liniei de transport. Se aplica numai cand transportul are cost > 0.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Default Unit of Measure')] = 'Unitate de Masura Implicita';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Unit of measure shown on invoice lines. Examples: buc (pieces), kg, ora (hours), m, l, set.')] = 'Unitatea de masura afisata pe liniile facturii. Exemple: buc (bucati), kg, ora, m, l, set.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Invoice Notes')] = 'Note Factura';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Public notes shown on every invoice (e.g. bank details, thank you message). Leave empty to use default notes from Storno.')] = 'Note publice afisate pe fiecare factura (ex: detalii bancare, mesaj de multumire). Lasati gol pentru a folosi notele implicite din Storno.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Internal Note Format')] = 'Format Nota Interna';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Internal note format (visible only in Storno, not on the invoice). Use {reference} for order reference and {id} for order ID. Example: PrestaShop #{reference}')] = 'Formatul notei interne (vizibila doar in Storno, nu pe factura). Folositi {reference} pentru referinta comenzii si {id} pentru ID-ul comenzii. Exemplu: PrestaShop #{reference}';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Save Invoice Settings')] = 'Salveaza Setarile de Facturare';

// ─── Label form ───────────────────────────────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Invoice Line Labels')] = 'Etichete Linii Factura';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Shipping Line')] = 'Linia Transport';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Description for the shipping cost line on the invoice. Appears as a separate line when the order has delivery costs.')] = 'Descrierea liniei de transport pe factura. Apare ca linie separata cand comanda are cost de livrare.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Discount Line')] = 'Linia Discount';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Description for the discount line on the invoice. Appears as a negative line when the order has discounts (cart rules, vouchers).')] = 'Descrierea liniei de discount pe factura. Apare ca linie cu valoare negativa cand comanda are reduceri (reguli cos, vouchere).';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Gift Wrapping Line')] = 'Linia Ambalare Cadou';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Description for the gift wrapping line. Only appears when the customer selected gift wrapping.')] = 'Descrierea liniei de ambalare cadou. Apare doar cand clientul a selectat ambalare cadou in cos.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Save Labels')] = 'Salveaza Etichetele';

// ─── Payment method mapping form ──────────────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Payment Method Mapping')] = 'Mapare Metode de Plata';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Each PrestaShop payment module is mapped to a Storno payment method. This appears on the invoice as the "Payment method" field.')] = 'Fiecare modul de plata din PrestaShop este mapat la o metoda de plata din Storno. Aceasta apare pe factura in campul "Metoda de plata".';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Bank Transfer')] = 'Transfer Bancar';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Cash')] = 'Numerar';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Card')] = 'Card';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Cheque')] = 'Cec';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Other')] = 'Altele';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Wire Payment (ps_wirepayment)')] = 'Plata prin Transfer (ps_wirepayment)';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Bank transfer module')] = 'Modulul de transfer bancar';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Check Payment (ps_checkpayment)')] = 'Plata prin Cec (ps_checkpayment)';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Check payment module')] = 'Modulul de plata prin cec';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Cash on Delivery (ps_cashondelivery)')] = 'Plata la Livrare (ps_cashondelivery)';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Cash on delivery module')] = 'Modulul de plata la livrare';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Stripe')] = 'Stripe';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Stripe payment module')] = 'Modulul de plata Stripe';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('PayPal')] = 'PayPal';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('PayPal payment module')] = 'Modulul de plata PayPal';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Default (other modules)')] = 'Implicit (alte module)';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Fallback for any unrecognized payment module')] = 'Fallback pentru orice modul de plata nerecunoscut';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Save Payment Mapping')] = 'Salveaza Maparea Platilor';

// ─── Action buttons ───────────────────────────────────────────────────────────

$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Actions')] = 'Actiuni';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Registered')] = 'Inregistrat';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Not registered')] = 'Neinregistrat';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Webhook:')] = 'Webhook:';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('The webhook allows Storno to notify PrestaShop when an invoice is validated/rejected by ANAF or when a payment is recorded.')] = 'Webhook-ul permite Storno sa notifice PrestaShop cand o factura este validata/respinsa de ANAF sau cand se inregistreaza o plata.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Webhooks documentation')] = 'Documentatie webhooks';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Register Webhook')] = 'Inregistreaza Webhook';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Test API connection:')] = 'Test conexiune API:';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Checks whether API URL, API Key and Company UUID are correct and Storno is reachable.')] = 'Verifica daca API URL, API Key si Company UUID sunt corecte si Storno este accesibil.';
$_MODULE['<stornoinvoicing>stornoinvoicing_' . md5('Test Connection')] = 'Testeaza Conexiunea';

// ─── admin_order.tpl strings ──────────────────────────────────────────────────

$_MODULE['<stornoinvoicing>admin_order_' . md5('Invoice Number')] = 'Numar Factura';
$_MODULE['<stornoinvoicing>admin_order_' . md5('Status')] = 'Status';
$_MODULE['<stornoinvoicing>admin_order_' . md5('Error')] = 'Eroare';
$_MODULE['<stornoinvoicing>admin_order_' . md5('Created')] = 'Creat';
$_MODULE['<stornoinvoicing>admin_order_' . md5('Not yet assigned')] = 'Inca neatribuit';

// Strings moved to views/templates/admin/actions.tpl
$_MODULE['<stornoinvoicing>actions_' . md5('Actions')] = 'Actiuni';
$_MODULE['<stornoinvoicing>actions_' . md5('Registered')] = 'Inregistrat';
$_MODULE['<stornoinvoicing>actions_' . md5('Not registered')] = 'Neinregistrat';
$_MODULE['<stornoinvoicing>actions_' . md5('Webhook:')] = 'Webhook:';
$_MODULE['<stornoinvoicing>actions_' . md5('The webhook allows Storno to notify PrestaShop when an invoice is validated/rejected by ANAF or when a payment is recorded.')] = 'Webhook-ul permite Storno sa notifice PrestaShop cand o factura este validata/respinsa de ANAF sau cand se inregistreaza o plata.';
$_MODULE['<stornoinvoicing>actions_' . md5('Webhooks documentation')] = 'Documentatie webhooks';
$_MODULE['<stornoinvoicing>actions_' . md5('Register Webhook')] = 'Inregistreaza Webhook';
$_MODULE['<stornoinvoicing>actions_' . md5('Test API connection:')] = 'Test conexiune API:';
$_MODULE['<stornoinvoicing>actions_' . md5('Checks whether API URL, API Key and Company UUID are correct and Storno is reachable.')] = 'Verifica daca API URL, API Key si Company UUID sunt corecte si Storno este accesibil.';
$_MODULE['<stornoinvoicing>actions_' . md5('Test Connection')] = 'Testeaza Conexiunea';
