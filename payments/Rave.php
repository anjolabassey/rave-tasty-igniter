<?php namespace Igniter\Rave\Payments;

use Admin\Classes\BasePaymentGateway;
use ApplicationException;

class Rave extends BasePaymentGateway
{
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

  public function beforeRenderPaymentForm($host, $controller)
  {
    // $controller->addCss('~/extensions/igniter/payregister/assets/stripe.css', 'stripe-css');
    $controller->addJs('https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js', 'stripe-js');
    // $controller->addJs('~/extensions/igniter/payregister/assets/process.stripe.js', 'process-stripe-js');
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
    try {
      $gateway = $this->createGateway();
      $response = '';
      if (!$response->isSuccessful()) {
        $order->logPaymentAttempt('Payment error -> ' . $response->getMessage(), 1, $fields, $response->getData());
        return false;
      }
      if ($order->markAsPaymentProcessed()) {
        $order->logPaymentAttempt('Payment successful', 1, $fields, $response->getData());
        $order->updateOrderStatus($paymentMethod->order_status, ['notify' => false]);
      }
    } catch (Exception $ex) {
      throw new ApplicationException('Sorry, there was an error processing your payment. Please try again later.');
    }
  }

  protected function createGateway()
  {
    $gateway = 
    return $gateway;
  }
}
