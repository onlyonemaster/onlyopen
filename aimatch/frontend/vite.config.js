import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 3001,
    allowedHosts: ['open.kiam.kr', 'localhost', '127.0.0.1', '172.233.72.208']
  }
})
