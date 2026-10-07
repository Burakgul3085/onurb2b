import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: {
                    DEFAULT: '#1c2834',
                    soft: '#2a3b4d',
                    muted: '#5e6d7c',
                },
                paper: '#f4f0e7',
                brass: {
                    DEFAULT: '#9a5b2f',
                    light: '#c4844a',
                },
            },
        },
    },

    plugins: [forms],
};
