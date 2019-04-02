<?php namespace Igniter\Rave;

use System\Classes\BaseExtension;

class Extension extends BaseExtension
{
    // public function registerComponents()
    // {
    //     return [
    //         'Igniter\Rave\Components\Block' => [
    //             'code' => 'block',
    //             'name' => 'lang:igniter.rave::default.text_component_title',
    //             'description' => 'lang:igniter.rave::default.text_component_desc',
    //         ],
    //     ];
    // }

    public function registerPaymentGateways()
    {
        return [
            'Igniter\Rave\Payments\Rave' => [
                'code' => 'rave',
                'name' => 'lang:igniter.rave::default.rave.text_payment_title',
                'description' => 'lang:igniter.rave::default.text_payment_desc',
            ]
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
