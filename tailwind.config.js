/** @type {import('tailwindcss').Config} */
const colors = require('tailwindcss/colors')

module.exports = {
  content: ["./**/*.{njk,md}", "./.eleventy.js", "!./node-modules/"],
  theme: {
    colors: {
      'phthalo-green': '#123524',
      'hunter-green': '#2e5233ff',
      'dark-olive-green': '#556b2fff',
      'dark-olive-green-2': '#56721dff',
      'moss-green': '#74954bff',
      'olive-green': '#b3b256ff',
      'vegas-gold': '#caba68ff',
      'metallic-gold': '#cfaf3cff',
      'straw': '#e4d96fff',
      'lemon-yellow': '#ffff9f',
      'bittersweet-shimmer': '#bc4749ff',
      transparent: 'transparent',
      current: 'currentColor',
      black: colors.black,
      white: colors.white,
      gray: colors.gray,
    },
    container: {
      center: true,
      screens: {
        sm: '640px',
        md: '768px',
      },
    },
  },
  plugins: [],
}
