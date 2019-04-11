<?php
namespace Igniter\Rave\Payments;
session_start();

use Admin\Classes\BasePaymentGateway;
use Exception;
use October\Rain\Exception\ApplicationException;
use Redirect;


class Rave extends BasePaymentGateway
{
    protected $orderModel = 'Igniter\Cart\Models\Orders_model';

    public function isApplicable($total, $host)
    {
        return $host->order_total <= $total;
    }

    public function isTestMode()
    {
        return $this->model->transaction_mode != 'live';
    }

    public function getPublicKey()
    {
        return $this->isTestMode() ? $this->model->test_public_key : $this->model->live_public_key;
    }

    public function getSecretKey()
    {   
        return $this->isTestMode() ? $this->model->test_secret_key : $this->model->live_secret_key;
    }

    public function registerEntryPoints()
    {
        return [
            'rave_return_url' => 'processReturnUrl',
            'rave_cancel_url' => 'processCancelUrl',
        ];
    }

    /**
     * Processes payment using passed data.
     *
     * @param array $data
     * @param \Admin\Models\Payments_model $host
     * @param \Admin\Models\Orders_model $order
     *
     * @throws \ApplicationException
     */
    public function processPaymentForm($data, $host, $order)
    {

        $paymentMethod = $order->payment_method;
        if (!$paymentMethod or $paymentMethod->code != $host->code)
            throw new ApplicationException('Payment method not found');

        if (!$this->isApplicable($order->order_total, $host))
            throw new ApplicationException(sprintf(
                lang('igniter.payregister::default.alert_min_order_total'),
                currency_format($host->order_total),
                $host->name
            ));

        $fields = $this->getPaymentFormFields($order, $data);

        // meta data
        $metaData = array(
            array(
                'metaname' => 'fields',
                'metavalue' => serialize($fields)
            )
        );

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/hosted/pay",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'amount' => $fields['amount'],
                'customer_email' => $fields['customer_email'],
                'currency' => $fields['currency'],
                'country' => $fields['country'],
                'txref' => $fields['txref'],
                'PBFPubKey' => $fields['public_key'],
                'redirect_url' => $fields['redirect_url'],
                'custom_logo' => $fields['custom_logo'],
                'custom_title' => $fields['custom_title'],
                'meta' => $metaData,
            ]),
            CURLOPT_HTTPHEADER => [
                "content-type: application/json",
                "cache-control: no-cache"
            ],
        ));
        
        $_SESSION['seckey'] = $fields['secret_key'];
        $_SESSION['cancelurl'] = $fields['cancel_url'];
        
        $response = curl_exec($curl);
        $err = curl_error($curl);

        if ($err) {
            // there was an error contacting the rave API
            throw new ApplicationException('Curl returned error: ' . $err);
        }

        $transaction = json_decode($response);

        if (!$transaction->data && !$transaction->data->link) {

            $order->logPaymentAttempt('API returned error: ' . $transaction->message, 1, $fields, $transaction->message);
            // there was an error from the API
            // throw new ApplicationException('API returned error: ' . $transaction->message);

            return false;
        }

        // redirect to page so User can pay
        return Redirect::to($transaction->data->link);
    }

    public function processReturnUrl($params)
    {
        // get txref
        if (isset($_GET['txref'])) {
            $txref = $_GET['txref'];
        } else {
            $resp = json_decode($_GET['resp'], true);
            $txref = $resp['data']['data']['txRef'];
        }

        $hash = $params;
        $order = $this->createOrderModel()->whereHash($hash)->first();
        // $paymentMethod = $order->payment_method;

        if (!$hash or !$order)
            throw new ApplicationException('No order found');

        if (!strlen($redirectPage = input('redirect')))
            throw new ApplicationException('No redirect page found');

        if (!$paymentMethod = $order->payment_method or $paymentMethod->getGatewayClass() != static::class)
            throw new ApplicationException('No valid payment method found');

        // verify transaction
        $postdata = array(
            'SECKEY' => $_SESSION['seckey'],
            'txref' => $txref
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = [
            'Content-Type: application/json',
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $request = curl_exec($ch);
        $err = curl_error($ch);

        if ($err) {
            // there was an error contacting rave
            die('Curl returned error: ' . $err);
        }


        curl_close($ch);

        $result = json_decode($request, true);

        if ('error' == $result['status']) {
            // there was an error from the API
            //   die('API returned error: ' . $result->message);
            // throw new ApplicationException('Curl returned error: ' . $result['status']);
            
            $cancelUrl = $_SESSION['cancelurl'];
 
            // destroy the session 
            session_destroy();

            return Redirect::to($order->getUrl($cancelUrl));
        }
        
        // unserialize meta data to retrieve fields
        $meta = $result['data']['meta'][0];
        $fields = unserialize($meta['metavalue']);

        if ('successful' == $result['data']['status'] && ('00' == $result['data']['chargecode'] || '0' == $result['data']['chargecode'])) {

            if ($order->markAsPaymentProcessed()) {
                $order->logPaymentAttempt('Payment successful', 1, $fields, $result['data']);
                $order->updateOrderStatus($paymentMethod->order_status, ['notify' => false]);
            }
            
            // destroy the session 
            session_destroy();
            
            return Redirect::to($order->getUrl($redirectPage));
        } else {
            $cancelUrl = $_SESSION['cancelurl'];
 
            // destroy the session 
            session_destroy();

            return Redirect::to($order->getUrl($cancelUrl));
        }
    }
    
    public function processCancelUrl($params)
    {
        $hash = $params;
        $order = $this->createOrderModel()->whereHash($hash)->first();
        
        if (!$hash OR !$order)
            throw new ApplicationException('No order found');
            
        if (!strlen($redirectPage = input('redirect')))
            throw new ApplicationException('No redirect page found');
            
        if (!$paymentMethod = $order->payment_method OR $paymentMethod->getGatewayClass() != static::class)
            throw new ApplicationException('No valid payment method found');
            
        $order->logPaymentAttempt('Payment canceled by customer', 0, input());
            
        return Redirect::to($order->getUrl($redirectPage, null));
    }

    public function getPaymentFormFields($order, $data = [])
    {
        
        $returnUrl = $this->makeEntryPointUrl('rave_return_url') . '/' . $order->hash;
        $cancelUrl = $this->makeEntryPointUrl('rave_cancel_url') . '/' . $order->hash;

        $currency = currency()->getUserCurrency();

        switch ($currency) {
            case 'KES':
                $country = 'KE';
                break;
            case 'GHS':
                $country = 'GH';
                break;
            case 'ZAR':
                $country = 'ZA';
                break;
            case 'TZS':
                $country = 'TZ';
                break;

            default:
                $country = 'NG';
                break;
        }

        $fields = array();
        $fields['public_key'] = $this->getPublicKey();
        $fields['secret_key'] = $this->getSecretKey();
        $fields['customer_email'] = $order->email;
        $fields['customer_firstname'] = $order->first_name;
        $fields['customer_lastname'] = $order->last_name;
        $fields['customer_phone'] = $order->telephone;
        $fields['custom_logo'] = $this->model->modal_logo;
        $fields['custom_title'] = $this->model->modal_title;
        $fields['country'] = $country;
        $fields['redirect_url'] = $returnUrl . '?redirect=' . array_get($data, 'successPage');
        $fields['cancel_url'] = $cancelUrl.'?redirect='.array_get($data, 'cancelPage');
        $fields['txref'] = $order->order_id . '_' . time();
        $fields['amount'] = number_format($order->order_total, 2, '.', '');
        $fields['currency'] = $currency;
        
        return $fields;
    }
}
