<?php

return [
    'cart' => [
        'max_line_quantity' => (int) env('COMMERCE_MAX_LINE_QUANTITY', 99),
        'max_total_quantity' => (int) env('COMMERCE_MAX_TOTAL_QUANTITY', 99),
    ],

    'reservation_minutes' => (int) env('COMMERCE_RESERVATION_MINUTES', 60),
    'confirmation_minutes' => (int) env('COMMERCE_CONFIRMATION_MINUTES', 60),
];
