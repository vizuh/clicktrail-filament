<?php

declare(strict_types=1);

return [
    // ClickTrail site ID issued by the collector (mirrors clicktrail.php).
    'site_id' => env('CLICKTRAIL_SITE_ID', ''),

    // Collector endpoint for batch delivery.
    'endpoint' => env('CLICKTRAIL_ENDPOINT', 'https://collect.clicktrail.dev/v1/events/batch'),

    // Class-string implementing the Laravel adapter's ConsentResolverInterface.
    // Empty => NullConsentResolver (unknown=denied everywhere).
    'consent_resolver' => env('CLICKTRAIL_CONSENT_RESOLVER', ''),

    // Capability gates mirrored from the consent contract. A gate that is off
    // means that use does not require CMP consent (gate-toggle semantics).
    'capability_gates' => [
        'analytics' => true,
        'advertising' => true,
        'ad_user_data' => true,
    ],
];
