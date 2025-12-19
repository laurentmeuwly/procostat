<?php

namespace Procorad\Procostat\Domain\Decision;

enum FitnessStatus: string
{
    case CONFORME = 'conforme';
    case DISCUTABLE = 'discutable';
    case NON_CONFORME = 'non_conforme';
}
