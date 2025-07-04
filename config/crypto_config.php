<?php
// Cryptocurrency payment configuration
return [
    'payment_methods' => [
        'btc' => [
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa', // Replace with your BTC address
            'network' => 'Bitcoin',
            'min_amount' => 0.001,
            'confirmations_required' => 1,
            'enabled' => true
        ],
        'usdt' => [
            'name' => 'Tether',
            'symbol' => 'USDT',
            'address' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5oREqjK', // Replace with your USDT address
            'network' => 'TRC20',
            'min_amount' => 5,
            'confirmations_required' => 1,
            'enabled' => true
        ]
    ],
    'qr_api' => [
        'provider' => 'qrserver', // qrserver.com API
        'size' => '300x300',
        'format' => 'png'
    ],
    'payment_status' => [
        'pending' => 'Pending Payment',
        'verifying' => 'Verifying Payment',
        'confirmed' => 'Payment Confirmed',
        'failed' => 'Payment Failed',
        'cancelled' => 'Payment Cancelled'
    ]
];
?>
