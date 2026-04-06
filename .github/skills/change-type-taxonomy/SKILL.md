---
name: change-type-taxonomy
description: "Use when determining what kind of change is being made so scope, validation, and risk stay proportional."
---

# Change Type Taxonomy

## Change classes

- Feature: adds or expands behavior
- Bugfix: corrects incorrect behavior
- Docs: changes explanations, examples, navigation, or contributor guidance
- CI or governance: changes workflows, hooks, quality tools, or release policy
- Review or risk pass: analyzes existing changes without adding behavior

## Expected validation by type

- Feature or bugfix: relevant tests plus docs impact
- Docs: link or structure sanity plus truthfulness review
- CI or governance: command map consistency plus workflow or hook review
- Review: findings, assumptions, and residual risk

## Scope rule

Choose the narrowest change class that explains the work. Do not hide governance changes inside a feature task.
