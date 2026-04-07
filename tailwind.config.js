/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans:    ['DM Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                display: ['Syne', 'ui-sans-serif', 'sans-serif'],
                mono:    ['JetBrains Mono', 'ui-monospace', 'monospace'],
            },
            colors: {
                brand: {
                    50:  '#EFF6FF',
                    100: '#DBEAFE',
                    200: '#BFDBFE',
                    400: '#60A5FA',
                    600: '#2563EB',
                    700: '#1D4ED8',
                    800: '#1E40AF',
                    900: '#1E3A8A',
                },
            },
        },
    },
    plugins: [],
};
