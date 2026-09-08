{**
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
 *}
<div class="panel">
    <div class="panel-heading"><i class="icon-link"></i> {l s='Actions' mod='stornoinvoicing'}</div>
    <div class="form-wrapper">
        <div class="row">
            <div class="col-lg-6">
                <p>
                    <strong>{l s='Webhook:' mod='stornoinvoicing'}</strong>
                    {if $storno_webhook_registered}
                        <span class="badge badge-success">{l s='Registered' mod='stornoinvoicing'}</span>
                    {else}
                        <span class="badge badge-warning">{l s='Not registered' mod='stornoinvoicing'}</span>
                    {/if}
                </p>
                <p class="help-block">
                    {l s='The webhook allows Storno to notify PrestaShop when an invoice is validated/rejected by ANAF or when a payment is recorded.' mod='stornoinvoicing'}
                    <a href="{$storno_docs_url|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">{l s='Webhooks documentation' mod='stornoinvoicing'}</a>
                </p>
                <form method="post" action="{$storno_form_action|escape:'htmlall':'UTF-8'}">
                    <button type="submit" name="submitStornoRegisterWebhook" class="btn btn-default">
                        <i class="icon-refresh"></i> {l s='Register Webhook' mod='stornoinvoicing'}
                    </button>
                </form>
            </div>
            <div class="col-lg-6">
                <p><strong>{l s='Test API connection:' mod='stornoinvoicing'}</strong></p>
                <p class="help-block">{l s='Checks whether API URL, API Key and Company UUID are correct and Storno is reachable.' mod='stornoinvoicing'}</p>
                <form method="post" action="{$storno_form_action|escape:'htmlall':'UTF-8'}">
                    <button type="submit" name="submitStornoTestConnection" class="btn btn-default">
                        <i class="icon-check"></i> {l s='Test Connection' mod='stornoinvoicing'}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
