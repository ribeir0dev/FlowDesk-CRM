module.exports = {
  content: [
    "./public/**/*.php",
    "./app/views/**/*.php",
    "./resources/js/**/*.js"
  ],
  darkMode: ['class', '[data-theme="dark"]'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: "#f5f3ff",
          100: "#ede9fe",
          200: "#ddd6fe",
          300: "#c4b5fd",
          400: "#a78bfa",
          500: "#8b5cf6",
          600: "#7c3aed",
          700: "#6d28d9",
          800: "#5b21b6",
          900: "#4c1d95"
        }
      },
      boxShadow: {
        soft: "0 10px 30px rgba(2, 6, 23, 0.08)",
        card: "0 8px 24px rgba(15, 23, 42, 0.10)"
      },
      borderRadius: {
        xl2: "1.25rem"
      }
    }
  },
  plugins: []
};