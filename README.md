# Procostat

**Procostat** is a statistical analysis engine designed for interlaboratory comparison studies and proficiency testing.
It provides reliable statistical computations, explicit decision rules, and full traceability to ensure auditability and reproducibility.

This package is part of the Procorad ecosystem but can be used independently as a backend statistical engine.

---

## Key Features

- Statistical analysis for interlaboratory comparisons
- Explicit and versioned statistical rules
- Deterministic and reproducible computations
- Full audit trail (parameters, rules, decisions, engine version)
- Modular architecture with optional external adapters (e.g. Python CLI)
- Framework-agnostic core (Laravel integration provided)

---

## Design Principles

Procostat is designed as a **decision engine**, not as a data science toolbox.

Key principles:
- No implicit statistical decisions
- All thresholds and rules are explicit and traceable
- Separation of concerns between:
  - data validation
  - statistical computation
  - decision logic
  - audit and traceability
- Reproducibility is a first-class concern

---

## Architecture Overview

Procostat follows a layered architecture:

- **Public API**: entry point for applications
- **Application layer**: orchestration of analysis workflows
- **Domain layer**: statistical rules, computations, and decisions
- **Infrastructure layer**: adapters (e.g. Python CLI), persistence, logging

The statistical workflow is implemented as an explicit pipeline of steps,
making the analysis process transparent and auditable.

Mermaid diagrams describing the architecture, use cases, and sequence flows
are provided in the documentation.

---

## Typical Workflow

1. The client application prepares and normalizes the dataset
2. The analysis is triggered via the Procostat API
3. The engine validates the dataset and loads versioned rules
4. Statistical tests are applied according to the decision flow
5. Decisions and indicators are computed
6. A complete audit trail is recorded
7. An immutable result object is returned to the client application

---

## Public API (Conceptual)

```php
$result = Procostat::runAnalysis(
    Dataset $dataset,
    AnalysisConfig $config
);
```

The returned ProcostatResult object is immutable and contains:

- statistical results
- decision indicators
- references to the audit trail
- engine and rules versions

---

## Audit and Reproducibility

Procostat ensures that any analysis can be audited and reproduced:

- all input parameters are recorded
- applied statistical rules are versioned
- all intermediate and final decisions are traceable
- the engine version is part of the audit context

Re-running an analysis with the same inputs and configuration will always
produce identical results.

---

## Integration

Procostat is distributed as a Composer package and integrates naturally with
Laravel applications.

External statistical computations (e.g. Python-based tests) are optional and
isolated behind adapters.

---

## Status

This package is currently under active development.
The public API may evolve until the first stable release.

---

## License

This package is released under the MIT License.
