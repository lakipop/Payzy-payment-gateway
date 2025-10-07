# Security Policy

## Supported Versions

We actively support the following versions with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take the security of our software seriously. If you believe you have found a security vulnerability in this package, please report it to us as described below.

**Please do not report security vulnerabilities through public GitHub issues.**

### How to Report

Please send an email to [lakindu02@gmail.com] with the following information:

- Type of issue (e.g. buffer overflow, SQL injection, cross-site scripting, etc.)
- Full paths of source file(s) related to the manifestation of the issue
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit the issue

### What to Expect

- You will receive a confirmation of your report within 48 hours
- We will send a more detailed response within 72 hours indicating the next steps
- We will work with you to understand and resolve the issue
- We will credit you for the discovery (unless you prefer to remain anonymous)

### Security Measures

This package implements several security measures:

#### HMAC Signature Verification

All payment callbacks are verified using HMAC-SHA256 signatures to ensure data integrity and authenticity.

#### SSL/TLS Encryption

All communication with the Payzy API uses SSL/TLS encryption.

#### Input Validation

All input data is validated and sanitized before processing.

#### SQL Injection Protection

We use Laravel's Eloquent ORM and parameter binding to prevent SQL injection attacks.

#### CSRF Protection

Web routes include CSRF protection by default.

#### Configuration Security

Sensitive configuration values are stored in environment variables, not in code.

### Responsible Disclosure

We follow responsible disclosure practices and ask that you do the same:

- Give us reasonable time to investigate and mitigate an issue before public exposure
- Do not access or modify data that doesn't belong to you
- Do not perform actions that could negatively affect our users or services
- Do not disclose the issue until we have resolved it and given you permission

## Security Updates

Security updates will be released as patch versions and will be clearly marked in the changelog. We recommend keeping the package updated to the latest version.

To receive notifications about security updates:

1. Watch this repository on GitHub
2. Subscribe to our security mailing list at [lakindu02@gmail.com] .

## Bug Bounty

We do not currently offer a bug bounty program, but we greatly appreciate security researchers who help us keep our software secure. We will acknowledge your contribution and may offer recognition for significant discoveries.

## Questions

If you have questions about this security policy, please contact us at [lakindu02@gmail.com] .
