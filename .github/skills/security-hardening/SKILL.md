---
name: security-hardening
description: "Use when touching secret scanning, dependency review, workflow permissions, or repository automation that depends on credentials."
---

# Security Hardening

## Focus areas

- Secret scanning truthfulness and configuration
- Workflow permissions minimization
- Dependency review and audit surfaces
- Credential-dependent release automation

## Rules

- Do not imply a security workflow is active if it is only planned.
- Prefer explicit workflow permissions over broad defaults.
- Document required secrets before enabling publish or upload jobs.
- Re-run Snyk scans after adding first-party supported-language files.
