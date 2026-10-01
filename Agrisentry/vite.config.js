import { defineConfig } from 'vite';

export default defineConfig({
    publicDir: false,
    build: {
        outDir: 'public/firebase',
        lib: { entry: 'src/firebase-live.js', formats: ['es'], fileName: () => 'firebase-live.js' },
    },
});
