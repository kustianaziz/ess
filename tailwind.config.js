import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#1657FF',
                    dark: '#0F3FCC',
                    soft: '#EAF0FF',
                },
                success: '#22B14C',
                danger: '#E0333D',
                warning: {
                    bg: '#FDEDEF',
                },
                badge: '#FF4757',
                page: '#F5F6F8',
                card: '#FFFFFF',
                text: {
                    primary: '#111318',
                    secondary: '#8A8F98',
                    tertiary: '#B0B4BB',
                },
                border: {
                    subtle: '#ECEDF0',
                },
                icon: {
                    yellow: '#FFF3D6',
                    purple: '#F1E8FF',
                    orange: '#FFE9D6',
                    red: '#FFE1E1',
                    blue: '#DCE9FF',
                    green: '#DFF7E6',
                    pink: '#FFE1EC',
                    teal: '#D9F5F0',
                }
            },
            fontSize: {
                display: ['28px', '1.2'],
                h1: ['20px', '1.2'],
                h2: ['16px', '1.4'],
                body: ['14px', '1.4'],
                caption: ['13px', '1.4'],
                small: ['12px', '1.4'],
            },
            fontWeight: {
                bold: '700',
                semibold: '600',
                regular: '400',
            },
            spacing: {
                'page-x': '12px',
                'section-gap': '8px',
                'card-padding': '12px',
                xs: '4px',
                sm: '6px',
                md: '8px',
                lg: '12px',
                xl: '16px',
            },
            borderRadius: {
                card: '12px',
                button: '8px',
                'icon-box': '10px',
                pill: '999px',
            },
            boxShadow: {
                card: '0 1px 4px rgba(17, 19, 24, 0.05)',
            }
        },
    },

    plugins: [forms],
};
