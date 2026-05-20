<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Threshold standard
    |--------------------------------------------------------------------------
    |
    | Normative reference used for performance thresholds.
    | Supported values:
    | - iso13528
    | - iso13528_2015
    | - iso13528_2022
    |
    */

    'threshold_standard' => 'iso13528',


    /*
    |--------------------------------------------------------------------------
    | Population thresholds
    |--------------------------------------------------------------------------
    */

    'population' => [

        'min_exploitable' => 3,

        'min_full_evaluation' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | PROCORAD workflow thresholds
    |--------------------------------------------------------------------------
    */

    'workflow' => [

        // z > 5 => truncation
        'truncation_z_threshold' => 5.0,

        // PROCORAD override : toujours Z' comme indicateur primaire,
        // même si la valeur assignée est certifiée (indépendante).
        // ISO 13528 strict utiliserait Z dans ce cas.
        // Mettre à false pour revenir au comportement ISO 13528.
        'force_z_prime' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Normality thresholds
    |--------------------------------------------------------------------------
    */

    'normality' => [

        'skewness' => [
            'presumed_normal' => 0.5,
            'warning' => 2.0,
        ],

        'kurtosis' => [
            'presumed_normal' => 0.5,
            'warning' => 2.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance indicators
    |--------------------------------------------------------------------------
    */

    'performance' => [

        'acceptable' => 2.0,

        'warning' => 3.0,
    ],
];
