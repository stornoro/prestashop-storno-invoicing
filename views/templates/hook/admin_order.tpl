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
                        <a href="{$storno_url}/invoices/{$storno_invoice_id}" target="_blank" rel="noopener">
                            {$storno_invoice_number}
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
                        <span class="badge badge-success">{$storno_status}</span>
                    {elseif $storno_status == 'rejected' || $storno_status == 'error'}
                        <span class="badge badge-danger">{$storno_status}</span>
                    {elseif $storno_status == 'issued' || $storno_status == 'sent_to_provider'}
                        <span class="badge badge-info">{$storno_status}</span>
                    {elseif $storno_status == 'paid'}
                        <span class="badge badge-success">{$storno_status}</span>
                    {else}
                        <span class="badge badge-warning">{$storno_status}</span>
                    {/if}
                </td>
            </tr>
            {if $storno_error}
            <tr>
                <td><strong>{l s='Error' mod='stornoinvoicing'}</strong></td>
                <td><span class="text-danger">{$storno_error}</span></td>
            </tr>
            {/if}
            <tr>
                <td><strong>{l s='Created' mod='stornoinvoicing'}</strong></td>
                <td>{$date_add}</td>
            </tr>
        </table>
    </div>
</div>
