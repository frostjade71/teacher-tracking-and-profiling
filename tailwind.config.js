/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./app/**/*.php", "./public/**/*.js", "./public/**/*.php"],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        slate: {
          850: '#151e2e',
          950: '#020617',
        }
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
