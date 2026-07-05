# Security Policy

## Supported versions

The latest stable release of **JOOservices Laravel Repository** is supported for security fixes.

Older releases may be unsupported unless maintainers explicitly state otherwise in release notes or repository documentation.

## Reporting a vulnerability

Do not open public GitHub issues for suspected vulnerabilities.

Report security concerns privately to [admin@jooservices.com](mailto:admin@jooservices.com) with:

- a clear summary of the issue
- affected package version(s)
- impact and expected risk
- reproduction details or proof of concept when available

If you are unsure whether a report is security-related, contact maintainers privately first.

## What happens next

Maintainers will acknowledge reports as soon as they can.

Investigation, validation, and any fix or coordinated disclosure timeline will depend on severity, exploitability, and release risk. No guaranteed SLA is promised.

## Scope

This policy covers security issues in repository-managed behavior such as:

- repository trait composition and query lifecycle
- request-query parsing, allowlists, and strict-mode validation
- criteria application and terminal query operations
- dependency and CI or security-workflow configuration issues that affect package consumers or repository integrity

## Non-security issues

Normal bugs, feature requests, questions, and documentation improvements should use the standard GitHub issue templates instead of private security reporting.
