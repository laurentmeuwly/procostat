<?php

use Procorad\Procostat\Domain\Statistics\Robust\RobustEstimator;

/**
 * Validation de l'Algorithme A ISO 13528 implémenté dans RobustEstimator.
 *
 * Ces tests utilisent estimateWithTrace() pour inspecter le détail de chaque
 * itération et comparer avec des valeurs de référence calculées indépendamment
 * (outil externe du client, calcul Python, etc.).
 *
 * Tolérance : 3 chiffres significatifs (critère de convergence ISO 13528 §C.3).
 */

// ─── Helpers ────────────────────────────────────────────────────────────────

function sig3(float $x): float
{
    if ($x == 0.0) return 0.0;
    $p = (int) floor(log10(abs($x)));
    $scale = 10 ** (3 - 1 - $p);
    return round($x * $scale) / $scale;
}

// ─── Dataset 1 : 24 valeurs, troncature attendue (z > 5 pour 0.0713) ────────

it('algorithme A converge sur 24 valeurs avec résultats corrects à 3 chiffres significatifs', function () {
    $values = [
        0.0116, 0.0105, 0.0105, 0.00956, 0.0713, 0.0111,
        0.0109, 0.0112, 0.0113, 0.0112, 0.00549, 0.0136,
        0.0138, 0.00844, 0.011, 0.0105, 0.0102, 0.00897,
        0.0116, 0.0092, 0.00813, 0.0114, 0.0125, 0.0118,
    ];
    $expected_xStar          = 0.010844;
    $expected_sStar          = 0.001727;
    $expected_max_iterations = 20;

    $trace = RobustEstimator::estimateWithTrace($values);

    // ── Convergence ──────────────────────────────────────────────────────────
    expect($trace['converged'])->toBeTrue(
        "L'algorithme n'a pas convergé en {$expected_max_iterations} itérations"
    );

    expect($trace['iterations'])->toBeLessThanOrEqual($expected_max_iterations);

    // ── Résultat final à 3 chiffres significatifs ─────────────────────────────
    expect(sig3($trace['xStar']))->toBe(sig3($expected_xStar),
        sprintf("x* attendu %e, obtenu %e", $expected_xStar, $trace['xStar'])
    );

    expect(sig3($trace['sStar']))->toBe(sig3($expected_sStar),
        sprintf("s* attendu %e, obtenu %e", $expected_sStar, $trace['sStar'])
    );

    // ── Cohérence des étapes ──────────────────────────────────────────────────
    // L'étape 0 est l'initialisation (médiane + MAD)
    expect($trace['steps'])->not->toBeEmpty();
    expect($trace['steps'][0]['i'])->toBe(0);
    expect($trace['steps'][0]['note'])->toContain('initial');

    // Les étapes suivantes doivent être strictement croissantes en indice
    foreach ($trace['steps'] as $k => $step) {
        expect($step['i'])->toBe($k);
    }

    // La dernière étape doit être marquée convergée
    $lastStep = end($trace['steps']);
    expect($lastStep['converged'])->toBeTrue();

});


it('affiche le détail des itérations pour audit visuel (non-assertif)', function () {
    $values = [
        0.0116, 0.0105, 0.0105, 0.00956, 0.0713, 0.0111,
        0.0109, 0.0112, 0.0113, 0.0112, 0.00549, 0.0136,
        0.0138, 0.00844, 0.011, 0.0105, 0.0102, 0.00897,
        0.0116, 0.0092, 0.00813, 0.0114, 0.0125, 0.0118,
    ];

    $trace = RobustEstimator::estimateWithTrace($values);

    $header = sprintf("%-4s  %-14s  %-14s  %-14s  %s",
        'i', 'x*', 's*', 'delta', 'converged');

    $lines = [$header, str_repeat('-', 60)];

    foreach ($trace['steps'] as $step) {
        $lines[] = sprintf(
            "%-4d  %-14s  %-14s  %-14s  %s",
            $step['i'],
            $step['i'] === 0 ? number_format($step['xStar'], 8) : sprintf('%.8e', $step['xStar']),
            $step['i'] === 0 ? number_format($step['sStar'], 8) : sprintf('%.8e', $step['sStar']),
            $step['i'] === 0 ? '—' : sprintf('%.8e', $step['delta']),
            $step['converged'] ? '✓ STOP' : '',
        );
    }

    $lines[] = str_repeat('-', 60);
    $lines[] = sprintf("Convergé : %s  |  Itérations : %d",
        $trace['converged'] ? 'OUI' : 'NON',
        $trace['iterations'],
    );
    $lines[] = sprintf("x* final = %.6e  |  s* final = %.6e",
        $trace['xStar'],
        $trace['sStar'],
    );

    // dump() affiche dans la sortie du test sans faire échouer
    dump(implode("\n", $lines));

    expect(true)->toBeTrue(); // test toujours vert — audit visuel uniquement
});


// ─── Dataset 2 : 6 valeurs (descriptive_only), aucune troncature attendue ───

it('algorithme A sur 6 valeurs (descriptive_only) sans aberrant', function () {
    $values = [0.0256, 0.0200, 0.0244, 0.0228, 0.0210, 0.0197];

    $trace = RobustEstimator::estimateWithTrace($values);

    expect($trace['converged'])->toBeTrue();
    expect($trace['iterations'])->toBeLessThanOrEqual(20);

    // Aucune valeur ne dépasse z > 5 sur cette population
    $mean   = $trace['xStar'];
    $stdDev = $trace['sStar'];
    foreach ($values as $v) {
        $z = abs($v - $mean) / $stdDev;
        expect($z)->toBeLessThan(5.0, "Labo avec valeur {$v} a z={$z} > 5 inattendu");
    }

    dump(sprintf(
        "6 valeurs — x* = %.4e  s* = %.4e  (%d itérations)",
        $trace['xStar'],
        $trace['sStar'],
        $trace['iterations'],
    ));
});


// ─── Dataset 3 : minimum (3 valeurs) ─────────────────────────────────────────

it('algorithme A fonctionne avec le minimum de 3 valeurs', function () {
    $values = [1.0, 2.0, 3.0];

    $trace = RobustEstimator::estimateWithTrace($values);

    expect($trace['converged'])->toBeTrue();
    expect($trace['xStar'])->toBeGreaterThan(0.0);
    expect($trace['sStar'])->toBeGreaterThan(0.0);
});


// ─── Contrat : estimateWithTrace() == estimate() ─────────────────────────────

it('estimateWithTrace retourne les mêmes valeurs finales que estimate', function () {
    $values = [
        0.0116, 0.0105, 0.0105, 0.00956, 0.0111,
        0.0109, 0.0112, 0.0113, 0.0112, 0.00549,
    ];

    [$xStarDirect, $sStarDirect] = RobustEstimator::estimate($values);
    $trace = RobustEstimator::estimateWithTrace($values);

    expect($trace['xStar'])->toBe($xStarDirect);
    expect($trace['sStar'])->toBe($sStarDirect);
});
