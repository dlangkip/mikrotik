<?php

/**
 * PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *
 * Payment Gateway M-Pesa
 *
 * created by @Kiprotich
 *
 **/

function mpesa_validate_config()
{
    global $config;
    if (empty($config['mpesa_consumer_key']) || empty($config['mpesa_consumer_secret']) || empty($config['mpesa_shortcode']) || empty($config['mpesa_passkey'])) {
        sendTelegram("M-Pesa payment gateway not configured");
        r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup M-Pesa payment gateway, please tell admin"));
    }
}

function mpesa_show_config()
{
    global $ui;
    $ui->assign('_title', 'M-Pesa - Payment Gateway');
    $ui->display('mpesa.tpl');
}

function mpesa_save_config()
{
    global $admin, $_L;
    $mpesa_consumer_key = _post('mpesa_consumer_key');
    $mpesa_consumer_secret = _post('mpesa_consumer_secret');
    $mpesa_shortcode = _post('mpesa_shortcode');
    $mpesa_passkey = _post('mpesa_passkey');
    $mpesa_environment = _post('mpesa_environment');

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_consumer_key')->find_one();
    if ($d) {
        $d->value = $mpesa_consumer_key;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mpesa_consumer_key';
        $d->value = $mpesa_consumer_key;
        $d->save();
    }

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_consumer_secret')->find_one();
    if ($d) {
        $d->value = $mpesa_consumer_secret;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mpesa_consumer_secret';
        $d->value = $mpesa_consumer_secret;
        $d->save();
    }

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_shortcode')->find_one();
    if ($d) {
        $d->value = $mpesa_shortcode;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mpesa_shortcode';
        $d->value = $mpesa_shortcode;
        $d->save();
    }

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_passkey')->find_one();
    if ($d) {
        $d->value = $mpesa_passkey;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mpesa_passkey';
        $d->value = $mpesa_passkey;
        $d->save();
    }

    $d = ORM::for_table('tbl_appconfig')->where('setting', 'mpesa_environment')->find_one();
    if ($d) {
        $d->value = $mpesa_environment;
        $d->save();
    } else {
        $d = ORM::for_table('tbl_appconfig')->create();
        $d->setting = 'mpesa_environment';
        $d->value = $mpesa_environment;
        $d->save();
    }

    _log('[' . $admin['username'] . ']: M-Pesa ' . Lang::T('Settings_Saved_Successfully'), 'Admin', $admin['id']);

    r2(U . 'paymentgateway/mpesa', 's', Lang::T('Settings_Saved_Successfully'));
}

function mpesa_create_transaction($trx, $user)
{
    global $config;
    $url = mpesa_get_api_url() . 'mpesa/stkpush/v1/processrequest';
    $callback_url = U . 'callback/mpesa';

    $timestamp = date('YmdHis');
    $password = base64_encode($config['mpesa_shortcode'] . $config['mpesa_passkey'] . $timestamp);

    $curl_post_data = array(
        'BusinessShortCode' => $config['mpesa_shortcode'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $trx['price'],
        'PartyA' => $user['phonenumber'],
        'PartyB' => $config['mpesa_shortcode'],
        'PhoneNumber' => $user['phonenumber'],
        'CallBackURL' => $callback_url,
        'AccountReference' => $trx['id'],
        'TransactionDesc' => 'Payment for Order #' . $trx['id']
    );

    $data_string = json_encode($curl_post_data);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Authorization:Bearer ' . mpesa_get_access_token()
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($curl_response);

    if (isset($response->ResponseCode) && $response->ResponseCode == "0") {
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('username', $user['username'])
            ->where('status', 1)
            ->find_one();
        $d->gateway_trx_id = $response->CheckoutRequestID;
        $d->payment_method = 'M-Pesa';
        $d->payment_channel = 'M-Pesa STK Push';
        $d->pg_request = json_encode($response);
        $d->pg_url_payment = U . "order/view/" . $d['id'];
        $d->expired_date = date('Y-m-d H:i:s', strtotime("+1 hour"));
        $d->save();

        r2(U . "order/view/" . $d['id'], 's', Lang::T("Transaction initiated. Please check your phone to complete the payment."));
    } else {
        sendTelegram("M-Pesa payment failed\n\n" . json_encode($response, JSON_PRETTY_PRINT));
        r2(U . 'order/package', 'e', Lang::T("Failed to create transaction. Please try again later."));
    }
}

function mpesa_payment_notification()
{
    $callbackJSONData = file_get_contents('php://input');
    $callbackData = json_decode($callbackJSONData);

    $resultCode = $callbackData->Body->stkCallback->ResultCode;
    $resultDesc = $callbackData->Body->stkCallback->ResultDesc;
    $merchantRequestID = $callbackData->Body->stkCallback->MerchantRequestID;
    $checkoutRequestID = $callbackData->Body->stkCallback->CheckoutRequestID;

    $trx = ORM::for_table('tbl_payment_gateway')
        ->where('gateway_trx_id', $checkoutRequestID)
        ->find_one();

    if (!$trx) {
        return;
    }

    if ($resultCode == "0") {
        $amount = $callbackData->Body->stkCallback->CallbackMetadata->Item[0]->Value;
        $mpesaReceiptNumber = $callbackData->Body->stkCallback->CallbackMetadata->Item[1]->Value;
        $transactionDate = $callbackData->Body->stkCallback->CallbackMetadata->Item[3]->Value;
        $phoneNumber = $callbackData->Body->stkCallback->CallbackMetadata->Item[4]->Value;

        $user = ORM::for_table('tbl_customers')
            ->where('username', $trx['username'])
            ->find_one();

        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], 'M-Pesa')) {
            _log("M-Pesa Payment Successful, But Failed to activate Package");
        }

        $trx->pg_paid_response = json_encode($callbackData);
        $trx->paid_date = date('Y-m-d H:i:s', strtotime($transactionDate));
        $trx->status = 2;
        $trx->save();

        _log("M-Pesa Payment Successful: $mpesaReceiptNumber");
    } else {
        $trx->status = 3;
        $trx->save();
        _log("M-Pesa Payment Failed: $resultDesc");
    }
}

function mpesa_get_status($trx, $user)
{
    global $config;
    $url = mpesa_get_api_url() . 'mpesa/stkpushquery/v1/query';

    $curl_post_data = array(
        'BusinessShortCode' => $config['mpesa_shortcode'],
        'Password' => base64_encode($config['mpesa_shortcode'] . $config['mpesa_passkey'] . date('YmdHis')),
        'Timestamp' => date('YmdHis'),
        'CheckoutRequestID' => $trx['gateway_trx_id']
    );

    $data_string = json_encode($curl_post_data);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type:application/json',
        'Authorization:Bearer ' . mpesa_get_access_token()
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($curl_response);

    if (isset($response->ResultCode) && $response->ResultCode == "0") {
        r2(U . "order/view/" . $trx['id'], 's', Lang::T("Transaction successful."));
    } else {
        r2(U . "order/view/" . $trx['id'], 'w', Lang::T("Transaction still unpaid or failed."));
    }
}

function mpesa_get_access_token()
{
    global $config;
    $url = mpesa_get_api_url() . 'oauth/v1/generate?grant_type=client_credentials';

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    $credentials = base64_encode($config['mpesa_consumer_key'] . ':' . $config['mpesa_consumer_secret']);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . $credentials));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $curl_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($curl_response);
    return $response->access_token;
}


//validation for phone number format
function validate_mpesa_phone($phone) {
    // Remove any spaces or special characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it starts with country code (254 for Kenya)
    if (!preg_match('/^254[7|1][0-9]{8}$/', $phone)) {
        // Try to format it
        if (strlen($phone) === 9) {
            $phone = '254' . $phone;
        } else if (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '254' . substr($phone, 1);
        }
    }
    
    return $phone;
}

function mpesa_get_api_url()
{
    global $config;
    return ($config['mpesa_environment'] == 'live') 
        ? 'https://api.safaricom.co.ke/' 
        : 'https://sandbox.safaricom.co.ke/';
}

function handle_mpesa_error($error_message) {
    _log('M-Pesa Error: ' . $error_message, 'Payment Gateway');
    sendTelegram('M-Pesa Error: ' . $error_message);
    return false;
}
