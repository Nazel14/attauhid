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
                // =====================================================
                // PALET TK ATTAUHID
                //
                // Nama token `claude`, `cream`, dan `coal` SENGAJA
                // DIPERTAHANKAN meskipun isinya sudah bukan warna Claude.
                // Ketiganya dipakai di puluhan berkas JSX; mengganti
                // namanya berarti menyunting semuanya sekaligus, dan satu
                // kelas yang terlewat akan hilang warnanya TANPA PESAN
                // ERROR — Tailwind diam saja untuk kelas yang tidak
                // dikenalnya. Jadi yang diganti nilainya, bukan namanya.
                //
                // Warna dasar diambil dari berkas logo asli di Profil TK
                // Attauhid, bukan ditebak dari gambar kecil:
                //   hijau #7EB842 · biru #36A7DF · oranye #FA902C
                // =====================================================

                // Hijau At-Tauhid — warna utama (dulu terracotta Claude)
                claude: {
                    50:  '#F4F9EC',
                    100: '#E6F2D5',
                    200: '#CEE6AF',
                    300: '#B0D782',
                    400: '#96C85D',
                    500: '#7EB842', // hijau logo
                    600: '#649634',
                    700: '#4D752A',
                    800: '#3D5C24',
                    900: '#334B20',
                },

                // Netral terang (light mode). Sebelumnya ivory kecokelatan;
                // sekarang condong hijau tipis supaya menyatu dengan logo.
                // cream-100 sengaja dibuat PERSIS SAMA dengan latar <body>
                // di resources/views/app.blade.php (#F6F7F2) — kalau beda,
                // akan terlihat kedipan warna sepersekian detik sebelum
                // React selesai memasang halaman.
                cream: {
                    50:  '#FAFBF6',
                    100: '#F6F7F2',
                    200: '#EDEFE6',
                    300: '#E1E4D8',
                    400: '#CDD1C2',
                },

                // Netral gelap (dark mode) — charcoal kehijauan, bukan biru.
                // coal-800 juga disamakan dengan latar <body> versi gelap.
                coal: {
                    900: '#1B1D19',
                    800: '#282C25', // latar halaman
                    700: '#323730', // kartu & sidebar
                    600: '#3F453C', // border
                    500: '#4E5549', // hover / aksen halus
                },

                // Dua warna logo selain hijau. Token BARU, belum dipakai
                // berkas mana pun — tersedia untuk aksen berikutnya tanpa
                // perlu menulis hex mentah di JSX lagi.
                tauhid: {
                    hijau:  '#7EB842',
                    biru:   '#36A7DF',
                    oranye: '#FA902C',
                },

                // Alias untuk modul keuangan. JSX bawaan edufinance memakai
                // `primary` (biru #2563eb); dipetakan ke palet claude supaya
                // seluruh aplikasi tetap satu nuansa tanpa perlu menulis
                // ulang kelas CSS di puluhan halaman.
                primary: {
                    DEFAULT: '#7EB842',
                    light:   '#96C85D',
                    dark:    '#649634',
                    50:      '#F4F9EC',
                },

                // SENGAJA DIBIARKAN HIJAU EMERALD, belum diganti.
                // Di edufinance `secondary` biasanya menandai status
                // berhasil/lunas, bukan sekadar warna kedua. Kalau ternyata
                // hanya dipakai sebagai aksen, ganti ke biru logo #36A7DF
                // supaya tidak bertabrakan dengan `primary` yang kini juga
                // hijau. Periksa dulu pemakaiannya:
                //     git --no-pager grep -n "secondary" -- resources/js
                secondary: {
                    DEFAULT: '#10b981',
                    light:   '#34d399',
                    dark:    '#059669',
                    50:      '#ecfdf5',
                },
            },
            boxShadow: {
                'soft':  '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                // rgba di bawah adalah #7EB842. Bayangan tidak bisa memakai
                // nama token, jadi angkanya harus ikut diubah manual setiap
                // kali hijau utamanya berubah.
                'glow':  '0 0 15px rgba(126, 184, 66, 0.4)',
                'float': '0 10px 30px -5px rgba(0, 0, 0, 0.08)',
            },
        },
    },

    plugins: [],
};