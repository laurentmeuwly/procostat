<?php

require __DIR__ . '/../vendor/autoload.php';

use Procorad\Procostat\Application\RunAnalysis;
use Procorad\Procostat\Tests\Procostat\Dataset\Dataset25XGA88Y;
use Procorad\Procostat\Tests\Support\ProcostatResultView;

use Procorad\Procostat\Application\Resolvers\ThresholdsResolver;

use Procorad\Procostat\Contracts\AuditStore;
use Procorad\Procostat\Contracts\NormalityAdapter;
use Procorad\Procostat\Domain\AssignedValue\AssignedValueResolver;

// bootstrap minimal du moteur
$engine = new RunAnalysis(
    normalityAdapter: new NormalityAdapter(),
    assignedValueResolver: new AssignedValueResolver(),
    thresholdsResolver: new ThresholdsResolver(),
    auditStore: new AuditStore(),
    thresholdStandard: 'iso13528'
);

$dataset = Dataset25XGA88Y::create();
$result = $engine->analyze($dataset);

echo json_encode(
    ProcostatResultView::forDiscussion($result),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
);
