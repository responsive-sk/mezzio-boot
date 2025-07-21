# Theme System

Mezzio Boot includes a modern theme system with Bootstrap 5.3 and TailwindCSS + Alpine.js themes, built with Vite for optimal performance.

## Overview

The theme system provides:

- **Bootstrap 5.3 Theme**: Complete component library with responsive design
- **TailwindCSS + Alpine.js Theme**: Utility-first CSS with reactive JavaScript
- **Vite Build System**: Modern asset compilation with hot reload
- **Production Optimization**: Minified, versioned assets with cache busting
- **Template Integration**: Seamless integration with NativePhpRenderer

## Theme Structure

```
src/Templates/
├── bootstrap/              # Bootstrap 5.3 theme
│   ├── src/
│   │   ├── main.js        # JavaScript entry point
│   │   └── main.scss      # Sass entry point
│   ├── package.json       # Dependencies and scripts
│   ├── vite.config.js     # Vite configuration
│   └── node_modules/      # Dependencies (gitignored)
├── main/                  # TailwindCSS + Alpine.js theme
│   ├── src/
│   │   ├── main.js        # JavaScript entry point
│   │   └── main.css       # CSS entry point
│   ├── package.json       # Dependencies and scripts
│   ├── vite.config.js     # Vite configuration
│   └── node_modules/      # Dependencies (gitignored)
└── app/                   # Application templates
    ├── index.phtml        # Homepage
    ├── bootstrap-demo.phtml
    └── main-demo.phtml
```

## Building Themes

### Prerequisites

- Node.js 18+ and pnpm
- PHP 8.2+ for development server

### Build Commands

```bash
# Install dependencies for Bootstrap theme
cd src/Templates/bootstrap
pnpm install

# Build Bootstrap theme for production
pnpm run build

# Development mode with hot reload
pnpm run dev

# Install dependencies for TailwindCSS theme
cd src/Templates/main
pnpm install

# Build TailwindCSS theme for production
pnpm run build

# Development mode with hot reload
pnpm run dev
```

### Build Output

Compiled assets are output to:

```
public/themes/
├── bootstrap/
│   └── assets/
│       ├── main-[hash].css    # Compiled CSS
│       ├── main-[hash].js     # Compiled JavaScript
│       └── .vite/
│           └── manifest.json  # Asset manifest
└── main/
    └── assets/
        ├── main-[hash].css    # Compiled CSS
        ├── main-[hash].js     # Compiled JavaScript
        └── .vite/
            └── manifest.json  # Asset manifest
```

## Theme Configuration

### Vite Configuration

Each theme has its own `vite.config.js`:

```javascript
// src/Templates/bootstrap/vite.config.js
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  build: {
    outDir: path.resolve(__dirname, '../../../public/themes/bootstrap'),
    rollupOptions: {
      input: './src/main.js',
    },
    manifest: true,
  },
});
```

### Package Configuration

Theme dependencies in `package.json`:

```json
{
  "name": "bootstrap-theme",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "clean": "rm -rf ../../../public/themes/bootstrap"
  },
  "dependencies": {
    "bootstrap": "^5.3.0",
    "@popperjs/core": "^2.11.8"
  },
  "devDependencies": {
    "vite": "^5.0.0",
    "sass": "^1.69.0"
  }
}
```

## Using Themes in Templates

### Asset Loading

Themes are loaded in handlers using manifest-based URLs:

```php
// src/App/Handler/BootstrapDemoHandler.php
class BootstrapDemoHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Vite compiled assets with versioning
        $cssUrl = '/themes/bootstrap/assets/main-D30XL3Ms.css';
        $jsUrl = '/themes/bootstrap/assets/main-Df2FmC7f.js';

        return new HtmlResponse($this->template->render('app::bootstrap-demo', [
            'cssUrl' => $cssUrl,
            'jsUrl' => $jsUrl,
        ]));
    }
}
```

### Template Integration

Templates use the default layout with theme-specific assets:

```php
<?php 
// src/Templates/app/bootstrap-demo.phtml
$layout('layout::default', [
    'title' => 'Bootstrap Theme Demo - Mezzio Light',
    'description' => 'Bootstrap 5.3 theme demonstration',
    'body_class' => 'bootstrap-demo'
]);
?>

<div class="page-intro">
    <div class="container">
        <h1 class="display-4">Bootstrap 5.3 Theme</h1>
        <p class="lead">Modern responsive components and utilities</p>
    </div>
</div>
```

## Theme Development

### Bootstrap Theme

Features:
- Bootstrap 5.3 components
- Sass customization
- Responsive grid system
- JavaScript plugins

Development:
```bash
cd src/Templates/bootstrap
pnpm run dev  # Start development server
# Edit src/main.scss and src/main.js
# Changes auto-reload in browser
```

### TailwindCSS Theme

Features:
- Utility-first CSS
- Alpine.js reactivity
- Custom component classes
- JIT compilation

Development:
```bash
cd src/Templates/main
pnpm run dev  # Start development server
# Edit src/main.css and src/main.js
# Tailwind JIT compiles on demand
```

## Performance

### Asset Sizes

**Bootstrap Theme:**
- CSS: 231KB raw → 31KB gzipped
- JavaScript: 80KB raw → 24KB gzipped

**TailwindCSS Theme:**
- CSS: 17KB raw → 3.5KB gzipped
- JavaScript: 45KB raw → 16KB gzipped

### Optimization Features

- **Tree Shaking**: Unused code eliminated
- **Minification**: CSS and JS compressed
- **Code Splitting**: Separate vendor bundles
- **Cache Busting**: Hash-based filenames
- **Gzip Compression**: Server-level compression

## Demo Pages

### Bootstrap Demo (`/bootstrap-demo`)

Showcases:
- Grid system and layout
- Typography and components
- Forms and buttons
- Navigation and cards
- Responsive utilities

### TailwindCSS Demo (`/main-demo`)

Showcases:
- Utility classes
- Custom components
- Alpine.js reactivity
- Responsive design
- Dark mode support

## Production Deployment

### Build Process

```bash
# Build all themes for production
cd src/Templates/bootstrap && pnpm run build
cd src/Templates/main && pnpm run build

# Assets are output to public/themes/ with versioned filenames
```

### Asset Manifest

Vite generates manifest files for asset resolution:

```json
{
  "src/main.js": {
    "file": "assets/main-D30XL3Ms.js",
    "src": "src/main.js",
    "isEntry": true,
    "css": ["assets/main-BVkJOiu1.css"]
  }
}
```

### Server Configuration

Ensure proper MIME types and caching:

```apache
# .htaccess
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
</IfModule>

<IfModule mod_headers.c>
    <FilesMatch "\.(css|js)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
</IfModule>
```

## Customization

### Adding New Themes

1. Create theme directory in `src/Templates/`
2. Add `package.json` with dependencies
3. Configure `vite.config.js` for build output
4. Create source files (`src/main.js`, `src/main.css`)
5. Add demo handler and template
6. Update navigation menu

### Extending Existing Themes

Bootstrap customization:
```scss
// src/Templates/bootstrap/src/main.scss
$primary: #your-color;
$font-family-base: 'Your Font';

@import 'bootstrap/scss/bootstrap';

// Custom styles
.custom-component {
    // Your styles
}
```

TailwindCSS customization:
```javascript
// src/Templates/main/tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        'brand': '#your-color',
      },
      fontFamily: {
        'custom': ['Your Font', 'sans-serif'],
      },
    },
  },
};
```

This theme system provides a solid foundation for modern web application styling with excellent performance and developer experience.
