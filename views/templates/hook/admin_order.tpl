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
    <div class="panel-heading">
        <i class="icon-file-text"></i> Storno Invoice
    </div>
    <div class="panel-body">
        <table class="table">
            <tr>
                <td><strong>{l s='Invoice Number' mod='stornoinvoicing'}</strong></td>
                <td>
                    {if $storno_invoice_number}
                        <a href="{$storno_url|escape:'htmlall':'UTF-8'}/invoices/{$storno_invoice_id|escape:'htmlall':'UTF-8'}" target="_blank" rel="noopener">
                            {$storno_invoice_number|escape:'htmlall':'UTF-8'}
                        </a>
                    {else}
                        <em>{l s='Not yet assigned' mod='stornoinvoicing'}</em>
                    {/if}
                </td>
            </tr>
            <tr>
                <td><strong>{l s='Status' mod='stornoinvoicing'}</strong></td>
                <td>
                    {if $storno_status == 'validated'}
                        <span class="badge badge-success">{$storno_status|escape:'htmlall':'UTF-8'}</span>
                    {elseif $storno_status == 'rejected' || $storno_status == 'error'}
                        <span class="badge badge-danger">{$storno_status|escape:'htmlall':'UTF-8'}</span>
                    {elseif $storno_status == 'issued' || $storno_status == 'sent_to_provider'}
                        <span class="badge badge-info">{$storno_status|escape:'htmlall':'UTF-8'}</span>
                    {elseif $storno_status == 'paid'}
                        <span class="badge badge-success">{$storno_status|escape:'htmlall':'UTF-8'}</span>
                    {else}
                        <span class="badge badge-warning">{$storno_status|escape:'htmlall':'UTF-8'}</span>
                    {/if}
                </td>
            </tr>
            {if $storno_error}
            <tr>
                <td><strong>{l s='Error' mod='stornoinvoicing'}</strong></td>
                <td><span class="text-danger">{$storno_error|escape:'htmlall':'UTF-8'}</span></td>
            </tr>
            {/if}
            <tr>
                <td><strong>{l s='Created' mod='stornoinvoicing'}</strong></td>
                <td>{$date_add|escape:'htmlall':'UTF-8'}</td>
            </tr>
        </table>
    </div>
</div>
