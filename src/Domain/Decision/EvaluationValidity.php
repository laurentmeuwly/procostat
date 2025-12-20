<?php

namespace Procorad\Procostat\Domain\Decision;

enum EvaluationValidity: string
{
    case OFFICIAL = 'official';
    case INFORMATIVE = 'informative';
    case NOT_VALID = 'not_valid';
}
