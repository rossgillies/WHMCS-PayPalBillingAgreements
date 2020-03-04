<?php

if (!defined("WHMCS")) {
    header('HTTP/1.1 403 Forbidden');
    die();
}

use WHMCS\Database\Capsule;

function paypalbilling_MetaData()
{
    return [
        'DisplayName' => 'PayPal Billing Agreement',
        'APIVersion' => '1.1',
        'TokenisedStorage' => true,
        'DisableLocalCreditCardInput' => false,
    ];
}

function paypalbilling_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'system',
            'Value' => 'PayPal Billing Agreement',
        ],
        'apiUsername' => [
            'FriendlyName' => 'API User',
            'Type' => 'text',
            'Size' => 128,
        ],
        'apiPassword' => [
            'FriendlyName' => 'API Password',
            'Type' => 'text',
            'Size' => 128,
        ],
        'apiSignature' => [
            'FriendlyName' => 'API Signature',
            'Type' => 'text',
            'Size' => 128,
        ],
        'enableCronStatusCheck' => [
            'FriendlyName' => 'Enable Cron Status Check',
            'Description' => 'Enable validating all active billing agreements are still active during nightly cron job',
            'Type' => 'yesno',
        ]
    ];
}

function paypalbilling_link($params)
{
    $clientId = $params['clientdetails']['userid'];

    $billingAgreement = Capsule::table('paypal_billingagreement')
        ->where('client_id', '=', $clientId)
        ->where('status', '=', 'Active')
        ->first();

    $disableAutoCCProcessing = @Capsule::table('tblclients')
        ->where('id', '=', $clientId)
        ->first()
        ->disableautocc;

    $processString = '';
    
    $addFunds = Capsule::table('tblinvoiceitems')
        ->where('invoiceid', '=', $invoice->id)
        ->where('type', '=', 'AddFunds')
        ->count();
    
    if ($billingAgreement && !$disableAutoCCProcessing && ($addFunds > 0)) {
        $ccProcessDaysBefore = @Capsule::table('tblconfiguration')
            ->where('setting', '=', 'CCProcessDaysBefore')
            ->first()
            ->value;
        $ccProcessDaysBefore = intval($ccProcessDaysBefore);

        $startProcessDate = strtotime("-{$ccProcessDaysBefore} days", strtotime($params['dueDate'])) + 3600 * 7;
        $thisProcessDate = strtotime('today 07:00');

        if ($thisProcessDate < time()) {
            $thisProcessDate = strtotime('tomorrow 07:00');
        }

        $nextProcessDate = max($startProcessDate, $thisProcessDate);
        $processString = '<br /><p>We\'ll attempt to automatically charge your PayPal account on ' . date('d/m/Y', $nextProcessDate) . ' at ' . date('g:iA T', $nextProcessDate) . '.</p>';
    }

    return <<<EOF
<form action="paypalbilling.php">
    <input type="hidden" name="action" value="submitpayment" />
    <input type="hidden" name="invoiceid" value="{$params['invoiceid']}" />
    <input type="submit" class="btn btn-default" value="Pay Invoice" />
</form>
{$processString}
EOF;
}

function paypalbilling_refund($params)
{
    require_once __DIR__ . '/paypalbilling/PayPalNVP.php';

    $response = (new PayPalNVP())
        ->addPair('TRANSACTIONID', $params['transid'])
        ->addPair('REFUNDTYPE', 'Partial')
        ->addPair('AMT', $params['amount'])
        ->addPair('CURRENCYCODE', 'USD')
        ->execute('RefundTransaction');

    if ($response['success'] && $response['response']['ACK'] == 'Success' && $response['response']['REFUNDTRANSACTIONID']) {
        return [
            'status' => 'success',
            'rawdata' => $response,
            'transid' => $response['response']['REFUNDTRANSACTIONID'],
            'fees' => $response['response']['FEEREFUNDAMT'],
        ];
    }

    return [
        'status' => 'error',
        'rawdata' => $response,
    ];
}
