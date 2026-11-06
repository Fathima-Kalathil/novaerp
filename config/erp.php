<?php

return [
    // VAT applied to sales orders (5% in the UAE)
    'vat_rate' => (float) env('ERP_VAT_RATE', 0.05),

    // Secret token for the one-time /setup/{token} route (migrate + seed).
    // Leave empty to disable the route completely.
    'setup_token' => env('SETUP_TOKEN'),
];
