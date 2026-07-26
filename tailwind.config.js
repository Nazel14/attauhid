import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Oranye lembut khas Claude (terracotta)
                claude: {
                    50: '#FBF5F1',
                    100: '#F6E9E1',
                    200: '#EDD3C4',
                    300: '#E5B49D',
                    400: '#DF9678',
                    500: '#D97757',
                    600: '#C4613F',
                    700: '#A34F33',
                    800: '#82402A',
                    900: '#6A3523',
                },
                // Putih hangat / ivory khas Claude (light mode)
                cream: {
                    50: '#FAF9F5',
                    100: '#F5F4EF',
                    200: '#EDEBE4',
                    300: '#E3E0D5',
                    400: '#D1CDBF',
                },
                // Gelap hangat khas Claude (dark mode) — charcoal kecokelatan, bukan biru
                coal: {
                    900: '#1A1918',
                    800: '#262624', // latar halaman
                    700: '#30302E', // kartu & sidebar
                    600: '#3E3D3A', // border
                    500: '#4D4B47', // hover / aksen halus
                },

                // Alias untuk modul keuangan. JSX bawaan edufinance memakai
                // `primary` (biru #2563eb); dipetakan ke palet claude supaya
                // seluruh aplikasi tetap satu nuansa terracotta tanpa perlu
                // menulis ulang kelas CSS di puluhan halaman.
                primary: {
                    DEFAULT: '#D97757',
                    light:   '#DF9678',
                    dark:    '#C4613F',
                    50:      '#FBF5F1',
                },
                secondary: {
                    DEFAULT: '#10b981',
                    light:   '#34d399',
                    dark:    '#059669',
                    50:      '#ecfdf5',
                },
            },
            boxShadow: {
                'soft':  '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                'glow':  '0 0 15px rgba(217, 119, 87, 0.4)',
                'float': '0 10px 30px -5px rgba(0, 0, 0, 0.08)',
            },
        },
    },

    plugins: [],
};