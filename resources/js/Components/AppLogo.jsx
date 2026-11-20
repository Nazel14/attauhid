/**
 * Logo TK Attauhid — atap + dua sisi kubus, tanpa lingkaran pembungkus.
 *
 * SVG-nya ditulis langsung di sini, BUKAN memuat /logo.svg lewat <img>.
 * Alasannya: komponen ini dipakai di sidebar, halaman login, dan layar
 * pemuatan — kalau berupa <img>, ketiganya menunggu satu permintaan HTTP
 * tambahan dan logonya berkedip kosong sepersekian detik setiap kali.
 *
 * Konsekuensinya: KALAU /public/logo.svg DIUBAH, BERKAS INI HARUS DIUBAH
 * JUGA. Keduanya tidak saling membaca. Ini yang kemarin bikin favicon
 * sudah berganti tapi logo sidebar masih yang lama.
 *
 * viewBox 420x420 sengaja persegi meskipun gambarnya lebih tinggi daripada
 * lebar, supaya sama persis dengan /public/logo.svg dan proporsinya aman di
 * kotak sekecil apa pun. Gambarnya digeser ke tengah lewat <g transform>;
 * koordinat jalurnya tidak diubah sama sekali.
 *
 * Warna diambil dari berkas logo asli di Profil TK Attauhid, bukan ditebak:
 *   hijau #7EB842 · biru #36A7DF · oranye #FA902C
 *
 * Warnanya ditulis sebagai hex, bukan currentColor, karena logo ini memang
 * tiga warna — mewarisi warna teks induknya justru akan meratakan ketiganya
 * jadi satu blok yang tidak terbaca.
 *
 * aria-hidden="true" dipertahankan dari versi sebelumnya dan memang benar:
 * di sidebar maupun di halaman login, nama sekolah selalu tertulis sebagai
 * teks tepat di sebelah logo ini. Memberi logo label lagi membuat pembaca
 * layar menyebut "TK Attauhid" dua kali berturut-turut.
 *
 * Tidak ada <linearGradient> lagi, jadi useId() tidak diperlukan — id unik
 * tadi memang wajib untuk gradien (dua logo di satu halaman akan saling
 * menimpa definisinya), tapi logo ini warnanya rata.
 */
export default function AppLogo({ className = 'h-9 w-9', ...props }) {
    return (
        <svg
            viewBox="0 0 420 420"
            className={className}
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
            {...props}
        >
            <g transform="translate(31 9)">
                {/* Sisi atas. Lingkaran di tengahnya LUBANG sungguhan
                    (fill-rule evenodd), bukan bentuk putih yang ditumpuk —
                    jadi logo tetap benar di atas latar warna apa pun,
                    termasuk kotak abu-abu di sidebar dan mode gelap. */}
                <path
                    fill="#7EB842"
                    fillRule="evenodd"
                    stroke="#7EB842"
                    strokeWidth="9"
                    strokeLinejoin="round"
                    d="M179 0 357.5 103 358 147 299 131 179 197 59 131 0 147 0.5 103 Z
                       M179 59.5 A46.5 46.5 0 1 0 179 152.5 A46.5 46.5 0 1 0 179 59.5 Z"
                />

                {/* Sisi kiri */}
                <path
                    fill="#36A7DF"
                    stroke="#36A7DF"
                    strokeWidth="9"
                    strokeLinejoin="round"
                    d="M0.5 186 61 153 168.5 216 168.5 402 2.5 303 Z"
                />

                {/* Sisi kanan */}
                <path
                    fill="#FA902C"
                    stroke="#FA902C"
                    strokeWidth="9"
                    strokeLinejoin="round"
                    d="M357.5 186 297 153 189.5 216 189.5 402 355.5 303 Z"
                />
            </g>
        </svg>
    );
}