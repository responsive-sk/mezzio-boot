# Code Quality Standards

This document outlines the code quality standards and tools used in DotKernel Light.

## Overview

DotKernel Light maintains the highest code quality standards using automated tools and strict configuration. All code must pass maximum level static analysis and follow PSR-12 coding standards.

## Static Analysis

### PHPStan Configuration

PHPStan is configured at **maximum strictness level** for comprehensive code analysis:

```neon
# phpstan.neon
parameters:
    level: max
    paths:
        - bin
        - config
        - src
        - test
    excludePaths:
        - src/Templates/bootstrap/node_modules
        - src/Templates/main/node_modules
    treatPhpDocTypesAsCertain: false
    ignoreErrors:
        # Allow mixed types in template variables
        - '#Parameter \#2 \$variables of method .+::render\(\) expects array<string, mixed>, array.+ given#'
        # Allow dynamic property access in templates
        - '#Access to an undefined property .+::\$.+#'
        # Allow array access on mixed types in templates
        - '#Cannot access offset .+ on mixed#'
    reportUnmatchedIgnoredErrors: false
```

### Running PHPStan

```bash
# Analyze all code
vendor/bin/phpstan analyse

# Analyze specific directory
vendor/bin/phpstan analyse src/

# Generate baseline (if needed)
vendor/bin/phpstan analyse --generate-baseline
```

## Code Style

### PHP CS Fixer Configuration

Code follows PSR-12 standards with additional rules for consistency:

```php
// .php-cs-fixer.php
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP82Migration' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => true,
        // ... additional rules
    ]);
```

### Running PHP CS Fixer

```bash
# Check code style (dry run)
vendor/bin/php-cs-fixer fix --dry-run --diff

# Fix code style issues
vendor/bin/php-cs-fixer fix

# Fix specific directory
vendor/bin/php-cs-fixer fix src/
```

## Quality Standards

### Type Safety

- **Strict types**: All PHP files use `declare(strict_types=1)`
- **Type hints**: All parameters and return types must be typed
- **PHPDoc**: Complex types documented with `@param` and `@return`
- **Generic types**: Array types specify key and value types

Example:
```php
<?php

declare(strict_types=1);

/**
 * @param array<string, mixed> $config
 * @return array<string, string>
 */
public function processConfig(array $config): array
{
    // Implementation
}
```

### Error Handling

- **Exceptions**: Use typed exceptions for error conditions
- **Validation**: Input validation with proper error messages
- **Null safety**: Explicit null checks and handling

### Documentation

- **PHPDoc blocks**: All public methods documented
- **Type annotations**: Complex types properly documented
- **Examples**: Usage examples in docblocks where helpful

## Automated Checks

### Pre-commit Hooks

Quality checks run automatically before commits:

```bash
# Install pre-commit hooks
composer install

# Manual quality check
composer check-quality
```

### CI/CD Pipeline

Continuous integration runs:

1. **PHPStan** - Static analysis at max level
2. **PHP CS Fixer** - Code style validation
3. **PHPUnit** - Unit and integration tests
4. **Security** - Dependency vulnerability scanning

## Development Workflow

### Before Committing

1. Run static analysis: `vendor/bin/phpstan analyse`
2. Fix code style: `vendor/bin/php-cs-fixer fix`
3. Run tests: `vendor/bin/phpunit`
4. Verify application works: Test key functionality

### Code Review Checklist

- [ ] PHPStan passes at max level
- [ ] Code style follows PSR-12
- [ ] All methods have type hints
- [ ] Complex types documented
- [ ] Error handling implemented
- [ ] Tests cover new functionality
- [ ] No security vulnerabilities

## Tools and Dependencies

### Development Dependencies

```json
{
    "require-dev": {
        "phpstan/phpstan": "^1.10",
        "phpstan/phpstan-phpunit": "^1.3",
        "friendsofphp/php-cs-fixer": "^3.84"
    }
}
```

### IDE Configuration

Recommended IDE settings:

- **PHPStan**: Enable real-time analysis
- **PHP CS Fixer**: Format on save
- **Type hints**: Show parameter types
- **Strict mode**: Enable strict type checking

## Quality Metrics

Current quality status:

- **PHPStan Level**: Maximum (10/10)
- **Code Coverage**: Target 80%+
- **Cyclomatic Complexity**: Max 10 per method
- **Technical Debt**: Minimal

## Best Practices

### Type Declarations

```php
// Good: Specific types
public function getUsers(): array<User>

// Better: Even more specific
/**
 * @return array<int, User>
 */
public function getUsers(): array
```

### Error Handling

```php
// Good: Typed exceptions
if (!$user) {
    throw new UserNotFoundException("User not found: {$id}");
}

// Good: Null safety
$name = $user?->getName() ?? 'Unknown';
```

### Documentation

```php
/**
 * Process user registration with validation.
 *
 * @param array<string, mixed> $userData User input data
 * @return User Validated and persisted user
 * @throws ValidationException When validation fails
 * @throws DatabaseException When persistence fails
 */
public function registerUser(array $userData): User
```

This configuration ensures DotKernel Light maintains enterprise-grade code quality suitable for production environments.
