<?php

declare(strict_types=1);

namespace ResponsiveSk\PhpDebugBarMiddleware;

/**
 * Custom styles for DebugBar with responsive.sk branding
 */
class CustomDebugBarStyles
{
    /**
     * Get custom CSS to override DebugBar logo with responsive.sk logo
     */
    public static function getCustomCss(): string
    {
        // SVGO optimized responsive.sk logo (optimized for small size)
        $logoSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48">' .
                   '<path d="m6.958 42.353 9.78-5.647 9.782 5.647L16.739 48Z" fill="%23faa"/>' .
                   '<path d="M6.958 42.353v-28.19l9.78 5.602V48Z" fill="%23d40000"/>' .
                   '<path d="M6.958 14.162 31.41 0l9.781 5.647L16.74 19.765Z" fill="%23f55"/>' .
                   '<path d="M41.191 5.647v11.294l-14.671 8.47v16.942L16.739 48V19.765Z" fill="%23a00"/>' .
                   '</svg>';

        // URL encode the SVG
        $encodedLogo = rawurlencode($logoSvg);

        return <<<CSS
/* Custom responsive.sk branding for DebugBar */
a.phpdebugbar-restore-btn:after {
    background: var(--debugbar-header) url("data:image/svg+xml,{$encodedLogo}") no-repeat center / 16px 16px !important;
}

/* Optional: Add subtle branding to DebugBar header */
div.phpdebugbar-header {
    background: linear-gradient(135deg, var(--debugbar-header) 0%, var(--debugbar-header) 95%, rgba(255, 85, 85, 0.1) 100%) !important;
}

/* Optional: Custom accent color for active tabs */
a.phpdebugbar-tab.phpdebugbar-active {
    border-bottom-color: #ff5555 !important;
}

/* Optional: Custom color for important badges */
a.phpdebugbar-tab span.phpdebugbar-badge.phpdebugbar-important {
    background: #d40000 !important;
}
CSS;
    }

    /**
     * Get minimal CSS with just logo replacement
     */
    public static function getMinimalCss(): string
    {
        // SVGO optimized responsive.sk logo with precise colors and PERFECT CENTERING
        $logoSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 48 48">' .
                   '<path d="m6.958 42.353 9.78-5.647 9.782 5.647L16.739 48Z" fill="#faa"/>' .
                   '<path d="M6.958 42.353v-28.19l9.78 5.602V48Z" fill="#d40000"/>' .
                   '<path d="M6.958 14.162 31.41 0l9.781 5.647L16.74 19.765Z" fill="#f55"/>' .
                   '<path d="M41.191 5.647v11.294l-14.671 8.47v16.942L16.739 48V19.765Z" fill="#a00"/>' .
                   '</svg>';

        $encodedLogo = rawurlencode($logoSvg);

        return <<<CSS
/* responsive.sk logo for DebugBar - MAXIMUM SIZE - PERFECTLY CENTERED */
a.phpdebugbar-restore-btn {
    width: 32px !important;
    height: 32px !important;
    background: transparent !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}
a.phpdebugbar-restore-btn:after {
    background: transparent url("data:image/svg+xml,{$encodedLogo}") no-repeat center center !important;
    background-size: 28px 28px !important;
    background-color: transparent !important;
    width: 28px !important;
    height: 28px !important;
    position: static !important;
    left: auto !important;
    top: auto !important;
}
CSS;
    }
}
