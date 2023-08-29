<?php

namespace App\Http\Controllers\Gateway\UddoktaPay;

use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Http\Controllers\Gateway\PaymentController;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;

class ProcessController extends Controller
{

    public static function process($deposit)
    {
        $gateway_currency = $deposit->gatewayCurrency();

        $uddoktapayParams = json_decode($gateway_currency->gateway_parameter);

        $requestData = [
            'full_name'     => isset(optional($deposit->user)->username) ? optional($deposit->user)->username : "John Doe",
            'email'         => isset(optional($deposit->user)->email) ? optional($deposit->user)->email : "John Doe",
            'amount'        => round($deposit->final_amo,2),
            'metadata'      => [
                'trx_id'                => $deposit->trx
            ],
            'redirect_url'  =>  route('ipn.'.$deposit->gateway->alias),
            'return_type'   => 'GET',
            'cancel_url'    => route(gatewayRedirectUrl()),
            'webhook_url'   => route('ipn.'.$deposit->gateway->alias),
        ];
        
        try {
        $redirect_url = self::initPayment($requestData, $uddoktapayParams);
        $send['redirect'] = TRUE;
        $send['redirect_url'] = $redirect_url;
        } catch (\Exception $e) {
            $send['error'] = TRUE;
            $send['message'] = $e->getMessage();
        }
        
        return json_encode($send);
    }
    public function ipn(Request $request)
    {
        $upAcc = GatewayCurrency::where('gateway_alias', 'UddoktaPay')->orderBy('id', 'desc')->first();
        $uddoktapayParams = json_decode($upAcc->gateway_parameter);
        
        $response = self::verifyPayment($request, $uddoktapayParams);
        
        if(isset($response['status']) && $response['status'] === 'COMPLETED')
        {
            $deposit = Deposit::where('trx', $response['metadata']['trx_id'])->orderBy('id', 'DESC')->first();
            PaymentController::userDataUpdate($deposit);
            
        }
        return redirect(route(gatewayRedirectUrl(true)));
    }
    
    public static function initPayment($requestData, $uddoktapayParams)
    {
        $host = parse_url($uddoktapayParams->api_url,  PHP_URL_HOST);
        $apiUrl = "https://{$host}/api/checkout-v2";
        
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_HTTPHEADER => [
                "RT-UDDOKTAPAY-API-KEY: " . $uddoktapayParams->api_key,
                "accept: application/json",
                "content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        } else {
            $result = json_decode($response, true);
            if (isset($result['status']) && isset($result['payment_url'])) {
                return $result['payment_url'];
            } else {
                throw new Exception($result['message']);
            }
        }
        throw new Exception("Please recheck configurations");
    }
    
    public static function verifyPayment($resuest, $uddoktapayParams)
    {
        $host = parse_url($uddoktapayParams->api_url,  PHP_URL_HOST);
        $verifyUrl = "https://{$host}/api/verify-payment";

        $invoice_data = [
            'invoice_id'    => $resuest->invoice_id
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $verifyUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($invoice_data),
            CURLOPT_HTTPHEADER => [
                "RT-UDDOKTAPAY-API-KEY: " . $uddoktapayParams->api_key,
                "accept: application/json",
                "content-type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            throw new Exception("cURL Error #:" . $err);
        } else {
            return json_decode($response, true);
        }
        throw new Exception("Please recheck configurations");
    }
}