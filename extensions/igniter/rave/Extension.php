<?php namespace Igniter\Rave;

use System\Classes\BaseExtension;

class Extension extends BaseExtension
{

    public function registerPaymentGateways()
    {
        return [
            'Igniter\Rave\Payments\Rave' => [
                'code' => 'rave',
                'name' => 'lang:igniter.rave::default.rave.text_payment_title',
                'description' => 'lang:igniter.rave::default.rave.text_payment_desc'
            ]
        ];
    }

    public function extensionMeta()
    {
        return [
            'name' => 'Rave',
            'author' => 'Anjola Bassey',
            'description' => 'Accept card, bank account, mobile money and mpesa payments during checkout via Rave',
            'icon' => 'fa-money',
            'version' => 'v1.0.0'
        ];
    }

    public function registerPermissions()
    {
        return [
            'Payment.Rave' => [
                'description' => 'Ability to manage payments via Rave Payment Gateway',
                'group' => 'payment',
            ]
        ];
    }
}
