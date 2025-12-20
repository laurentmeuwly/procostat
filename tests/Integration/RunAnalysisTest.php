<?php

namespace Procorad\Procostat\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Procorad\Procostat\Application\RunAnalysis;
use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;
use Procorad\Procostat\Domain\Decision\FitnessStatus;
use Procorad\Procostat\Domain\Audit\AuditTrail;
use Procorad\Procostat\Domain\Norms\NormReference;
use Procorad\Procostat\DTO\LabEvaluation;
use Procorad\Procostat\DTO\ProcostatResult;
use Procorad\Procostat\Infrastructure\Audit\NullAuditStore;

final class RunAnalysisTest extends TestCase
{
    public function test_run_analysis_produces_lab_evaluation_and_audit(): void
    {
        $useCase = new RunAnalysis(
            thresholdsResolver: new ThresholdsResolver(),
            auditStore: new NullAuditStore()
        );

        $result = $useCase([
            'laboratoryCode' => 'LAB-042',
            'participantCount' => 12,
            'zPrimeScore' => 1.7,
            'zetaScore' => 1.3,
            'biasPercent' => 4.2,
            'thresholdStandard' => 'iso13528',
        ]);

        $this->assertInstanceOf(ProcostatResult::class, $result);

        // LabEvaluation
        $this->assertSame(
            FitnessStatus::CONFORME,
            $result->labEvaluation->fitnessStatus
        );

        $this->assertSame(
            'z_prime',
            $result->labEvaluation->decisionBasis
        );

        // Audit exists
        $this->assertCount(
            1,
            $result->auditTrail->all()
        );

        // Audit concerns the right laboratory
        $this->assertSame(
            'LAB-042',
            $result->auditTrail->all()[0]->laboratoryCode
        );

        // Engine version traceability
        $this->assertNotEmpty($result->engineVersion);
    }
}
