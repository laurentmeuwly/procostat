<?php

namespace Procorad\Procostat\Domain\Decision;

enum FitnessStatus: string
{
    case CONFORME = 'conforme';
    case DISCUTABLE = 'discutable';
    case NON_CONFORME = 'non_conforme';
    // Labo exclu du calcul (z > 5 troncature) — pas de fitness applicable
    case NON_EVALUABLE = 'non_evaluable';

}
