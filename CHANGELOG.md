# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release
- Complete Payzy payment gateway integration
- Laravel 10+ support
- HMAC-SHA256 signature verification
- Payment status tracking
- Webhook and callback support
- Database migrations for payments and payment items
- Comprehensive test suite
- Full documentation

### Security
- HMAC signature verification for all callbacks
- SSL/TLS encryption for API communication
- Input validation and sanitization

## [1.0.0] - 2024-01-01

### Added
- Initial stable release
- Complete Payzy API integration
- Laravel service provider and facade
- Payment tracking models
- Callback handling
- Comprehensive documentation
- Test suite
- Event system for payment lifecycle
- Support for payment items and order tracking
- Configuration system with environment variables
- Error handling and logging
- Payment verification and status management

### Features
- ✅ Payment processing with Payzy API
- ✅ Secure signature verification
- ✅ Payment status tracking
- ✅ Callback and webhook handling
- ✅ Laravel integration (Service Provider, Facade)
- ✅ Database migrations
- ✅ Event system
- ✅ Test mode support
- ✅ Payment items support
- ✅ Comprehensive configuration
- ✅ Full test coverage
- ✅ Detailed documentation

### Requirements
- PHP 8.1+
- Laravel 10.0+
- MySQL 8.0+
- Guzzle HTTP Client 7.0+