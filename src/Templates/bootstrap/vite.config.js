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
        entryFileNames: 'assets/[name].js',
        chunkFileNames: 'assets/[name].js',
        assetFileNames: 'assets/[name].[ext]'
      }
    }
  },
  server: {
    port: 3001
  }
}));
