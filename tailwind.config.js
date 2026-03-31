// tailwind.config.js
module.exports = {
  content: [
    './resources/js/**/*.{js,jsx,ts,tsx}',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        border: 'var(--border)',
        ring: 'var(--ring)',
      },
    },
  },
  plugins: [
    require('tailwindcss-animate'),
    function({ addUtilities }) {
      addUtilities({
        // definește clasa custom outline-ring
        '.outline-ring': {
          outline: '2px solid var(--ring)',
        },
        
      });
    },
  ],
};