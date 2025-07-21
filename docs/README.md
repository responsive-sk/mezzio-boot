# Mezzio Boot Documentation

This is the comprehensive documentation for Mezzio Boot - a PSR-15 compliant application skeleton built on Mezzio microframework and Laminas components.

## Table of Contents

- [Developer Protocol](#developer-protocol)
- [Security Policy](#security-policy)
- [Slovak Documentation](#slovak-documentation)
- [Changelog](#changelog)

---

## Developer Protocol

Zend4Boot is a PSR-15 compliant application skeleton built on Mezzio microframework and Laminas components.

### Server Requirements

#### OS
Production: UNIX-like system recommended

#### Web Server

**Apache ≥ 2.2**
- mod_rewrite enabled
- .htaccess support (AllowOverride All)
- The repository includes a default .htaccess file in the public/ folder

**Nginx**
- You must convert .htaccess into a valid nginx config

#### PHP
- **Version**: 8.2, 8.3, or 8.4
- Both mod_php and FPM are supported

**Required PHP Settings**
- memory_limit ≥ 128M
- mbstring extension
- Composer must be available in $PATH

**Recommended Extensions**
- opcache
- dom, simplexml – for XML/HTML processing
- gd, exif – for image handling
- zlib, zip, bz2 – for compression
- curl – for HTTP and API communication

### Code Standards & Structure

#### Autoloading
- Follows PSR-4 standards
- Autoloaded via composer.json

#### Architecture
```
.
├── src/Core/               # Infrastructure (core services, middleware)
├── modules/                # Modular, domain-specific functionality
│   ├── User/               # Example module
│   └── Blog/
├── config/                 # App configuration
├── public/                 # Public entrypoint and assets
├── templates/              # Shared templates/layouts
└── test/                   # Project-wide tests
```

### Module Guidelines

#### Structure of modules/<ModuleName>
```
modules/Module/
├── src/
│   ├── ConfigProvider.php
│   ├── RoutesDelegator.php
│   ├── Handler/
│   ├── Middleware/
│   ├── Service/
│   └── Entity/
├── templates/              # Twig templates
└── test/                   # Unit/functional tests
```

#### Module Registration Flow
1. Create modules/MyModule/src/
2. Add ConfigProvider and optionally RoutesDelegator
3. Register the module in config/config.php:
```php
return [
    new \MyModule\ConfigProvider(),
];
```
4. Run composer dump-autoload

#### Naming Conventions
- Use meaningful names: user, mark, media
- Avoid: utils, common, lib, or vague names
- **NEVER use**: admin, Admin, administrator
- **ALWAYS use**: mark, Mark for admin functionality

### Middleware Execution Order

Defined in config/pipeline.php:
1. Error handling middleware
2. Session middleware
3. CSRF protection
4. Routing
5. Authorization
6. Dispatch

### Authentication & Authorization

- Authentication is handled via a PSR-15 middleware (AuthenticationInterface implementation)
- Identity is injected into requests as user attribute
- Authorization via:
  - RequireLoginMiddleware
  - RequireRoleMiddleware (RBAC)

### Testing & QA

- Static analysis: PHPStan (phpstan.neon) — level max
- Code style: PHP_CodeSniffer, Twig CS Fixer
- Use meaningful commit messages (see Git section)

### Build & Deployment

- Frontend builds via pnpm, Vite, or Webpack
- Output goes to public/
- Use clear-config-cache.php when switching environments

### Configuration Folders

#### config/
- config.php – Loads ConfigProviders
- container.php – Defines service container
- development.config.php.dist – Dev-mode file
- pipeline.php – Middleware execution
- twig-cs-fixer.php – Twig formatting rules

#### config/autoload/
- *.global.php, *.local.php.dist – Modular config
- dependencies.global.php – DI definitions
- error-handling.global.php – Error logging
- mezzio.global.php – Mezzio config

### Other Notable Folders

#### data/cache/
Stores config and service cache files

#### log/
Daily logs based on config in error-handling.global.php

#### public/
- Entry point (index.php)
- Web assets: JS, CSS, fonts, images
- .htaccess – Apache rewrite rules
- robots.txt.dist – Sample bot rules

### Logging Guidelines

- Use PSR-3 logger (e.g. Monolog)
- Context arrays for structured logs
- Severity:
  - error – Failures
  - warning – Recoverable issues
  - info – Application-level events
  - debug – Dev-only context

### Git & Commits

- Use git flow or trunk-based branching
- Use conventional commits (feat, fix, chore, refactor...)
- Avoid force-push unless rewriting history with approval
- **NO EMOJI** in commit messages or documentation

### Documentation Standards

#### No Emoji Policy
- **Documentation**: No emoji in any documentation files
- **Commit Messages**: No emoji in commit messages
- **Code Comments**: No emoji in code comments
- **README Files**: No emoji in README files

```markdown
# Correct
feat: add Mark dashboard functionality
fix: resolve template path issues

# Incorrect
feat: ✨ add Mark dashboard functionality
fix: 🐛 resolve template path issues
```

### Path Management

- **Package**: responsive-sk/slim4-paths
- **Configuration**: Centralized in config/autoload/paths.global.php
- **Usage**: Always through Paths service, never hardcoded

```php
// Correct
$templatePath = $this->paths->src('App/templates/layout');
$configFile = $this->paths->config('app.php');

// Incorrect
$templatePath = __DIR__ . '/../templates/layout';
$configFile = dirname(__DIR__) . '/config/app.php';
```

### Frontend Development

#### Asset Management
- **Build Tool**: Vite
- **Package Manager**: pnpm (NEVER npm or yarn)
- **CSS Framework**: Bootstrap 5
- **Preprocessor**: SCSS

#### Theme System
- **Dark/Light Theme**: Mandatory
- **CSS Variables**: For theme switching
- **Local Storage**: Theme persistence
- **System Detection**: prefers-color-scheme

### Optional: API Strategy (for SPA or external apps)

- Namespace under /api/*
- All endpoints return JSON
- Use JsonResponse with consistent structure
- Recommended: implement OpenAPI (via cebe/php-openapi, swagger-php, etc.)

### Development Best Practices

- Keep modules loosely coupled
- Use dependency injection everywhere
- Follow SOLID and PSR guidelines
- Prefer immutability and typed properties
- Keep configuration minimal and explicit
- **PHPStan level MAX** - strictest type checking
- **Type Safety**: Strict types everywhere

```php
<?php

declare(strict_types=1);

namespace Light\Module;

use function assert;
use function is_array;

class Example
{
    public function __construct(
        private readonly ServiceInterface $service
    ) {
    }
}
```

### Quality Gates

#### Before Commit
```bash
composer check          # Run all quality checks
pnpm run build          # Ensure assets compile
```

#### CI/CD Pipeline
1. **Static Analysis**: PHPStan level MAX
2. **Code Style**: PHP CodeSniffer
3. **Tests**: PHPUnit with coverage
4. **Asset Build**: Vite compilation
5. **Security**: Dependency scanning

This protocol ensures consistent, high-quality development across the entire application.

---

## Security Policy

### Supported Versions

| Version | Supported          |
|---------| ------------------ |
| 1.x     | :white_check_mark: |

### Reporting Potential Security Issues

If you have encountered a potential security vulnerability in this project,
please report it to us at <security@dotkernel.com>. We will work with you to
verify the vulnerability and patch it.

When reporting issues, please provide the following information:

- Component(s) affected
- A description indicating how to reproduce the issue
- A summary of the security vulnerability and impact

We request that you contact us via the email address above and give the
project contributors a chance to resolve the vulnerability and issue a new
release prior to any public exposure; this helps protect the project's
users, and provides them with a chance to upgrade and/or update in order to
protect their applications.

### Policy

If we verify a reported security vulnerability, our policy is:

- We will patch the current release branch, as well as the immediate prior minor
  release branch.

- After patching the release branches, we will immediately issue new security
  fix releases for each patched release branch.

---

## Slovak Documentation

Slovenská dokumentácia je dostupná v adresári `sk/`:

- [Dark Theme Implementation](sk/DARK_THEME.md) - Implementácia dark theme funkcionality
- [Paths Integration](sk/PATHS_INTEGRATION.md) - Integrácia responsive-sk/slim4-paths v6.0
- [Shared Hosting Compatibility](sk/SHARED_HOSTING_COMPATIBILITY.md) - Kompatibilita so shared hostingom
- [Var Directory Migration](sk/VAR_DIRECTORY_MIGRATION.md) - Migrácia na var/ directory štruktúru

---

## Changelog

Pre úplný zoznam zmien a verzií pozrite [CHANGELOG.md](CHANGELOG.md).
