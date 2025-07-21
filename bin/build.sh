#!/bin/bash

# Mezzio Boot - Build Script Wrapper
# Compatible with slim4-paths v6.0

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Function to display usage
show_usage() {
    echo "Mezzio Boot Build Script"
    echo "Compatible with slim4-paths v6.0"
    echo ""
    echo "Usage: $0 [BUILD_TARGET]"
    echo ""
    echo "Build Targets:"
    echo "  production              - Full production build (default)"
    echo "  shared-hosting          - Shared hosting build"
    echo "  shared-hosting-minimal  - Minimal shared hosting build"
    echo ""
    echo "Examples:"
    echo "  $0                      # Production build"
    echo "  $0 production           # Production build"
    echo "  $0 shared-hosting       # Shared hosting build"
    echo "  $0 shared-hosting-minimal # Minimal shared hosting build"
    echo ""
    echo "Environment Variables:"
    echo "  BUILD_DIR=./build       # Build output directory"
    echo "  PACKAGE_NAME=dotkernel-light # Package name prefix"
    echo "  VERSION=auto            # Version (auto = timestamp)"
    echo "  BASE_URL=https://example.com # Base URL for sitemap and robots.txt"
}

# Function to check requirements
check_requirements() {
    echo -e "${BLUE}Checking requirements...${NC}"
    
    # Check PHP
    if ! command -v php &> /dev/null; then
        echo -e "${RED}Error: PHP is not installed or not in PATH${NC}"
        exit 1
    fi
    
    # Check PHP version
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    if ! php -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
        echo -e "${RED}Error: PHP 8.2+ required, found: ${PHP_VERSION}${NC}"
        exit 1
    fi
    
    # Check Composer
    if ! command -v composer &> /dev/null; then
        echo -e "${RED}Error: Composer is not installed or not in PATH${NC}"
        exit 1
    fi
    
    # Check if we're in project root
    if [ ! -f "composer.json" ]; then
        echo -e "${RED}Error: Must be run from project root directory${NC}"
        exit 1
    fi
    
    # Check if vendor exists
    if [ ! -d "vendor" ]; then
        echo -e "${YELLOW}Warning: vendor directory not found, running composer install...${NC}"
        composer install
    fi
    
    echo -e "${GREEN}Requirements check passed${NC}"
}

# Function to display build info
show_build_info() {
    local target="$1"
    
    echo ""
    echo "=== Build Information ==="
    echo "Target: $target"
    echo "PHP Version: $(php -r "echo PHP_VERSION;")"
    echo "Composer Version: $(composer --version --no-ansi | head -n1)"
    echo "Build Directory: ${BUILD_DIR:-./build}"
    echo "Package Name: ${PACKAGE_NAME:-dotkernel-light}"
    echo "Version: ${VERSION:-auto}"
    echo "Base URL: ${BASE_URL:-https://example.com}"
    echo "Compatible with: slim4-paths v6.0"
    echo "========================="
    echo ""
}

# Main execution
main() {
    local build_target="${1:-production}"
    
    # Show usage if help requested
    if [[ "$1" == "-h" || "$1" == "--help" ]]; then
        show_usage
        exit 0
    fi
    
    # Validate build target
    case "$build_target" in
        "production"|"shared-hosting"|"shared-hosting-minimal")
            ;;
        *)
            echo -e "${RED}Error: Invalid build target '$build_target'${NC}"
            echo ""
            show_usage
            exit 1
            ;;
    esac
    
    echo -e "${BLUE}Mezzio Boot Build Script${NC}"
    echo -e "${BLUE}Compatible with slim4-paths v6.0${NC}"
    echo ""
    
    check_requirements
    show_build_info "$build_target"
    
    # Run the secure PHP build script
    echo -e "${BLUE}Starting secure build process...${NC}"
    php bin/build-production-secure.php "$build_target"
    
    echo ""
    echo -e "${GREEN}Build completed successfully!${NC}"
}

# Run main function with all arguments
main "$@"
