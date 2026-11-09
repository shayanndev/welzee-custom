<?php
/**
 * Welzee PRODUCTION private configuration TEMPLATE.
 *
 * Install as:   /home/welzywbw/welzee_private/config.php
 * (i.e. OUTSIDE public_html - never upload this file into public_html)
 *
 * chmod 600 (or 640). Replace every TO_BE_CONFIGURED value.
 * The application finds this file automatically at
 *   dirname($_SERVER['DOCUMENT_ROOT']) . '/welzee_private/config.php'
 * or via the WELZEE_CONFIG_FILE environment variable.
 * Changing values here requires NO rebuild or re-upload of the website.
 */
return [
    'env'      => 'production',
    'site_url' => 'https://welzee.com',          // canonical, non-www, https
    'timezone' => 'Asia/Karachi',
    'log_dir'  => '/home/welzywbw/welzee_private/logs',   // create this folder (chmod 750)

    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'TO_BE_CONFIGURED',   // cPanel prefixes names, e.g. welzywbw_welzee
        'user' => 'TO_BE_CONFIGURED',   // e.g. welzywbw_welzee
        'pass' => 'TO_BE_CONFIGURED',
        'time_zone' => '+05:00',
    ],

    'smtp' => [
        // Recommended: a cPanel mailbox such as orders@welzee.com
        'host'       => 'TO_BE_CONFIGURED',   // e.g. mail.welzee.com
        'port'       => 465,                  // 465 + 'ssl'  or  587 + 'tls'
        'secure'     => 'ssl',
        'user'       => 'TO_BE_CONFIGURED',   // full mailbox address
        'pass'       => 'TO_BE_CONFIGURED',
        'from_email' => 'TO_BE_CONFIGURED',   // must be an address on welzee.com for SPF/DKIM
        'from_name'  => 'Welzee Custom Orders',
        'to_email'   => 'TO_BE_CONFIGURED',   // inbox that receives order notifications
    ],

    'limits' => [
        'orders_per_hour_per_ip'     => 5,
        'min_seconds_between_orders' => 10,
    ],
];

