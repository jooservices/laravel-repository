---
name: review-and-risk-assessment
description: "Use when reviewing a change, preparing release notes, or summarizing implementation risk. Prioritizes regressions, truthfulness, and validation gaps."
---

# Review And Risk Assessment

## Review priorities

- Behavioral regressions
- Documentation drift
- Workflow or release breakage
- Test coverage gaps
- Policy claims that exceed implemented behavior

## Risk summary format

- What changed
- What could break
- What was validated
- What still depends on external setup, secrets, or repository settings

## Repository-specific checks

- Keep trait-based architecture boundaries intact.
- Keep `RequestQueryParser` docs limited to implemented clause families.
- Flag any workflow that assumes secrets or services not documented in the repo.
