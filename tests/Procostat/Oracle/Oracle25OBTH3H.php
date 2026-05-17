<?php

namespace Procorad\Procostat\Tests\Procostat\Oracle;

final class Oracle25OBTH3H
{
    /**
     * Valeur certifiée MRC — indépendante des participants.
     */
    public const ASSIGNED_VALUE           = 19.942;
    public const ASSIGNED_UNCERTAINTY_K2  = 5.114336;

    /**
     * Statistiques robustes (algorithme A, ISO 13528).
     * s* élevé (~5.8) reflète la forte dispersion de la population.
     */
    public const ROBUST_MEAN    = 19.94;
    public const ROBUST_STD_DEV = 7.38;

    public const PERFORMANCE_INDICATOR = 'z'; // primaryIndicator du résultat
    public const LAB_DECISION_BASIS    = 'z_prime'; // score effectif dans LabEvaluation

    /**
     * Lab 6 : valeur = 62.0 Bq/kg, biais +211%, z' = 6.6 → NON_CONFORME.
     * Aberrant Grubbs confirmé (G = 2.94 > seuil 2.607 pour n=13).
     *
     * @var int[]
     */
    public const NON_CONFORM_LABS = [6];

    /**
     * Lab 52 : valeur = 34.7 Bq/kg, z' = 2.33 → DISCUTABLE (2 < |z'| ≤ 3).
     *
     * @var int[]
     */
    public const WARNING_LABS = [];
}
