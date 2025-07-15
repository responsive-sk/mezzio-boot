// Main theme entry point with TailwindCSS + Alpine.js
import Alpine from 'alpinejs';
import './style.css';

// Import images for Vite processing
import hdmBootImg from './images/php82.jpg';
import slim4Img from './images/javascript.jpg';
import ephemerisImg from './images/digital-marketing.jpg';
import logoSvg from './images/nav/logo.svg';

// Make images available globally for dynamic use
window.themeAssets = {
    images: {
        hdmBoot: hdmBootImg,
        slim4: slim4Img,
        ephemeris: ephemerisImg,
        logo: logoSvg
    }
};

// Initialize Alpine.js
window.Alpine = Alpine;
Alpine.start();

console.log('Main theme (TailwindCSS + Alpine.js) loaded');
console.log('Theme assets loaded:', window.themeAssets);
