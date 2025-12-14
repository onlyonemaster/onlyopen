/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#295CFF',
        'primary-hover': '#1947d9',
      },
    },
  },
  plugins: [],
}
