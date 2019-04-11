<?php
return [
  'fields' => [
    'transaction_mode' => [
      'label' => 'lang:igniter.rave::default.rave.label_transaction_mode',
      'type' => 'radio',
      'default' => 'test',
      'options' => [
        'live' => 'lang:igniter.rave::default.rave.text_live',
        'test' => 'lang:igniter.rave::default.rave.text_test',
      ]
    ],
    'live_public_key' => [
      'label' => 'lang:igniter.rave::default.rave.label_live_public_key',
      'type' => 'text',
      'comment' => 'lang:igniter.rave::default.rave.help_live_public_key',
      'trigger' => [
        'action' => 'show',
        'field' => 'transaction_mode',
        'condition' => 'value[live]',
      ]
    ],
    'live_secret_key' => [
      'label' => 'lang:igniter.rave::default.rave.label_live_secret_key',
      'type' => 'text',
      'comment' => 'lang:igniter.rave::default.rave.help_live_secret_key',
      'trigger' => [
        'action' => 'show',
        'field' => 'transaction_mode',
        'condition' => 'value[live]',
      ]
    ],
    'test_public_key' => [
      'label' => 'lang:igniter.rave::default.rave.label_test_public_key',
      'type' => 'text',
      'comment' => 'lang:igniter.rave::default.rave.help_test_public_key',
      'trigger' => [
        'action' => 'show',
        'field' => 'transaction_mode',
        'condition' => 'value[test]',
      ]
    ],
    'test_secret_key' => [
      'label' => 'lang:igniter.rave::default.rave.label_test_secret_key',
      'type' => 'text',
      'comment' => 'lang:igniter.rave::default.rave.help_test_secret_key',
      'trigger' => [
        'action' => 'show',
        'field' => 'transaction_mode',
        'condition' => 'value[test]',
      ]
    ],
    'modal_title' => [
      'label' => 'lang:igniter.rave::default.rave.label_modal_title',
      'type' => 'text',
      'comment' => 'lang:igniter.rave::default.rave.help_modal_title',
    ],
    'modal_logo' => [
      'label' => 'lang:igniter.rave::default.rave.label_modal_logo',
      'type' => 'text',
      'comment' => 'lang:igniter.rave::default.rave.help_modal_logo',
    ],
    'order_total' => [
      'label' => 'lang:igniter.rave::default.label_order_total',
      'type' => 'number',
      'comment' => 'lang:igniter.rave::default.help_order_total',
    ],
    'order_status' => [
      'label' => 'lang:igniter.rave::default.label_order_status',
      'type' => 'select',
      'options' => ['Admin\Models\Statuses_model', 'getDropdownOptionsForOrder'],
      'comment' => 'lang:igniter.rave::default.help_order_status',
    ],
  ],
];
