/**
 * UNIFIED INPUT JAVASCRIPT — v3.8
 *
 * PERUBAHAN v3.8:
 * - Toast & kotak error memakai desain kartu yang sama dengan InputSetoran.jsx
 *   (kartu putih/coal, ring, tile ikon berwarna). Tanpa emoji.
 * - loadPresensiHariIni() DIHAPUS dari file ini. Versi yang dipakai ada di
 *   unified-input-page.js (dimuat sesudahnya dan menimpa deklarasi di sini).
 *   Versi lama di sini hanya kenal 2 kolom tetap, jadi kalau sampai jalan ia
 *   menghapus kolom Metode & Progress dari header tabel.
 * - Validasi Presensi Only: stopImmediatePropagation() saat gagal, supaya
 *   listener CSRF di unified-input-page.js tidak tetap memanggil form.submit().
 *
 * CATATAN TAILWIND: file di public/js tidak dipindai Tailwind. Semua class di
 * bawah sengaja disalin dari InputSetoran.jsx supaya pasti ter-generate.
 *
 * - formCombined & formSetoranOnly tetap ditangani setoran-interactive.js
 * - switchMode() ada di unified-input-page.js
 */

console.log('Unified Input JS v3.8 loading...');

// =====================================================
// GLOBAL STATE
// =====================================================
let currentMode = 'combined';
let quranLoaded = {
    combined: false,
    setoran_only: false
};

// =====================================================
// UI HELPERS — kartu notifikasi (sama dengan toast di InputSetoran.jsx)
// =====================================================
const UI_TONE = {
    success: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    error:   'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400',
    warning: 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400',
    info:    'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
};
const UI_ICON = {
    success: '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    error:   '<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    warning: '<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    info:    '<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
};
const UI_X = '<path d="M6 18L18 6M6 6l12 12"/>';

function uiSvg(d, cls) {
    return `<svg class="${cls}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;
}

function uiEsc(s) {
    return String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

// Buang emoji di awal pesan — script lain masih memanggil showToast('⚠️ ...').
function uiStripEmoji(msg) {
    return String(msg ?? '').replace(/^[\p{Extended_Pictographic}️‍\s]+/u, '');
}

function buildNoticeCard(type, bodyHtml) {
    const t = UI_TONE[type] ? type : 'info';
    const wrap = document.createElement('div');
    wrap.className = 'fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in';
    wrap.innerHTML = `
        <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl shadow-xl px-3.5 py-3 flex items-start gap-3">
            <span class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${UI_TONE[t]}">${uiSvg(UI_ICON[t], 'w-4 h-4')}</span>
            <div class="flex-1 min-w-0 pt-1.5 text-sm text-stone-800 dark:text-stone-100">${bodyHtml}</div>
            <button type="button" class="flex-shrink-0 mt-1 w-6 h-6 rounded-lg flex items-center justify-center text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 hover:bg-cream-200 dark:hover:bg-coal-600 transition">${uiSvg(UI_X, 'w-3.5 h-3.5')}</button>
        </div>`;
    const remove = () => {
        wrap.style.transition = 'opacity .3s';
        wrap.style.opacity = '0';
        setTimeout(() => wrap.remove(), 300);
    };
    wrap.querySelector('button').addEventListener('click', remove, { once: true });
    wrap._remove = remove;
    return wrap;
}

// =====================================================
// SIMPLIFIED DEPENDENCY CHECK - ONLY CHECK CRITICAL
// =====================================================
let dependenciesReady = false;
let checkAttempts = 0;
const MAX_ATTEMPTS = 40; // 20 detik

function checkDependencies() {
    checkAttempts++;

    const critical = ['loadQuran', 'countTotalWords', 'markWord'];
    const missing = critical.filter(fn => typeof window[fn] !== 'function');

    if (missing.length > 0) {
        if (checkAttempts >= MAX_ATTEMPTS) {
            console.error('Failed to load dependencies after 20 seconds. Missing:', missing);

            const kbd = 'style="padding:1px 5px;border-radius:5px;font-size:11px;font-family:ui-monospace,monospace;background:rgba(120,113,108,.15);"';
            document.body.appendChild(buildNoticeCard('error', `
                <p class="font-semibold">Gagal memuat komponen sistem</p>
                <ol class="text-xs space-y-0.5 text-stone-600 dark:text-stone-300" style="margin-top:4px;padding-left:16px;list-style:decimal;">
                    <li>Hard refresh: tekan <kbd ${kbd}>Ctrl+Shift+R</kbd></li>
                    <li>Atau hapus cache browser</li>
                    <li>Muat ulang halaman</li>
                </ol>`));
            return false;
        }

        if (checkAttempts % 5 === 0) {
            console.warn(`Attempt ${checkAttempts}/${MAX_ATTEMPTS}: still waiting...`, missing);
        }

        setTimeout(checkDependencies, 500);
        return false;
    }

    dependenciesReady = true;
    console.log(`All dependencies loaded (took ${checkAttempts * 0.5}s)`);
    return true;
}

checkDependencies();

// =====================================================
// PRESENSI ONLY FORM VALIDATION - STANDALONE
// formCombined & formSetoranOnly dihandle setoran-interactive.js
// =====================================================
function setupPresensiOnlyValidation() {
    const formPresensiOnly = document.getElementById('formPresensiOnly');
    if (!formPresensiOnly) {
        console.warn('formPresensiOnly not found');
        return;
    }

    if (formPresensiOnly._presensiValidationAttached) return;
    formPresensiOnly._presensiValidationAttached = true;

    formPresensiOnly.addEventListener('submit', function (e) {
        // Lewat lagi saat listener CSRF memanggil submit ulang → tidak perlu validasi ulang
        if (this.dataset.csrfReady === '1') return;

        const santriId = formPresensiOnly.querySelector('[name="santri_id"]')?.value;
        const tanggal  = formPresensiOnly.querySelector('[name="tanggal"]')?.value;
        const sesi     = formPresensiOnly.querySelector('[name="sesi"]')?.value;
        const status   = formPresensiOnly.querySelector('[name="status"]:checked');

        const gagal = (msg) => {
            e.preventDefault();
            // PENTING: hentikan listener CSRF di unified-input-page.js. Tanpa ini
            // listener itu tetap jalan dan memanggil form.submit() → data kosong terkirim.
            e.stopImmediatePropagation();
            showToast(msg, 'error');
        };

        if (!santriId) return gagal('Pilih santri terlebih dahulu.');
        if (!tanggal)  return gagal('Pilih tanggal terlebih dahulu.');
        if (!sesi)     return gagal('Pilih sesi terlebih dahulu.');
        if (!status)   return gagal('Pilih status kehadiran.');

        const submitBtn = formPresensiOnly.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = 'Menyimpan…';
            submitBtn.disabled = true;
        }
        // Lanjut ke listener CSRF (unified-input-page.js) yang melakukan submit.
    });
}

// =====================================================
// TOAST NOTIFICATION
// =====================================================
function showToast(message, type = 'info') {
    const card = buildNoticeCard(type, `<p class="font-medium">${uiEsc(uiStripEmoji(message))}</p>`);
    document.body.appendChild(card);
    setTimeout(() => { if (card.isConnected) card._remove(); }, 3500);
}

// =====================================================
// EXPORT TO WINDOW SCOPE
// =====================================================
window.showToast = showToast;

// =====================================================
// DOM READY INITIALIZATION
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    setupPresensiOnlyValidation();

    // loadPresensiHariIni() didefinisikan di unified-input-page.js. Dipanggil
    // lewat pembungkus supaya selalu memakai versi terbaru di window.
    const refresh = () => {
        if (typeof window.loadPresensiHariIni === 'function') window.loadPresensiHariIni();
    };

    refresh();
    setInterval(refresh, 30000);

    document.querySelectorAll('[name="tanggal"]').forEach(input => {
        input.addEventListener('change', refresh);
    });

    console.log('Unified Input v3.8 initialization complete');
});

console.log('Unified Input Script v3.8 LOADED');