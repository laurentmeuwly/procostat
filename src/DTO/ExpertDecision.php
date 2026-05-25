<?php

namespace Procorad\Procostat\DTO;

/**
 * Decision de l'expert suite a l'echec de la validation de la valeur certifiee (paragraphe 9.2.2).
 *
 * Transmise en passe 2 de RunAnalysis apres que l'UI a affiche
 * le bouton [Validation par l'expert] et recueilli la decision.
 *
 * keepCertifiedValue = true  : l'expert maintient Xref malgre l'inegalite.
 *                              Les scores sont calcules avec Xref. L'info est tracee.
 * keepCertifiedValue = false : l'expert accepte la substitution par la moyenne robuste.
 *                              Comportement identique a la passe 1 automatique.
 */
final class ExpertDecision
{
    public function __construct(
        // true  = conserver la valeur certifiée (choix expert)
        // false = accepter la substitution par la moyenne robuste
        public readonly bool $keepCertifiedValue,
        public readonly ?string $justification = null,
    ) {}
}
