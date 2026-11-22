import { useEffect } from 'react';
import { Head } from '@inertiajs/react';
import GuruAuthenticatedLayout from '@/Layouts/GuruAuthenticatedLayout';

/* ============================================================
 * URUTAN SCRIPT — SAMA PERSIS dengan urutan di Blade lama.
 * File terakhir `unified-input-page.js` = hasil pindahan
 * SEMUA <script> inline dari Blade (lihat instruksi di chat).
 * ============================================================ */
const ENGINE_SCRIPTS = [
    'complete-quran-mapping.js',
    'complete-quran-mapping-wrapper.js',
    'auto-fill-page.js',
    'page-juz-calculator.js',
    'mode-handler.js',
    'quran-api.js',
    'setoran-interactive.js',
    'iqro-data.js',
    'aisar-data.js',
    'iqro-handler.js',
    'aisar-handler.js',
    'audio-recorder.js',
    'unified-input.js',
];
const PAGE_SCRIPT = 'unified-input-page.js';

/* ---------- builder opsi <option> ---------- */
const optSurat = (list) => list.map((s) => `<option value="${s}">${s}</option>`).join('');
const optSesi = (list) => list.map((s) => `<option value="${s}">${s}</option>`).join('');
const optSantri = (list) =>
    list.map((s) =>
        `<option value="${s.id}" data-target-metode="${s.target_metode || 'quran'}" data-target-metode-2="${s.target_metode_2 || ''}" data-target-jilid="${s.target_jilid ?? ''}">${s.name}</option>`
    ).join('');
const optSantriPlain = (list) =>
    list.map((s) => `<option value="${s.id}">${s.name}</option>`).join('');

const formatTanggalID = (str) => {
    if (!str) return '';
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

/* ============================================================
 * DESIGN TOKEN — disamakan dengan Dashboard.jsx
 * (kartu ring-1 rounded-2xl, judul section dengan tile ikon,
 *  palet cream/coal/stone, ikon line monokrom, tanpa emoji)
 * ============================================================ */
const CARD = 'bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl';
const SUB = 'bg-cream-100 dark:bg-coal-600/40 ring-1 ring-cream-300 dark:ring-coal-600 rounded-xl';
const TILE = 'w-6 h-6 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-stone-500 dark:text-stone-400 flex items-center justify-center flex-shrink-0';
const LBL = 'block text-xs font-medium text-stone-600 dark:text-stone-400 mb-1.5';
const LBL_SM = 'block text-[11px] text-stone-500 dark:text-stone-400 mb-1';
const REQ = '<span class="text-red-500">*</span>';
const BTN_SOFT = 'inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 hover:bg-cream-300 dark:hover:bg-coal-500 text-stone-600 dark:text-stone-300 text-xs font-semibold transition active:scale-95';
const BTN_SUBMIT = 'w-full py-3 bg-claude-500 hover:bg-claude-600 text-white font-semibold text-sm rounded-xl transition active:scale-[.98] flex items-center justify-center gap-2';
const TONE = {
    claude: 'bg-claude-100 dark:bg-claude-500/15 text-claude-600 dark:text-claude-400',
    emerald: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-600 dark:text-emerald-400',
    stone: 'bg-cream-200 dark:bg-coal-600 text-stone-500 dark:text-stone-400',
    red: 'bg-red-100 dark:bg-red-500/15 text-red-600 dark:text-red-400',
    amber: 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400',
};

/* ---------- ikon (path stroke 24x24) ---------- */
const P = {
    target: '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/>',
    check: '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    book: '<path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
    bookClosed: '<path d="M4 19.5A2.5 2.5 0 016.5 17H20M4 19.5A2.5 2.5 0 006.5 22H20V2H6.5A2.5 2.5 0 004 4.5z"/>',
    clipboard: '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
    clock: '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    mic: '<path d="M19 11a7 7 0 01-14 0m7 7v3m-4 0h8m-4-6a3 3 0 01-3-3V5a3 3 0 016 0v4a3 3 0 01-3 3z"/>',
    pencil: '<path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828z"/>',
    save: '<path d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-8 0V4a1 1 0 011-1h6a1 1 0 011 1v3m-8 0h8M9 21v-6h6v6"/>',
    x: '<path d="M6 18L18 6M6 6l12 12"/>',
    alert: '<path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    warn: '<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>',
    info: '<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    lock: '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/>',
    cursor: '<path d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>',
    calendar: '<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    chart: '<path d="M3 3v18h18M9 17V9m4 8V5m4 12v-6"/>',
    doc: '<path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    trash: '<path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16m-3 0V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3"/>',
    back: '<path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>',
    swatch: '<path d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>',
    chevron: '<path d="M19 9l-7 7-7-7"/>',
};
const svg = (d, cls = 'w-3.5 h-3.5', sw = 1.8) =>
    `<svg class="${cls}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="${sw}" stroke-linecap="round" stroke-linejoin="round">${d}</svg>`;

/* ---------- potongan markup berulang ---------- */
const secHead = (icon, title, extra = '') => `
    <div class="flex items-center gap-2 mb-4">
        <span class="${TILE}">${svg(icon)}</span>
        <h3 class="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">${title}</h3>
        ${extra}
    </div>`;

const hint = (icon, text) => `
    <p class="mt-1.5 flex items-start gap-1 text-[11px] leading-snug text-stone-400 dark:text-stone-500">
        ${svg(icon, 'w-3 h-3 mt-px flex-shrink-0')}<span>${text}</span>
    </p>`;

const subTitle = (icon, text) =>
    `<p class="text-xs font-semibold text-stone-600 dark:text-stone-300 mb-2.5 flex items-center gap-1.5">${svg(icon, 'w-3.5 h-3.5 text-stone-400')} ${text}</p>`;

const toast = (cls, tone, icon, body) => `
    <div class="${cls} fixed top-3 right-3 left-3 sm:left-auto z-[300] sm:max-w-sm animate-fade-in">
        <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-2xl shadow-xl px-3.5 py-3 flex items-start gap-3">
            <span class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 ${TONE[tone]}">${svg(icon, 'w-4 h-4')}</span>
            <div class="flex-1 min-w-0 pt-1.5 text-sm text-stone-800 dark:text-stone-100">${body}</div>
            <button type="button" class="close-notification flex-shrink-0 mt-1 w-6 h-6 rounded-lg flex items-center justify-center text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 hover:bg-cream-200 dark:hover:bg-coal-600 transition">${svg(P.x, 'w-3.5 h-3.5', 2)}</button>
        </div>
    </div>`;

const modeCard = (value, tone, icon, label) => `
    <label class="mode-card cursor-pointer block">
        <input type="radio" name="input_mode" value="${value}" class="sr-only" onclick="toggleMode(this)" onchange="switchMode(this.value)">
        <div class="mode-card-inner h-full rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 bg-cream-100 dark:bg-coal-600/40 hover:ring-claude-400 p-2.5 sm:p-3 text-center transition">
            <span class="w-9 h-9 mx-auto mb-1.5 rounded-xl flex items-center justify-center ${TONE[tone]}">${svg(icon, 'w-5 h-5')}</span>
            <span class="block text-[11px] sm:text-xs font-semibold text-stone-700 dark:text-stone-300 leading-tight">${label}</span>
        </div>
    </label>`;

const statusRadio = (value, dot, required = false) => `
    <label class="status-radio-label cursor-pointer block">
        <input type="radio" name="status" value="${value}" ${required ? 'required' : ''} class="sr-only">
        <div class="status-radio-inner rounded-xl ring-1 ring-cream-300 dark:ring-coal-600 bg-cream-100 dark:bg-coal-600/40 hover:ring-claude-400 p-2.5 text-center transition text-xs font-semibold text-stone-700 dark:text-stone-300 flex items-center justify-center gap-1.5">
            <span class="w-2 h-2 rounded-full ${dot}"></span>${value}
        </div>
    </label>`;

const miniStat = (id, label, box, dot) => `
    <div class="rounded-xl ring-1 p-3 text-center ${box}">
        <p class="text-2xl font-bold tracking-tight leading-none" id="${id}">0</p>
        <p class="mt-1.5 flex items-center justify-center gap-1 text-[11px] font-medium"><span class="w-1.5 h-1.5 rounded-full ${dot}"></span> ${label}</p>
    </div>`;

const riwayatCard = (mode, afterCls) => `
    <div class="${afterCls} hidden ${CARD} overflow-hidden">
        <button type="button" onclick="toggleRiwayatSetoran('${mode}')" class="w-full flex items-center justify-between px-4 sm:px-5 py-3.5 hover:bg-cream-100 dark:hover:bg-coal-600/40 transition">
            <span class="flex items-center gap-2">
                <span class="${TILE}">${svg(P.clock)}</span>
                <span class="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Riwayat Setoran</span>
            </span>
            <svg id="riwayatIcon${mode}" class="w-4 h-4 text-stone-400 transition-transform" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">${P.chevron}</svg>
        </button>
        <div id="riwayatBody${mode}" class="hidden px-4 sm:px-5 pb-4 border-t border-cream-300 dark:border-coal-600 pt-3">
            <div class="flex flex-wrap gap-1.5 mb-2.5" id="riwayatFilter${mode}">
                <button type="button" class="rs-chip is-active" data-f="">Semua</button>
                <button type="button" class="rs-chip" data-f="Hafalan">Hafalan</button>
                <button type="button" class="rs-chip" data-f="Murajaah">Murajaah</button>
                <button type="button" class="rs-chip" data-f="Tilawah">Tilawah</button>
            </div>
            <div id="riwayatList${mode}" class="space-y-1.5 max-h-72 overflow-y-auto custom-scrollbar pr-1"></div>
        </div>
    </div>`;

const infoDasar = ({ title, icon, santriSel, santriOpts, tanggalId, sesiId, tanggalHariIni, sesiOpts, hintText, extra = '' }) => `
    <div class="${CARD} p-4 sm:p-5">
        ${secHead(icon, title)}
        ${extra}
        <div class="space-y-3">
            <div>
                <label class="${LBL}">Nama Santri ${REQ}</label>
                <select name="santri_id" ${santriSel ? `id="${santriSel}"` : ''} required class="form-select w-full">
                    <option value="">-- Pilih Santri --</option>
                    ${santriOpts}
                </select>
                ${hint(P.info, hintText)}
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="${LBL}">Tanggal ${REQ}</label>
                    <input type="date" name="tanggal" ${tanggalId ? `id="${tanggalId}"` : ''} value="${tanggalHariIni}" required class="form-input w-full">
                </div>
                <div>
                    <label class="${LBL}">Sesi ${REQ}</label>
                    <select name="sesi" ${sesiId ? `id="${sesiId}"` : ''} required class="form-select w-full">
                        <option value="">-- Pilih --</option>
                        ${sesiOpts}
                    </select>
                </div>
            </div>
        </div>
    </div>`;

const rangeAyat = (mode, suratOpts, loadFn) => `
    <div id="rangeAyatBlock${mode}">
        <div class="${SUB} p-3">
            ${subTitle(P.book, 'Range Ayat')}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="${LBL_SM}">Surat Mulai ${REQ}</label>
                    <select name="surat_start" id="suratStart${mode}" required class="form-select-sm w-full">
                        <option value="">-- Pilih --</option>
                        ${suratOpts}
                    </select>
                </div>
                <div>
                    <label class="${LBL_SM}">Ayat Mulai ${REQ}</label>
                    <select name="ayat_start" id="ayatStart${mode}" disabled required class="form-select-sm w-full">
                        <option value="">-- Pilih Surat --</option>
                    </select>
                </div>
                <div>
                    <label class="${LBL_SM}">Surat Selesai ${REQ}</label>
                    <select name="surat_end" id="suratEnd${mode}" required class="form-select-sm w-full">
                        <option value="">-- Otomatis --</option>
                        ${suratOpts}
                    </select>
                </div>
                <div>
                    <label class="${LBL_SM}">Ayat Selesai ${REQ}</label>
                    <select name="ayat_end" id="ayatEnd${mode}" disabled required class="form-select-sm w-full">
                        <option value="">-- Pilih Ayat --</option>
                    </select>
                </div>
            </div>
            <button type="button" onclick="setQuranPanelTitle('quran'); ${loadFn}; if(window.innerWidth < 1024) openDrawer('quran');"
                    class="mt-3 w-full px-3 py-2 bg-claude-500 hover:bg-claude-600 text-white text-xs font-semibold rounded-lg transition active:scale-[.98] flex items-center justify-center gap-1.5">
                ${svg(P.book, 'w-4 h-4')} Tampilkan Al-Qur'an
            </button>
        </div>
    </div>`;

const iqroBlock = (mode, withHint) => `
    <div id="iqroBlock${mode}" class="hidden ${SUB} p-3">
        <p class="text-xs font-semibold text-stone-600 dark:text-stone-300 mb-2.5 flex items-center gap-1.5">${svg(P.bookClosed, 'w-3.5 h-3.5 text-stone-400')} Pilih Halaman <span class="lbl-metode-jilid">Iqro</span></p>
        <div class="mb-2">
            <label class="${LBL_SM}">Jilid ${REQ}</label>
            <select id="iqroJilid${mode}" class="form-select-sm w-full">
                <option value="1">Jilid 1</option><option value="2">Jilid 2</option><option value="3">Jilid 3</option>
                <option value="4">Jilid 4</option><option value="5">Jilid 5</option><option value="6">Jilid 6</option>
            </select>
            ${hint(P.lock, 'Otomatis terisi dari target santri.')}
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="${LBL_SM}">Halaman Mulai ${REQ}</label>
                <select id="iqroHalamanStart${mode}" class="form-select-sm w-full"><option value="">-- Mulai --</option></select>
            </div>
            <div>
                <label class="${LBL_SM}">Halaman Selesai ${REQ}</label>
                <select id="iqroHalamanEnd${mode}" class="form-select-sm w-full"><option value="">-- Selesai --</option></select>
            </div>
        </div>
        ${withHint ? hint(P.info, 'Untuk 1 halaman saja, isi Mulai &amp; Selesai sama.') : ''}
        <button type="button" id="btnJilid${mode}" data-metode="iqro" onclick="setQuranPanelTitle('iqro'); loadIqro('${mode}'); if(window.innerWidth < 1024) openDrawer('quran');"
                class="btn-jilid mt-3 w-full px-3 py-2 text-white text-xs font-semibold rounded-lg transition active:scale-[.98] flex items-center justify-center gap-1.5">
            <span>Tampilkan Halaman <span class="lbl-metode-jilid">Iqro</span></span>
        </button>
    </div>`;

const kategoriField = (id) => `
    <div>
        <label class="${LBL}">Kategori ${REQ}</label>
        <select name="kategori_setoran" id="${id}" required class="form-select w-full">
            <option value="">-- Pilih --</option>
            <option value="Hafalan">Hafalan</option>
            <option value="Murajaah">Murajaah</option>
            <option value="Tilawah">Tilawah</option>
        </select>
    </div>`;

const hiddenFields = (m) => `
    <input type="hidden" name="page_start" id="pageStart${m}">
    <input type="hidden" name="page_end" id="pageEnd${m}">
    <input type="hidden" name="page_start_portion" id="pageStartPortion${m}">
    <input type="hidden" name="page_end_portion" id="pageEndPortion${m}">
    <input type="hidden" name="total_page" id="totalPage${m}">
    <input type="hidden" name="juz_start" id="juzStart${m}">
    <input type="hidden" name="juz_end" id="juzEnd${m}">
    <input type="hidden" name="total_juz" id="totalJuz${m}">
    <input type="hidden" name="total_kata" id="totalKata${m}">
    <input type="hidden" name="kesalahan_kelancaran" id="kesalahanKelancaran${m}" value="0">
    <input type="hidden" name="kesalahan_tajwid" id="kesalahanTajwid${m}" value="0">
    <input type="hidden" name="kesalahan_pelafalan" id="kesalahanPelafalan${m}" value="0">
    <input type="hidden" name="nilai" id="nilai${m}">
    <input type="hidden" name="pdf_html_content" id="pdfHtmlData${m}">
    <input type="hidden" name="iqro_jilid" id="iqroJilidHidden${m}">
    <input type="hidden" name="iqro_halaman" id="iqroHalamanHidden${m}">
    <input type="hidden" name="iqro_halaman_end" id="iqroHalamanEndHidden${m}">`;

const skorRow = (dot, label, id, valCls, span2 = false) => `
    <div class="${SUB} p-2.5 flex items-center justify-between ${span2 ? 'col-span-2' : ''}">
        <span class="flex items-center gap-1.5 text-xs text-stone-600 dark:text-stone-400"><span class="w-2 h-2 rounded-full ${dot}"></span>${label}</span>
        <strong id="${id}" class="text-base font-bold tabular-nums ${valCls}">0</strong>
    </div>`;

const audioCard = (m, afterCls) => `
    <div class="${afterCls} hidden mobile-hide-audio ${CARD} p-4 sm:p-5">
        ${secHead(P.mic, 'Audio &amp; Penilaian')}
        <div id="sectionAudio${m}" class="space-y-3">
            <div class="flex gap-2">
                <button type="button" id="startRecording${m}" onclick="startRecording('${m}')" class="inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-red-600 hover:bg-red-500 text-white text-xs font-semibold rounded-xl transition active:scale-95"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="6"/></svg> Rekam</button>
                <button type="button" id="pauseRecording${m}" onclick="pauseRecording('${m}')" class="hidden inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-amber-100 hover:bg-amber-200 text-amber-800 ring-1 ring-amber-200 text-xs font-semibold rounded-xl transition active:scale-95 dark:bg-amber-500/15 dark:hover:bg-amber-500/25 dark:text-amber-300 dark:ring-amber-500/25"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="8" y="6" width="3.2" height="12" rx="1"/><rect x="12.8" y="6" width="3.2" height="12" rx="1"/></svg> Jeda</button>
                <button type="button" id="stopRecording${m}" onclick="stopRecording('${m}')" class="hidden inline-flex flex-1 items-center justify-center gap-1.5 py-2.5 bg-stone-700 hover:bg-stone-600 text-white text-xs font-semibold rounded-xl transition active:scale-95 dark:bg-coal-600 dark:hover:bg-coal-500 dark:ring-1 dark:ring-coal-500"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><rect x="7" y="7" width="10" height="10" rx="2"/></svg> Stop</button>
            </div>
            <div id="recordingTimer${m}" class="hidden text-xs font-semibold tabular-nums text-red-600 dark:text-red-400 text-center py-2 bg-red-50 dark:bg-red-500/10 ring-1 ring-red-100 dark:ring-red-500/20 rounded-xl">Durasi: 0:00</div>
            <audio id="audioPlayback${m}" controls class="w-full hidden rounded-lg"></audio>
            <button type="button" id="clearRecording${m}" onclick="clearRecording('${m}')" class="w-full py-2 ${BTN_SOFT} justify-center hidden">${svg(P.trash, 'w-3.5 h-3.5')} Hapus &amp; Rekam Ulang</button>
            <input type="hidden" name="audio_data" id="audioData${m}">
            ${hint(P.info, 'Gunakan Jeda untuk berhenti sementara.')}
            <div class="grid grid-cols-2 gap-2 pt-1">
                ${skorRow('bg-emerald-500', 'Kelancaran', `displayKelancaran${m}`, 'text-emerald-600 dark:text-emerald-400', true)}
                ${skorRow('bg-amber-500', 'Tajwid', `displayTajwid${m}`, 'text-amber-600 dark:text-amber-400')}
                ${skorRow('bg-red-500', 'Pelafalan', `displayPelafalan${m}`, 'text-red-600 dark:text-red-400')}
                <div class="col-span-2 rounded-xl ring-1 ring-claude-200 dark:ring-claude-500/20 bg-claude-50 dark:bg-claude-500/10 px-3 py-2.5 flex items-center justify-between">
                    <span class="text-xs font-bold text-stone-700 dark:text-stone-300">Nilai</span>
                    <strong id="displayNilai${m}" class="text-2xl font-bold tracking-tight tabular-nums text-claude-600 dark:text-claude-400">100</strong>
                </div>
            </div>
        </div>
    </div>`;

const catatanCard = (m, afterCls) => `
    <div class="${afterCls} hidden mobile-hide-bottom ${CARD} p-4 sm:p-5">
        ${secHead(P.pencil, 'Catatan Guru')}
        <textarea name="catatan_guru" id="catatanGuru${m}" rows="2" class="form-textarea w-full" placeholder="Tambahkan catatan..."></textarea>
    </div>`;

const metodeHint = hint(P.lock, 'Otomatis terisi sesuai target santri.');

/* ============================================================
 * MARKUP — disalin dari Blade, directive diganti props.
 * ============================================================ */
function buildMarkup({ santriList, sesiList, daftarSurat, tanggalHariIni, kelasName, tahunAjaran, csrf, flash, errors }) {
    const suratOpts = optSurat(daftarSurat);
    const sesiOpts = optSesi(sesiList);
    const santriOpts = optSantri(santriList);
    const santriPlain = optSantriPlain(santriList);
    const token = `<input type="hidden" name="_token" value="${csrf}">`;
    const errList = Object.values(errors || {}).flat();
    const tanggalLabel = formatTanggalID(tanggalHariIni);

    const notif = `
    ${flash?.success ? toast('success-notification', 'emerald', P.check, `<p class="font-medium">${flash.success}</p>`) : ''}
    ${flash?.error ? toast('error-notification', 'red', P.alert, `<p class="font-medium">${flash.error}</p>`) : ''}
    ${flash?.warning ? toast('warning-notification', 'amber', P.warn, `<p class="font-medium">${flash.warning}</p>`) : ''}
    ${errList.length ? toast('error-notification', 'red', P.alert, `
        <p class="font-semibold mb-1">Terdapat kesalahan:</p>
        <ul class="text-xs space-y-0.5 text-stone-600 dark:text-stone-300">${errList.map((e) => `<li>• ${e}</li>`).join('')}</ul>`) : ''}`;

    const taBadge = tahunAjaran ? `
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 text-[11px] font-medium text-stone-600 dark:text-stone-300">
            ${svg(P.calendar, 'w-3.5 h-3.5 text-stone-400')}
            ${tahunAjaran}
            <span class="px-1.5 py-px rounded-full bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 text-[10px] font-semibold">Aktif</span>
        </span>` : '';

    return `
<div id="inputPage" class="min-h-screen [--acc-50:theme(colors.claude.50)] [--acc-100:theme(colors.claude.100)] [--acc-200:theme(colors.claude.200)] [--acc-400:theme(colors.claude.400)] [--acc:theme(colors.claude.500)] [--acc-600:theme(colors.claude.600)] [--acc-700:theme(colors.claude.700)] [--sf-500:theme(colors.coal.500)] [--sf-600:theme(colors.coal.600)] [--sf-700:theme(colors.coal.700)] [--cr-100:theme(colors.cream.100)] [--cr-200:theme(colors.cream.200)] [--cr-300:theme(colors.cream.300)] [--cr-400:theme(colors.cream.400)]">
    ${notif}

    <div id="mobileDrawerOverlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-[150] hidden lg:hidden" onclick="closeDrawer()"></div>
    <div id="mobileDrawer" class="fixed bottom-0 left-0 right-0 z-[160] lg:hidden translate-y-full transition-transform duration-300 ease-out">
        <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 rounded-t-2xl shadow-2xl max-h-[90vh] flex flex-col">
            <div class="relative flex items-center justify-between px-4 pt-4 pb-3 border-b border-cream-300 dark:border-coal-600 flex-shrink-0">
                <div class="w-8 h-1 bg-cream-400 dark:bg-coal-500 rounded-full absolute left-1/2 -translate-x-1/2 top-1.5"></div>
                <span id="drawerTitle" class="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200">Presensi Hari Ini</span>
                <button onclick="closeDrawer()" class="w-7 h-7 rounded-lg bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 hover:bg-cream-300 dark:hover:bg-coal-500 flex items-center justify-center text-stone-500 dark:text-stone-300 transition">
                    ${svg(P.x, 'w-4 h-4', 2)}
                </button>
            </div>
            <div id="drawerContent" class="overflow-y-auto flex-1 p-4"></div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">

        <!-- ===== HEADER (sama dengan Dashboard) ===== -->
        <div class="flex flex-wrap items-center gap-3 mb-5">
            <div class="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center flex-shrink-0 text-stone-600 dark:text-stone-300">
                ${svg(P.pencil, 'w-6 h-6', 1.7)}
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-lg sm:text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 truncate">Input Presensi &amp; Setoran</h1>
                <p class="text-[11px] text-stone-500 mt-0.5">
                    Kelas <span class="font-semibold text-claude-600 dark:text-claude-400">${kelasName}</span>
                    <span class="mx-1 opacity-40">·</span>${tanggalLabel}
                </p>
            </div>
            ${taBadge}
        </div>

        <div class="lg:grid lg:grid-cols-5 lg:gap-5">
            <div class="lg:col-span-2">
                <div class="lg:sticky lg:top-4 space-y-3">

                    <!-- ===== PILIH MODE ===== -->
                    <div class="${CARD} p-4 sm:p-5">
                        ${secHead(P.target, 'Pilih Mode Input')}
                        <div class="grid grid-cols-3 gap-2">
                            ${modeCard('combined', 'claude', P.target, 'Presensi<br>+ Setoran')}
                            ${modeCard('presensi_only', 'emerald', P.check, 'Presensi<br>Saja')}
                            ${modeCard('setoran_only', 'stone', P.book, 'Setoran<br>Tambahan')}
                        </div>
                    </div>

                    <div id="hintBelumPilih" class="${CARD} p-8 flex flex-col items-center justify-center text-center">
                        <span class="w-11 h-11 rounded-2xl bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center mb-3 text-stone-400">
                            ${svg(P.cursor, 'w-5 h-5')}
                        </span>
                        <p class="text-sm font-semibold text-stone-700 dark:text-stone-300">Pilih mode input di atas</p>
                        <p class="text-[11px] text-stone-400 mt-0.5">Form akan muncul di sini</p>
                    </div>

                    <hr class="border-cream-300 dark:border-coal-600 hidden" id="hrDivider">

                    <!-- ===== FORM: PRESENSI + SETORAN ===== -->
                    <form id="formCombined" action="/guru/input/store-combined" method="POST" enctype="multipart/form-data" novalidate class="hidden space-y-3">
                        ${token}
                        ${infoDasar({
                            title: 'Informasi Dasar', icon: P.clipboard,
                            santriSel: 'santriSelectCombined', santriOpts,
                            tanggalId: 'tanggalCombined', sesiId: 'sesiSelectCombined',
                            tanggalHariIni, sesiOpts,
                            hintText: 'Pilih nama santri dulu untuk menampilkan data setoran.',
                        })}

                        <input type="hidden" name="status" value="Hadir">

                        ${riwayatCard('Combined', 'combined-after-santri')}

                        <div class="combined-after-santri hidden ${CARD} p-4 sm:p-5">
                            ${secHead(P.book, 'Data Setoran')}
                            <div class="space-y-3">
                                <div>
                                    <label class="${LBL}">Metode ${REQ}</label>
                                    <div class="metode-field">
                                        <span class="metode-dot" id="metodeDotCombined" data-metode="quran"></span>
                                        <select name="metode" id="metodeCombined" required class="form-select w-full has-dot">
                                            <option value="Al-Qur'an">Al-Qur'an</option>
                                            <option value="Iqro">Iqro</option>
                                            <option value="Aisar">Aisar</option>
                                        </select>
                                    </div>
                                    ${metodeHint}
                                </div>
                                ${kategoriField('kategoriSetoranCombined')}
                                ${rangeAyat('Combined', suratOpts, 'loadQuranCombined()')}
                                ${iqroBlock('Combined', false)}
                            </div>
                        </div>

                        ${hiddenFields('Combined')}

                        ${audioCard('Combined', 'combined-after-santri')}
                        ${catatanCard('Combined', 'combined-after-santri')}

                        <button type="submit" id="submitBtnCombined" class="combined-after-santri hidden mobile-hide-bottom ${BTN_SUBMIT}">${svg(P.save, 'w-4 h-4', 2)} Simpan Presensi &amp; Setoran</button>
                    </form>

                    <!-- ===== FORM: PRESENSI SAJA ===== -->
                    <form id="formPresensiOnly" action="/guru/input/store-presensi" method="POST" class="hidden space-y-3">
                        ${token}
                        <div class="${CARD} p-4 sm:p-5">
                            ${secHead(P.check, 'Presensi Saja')}
                            <div class="space-y-3">
                                <div>
                                    <label class="${LBL}">Nama Santri ${REQ}</label>
                                    <select name="santri_id" required class="form-select w-full">
                                        <option value="">-- Pilih Santri --</option>
                                        ${santriPlain}
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="${LBL}">Tanggal ${REQ}</label>
                                        <input type="date" name="tanggal" value="${tanggalHariIni}" required class="form-input w-full">
                                    </div>
                                    <div>
                                        <label class="${LBL}">Sesi ${REQ}</label>
                                        <select name="sesi" required class="form-select w-full">
                                            <option value="">-- Pilih --</option>
                                            ${sesiOpts}
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="${LBL}">Status Kehadiran ${REQ}</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        ${statusRadio('Hadir', 'bg-emerald-500', true)}
                                        ${statusRadio('Sakit', 'bg-orange-500')}
                                        ${statusRadio('Izin', 'bg-amber-500')}
                                        ${statusRadio('Alpha', 'bg-red-500')}
                                    </div>
                                </div>
                                <div>
                                    <label class="${LBL}">Keterangan <span class="text-stone-400 font-normal">(opsional)</span></label>
                                    <textarea name="keterangan" rows="2" class="form-textarea w-full" placeholder="Contoh: Demam tinggi..."></textarea>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="${BTN_SUBMIT}">${svg(P.save, 'w-4 h-4', 2)} Simpan Presensi</button>
                    </form>

                    <!-- ===== FORM: SETORAN TAMBAHAN ===== -->
                    <form id="formSetoranOnly" action="/guru/input/store-setoran" method="POST" enctype="multipart/form-data" novalidate class="hidden space-y-3">
                        ${token}
                        ${infoDasar({
                            title: 'Setoran Tambahan', icon: P.book,
                            santriSel: 'santriSelectSetoranOnly', santriOpts,
                            tanggalId: 'tanggalSetoranOnly', sesiId: 'sesiSelectSetoranOnly',
                            tanggalHariIni, sesiOpts,
                            hintText: 'Pilih nama santri dulu untuk menampilkan data setoran.',
                            extra: `
                            <div class="mb-3 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-500/10 ring-1 ring-amber-100 dark:ring-amber-500/20 flex items-start gap-2 text-[11px] text-amber-700 dark:text-amber-400">
                                ${svg(P.warn, 'w-3.5 h-3.5 mt-px flex-shrink-0')}
                                <span>Santri harus sudah presensi <strong>Hadir</strong> di sesi yang sama.</span>
                            </div>`,
                        })}

                        ${riwayatCard('SetoranOnly', 'setoran-after-santri')}

                        <div class="setoran-after-santri hidden ${CARD} p-4 sm:p-5">
                            ${secHead(P.book, 'Data Setoran')}
                            <div class="space-y-3">
                                <div>
                                    <label class="${LBL}">Metode ${REQ}</label>
                                    <select name="metode" id="metodeSetoranOnly" required class="form-select w-full">
                                        <option value="Al-Qur'an">Al-Qur'an</option>
                                        <option value="Iqro">Iqro</option>
                                        <option value="Aisar">Aisar</option>
                                    </select>
                                    ${metodeHint}
                                </div>
                                ${kategoriField('kategoriSetoranOnly')}
                                ${rangeAyat('SetoranOnly', suratOpts, 'loadQuranSetoranOnly()')}
                                ${iqroBlock('SetoranOnly', true)}
                            </div>
                        </div>

                        ${hiddenFields('SetoranOnly')}

                        ${audioCard('SetoranOnly', 'setoran-after-santri')}
                        ${catatanCard('SetoranOnly', 'setoran-after-santri')}

                        <button type="submit" id="submitBtnSetoranOnly" class="setoran-after-santri hidden mobile-hide-bottom ${BTN_SUBMIT}">${svg(P.save, 'w-4 h-4', 2)} Simpan Setoran Tambahan</button>
                    </form>

                </div>
            </div>

            <!-- ===== PANEL KANAN (desktop) ===== -->
            <div class="hidden lg:block lg:col-span-3">
                <div class="lg:sticky lg:top-4 ${CARD} overflow-hidden">

                    <div id="panelPetunjuk" class="p-5">
                        ${secHead(P.clipboard, 'Petunjuk Penggunaan', '<span class="ml-auto text-[11px] text-stone-400">Pilih mode di kiri untuk memulai</span>')}
                        <div class="space-y-2">
                            ${[
                                ['claude', P.target, 'Presensi + Setoran', 'Catat kehadiran &amp; setoran sekaligus dalam 1 submit.'],
                                ['emerald', P.check, 'Presensi Saja', 'Untuk Hadir tanpa setoran, atau Sakit / Izin / Alpha.'],
                                ['stone', P.book, 'Setoran Tambahan', 'Setoran ke-2, ke-3, dst. Santri harus sudah hadir.'],
                            ].map(([tone, icon, t, d]) => `
                            <div class="flex items-center gap-3 px-3 py-2.5 ${SUB}">
                                <span class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${TONE[tone]}">${svg(icon, 'w-5 h-5')}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-stone-800 dark:text-stone-100">${t}</p>
                                    <p class="text-[11px] text-stone-500 dark:text-stone-400">${d}</p>
                                </div>
                            </div>`).join('')}
                        </div>
                    </div>

                    <div id="defaultView" class="hidden p-5">
                        ${secHead(P.check, 'Presensi Hari Ini', `<span class="ml-auto text-[11px] text-stone-400">${tanggalLabel}</span>`)}

                        <div class="grid grid-cols-4 gap-2 mb-4">
                            ${miniStat('statHadir', 'Hadir', 'bg-emerald-50 dark:bg-emerald-500/10 ring-emerald-100 dark:ring-emerald-500/20 text-emerald-600 dark:text-emerald-400', 'bg-emerald-500')}
                            ${miniStat('statSakit', 'Sakit', 'bg-orange-50 dark:bg-orange-500/10 ring-orange-100 dark:ring-orange-500/20 text-orange-600 dark:text-orange-400', 'bg-orange-500')}
                            ${miniStat('statIzin', 'Izin', 'bg-amber-50 dark:bg-amber-500/10 ring-amber-100 dark:ring-amber-500/20 text-amber-600 dark:text-amber-400', 'bg-amber-500')}
                            ${miniStat('statAlpha', 'Alpha', 'bg-red-50 dark:bg-red-500/10 ring-red-100 dark:ring-red-500/20 text-red-600 dark:text-red-400', 'bg-red-500')}
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mb-3 text-[11px] text-stone-500 dark:text-stone-400">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Hadir</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-orange-500"></span> Sakit</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Izin</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span> Alpha</span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded bg-cream-200 dark:bg-coal-600 ring-1 ring-cream-300 dark:ring-coal-500 flex items-center justify-center text-stone-500 dark:text-stone-400">${svg(P.book, 'w-2.5 h-2.5')}</span>
                                Setoran
                            </span>
                        </div>

                        <div id="daftarPresensiHariIni" class="max-h-[420px] overflow-y-auto overflow-x-auto tabel-presensi-scroll rounded-xl ring-1 ring-cream-300 dark:ring-coal-600">
                            <!-- min-w-max dihapus: kalau dipertahankan, tabel melebar mengikuti
                                 nama terpanjang sehingga teks tidak pernah turun baris dan
                                 malah memunculkan scroll horizontal. -->
                            <table class="w-full text-xs">
                                <thead class="bg-cream-100 dark:bg-coal-600 sticky top-0 z-10">
                                    <tr id="tableHeaderRow">
                                        <th class="px-3 py-2 text-center text-[11px] text-stone-500 dark:text-stone-400 font-semibold w-10">No</th>
                                        <th class="px-2 py-2 text-left text-[11px] text-stone-500 dark:text-stone-400 font-semibold">Nama Santri</th>
                                        <th class="px-2 py-2 text-left text-[11px] text-stone-500 dark:text-stone-400 font-semibold whitespace-nowrap">Metode</th>
                                        <th class="px-2 py-2 text-left text-[11px] text-stone-500 dark:text-stone-400 font-semibold whitespace-nowrap">Progress</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBodyPresensi" class="divide-y divide-cream-200 dark:divide-coal-600/60">
                                    <tr><td colspan="7" class="pt-empty">Memuat data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="quranPanel" class="hidden p-5">
                        <div class="hidden md:flex items-center justify-between mb-4">
                            <!-- Tile ikon SENGAJA <i>, bukan <span>: setQuranPanelTitle() di
                                 unified-input-page.js mengganti teks <span> PERTAMA di h3 ini.
                                 Kalau tile-nya <span>, ikon tertimpa dan judul tampil ganda. -->
                            <h3 id="quranPanelTitle" class="text-sm font-bold tracking-tight text-stone-800 dark:text-stone-200 flex items-center gap-2"><i class="${TILE} not-italic">${svg(P.book)}</i><span>Al-Qur'an Digital</span></h3>
                            <div class="flex gap-2">
                                <button type="button" onclick="clearMarking()" class="${BTN_SOFT}">${svg(P.trash, 'w-3.5 h-3.5')} Reset</button>
                                <button type="button" onclick="backToDefaultView()" class="${BTN_SOFT}">${svg(P.back, 'w-3.5 h-3.5')} Kembali</button>
                            </div>
                        </div>

                        <div data-color-guide class="mb-3 ${SUB} overflow-hidden">
                            <button type="button" onclick="toggleColorGuide(this)" class="w-full px-3 py-2.5 hover:bg-cream-200 dark:hover:bg-coal-600 text-left flex justify-between items-center transition">
                                <span class="text-xs font-semibold text-stone-600 dark:text-stone-300 flex items-center gap-1.5">${svg(P.swatch, 'w-3.5 h-3.5 text-stone-400')} Panduan Warna Marking</span>
                                <svg id="colorGuideIcon" class="w-4 h-4 text-stone-400 transition-transform" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">${P.chevron}</svg>
                            </button>
                            <div id="colorGuideContent" class="hidden bg-white dark:bg-coal-700 p-3 border-t border-cream-300 dark:border-coal-600">
                                <div class="space-y-1.5 text-xs text-stone-700 dark:text-stone-300">
                                    <div class="flex items-center gap-2"><span class="w-3 h-3 bg-green-400 rounded flex-shrink-0"></span><strong>Kelancaran</strong><span class="text-stone-400">— -4 poin/kata</span></div>
                                    <div class="flex items-center gap-2"><span class="w-3 h-3 bg-yellow-400 rounded flex-shrink-0"></span><strong>Tajwid</strong><span class="text-stone-400">— -4 poin/kata</span></div>
                                    <div class="flex items-center gap-2"><span class="w-3 h-3 bg-red-400 rounded flex-shrink-0"></span><strong>Pelafalan</strong><span class="text-stone-400">— -4 poin/kata</span></div>
                                    ${hint(P.info, 'Klik kata untuk marking. Klik lagi untuk ganti/hapus.')}
                                </div>
                            </div>
                        </div>

                        <div id="quranDisplay" class="${SUB} p-4 text-right overflow-y-auto custom-scrollbar mb-3" style="font-family: 'Amiri', 'Traditional Arabic', serif; min-height: 280px; max-height: 380px;"></div>

                        <!-- data-stat-block: penanda untuk openDrawer() — blok ini dibuang
                             dari clone drawer karena drawer punya statistik sendiri. -->
                        <div data-stat-block class="${SUB} p-3">
                            ${subTitle(P.chart, 'Statistik Setoran')}
                            <div class="grid grid-cols-3 gap-2 mb-2">
                                <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-2.5 rounded-xl"><p class="text-[11px] text-stone-500 dark:text-stone-400">Total Kata</p><p class="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 tabular-nums" id="displayTotalKata">0</p></div>
                                <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-2.5 rounded-xl"><p class="text-[11px] text-stone-500 dark:text-stone-400">Juz Range</p><p class="text-lg font-bold tracking-tight text-claude-600 dark:text-claude-400" id="displayJuzRange">-</p></div>
                                <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-2.5 rounded-xl"><p class="text-[11px] text-stone-500 dark:text-stone-400">Total Juz</p><p class="text-lg font-bold tracking-tight text-stone-900 dark:text-stone-100 tabular-nums" id="displayTotalJuz">0.00</p></div>
                            </div>
                            <div class="bg-white dark:bg-coal-700 ring-1 ring-cream-300 dark:ring-coal-600 p-2.5 rounded-xl">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[11px] text-stone-500 dark:text-stone-400 font-medium flex items-center gap-1">${svg(P.doc, 'w-3 h-3 text-stone-400')} Halaman Mushaf Madinah</span>
                                    <span class="text-[10px] text-stone-400">Otomatis</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 ${SUB} px-2.5 py-2"><p class="text-[10px] text-stone-500">Mulai</p><p class="text-base font-bold text-stone-800 dark:text-stone-100 tabular-nums" id="displayPageStart">-</p></div>
                                    <span class="text-claude-500">→</span>
                                    <div class="flex-1 ${SUB} px-2.5 py-2"><p class="text-[10px] text-stone-500">Selesai</p><p class="text-base font-bold text-stone-800 dark:text-stone-100 tabular-nums" id="displayPageEnd">-</p></div>
                                    <div class="flex-1 rounded-xl ring-1 ring-claude-200 dark:ring-claude-500/20 bg-claude-50 dark:bg-claude-500/10 px-2.5 py-2"><p class="text-[10px] text-stone-500">Total</p><p class="text-base font-bold text-claude-600 dark:text-claude-400 tabular-nums" id="displayTotalPage">0.00</p></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div id="mobileFab" class="fixed bottom-4 right-4 z-[100] lg:hidden hidden">
        <button onclick="openDrawer('presensi')" class="flex items-center gap-2 px-4 py-3 bg-claude-500 hover:bg-claude-600 text-white text-sm font-semibold rounded-full shadow-xl shadow-claude-500/30 transition-all active:scale-95">
            ${svg(P.clipboard, 'w-4 h-4', 2)}
            Presensi
        </button>
    </div>
</div>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Amiri+Quran&display=swap" rel="stylesheet">
<style>
    /* ===== Warna aksen =====
       Nilai asli diambil dari tailwind.config.js lewat class
       [--acc:theme(colors.claude.500)] dst. di elemen #inputPage, jadi
       otomatis ikut kalau palet claude diganti. Blok :where() di bawah
       hanya CADANGAN (spesifisitas 0) kalau class itu tidak ter-generate. */
    :where(#inputPage) {
        --acc-50: #F4F9EC; --acc-100: #E6F2D5; --acc-200: #CDE5AC; --acc-400: #98C963;
        --acc: #7EB842; --acc-600: #67992F; --acc-700: #507726;
        --sf-500: #4B5247; --sf-600: #353B33; --sf-700: #262B25;
        --cr-100: #FAFAF7; --cr-200: #F3F4EE; --cr-300: #E1E4D8; --cr-400: #CDD1C4;
    }
    /* Field gelap = coal-600 45% di atas coal-700 — sama dengan baris item
       Dashboard (bg-coal-600/40), sedikit lebih terang dari kartu. */
    #inputPage { --field-dk: color-mix(in srgb, var(--sf-600) 45%, var(--sf-700)); }

    /* Override untuk markup yang DIRENDER JS (baris tabel, isi drawer, dsb.)
       yang masih memakai kelas gray — dipaksa ke palet coal. */
    html.dark #inputPage .dark\\:bg-gray-900 { background-color: var(--sf-700) !important; }
    html.dark #inputPage .dark\\:bg-gray-800,
    html.dark #inputPage .dark\\:bg-gray-800\\/50 { background-color: var(--field-dk) !important; }
    html.dark #inputPage .dark\\:border-gray-800 { border-color: var(--sf-600) !important; }

    /* ===== Baris tabel presensi (dirender unified-input-page.js) =====
       public/js tidak dipindai Tailwind, jadi gaya baris ditulis sebagai
       CSS biasa di sini. Warna lewat variable supaya ikut ganti tema
       tanpa render ulang (tabel hanya di-refresh berkala). */
    #inputPage {
        --pt-fg: #292524; --pt-sub: #78716C; --pt-hover: var(--cr-100); --pt-ring: var(--cr-400);
    }
    html.dark #inputPage {
        --pt-fg: #E7E5E4; --pt-sub: #A8A29E; --pt-hover: color-mix(in srgb, var(--sf-600) 35%, transparent); --pt-ring: var(--sf-500);
    }
    .pt-row { vertical-align: top; transition: background-color .15s; }
    .pt-row:hover { background: var(--pt-hover); }
    .pt-no { padding: 10px 8px; text-align: center; font-size: 11px; color: var(--pt-sub); font-variant-numeric: tabular-nums; }
    .pt-td { padding: 10px 8px; }
    .pt-nowrap { white-space: nowrap; }
    .pt-name { max-width: 180px; word-break: break-word; line-height: 1.35; font-size: 13px; color: var(--pt-fg); }
    .pt-th-sesi { padding: 8px; width: 3.5rem; text-align: center; font-size: 11px; font-weight: 600; color: var(--pt-sub); }
    .pt-cell { padding: 10px 4px; }
    .pt-stack { display: flex; flex-direction: column; align-items: center; gap: 2px; }
    .pt-line { display: flex; align-items: center; gap: 4px; }
    .pt-dot { display: block; width: 14px; height: 14px; border-radius: 9999px; flex-shrink: 0; }
    .pt-dot-empty { width: 16px; height: 16px; box-shadow: inset 0 0 0 1px var(--pt-ring); }
    .pt-waktu { font-size: 9px; color: var(--pt-sub); font-variant-numeric: tabular-nums; }
    .pt-empty { text-align: center; padding: 32px 8px; font-size: 12px; color: var(--pt-sub); }
    .pt-error { color: #EF4444; }

    /* Badge setoran di tabel — sama dengan badge di Dashboard */
    .setoran-badge {
        display: inline-flex; align-items: center; gap: 2px;
        padding: 1px 5px; border-radius: 6px; font-size: 10px; font-weight: 600;
        background-color: var(--cr-200); color: #57534E; box-shadow: inset 0 0 0 1px var(--cr-300);
    }
    html.dark .setoran-badge { background-color: var(--sf-600); color: #D6D3D1; box-shadow: inset 0 0 0 1px var(--sf-500); }

    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--cr-400); border-radius: 10px; }
    html.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--sf-500); }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: var(--acc); }

    /* Scrollbar kotak Qur'an/Iqro — ada CSS dari script Qur'an yang menimpa
       .custom-scrollbar (batang biru + panah). Dikunci di sini dengan
       selector ber-ID + !important, termasuk versi clone di drawer. */
    #inputPage #quranDisplay::-webkit-scrollbar,
    #inputPage #quranDisplayDrawer::-webkit-scrollbar { width: 6px !important; height: 6px !important; background: transparent !important; }
    #inputPage #quranDisplay::-webkit-scrollbar-track,
    #inputPage #quranDisplayDrawer::-webkit-scrollbar-track { background: transparent !important; box-shadow: none !important; border: 0 !important; }
    #inputPage #quranDisplay::-webkit-scrollbar-thumb,
    #inputPage #quranDisplayDrawer::-webkit-scrollbar-thumb { background: var(--cr-400) !important; border-radius: 9999px !important; border: 0 !important; }
    #inputPage #quranDisplay::-webkit-scrollbar-thumb:hover,
    #inputPage #quranDisplayDrawer::-webkit-scrollbar-thumb:hover { background: var(--acc) !important; }
    #inputPage #quranDisplay::-webkit-scrollbar-button,
    #inputPage #quranDisplayDrawer::-webkit-scrollbar-button { display: none !important; width: 0 !important; height: 0 !important; }
    html.dark #inputPage #quranDisplay::-webkit-scrollbar-thumb,
    html.dark #inputPage #quranDisplayDrawer::-webkit-scrollbar-thumb { background: var(--sf-500) !important; }
    #inputPage #quranDisplay, #inputPage #quranDisplayDrawer { scrollbar-width: thin !important; scrollbar-color: var(--cr-400) transparent !important; }
    html.dark #inputPage #quranDisplay, html.dark #inputPage #quranDisplayDrawer { scrollbar-color: var(--sf-500) transparent !important; }

    /* Tabel presensi: scrollbar diberi jalur sendiri (scrollbar-gutter) supaya
       tidak menindih kolom terakhir. */
    .tabel-presensi-scroll { scrollbar-gutter: stable; }
    .tabel-presensi-scroll::-webkit-scrollbar { width: 10px; height: 10px; }
    .tabel-presensi-scroll::-webkit-scrollbar-track { background: var(--cr-100); }
    .tabel-presensi-scroll::-webkit-scrollbar-thumb {
        background: var(--cr-400);
        border-radius: 9999px;
        border: 3px solid var(--cr-100);
    }
    .tabel-presensi-scroll::-webkit-scrollbar-thumb:hover { background: color-mix(in srgb, var(--cr-400) 80%, #000); }
    .tabel-presensi-scroll::-webkit-scrollbar-corner { background: var(--cr-100); }

    html.dark .tabel-presensi-scroll::-webkit-scrollbar-track,
    html.dark .tabel-presensi-scroll::-webkit-scrollbar-corner { background: var(--sf-700); }
    html.dark .tabel-presensi-scroll::-webkit-scrollbar-thumb {
        background: var(--sf-500);
        border-color: var(--sf-700);
    }
    html.dark .tabel-presensi-scroll::-webkit-scrollbar-thumb:hover { background: color-mix(in srgb, var(--sf-500) 75%, #fff); }

    .tabel-presensi-scroll { scrollbar-width: thin; scrollbar-color: var(--cr-400) var(--cr-100); }
    html.dark .tabel-presensi-scroll { scrollbar-color: var(--sf-500) var(--sf-700); }

    /* ===== Badge metode & bar progress di tabel presensi =====
       Warna WAJIB lewat CSS variable, bukan hex inline dari JS.
       Tabel ini dirender sekali oleh unified-input-page.js dan tidak
       dirender ulang saat tema diganti. */
    /* Sama dengan METODE_BADGE di Dashboard.jsx:
       quran = claude (aksen tema), iqro = amber, aisar = sky. */
    #inputPage {
        --pm-quran-bg: var(--acc-100); --pm-quran-fg: var(--acc-700); --pm-quran-ring: var(--acc-200);
        --pm-iqro-bg:  #FFFBEB;        --pm-iqro-fg:  #B45309;        --pm-iqro-ring:  #FEF3C7;
        --pm-aisar-bg: #F0F9FF;        --pm-aisar-fg: #0369A1;        --pm-aisar-ring: #E0F2FE;
        --pm-track:    var(--cr-300); --pm-label: #78716C;
        --pm-empty:    #A8A29E;
    }
    html.dark #inputPage {
        --pm-quran-bg: color-mix(in srgb, var(--acc) 15%, transparent); --pm-quran-fg: var(--acc-400); --pm-quran-ring: color-mix(in srgb, var(--acc) 20%, transparent);
        --pm-iqro-bg:  rgba(245,158,11,.10); --pm-iqro-fg:  #FBBF24; --pm-iqro-ring:  rgba(245,158,11,.20);
        --pm-aisar-bg: rgba(14,165,233,.10); --pm-aisar-fg: #38BDF8; --pm-aisar-ring: rgba(14,165,233,.20);
        --pm-track:    var(--sf-600); --pm-label: #A8A29E;
        --pm-empty:    #78716C;
    }

    .pm-badge {
        display: inline-block; white-space: nowrap;
        padding: 2px 7px; border-radius: 6px;
        font-size: 10px; font-weight: 600;
        transition: background-color .2s, color .2s;
    }
    .pm-badge[data-tone="quran"] { background: var(--pm-quran-bg); color: var(--pm-quran-fg); box-shadow: inset 0 0 0 1px var(--pm-quran-ring); }
    .pm-badge[data-tone="iqro"]  { background: var(--pm-iqro-bg);  color: var(--pm-iqro-fg);  box-shadow: inset 0 0 0 1px var(--pm-iqro-ring); }
    .pm-badge[data-tone="aisar"] { background: var(--pm-aisar-bg); color: var(--pm-aisar-fg); box-shadow: inset 0 0 0 1px var(--pm-aisar-ring); }
    .pm-badge .pm-plus { opacity: .45; }

    .pm-empty { color: var(--pm-empty); font-size: 10px; }

    .pm-prog      { display: flex; flex-direction: column; gap: 3px; min-width: 96px; }
    .pm-prog-row  { display: flex; align-items: center; gap: 5px; }
    .pm-prog-track{
        flex: 1; height: 4px; border-radius: 9999px;
        background: var(--pm-track); overflow: hidden;
        transition: background-color .2s;
    }
    .pm-prog-fill { display: block; height: 100%; border-radius: 9999px; }
    .pm-prog-fill[data-c="1"] { background: var(--acc); }
    .pm-prog-fill[data-c="2"] { background: #10B981; }
    .pm-prog-val  {
        font-size: 10px; font-weight: 600; min-width: 26px;
        text-align: right; color: var(--pm-label);
        transition: color .2s;
    }

    /* ===== Field form — palet hangat, sudut rounded-xl seperti Dashboard ===== */
    .form-input, .form-select, .form-select-sm, .form-textarea {
        background: #ffffff; border: 1px solid var(--cr-300); color: #292524;
        border-radius: 0.75rem; font-size: 0.8125rem; padding: 0.5rem 0.75rem;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    html.dark .form-input, html.dark .form-select, html.dark .form-select-sm, html.dark .form-textarea {
        background: var(--field-dk); border: 1px solid var(--sf-600); color: #E7E5E4; color-scheme: dark;
    }
    .form-select-sm { padding: 0.4rem 0.625rem; font-size: 0.75rem; border-radius: 0.625rem; }
    .form-textarea::placeholder { color: #A8A29E; }
    .form-select.is-locked {
        pointer-events: none;
        opacity: 0.9;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 2.25rem;
        background-color: var(--cr-200);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2378716c' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='4.5' y='11' width='15' height='10' rx='2'/%3E%3Cpath d='M8 11V7a4 4 0 0 1 8 0v4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.85rem center;
        background-size: 15px;
    }
    html.dark .form-select.is-locked {
        background-color: var(--sf-700);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23a8a29e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='4.5' y='11' width='15' height='10' rx='2'/%3E%3Cpath d='M8 11V7a4 4 0 0 1 8 0v4'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.85rem center;
        background-size: 15px;
    }

    /* ===== Panah select METODE =====
       Select metode TIDAK didaftarkan di ALL_SELECTS (unified-input-page.js),
       jadi ia tetap <select> native. Saat santri 2 metode, .is-locked tidak
       dipasang → panah bawaan diganti segitiga yang identik dengan .cs-arr. */
    #metodeCombined:not(.is-locked),
    #metodeSetoranOnly:not(.is-locked) {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding-right: 2rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 10 10'%3E%3Cpath d='M1 3.2 L5 7.6 L9 3.2 Z' fill='%23a8a29e'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.72rem center;
        background-size: 9px;
    }
    #metodeCombined:not(.is-locked)::-ms-expand,
    #metodeSetoranOnly:not(.is-locked)::-ms-expand { display: none; }

    .form-input:focus, .form-select:focus, .form-select-sm:focus, .form-textarea:focus {
        outline: none; border-color: var(--acc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 15%, transparent);
    }
    .form-textarea { resize: vertical; }
    input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; opacity: 0.5; }
    html.dark input[type="date"]::-webkit-calendar-picker-indicator { filter: none; opacity: 0.7; }
    input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; }

    /* ===== Kartu mode & radio status: state terpilih = aksen terracotta ===== */
    .mode-card input:checked ~ .mode-card-inner,
    .status-radio-label input:checked ~ .status-radio-inner {
        box-shadow: 0 0 0 2px var(--acc) !important;
        background-color: var(--acc-50) !important;
    }
    html.dark .mode-card input:checked ~ .mode-card-inner,
    html.dark .status-radio-label input:checked ~ .status-radio-inner {
        background-color: color-mix(in srgb, var(--acc) 12%, transparent) !important;
    }
    .status-radio-label input:checked ~ .status-radio-inner { color: var(--acc-700); }
    html.dark .status-radio-label input:checked ~ .status-radio-inner { color: var(--acc-400); }
    .mode-card input:focus-visible ~ .mode-card-inner,
    .status-radio-label input:focus-visible ~ .status-radio-inner { outline: 2px solid var(--acc); outline-offset: 2px; }

    @keyframes fade-in { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
    .animate-fade-in { animation: fade-in 0.25s ease-out; }
    .section-open .section-chevron { transform: rotate(180deg); }
    @media (max-width: 1023px) { .mobile-hide-audio { display: none !important; } .mobile-hide-bottom { display: none !important; } }

    /* ===== Select kustom (.cs-*) — dibuat oleh unified-input-page.js ===== */
    .cs-wrap { position: relative; width: 100%; }
    .cs-btn { width: 100%; text-align: left; padding: 7px 12px; border: 1px solid var(--cr-300); border-radius: 0.75rem; background: #ffffff; color: #292524; font-size: 0.8125rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; min-height: 36px; box-sizing: border-box; transition: border-color 0.15s, box-shadow 0.15s; font-family: inherit; line-height: 1.25; }
    html.dark .cs-btn { background: var(--field-dk); color: #E7E5E4; border-color: var(--sf-600); }
    .cs-btn:hover { border-color: var(--acc); }
    .cs-btn.open { border-color: var(--acc); box-shadow: 0 0 0 3px color-mix(in srgb, var(--acc) 15%, transparent); }
    .cs-btn.cs-disabled { opacity: 0.45; cursor: not-allowed; }
    .cs-btn .cs-txt { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1; font-size: 0.8125rem; }
    .cs-btn .cs-txt.ph { color: #A8A29E; font-style: normal; }
    .cs-btn .cs-arr { font-size: 8px; color: #A8A29E; flex-shrink: 0; margin-left: 6px; transition: transform 0.2s; }
    .cs-btn.open .cs-arr { transform: rotate(180deg); }
    .cs-drop { display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 99999; background: #ffffff; border: 1px solid var(--cr-300); border-radius: 0.75rem; box-shadow: 0 10px 28px rgba(41,37,36,0.12); overflow: hidden; }
    html.dark .cs-drop { background: var(--sf-700); border-color: var(--sf-600); box-shadow: 0 12px 32px rgba(0,0,0,0.45); }
    .cs-drop.open { display: block; }
    .cs-search { width: 100%; padding: 8px 12px; border: none; border-bottom: 1px solid var(--cr-300); outline: none; font-size: 0.75rem; background: var(--cr-100); color: #292524; box-sizing: border-box; font-family: inherit; }
    html.dark .cs-search { background: var(--field-dk); color: #E7E5E4; border-bottom-color: var(--sf-600); }
    .cs-search::placeholder { color: #A8A29E; }
    .cs-list { max-height: 180px; overflow-y: auto; margin: 0; padding: 4px; list-style: none; scrollbar-width: thin; scrollbar-color: var(--cr-400) transparent; }
    html.dark .cs-list { scrollbar-color: var(--sf-500) transparent; }
    .cs-list li { padding: 6px 10px; border-radius: 0.5rem; font-size: 0.75rem; cursor: pointer; color: #292524; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: background 0.1s; }
    html.dark .cs-list li { color: #E7E5E4; }
    .cs-list li:hover, .cs-list li.cs-hi { background: var(--acc) !important; color: white !important; }
    .cs-list li.cs-ph { color: #A8A29E; font-style: normal; }
    .cs-list li.cs-gone { display: none; }
    .cs-nores { padding: 8px 10px; font-size: 0.75rem; color: #A8A29E; text-align: center; display: none; }

    #mobileDrawer.open { transform: translateY(0) !important; }
    @media (max-width: 1023px) {
        #quranDisplayDrawer #quranText { font-size: 28px !important; line-height: 3.0 !important; word-spacing: -4px !important; }
        #quranDisplayDrawer .word-item { font-size: 24px !important; padding: 4px 6px !important; margin: 3px 0 !important; display: inline-block !important; }
        #quranDisplayDrawer .iqro-page .word-item { font-size: 32px !important; height: 64px !important; padding: 0 6px !important; margin: 0 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; line-height: 1.4 !important; }
        #quranDisplayDrawer #quranText > span:not(.word-item) { font-size: 16px !important; margin: 0 4px !important; }
        #quranDisplayDrawer p[style*="font-size: 38px"] { font-size: 26px !important; }
        #quranDisplayDrawer p[style*="font-size: 36px"] { font-size: 22px !important; }
        #quranDisplayDrawer p[style*="font-size: 16px"] { font-size: 12px !important; }
        #quranDisplayDrawer div[style*="border: 1.5px solid"] { padding: 14px !important; margin-top: 8px !important; margin-bottom: 12px !important; }
        #quranDisplayDrawer { min-height: 200px !important; max-height: 50vh !important; }
    }

    /* Variabel warna Qur'an viewer — navigasi pakai palet hangat */
    #quranPanel, #quranPanelDrawer, #quranDisplay, #quranDisplayDrawer { --q-fg: #F5F4EF; --q-num: rgba(245,244,239,0.55); --q-box-border: rgba(245,244,239,0.8); --q-line: rgba(245,244,239,0.28); --q-nav-bg: var(--sf-700); --q-nav-btn: var(--sf-600); --q-nav-btn-fg: #E7E5E4; --q-nav-label: var(--acc-400); }
    html:not(.dark) #quranPanel, html:not(.dark) #quranPanelDrawer, html:not(.dark) #quranDisplay, html:not(.dark) #quranDisplayDrawer { --q-fg: #292524; --q-num: rgba(41,37,36,0.55); --q-box-border: rgba(41,37,36,0.5); --q-line: rgba(41,37,36,0.2); --q-nav-bg: var(--cr-200); --q-nav-btn: var(--cr-300); --q-nav-btn-fg: #44403C; --q-nav-label: var(--acc-700); }
    #quranDisplay .iqro-page, #quranDisplay .iqro-page *, #quranDisplayDrawer .iqro-page, #quranDisplayDrawer .iqro-page *, .iqro-cap .iqro-page, .iqro-cap .iqro-page * { font-family: 'Amiri', 'Amiri Quran', serif !important; }

    /* Toast — garis hitung mundur berwarna sesuai jenis */
    .success-notification > div, .error-notification > div, .warning-notification > div { position: relative; overflow: hidden; }
    .success-notification > div::after, .error-notification > div::after, .warning-notification > div::after { content: ''; position: absolute; bottom: 0; left: 0; height: 2px; width: 100%; animation: notifCountdown 10s linear forwards; }
    .success-notification > div::after { background: #10B981; }
    .error-notification > div::after   { background: #EF4444; }
    .warning-notification > div::after { background: #F59E0B; }
    @keyframes notifCountdown { from { width: 100%; } to { width: 0%; } }

    .iqro-row-scroll::-webkit-scrollbar { height: 4px; }
    .iqro-row-scroll::-webkit-scrollbar-track { background: transparent; }
    .iqro-row-scroll::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--acc) 40%, transparent); border-radius: 4px; }

    /* ===== Riwayat Setoran ===== */
    #inputPage {
        --rs-card:  #FFFFFF;  --rs-line:  var(--cr-300);
        --rs-fg:    #292524;  --rs-sub:   #78716C;
        --rs-chip:  var(--cr-200);  --rs-chip-fg: #57534E;
    }
    html.dark #inputPage {
        --rs-card:  var(--field-dk);  --rs-line:  var(--sf-600);
        --rs-fg:    #E7E5E4;  --rs-sub:   #A8A29E;
        --rs-chip:  var(--sf-600);  --rs-chip-fg: #A8A29E;
    }
    .rs-chip {
        padding: 3px 10px; border-radius: 9999px; font-size: 10px; font-weight: 600;
        background: var(--rs-chip); color: var(--rs-chip-fg);
        border: 1px solid var(--rs-line); cursor: pointer; transition: all .15s;
    }
    .rs-chip:hover { border-color: var(--acc); }
    .rs-chip.is-active { background: var(--acc); color: #fff; border-color: var(--acc); }

    .rs-item {
        background: var(--rs-card); border: 1px solid var(--rs-line);
        border-radius: 12px; padding: 8px 10px;
    }
    .rs-item-top, .rs-item-bot { display: flex; align-items: center; justify-content: space-between; }
    .rs-item-bot { margin-top: 4px; }
    .rs-kat { font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 6px; }
    .rs-kat[data-k="Hafalan"]  { background: #EAF1EC; color: #4A6B54; }
    .rs-kat[data-k="Murajaah"] { background: #FDF3E3; color: #9A6B14; }
    .rs-kat[data-k="Tilawah"]  { background: #FBEDE7; color: #BE5D3D; }
    html.dark .rs-kat[data-k="Hafalan"]  { background: rgba(124,152,133,.18); color: #9DBAA6; }
    html.dark .rs-kat[data-k="Murajaah"] { background: rgba(212,162,78,.15);  color: #D9B36A; }
    html.dark .rs-kat[data-k="Tilawah"]  { background: rgba(217,119,87,.15);  color: #E8A183; }

    .rs-tgl    { font-size: 10px; color: var(--rs-sub); font-variant-numeric: tabular-nums; }
    .rs-materi { font-size: 12px; color: var(--rs-fg); margin-top: 5px; line-height: 1.35; word-break: break-word; }
    .rs-metode { font-size: 10px; color: var(--rs-sub); }
    .rs-nilai  { font-size: 13px; font-weight: 700; font-variant-numeric: tabular-nums; }
    .rs-nilai[data-n="hi"]  { color: #10B981; }
    .rs-nilai[data-n="mid"] { color: #D4A24E; }
    .rs-nilai[data-n="lo"]  { color: #EF4444; }
    .rs-empty  { font-size: 11px; color: var(--rs-sub); text-align: center; padding: 14px 0; }

    /* Tombol "Tampilkan Halaman" mengikuti metode aktif (data-metode di-sync syncJilidUI) */
    /* Warna = METODE_COLORS di Dashboard.jsx (iqro #FA902C, aisar #36A7DF) */
    .btn-jilid                            { background-image: none; background-color: #FA902C; }
    .btn-jilid[data-metode="iqro"]        { background-image: none; background-color: #FA902C; }
    .btn-jilid[data-metode="iqro"]:hover  { background-image: none; background-color: #E57C17; }
    .btn-jilid[data-metode="aisar"]       { background-image: none; background-color: #36A7DF; }
    .btn-jilid[data-metode="aisar"]:hover { background-image: none; background-color: #2490C6; }
</style>`;
}

/* ============================================================
 * COMPONENT
 * ============================================================ */
export default function InputSetoran(props) {
    const {
        santriList = [], sesiList = [], daftarSurat = [],
        tanggalHariIni = '', kelasName = '', tahunAjaran = null,
        flash = {}, errors = {},
    } = props;

    const csrf = typeof document !== 'undefined'
        ? (document.querySelector('meta[name="csrf-token"]')?.content || '')
        : '';

    useEffect(() => {
        // Revisit dalam dokumen yang sama → reload penuh supaya engine JS
        // (yang mengikat DOM by-ID) mendapat lingkungan bersih.
        if (window.__INPUT_PAGE_LOADED__) {
            window.location.reload();
            return;
        }
        window.__INPUT_PAGE_LOADED__ = true;
        window.__TANGGAL_HARI_INI__ = tanggalHariIni;

        // Metode & progress per santri untuk tabel presensi di panel kanan.
        // Endpoint /api/presensi/hari-ini tidak mengirim data ini, jadi
        // disuntikkan dari props halaman dan dibaca unified-input-page.js.
        window.__SANTRI_META__ = Object.fromEntries(
            santriList.map((s) => {
                const m = String(s.target_metode || 'quran').toLowerCase();
                return [String(s.id), {
                    primary: m === 'aisar' ? `Aisar ${s.target_jilid ?? '-'}`
                           : m === 'iqro'  ? `Iqro ${s.target_jilid ?? '-'}`
                           : 'Al-Quran',
                    tone: m === 'aisar' ? 'aisar' : m === 'iqro' ? 'iqro' : 'quran',
                    hasQuran2: String(s.target_metode_2 || '').toLowerCase() === 'quran',
                    progress: Number(s.progress_persen ?? 0),
                    progress2: Number(s.progress_juz2 ?? 0),
                }];
            })
        );

        function applyMetodeForSantri(santriSelectId, metodeSelectId) {
            const sSel = document.getElementById(santriSelectId);
            const mSel = document.getElementById(metodeSelectId);
            if (!sSel || !mSel) return;

            const toDisplay = (m) => {
                const t = String(m || '').toLowerCase();
                return t === 'aisar' ? 'Aisar' : (t === 'iqro' || t === 'ummi') ? 'Iqro' : "Al-Qur'an";
            };

            const apply = () => {
                const opt = sSel.options[sSel.selectedIndex];
                if (!opt?.value) {
                    mSel.classList.remove('is-locked');
                    Array.from(mSel.options).forEach(o => { o.hidden = false; o.disabled = false; });
                    return;
                }

                const m1 = toDisplay(opt.getAttribute('data-target-metode') || 'quran');
                const m2raw = opt.getAttribute('data-target-metode-2') || '';
                const m2 = m2raw ? toDisplay(m2raw) : '';

                const allowed = [m1];
                if (m2 && m2 !== m1) allowed.push(m2);

                // Tampilkan hanya opsi yang diizinkan
                Array.from(mSel.options).forEach(o => {
                    const ok = allowed.includes(o.value);
                    o.hidden = !ok;
                    o.disabled = !ok;
                });

                mSel.value = m1; // default ke metode utama

                if (allowed.length <= 1) {
                    mSel.classList.add('is-locked');
                    mSel.setAttribute('aria-readonly', 'true');
                } else {
                    mSel.classList.remove('is-locked');
                    mSel.removeAttribute('aria-readonly');
                }

                mSel.dispatchEvent(new Event('change', { bubbles: true }));
            };

            sSel.addEventListener('change', apply);
            apply();
        }

        // jalankan setelah markup siap
        setTimeout(() => {
            applyMetodeForSantri('santriSelectCombined', 'metodeCombined');
            applyMetodeForSantri('santriSelectSetoranOnly', 'metodeSetoranOnly');
        }, 0);

        // ============================================================
        // FIX: sinkronkan data-metode tombol jilid + buang label ganda
        // - Warna tombol "Tampilkan Halaman" ikut metode aktif (Iqro/Aisar)
        // - Judul blok "Pilih Halaman ..." hanya menyisakan 1 label metode
        // ============================================================
        // Aisar hanya tersedia sampai Jilid 5; Iqro sampai 6.
        const MAKS_JILID = { iqro: 6, aisar: 5 };

        function syncJilidUI() {
            ['Combined', 'SetoranOnly'].forEach((mode) => {
                const metodeEl = document.getElementById('metode' + mode);
                if (!metodeEl) return;

                const m = String(metodeEl.value || '').toLowerCase().includes('aisar') ? 'aisar' : 'iqro';

                const btn = document.getElementById('btnJilid' + mode);
                if (btn && btn.dataset.metode !== m) btn.dataset.metode = m;

                const jilidEl = document.getElementById('iqroJilid' + mode);
                if (!jilidEl) return;

                const maks = MAKS_JILID[m];
                let perluTurun = false;

                Array.from(jilidEl.options).forEach((o) => {
                    const nomor = parseInt(o.value, 10);
                    if (!nomor) return;
                    const boleh = nomor <= maks;
                    o.hidden = !boleh;
                    o.disabled = !boleh;
                    // Pindah ke jilid maksimum kalau yang sedang terpilih
                    // justru yang baru saja disembunyikan.
                    if (!boleh && jilidEl.value === o.value) perluTurun = true;
                });

                // Select ini didaftarkan di ALL_SELECTS (unified-input-page.js),
                // jadi tampilannya adalah widget .cs-wrap. `option.hidden` tidak
                // terbaca widget — daftar <li>-nya harus disembunyikan terpisah.
                const wrap = jilidEl.closest('.cs-wrap')
                          || jilidEl.parentElement?.querySelector('.cs-wrap');

                if (wrap) {
                    wrap.querySelectorAll('.cs-list li').forEach((li) => {
                        const nomor = parseInt(
                            String(li.dataset.value ?? li.textContent).replace(/\D+/g, ''), 10
                        );
                        if (!nomor) return;
                        li.style.display = nomor <= maks ? '' : 'none';
                    });
                }

                if (perluTurun) {
                    jilidEl.value = String(maks);
                    const txt = wrap?.querySelector('.cs-btn .cs-txt');
                    if (txt) {
                        txt.textContent = `Jilid ${maks}`;
                        txt.classList.remove('ph');
                    }
                    jilidEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            ['iqroBlockCombined', 'iqroBlockSetoranOnly'].forEach((id) => {
                const p = document.querySelector('#' + id + ' > p');
                if (!p) return;
                const labels = p.querySelectorAll('.lbl-metode-jilid');
                for (let i = 1; i < labels.length; i++) labels[i].remove();
            });
        }

        ['metodeCombined', 'metodeSetoranOnly'].forEach((id) => {
            document.getElementById(id)?.addEventListener('change', () => setTimeout(syncJilidUI, 30));
        });
        setTimeout(syncJilidUI, 100);
        const jilidUiTimer = setInterval(syncJilidUI, 500);

        // ✅ Versi STABIL → browser boleh cache. Naikkan angka ini manual
        // setiap kali ada perubahan di file /public/js/*.js
        const ASSET_VERSION = '2026-11-22-1';
        const all = [...ENGINE_SCRIPTS, PAGE_SCRIPT];
        let cancelled = false;

        // ✅ Stub sementara: kalau user keburu klik mode sebelum engine siap,
        // pilihan disimpan lalu dieksekusi setelah semua script loaded.
        window.__pendingInputMode = null;
        if (typeof window.toggleMode !== 'function') {
            window.toggleMode = function () {};
            window.switchMode = function (v) { window.__pendingInputMode = v; };
        }

        // ✅ Paralel download, eksekusi tetap sesuai urutan array (async = false)
        let remaining = all.length;
        all.forEach((name) => {
            const el = document.createElement('script');
            el.src = `/js/${name}?v=${ASSET_VERSION}`;
            el.async = false;               // ← kunci: download paralel, run berurutan
            el.dataset.inputPage = name;
            el.onload = el.onerror = () => {
                if (el.onerror && !el.dataset.ok) el.dataset.ok = '1';
                remaining--;
                if (remaining === 0 && !cancelled) {
                    document.dispatchEvent(new Event('DOMContentLoaded'));

                    // Eksekusi klik mode yang tertunda (kalau ada)
                    if (window.__pendingInputMode && typeof window.switchMode === 'function') {
                        const radio = document.querySelector(
                            `input[name="input_mode"][value="${window.__pendingInputMode}"]`
                        );
                        if (radio) radio.checked = true;
                        window.switchMode(window.__pendingInputMode);
                        window.__pendingInputMode = null;
                    }
                }
            };
            document.body.appendChild(el);
        });

        return () => {
            cancelled = true;
            clearInterval(jilidUiTimer);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const html = buildMarkup({ santriList, sesiList, daftarSurat, tanggalHariIni, kelasName, tahunAjaran, csrf, flash, errors });

    return (
        <GuruAuthenticatedLayout>
            <Head title="Input Presensi & Setoran" />
            <div dangerouslySetInnerHTML={{ __html: html }} />
        </GuruAuthenticatedLayout>
    );
}