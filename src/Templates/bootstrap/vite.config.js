import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig(({ mode }) => ({
  root: path.resolve(__dirname, 'src'),
  build: {
    outDir: path.resolve(__dirname, '../../../public/themes/bootstrap'),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: path.resolve(__dirname, 'src', 'main.js'),
      },
      output: {
        entryFileNames: mode === 'production'
          ? 'assets/[name]-[hash].js'
          : 'assets/[name].js',
        chunkFileNames: mode === 'production'
          ? 'assets/[name]-[hash].js'
          : 'assets/[name].js',
        assetFileNames: mode === 'production'
          ? 'assets/[name]-[hash].[ext]'
          : 'assets/[name].[ext]'
      }
    }
  },
  server: {
    port: 3001
  }
}));
