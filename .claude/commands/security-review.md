# security-review

Review security-sensitive repository changes with `security-hardening`, `ci-hooks-maintenance`, and `review-and-risk-assessment`.

Keep secret assumptions explicit and avoid describing planned enforcement as active.
Verify whether external-service secrets such as `CODECOV_TOKEN` and `PACKAGIST_TOKEN` are mandatory, optional, or gated by workflow conditions.
