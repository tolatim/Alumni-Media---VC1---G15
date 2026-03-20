/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: {
          700: '#1e40af',   // dark blue sidebar
          800: '#1e3a8a',
          900: '#172554',
        },
        accent: {
          green: '#10b981',
          red:   '#ef4444',
          blue:  '#3b82f6',
        }
      }
    },
  },
  plugins: [],
}