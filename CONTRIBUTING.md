# Contributing to Payzy Laravel Payment Gateway

We love your input! We want to make contributing to this project as easy and transparent as possible, whether it's:

- Reporting a bug
- Discussing the current state of the code
- Submitting a fix
- Proposing new features
- Becoming a maintainer

## Development Process

We use GitHub to host code, to track issues and feature requests, as well as accept pull requests.

## Pull Requests

Pull requests are the best way to propose changes to the codebase. We actively welcome your pull requests:

1. Fork the repo and create your branch from `main`.
2. If you've added code that should be tested, add tests.
3. If you've changed APIs, update the documentation.
4. Ensure the test suite passes.
5. Make sure your code lints.
6. Issue that pull request!

## Any contributions you make will be under the MIT Software License

In short, when you submit code changes, your submissions are understood to be under the same [MIT License](http://choosealicense.com/licenses/mit/) that covers the project. Feel free to contact the maintainers if that's a concern.

## Report bugs using GitHub's [issues](https://github.com/payzy-laravel/payment-gateway/issues)

We use GitHub issues to track public bugs. Report a bug by [opening a new issue](https://github.com/payzy-laravel/payment-gateway/issues/new); it's that easy!

## Write bug reports with detail, background, and sample code

**Great Bug Reports** tend to have:

- A quick summary and/or background
- Steps to reproduce
  - Be specific!
  - Give sample code if you can
- What you expected would happen
- What actually happens
- Notes (possibly including why you think this might be happening, or stuff you tried that didn't work)

## Development Setup

```bash
# Clone your fork
git clone https://github.com/your-username/payzy-laravel-payment-gateway.git
cd payzy-laravel-payment-gateway

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Run tests
composer test

# Run code style checks
composer format

# Run static analysis
composer analyse
```

## Coding Standards

We follow the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard and the [PSR-4](https://www.php-fig.org/psr/psr-4/) autoloading standard.

### Code Style

- Use 4 spaces for indentation
- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Write clear, concise comments
- Document all public methods and classes

### Testing

- Write tests for all new features
- Ensure existing tests pass
- Aim for high test coverage
- Use meaningful test names
- Follow the Arrange-Act-Assert pattern

```php
public function test_payment_can_be_initiated_successfully()
{
    // Arrange
    $orderData = $this->createValidOrderData();
    
    // Act
    $result = $this->payzyService->initiatePayment($orderData);
    
    // Assert
    $this->assertTrue($result['success']);
    $this->assertNotNull($result['redirect_url']);
}
```

## Documentation

- Update README.md for any new features
- Add docblocks to all public methods
- Include examples in documentation
- Keep documentation current with code changes

## License

By contributing, you agree that your contributions will be licensed under its MIT License.