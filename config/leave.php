<?php

// config/leave.php
return [
    // Total balance shown in the "Total Leave Balance" card (regular employees)
    'total' => 25,

    // Days per leave type (regular employees). Same numbers as the original cards.
    'types' => [
        'Mandatory Summer Leave'    => 5,
        'Convertible-to-Cash Leave' => 5,
        'Non-Convertible Leave'     => 5,
        'Sick Leave'                => 3,
        'Emergency Leave'           => 2,
        'Bereavement Leave'         => 3,
        'Church Wedding Leave'      => 2,
    ],

    // Small caption under the number (default: "days")
    'captions' => [
        'Church Wedding Leave' => 'days for single employees',
    ],

    // Non-regular employees can only file this type, with this many days
    'non_regular' => [
        'type' => 'Non-Convertible Leave',
        'days' => 5,
    ],
];