<?php

return [
    // Managed education centers should not expose self-registration by default.
    'public_registration' => (bool) env('LOLES_PUBLIC_REGISTRATION', false),
    // Optional demo seed on a clean deployment; no fixed-password accounts.
    'demo_data' => (bool) env('LOLES_DEMO_DATA', false),
];
