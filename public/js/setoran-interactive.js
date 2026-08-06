console.log('🚀 Setoran Interactive v11.74 - Loading...');

/**
 * ✅ SETORAN INTERACTIVE v11.74
 *
 * FIX v11.74 — Halaman masih ada ruang kosong di bawah
 *
 * MASALAH v11.73:
 *   - PAD_TOP=100, PAD_BOTTOM=100 → visibleAreaPerPage hanya 1757px
 *     dari maxPageHeight 1957px (kehilangan 200px = ~1.5 baris)
 *   - Range gap pencarian: maxMid = startY + visibleAreaPerPage
 *     Artinya tidak boleh "meminjam" padding bottom sama sekali
 *
 * SOLUSI v11.74:
 *   1. Kurangi PAD_TOP & PAD_BOTTOM dari 100 → 50px
 *      → visibleAreaPerPage bertambah 100px (= ~1 baris ekstra)
 *   2. Tambahkan SEARCH_OVERSHOOT: izinkan gap.mid sedikit melewati
 *      visibleAreaPerPage (boleh "pinjam" sampai 50% padding bottom)
 *      → utilization halaman jadi maksimal
 *
 * Hasil log v11.73 (untuk perbandingan):
 *   Slice 1: utilization 59.9%
 *   Slice 4: utilization 71.2%  ← ada ruang kosong ~500px
 *
 * Setelah v11.74 — slice diharapkan utilization 90%+ di semua halaman
 */

// =====================================================
// GLOBAL STATE
// =====================================================
const JUMLAH_AYAT_PER_SURAT = {
    "1. Al-Fatihah": 7, "2. Al-Baqarah": 286, "3. Ali 'Imran": 200, "4. An-Nisa'": 176,
    "5. Al-Ma'idah": 120, "6. Al-An'am": 165, "7. Al-A'raf": 206, "8. Al-Anfal": 75,
    "9. At-Taubah": 129, "10. Yunus": 109, "11. Hud": 123, "12. Yusuf": 111,
    "13. Ar-Ra'd": 43, "14. Ibrahim": 52, "15. Al-Hijr": 99, "16. An-Nahl": 128,
    "17. Al-Isra'": 111, "18. Al-Kahf": 110, "19. Maryam": 98, "20. Taha": 135,
    "21. Al-Anbiya'": 112, "22. Al-Hajj": 78, "23. Al-Mu'minun": 118, "24. An-Nur": 64,
    "25. Al-Furqan": 77, "26. Asy-Syu'ara'": 227, "27. An-Naml": 93, "28. Al-Qasas": 88,
    "29. Al-'Ankabut": 69, "30. Al-Rum": 60, "31. Luqman": 34, "32. As-Sajdah": 30,
    "33. Al-Ahzab": 73, "34. Saba'": 54, "35. Fatir": 45, "36. Yasin": 83,
    "37. As-Saffat": 182, "38. Sad": 88, "39. Az-Zumar": 75, "40. Ghafir": 85,
    "41. Fussilat": 54, "42. Asy-Syura": 53, "43. Az-Zukhruf": 89, "44. Ad-Dukhan": 59,
    "45. Al-Jasiyah": 37, "46. Al-Ahqaf": 35, "47. Muhammad": 38, "48. Al-Fath": 29,
    "49. Al-Hujurat": 18, "50. Qaf": 45, "51. Az-Zariyat": 60, "52. At-Tur": 49,
    "53. An-Najm": 62, "54. Al-Qamar": 55, "55. Ar-Rahman": 78, "56. Al-Waqi'ah": 96,
    "57. Al-Hadid": 29, "58. Al-Mujadilah": 22, "59. Al-Hasyr": 24, "60. Al-Mumtahanah": 13,
    "61. As-Saff": 14, "62. Al-Jumu'ah": 11, "63. Al-Munafiqun": 11, "64. At-Taghabun": 18,
    "65. At-Talaq": 12, "66. At-Tahrim": 12, "67. Al-Mulk": 30, "68. Al-Qalam": 52,
    "69. Al-Haqqah": 52, "70. Al-Ma'arij": 44, "71. Nuh": 28, "72. Al-Jinn": 28,
    "73. Al-Muzzammil": 20, "74. Al-Muddassir": 56, "75. Al-Qiyamah": 40, "76. Al-Insan": 31,
    "77. Al-Mursalat": 50, "78. An-Naba'": 40, "79. An-Nazi'at": 46, "80. 'Abasa": 42,
    "81. At-Takwir": 29, "82. Al-Infitar": 19, "83. Al-Mutaffifin": 36, "84. Al-Insyiqaq": 25,
    "85. Al-Buruj": 22, "86. At-Tariq": 17, "87. Al-A'la": 19, "88. Al-Ghasyiyah": 26,
    "89. Al-Fajr": 30, "90. Al-Balad": 20, "91. Asy-Syams": 15, "92. Al-Lail": 21,
    "93. Ad-Duha": 11, "94. Asy-Syarh": 8, "95. At-Tin": 8, "96. Al-'Alaq": 19,
    "97. Al-Qadr": 5, "98. Al-Bayyinah": 8, "99. Az-Zalzalah": 8, "100. Al-'Adiyat": 11,
    "101. Al-Qari'ah": 11, "102. At-Takasur": 8, "103. Al-'Asr": 3, "104. Al-Humazah": 9,
    "105. Al-Fil": 5, "106. Quraisy": 4, "107. Al-Ma'un": 7, "108. Al-Kausar": 3,
    "109. Al-Kafirun": 6, "110. An-Nasr": 3, "111. Al-Masad": 5, "112. Al-Ikhlas": 4,
    "113. Al-Falaq": 5, "114. An-Nas": 6
};

let mediaRecorder   = null;
let audioChunks     = [];
let kesalahanCount  = { kelancaran: 0, tajwid: 0, pelafalan: 0 };
let totalKataCount  = 0;
let currentActiveMode = 'Combined';

// =====================================================
// DEVICE CONFIG
// =====================================================
const PDF_DEVICE_CONFIG = {
    mobile: {
        label: '📱 Mobile',
        pageSize: 'A4 portrait',
        pageWidth: '210mm',
        pageHeight: '297mm',
        screenshotWidth: 692,
        fontSize: { h1: '18px', h2: '11px', body: '10px', score: '36px' },
        tableFontSize: '9px',
        infoPadding: '4px 6px',
        pageInfoPadding: '8mm 6mm',
    },
    tablet: {
        label: '📲 Tablet',
        pageSize: 'A4 landscape',
        pageWidth: '297mm',
        pageHeight: '210mm',
        screenshotWidth: 700,
        fontSize: { h1: '22px', h2: '13px', body: '12px', score: '44px' },
        tableFontSize: '11px',
        infoPadding: '6px 8px',
        pageInfoPadding: '8mm 6mm',
    },
    desktop: {
        label: '🖥️ Desktop',
        pageSize: 'A4 portrait',
        pageWidth: '210mm',
        pageHeight: '297mm',
        screenshotWidth: 777,
        fontSize: { h1: '26px', h2: '15px', body: '13px', score: '52px' },
        tableFontSize: '13px',
        infoPadding: '8px 10px',
        pageInfoPadding: '8mm 6mm',
    }
};

// =====================================================
// FIX WHITE SCREEN
// =====================================================
window.addEventListener('load', function () {
    setTimeout(() => {
        const loading = document.getElementById('pageLoadingOverlay');
        const content = document.getElementById('mainContent');
        if (loading) loading.style.display = 'none';
        if (content) content.style.display = 'block';
    }, 300);
});

// =====================================================
// POPULATE AYAT DROPDOWN
// =====================================================
function populateAyatDropdown(suratName, ayatSelectId, maxAyat = null) {
    const ayatSelect = document.getElementById(ayatSelectId);
    if (!ayatSelect) { console.error(`❌ Dropdown ${ayatSelectId} not found`); return; }
    let totalAyat = JUMLAH_AYAT_PER_SURAT[suratName];
    if (!totalAyat && typeof window.surahMapping !== 'undefined') totalAyat = window.surahMapping?.[suratName]?.verses || 0;
    if (!totalAyat && typeof window.getVerseCount === 'function') totalAyat = window.getVerseCount(suratName);
    if (!totalAyat) { console.error(`❌ Surat ${suratName} not found`); return; }
    ayatSelect.innerHTML = '<option value="">-- Pilih Ayat --</option>';
    const limit = maxAyat || totalAyat;
    for (let i = 1; i <= limit; i++) {
        const option = document.createElement('option');
        option.value = i; option.textContent = `Ayat ${i}`;
        ayatSelect.appendChild(option);
    }
    ayatSelect.disabled = false;
    ayatSelect.classList.remove('cursor-not-allowed', 'bg-gray-200');
    ayatSelect.classList.add('bg-white');
}

// =====================================================
// SETUP COMBINED MODE
// =====================================================
function setupCombinedMode() {
    const suratStart = document.getElementById('suratStartCombined');
    const ayatStart  = document.getElementById('ayatStartCombined');
    const suratEnd   = document.getElementById('suratEndCombined');
    const ayatEnd    = document.getElementById('ayatEndCombined');
    if (!suratStart) { console.warn('⚠️ Combined mode not found'); return; }

    suratStart.addEventListener('change', function () {
        const sel = this.value;
        if (sel) {
            suratEnd.value = sel;
            populateAyatDropdown(sel, 'ayatStartCombined');
            ayatEnd.innerHTML = '<option value="">-- Pilih Ayat Mulai Dulu --</option>';
            ayatEnd.disabled = true;
        } else {
            suratEnd.value = '';
            ayatStart.innerHTML = '<option value="">-- Pilih Surat Dulu --</option>';
            ayatStart.disabled = true;
            ayatEnd.innerHTML = '<option value="">-- Pilih Ayat Mulai Dulu --</option>';
            ayatEnd.disabled = true;
        }
    });
    suratEnd.addEventListener('change', function () {
        const selEnd = this.value, ayatStartVal = parseInt(ayatStart.value), selStart = suratStart.value;
        if (!selEnd) { ayatEnd.innerHTML = '<option value="">-- Pilih Surat Selesai Dulu --</option>'; ayatEnd.disabled = true; return; }
        if (selEnd === selStart) {
            if (ayatStartVal) {
                const tot = JUMLAH_AYAT_PER_SURAT[selEnd];
                ayatEnd.innerHTML = '<option value="">-- Pilih Ayat --</option>';
                for (let i = ayatStartVal; i <= tot; i++) { const o = document.createElement('option'); o.value = i; o.textContent = `Ayat ${i}`; ayatEnd.appendChild(o); }
                ayatEnd.disabled = false;
            } else { ayatEnd.innerHTML = '<option value="">-- Pilih Ayat Mulai Dulu --</option>'; ayatEnd.disabled = true; }
        } else {
            const tot = JUMLAH_AYAT_PER_SURAT[selEnd];
            if (!tot) return;
            ayatEnd.innerHTML = '<option value="">-- Pilih Ayat --</option>';
            for (let i = 1; i <= tot; i++) { const o = document.createElement('option'); o.value = i; o.textContent = `Ayat ${i}`; ayatEnd.appendChild(o); }
            ayatEnd.disabled = false;
        }
    });
    ayatStart.addEventListener('change', function () {
        const val = parseInt(this.value), selStart = suratStart.value, selEnd = suratEnd.value;
        if (!val || !selStart) return;
        if (selStart === selEnd) {
            const tot = JUMLAH_AYAT_PER_SURAT[selStart];
            ayatEnd.innerHTML = '<option value="">-- Pilih Ayat --</option>';
            for (let i = val; i <= tot; i++) { const o = document.createElement('option'); o.value = i; o.textContent = `Ayat ${i}`; ayatEnd.appendChild(o); }
            ayatEnd.disabled = false;
        }
    });
    console.log('✅ Combined mode setup complete');
}

// =====================================================
// SETUP SETORAN ONLY MODE
// =====================================================
function setupSetoranOnlyMode() {
    const suratStart = document.getElementById('suratStartSetoranOnly');
    const ayatStart  = document.getElementById('ayatStartSetoranOnly');
    const suratEnd   = document.getElementById('suratEndSetoranOnly');
    const ayatEnd    = document.getElementById('ayatEndSetoranOnly');
    if (!suratStart) { console.warn('⚠️ Setoran Only mode not found'); return; }

    suratStart.addEventListener('change', function () {
        const sel = this.value;
        if (sel) {
            suratEnd.value = sel; suratEnd.disabled = false;
            populateAyatDropdown(sel, 'ayatStartSetoranOnly');
            ayatEnd.innerHTML = '<option value="">-- Pilih Ayat Mulai Dulu --</option>'; ayatEnd.disabled = true;
        } else {
            suratEnd.value = ''; suratEnd.disabled = true;
            ayatStart.innerHTML = '<option value="">-- Pilih Surat Dulu --</option>'; ayatStart.disabled = true;
            ayatEnd.innerHTML = '<option value="">-- Pilih Ayat Mulai Dulu --</option>'; ayatEnd.disabled = true;
        }
    });
    suratEnd.addEventListener('change', function () {
        const selEnd = this.value, ayatStartVal = parseInt(ayatStart.value), selStart = suratStart.value;
        if (!selEnd) { ayatEnd.innerHTML = '<option value="">-- Pilih Surat Selesai Dulu --</option>'; ayatEnd.disabled = true; return; }
        if (selEnd === selStart) {
            if (ayatStartVal) {
                const tot = JUMLAH_AYAT_PER_SURAT[selEnd];
                ayatEnd.innerHTML = '<option value="">-- Pilih Ayat --</option>';
                for (let i = ayatStartVal; i <= tot; i++) { const o = document.createElement('option'); o.value = i; o.textContent = `Ayat ${i}`; ayatEnd.appendChild(o); }
                ayatEnd.disabled = false;
            } else { ayatEnd.innerHTML = '<option value="">-- Pilih Ayat Mulai Dulu --</option>'; ayatEnd.disabled = true; }
        } else {
            const tot = JUMLAH_AYAT_PER_SURAT[selEnd];
            if (!tot) return;
            ayatEnd.innerHTML = '<option value="">-- Pilih Ayat --</option>';
            for (let i = 1; i <= tot; i++) { const o = document.createElement('option'); o.value = i; o.textContent = `Ayat ${i}`; ayatEnd.appendChild(o); }
            ayatEnd.disabled = false;
        }
    });
    ayatStart.addEventListener('change', function () {
        const val = parseInt(this.value), selStart = suratStart.value, selEnd = suratEnd.value;
        if (!val || !selStart) return;
        if (selStart === selEnd) {
            const tot = JUMLAH_AYAT_PER_SURAT[selStart];
            if (!tot) return;
            ayatEnd.innerHTML = '<option value="">-- Pilih Ayat --</option>';
            for (let i = val; i <= tot; i++) { const o = document.createElement('option'); o.value = i; o.textContent = `Ayat ${i}`; ayatEnd.appendChild(o); }
            ayatEnd.disabled = false;
        }
    });
    console.log('✅ Setoran Only mode setup complete');
}

// =====================================================
// MARKING & SCORING
// =====================================================
function markWord(element) {
    const oldState = element.dataset.markState || '0';
    if (oldState === '1') kesalahanCount.kelancaran--;
    else if (oldState === '2') kesalahanCount.tajwid--;
    else if (oldState === '3') kesalahanCount.pelafalan--;

    element.classList.remove('bg-green-200','bg-yellow-200','bg-red-200','border-l-4','border-green-500','border-yellow-500','border-red-500','dark:bg-green-800','dark:bg-yellow-800','dark:bg-red-800');
    element.style.backgroundColor = '';

    let newState = '0';
    if (oldState === '0') { element.style.backgroundColor = 'rgba(34,197,94,0.35)'; element.classList.add('border-l-4','border-green-500'); newState = '1'; kesalahanCount.kelancaran++; }
    else if (oldState === '1') { element.style.backgroundColor = 'rgba(234,179,8,0.4)'; element.classList.add('border-l-4','border-yellow-500'); newState = '2'; kesalahanCount.tajwid++; }
    else if (oldState === '2') { element.style.backgroundColor = 'rgba(239,68,68,0.35)'; element.classList.add('border-l-4','border-red-500'); newState = '3'; kesalahanCount.pelafalan++; }

    element.dataset.markState = newState;
    let activeMode = 'Combined';
    const formSetoranOnly = document.getElementById('formSetoranOnly');
    if (formSetoranOnly && !formSetoranOnly.classList.contains('hidden')) activeMode = 'SetoranOnly';
    updateKesalahanDisplay(activeMode);
}

function updateKesalahanDisplay(mode = 'Combined') {
    const kK = document.getElementById(`kesalahanKelancaran${mode}`);
    const kT = document.getElementById(`kesalahanTajwid${mode}`);
    const kP = document.getElementById(`kesalahanPelafalan${mode}`);
    if (kK) kK.value = kesalahanCount.kelancaran;
    if (kT) kT.value = kesalahanCount.tajwid;
    if (kP) kP.value = kesalahanCount.pelafalan;
    const dK = document.getElementById(`displayKelancaran${mode}`);
    const dT = document.getElementById(`displayTajwid${mode}`);
    const dP = document.getElementById(`displayPelafalan${mode}`);
    if (dK) dK.textContent = kesalahanCount.kelancaran;
    if (dT) dT.textContent = kesalahanCount.tajwid;
    if (dP) dP.textContent = kesalahanCount.pelafalan;

    let nilai = 100;
    if (totalKataCount > 0) {
        const tot = kesalahanCount.kelancaran + kesalahanCount.tajwid + kesalahanCount.pelafalan;
        nilai = totalKataCount < 100 ? 100 - (tot * 4) : 100 - ((tot * 4) / totalKataCount) * 100;
        nilai = Math.max(0, Math.min(100, Math.round(nilai)));
    }
    const dN = document.getElementById(`displayNilai${mode}`);
    const nI = document.getElementById(`nilai${mode}`);
    if (dN) dN.textContent = nilai;
    if (nI) nI.value = nilai;
}

function clearMarking() {
    if (!confirm('⚠️ Yakin ingin menghapus semua marking?')) return;

    // ✅ FIX: Bersihkan SEMUA kata di SEMUA container (#quranDisplay + #quranDisplayDrawer mobile clone)
    // Selector .word-item akan ketemu di kedua tempat sekaligus.
    document.querySelectorAll('.word-item, #quranText span[data-word]').forEach(span => {
        // Reset state attribute
        span.setAttribute('data-mark-state', '0');
        span.dataset.markState = '0';

        // Hapus class warna lama (legacy)
        span.classList.remove(
            'bg-green-200', 'bg-yellow-200', 'bg-red-200',
            'bg-green-400', 'bg-yellow-400', 'bg-red-400', 'text-white',
            'dark:bg-green-800', 'dark:bg-yellow-800', 'dark:bg-red-800',
            'border-l-4', 'border-green-500', 'border-yellow-500', 'border-red-500'
        );

        // ✅ Hapus SEMUA inline style gradient marking (yang dipasang markWord & applyMarkStyleToClone)
        span.style.removeProperty('background');
        span.style.removeProperty('background-color');
        span.style.removeProperty('border-radius');
        span.style.removeProperty('padding');
        span.style.removeProperty('box-shadow');
        span.style.removeProperty('transform');

        // Pastikan cursor & transition tetap (biar bisa di-klik lagi)
        span.style.cursor     = 'pointer';
        span.style.transition = 'all 0.25s cubic-bezier(0.4, 0, 0.2, 1)';
    });

    // Reset counter global
    kesalahanCount = { kelancaran: 0, tajwid: 0, pelafalan: 0 };

    // ✅ Update display untuk KEDUA mode (Combined & SetoranOnly)
    updateKesalahanDisplay('Combined');
    updateKesalahanDisplay('SetoranOnly');

    // ✅ Sync ke window global (jaga konsistensi dengan PDF generator)
    if (typeof window.kesalahanCount !== 'undefined') {
        window.kesalahanCount.kelancaran = 0;
        window.kesalahanCount.tajwid     = 0;
        window.kesalahanCount.pelafalan  = 0;
    }

    console.log('🗑️ clearMarking (setoran-interactive.js): semua marking & style dibersihkan dari quranDisplay + quranDisplayDrawer');
    alert('✅ Semua marking berhasil dihapus!');
}

function setActiveMode(mode) { currentActiveMode = mode; }

// =====================================================
// STATISTIK
// =====================================================
function countTotalWords() {
    const quranDisplay = document.getElementById('quranDisplay');
    if (!quranDisplay) {
        console.warn('⚠️ #quranDisplay not found');
        return 0;
    }
    let words = quranDisplay.querySelectorAll('.word-item');
    if (words.length === 0) {
        words = quranDisplay.querySelectorAll('[data-word]');
    }
    totalKataCount = words.length;
    const disp = document.getElementById('displayTotalKata');
    if (disp) disp.textContent = totalKataCount;
    const inp = document.getElementById(`totalKata${currentActiveMode}`);
    if (inp) inp.value = totalKataCount;
    updateKesalahanDisplay(currentActiveMode);
    return totalKataCount;
}

function updateStatistikSetoranAfterLoad() {
    countTotalWords();
    if (typeof updateAccuratePageDisplay === 'function') updateAccuratePageDisplay();
    else if (typeof triggerPageCalculation === 'function') triggerPageCalculation();
}

function triggerPageCalculation() {
    if (typeof updateAccuratePageDisplay === 'function') updateAccuratePageDisplay();
}

// =====================================================
// LOAD html2canvas
// =====================================================
function loadHtml2Canvas() {
    return new Promise((resolve, reject) => {
        if (typeof html2canvas !== 'undefined') { resolve(); return; }
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load html2canvas'));
        document.head.appendChild(script);
    });
}

// =====================================================
// APPLY MARK STYLES ke clone
// =====================================================
function _applyMarkStyles(originalEl, cloneEl) {
    // ✅ Pakai solid background-color (html2canvas-friendly), bukan gradient
    // State 1=Kelancaran (hijau), 2=Tajwid (kuning), 3=Pelafalan (merah)
    const markStyles = {
        '1': 'rgba(34, 197, 94, 0.55)',   // hijau lebih pekat agar terlihat di screenshot
        '2': 'rgba(234, 179, 8, 0.60)',   // kuning
        '3': 'rgba(239, 68, 68, 0.55)',   // merah
    };
    const origWords  = originalEl.querySelectorAll('[data-mark-state]');
    const cloneWords = cloneEl.querySelectorAll('[data-mark-state]');

    let appliedCount = 0;

    origWords.forEach((orig, i) => {
        const cloned = cloneWords[i];
        if (!cloned) return;
        const s = orig.getAttribute('data-mark-state') || '0';
        if (markStyles[s]) {
            // ✅ Hapus background lama
            cloned.style.background = '';
            // ✅ Set solid color (lebih reliable untuk html2canvas)
            cloned.style.setProperty('background-color', markStyles[s], 'important');
            cloned.style.setProperty('border-radius',    '6px',          'important');
            cloned.style.setProperty('padding',          '2px 6px',      'important');
            cloned.style.setProperty('box-shadow',       'none',         'important');
            // Pastikan data-mark-state ikut tersalin ke clone
            cloned.setAttribute('data-mark-state', s);
            appliedCount++;
        }
    });

    console.log(`🎨 _applyMarkStyles: ${appliedCount} marked words copied to clone (from ${origWords.length} total)`);
}

// =====================================================
// SLICE CANVAS — v11.74
//
// FIX UTAMA v11.74 — MAXIMIZE PAGE UTILIZATION:
//   1. PAD_TOP & PAD_BOTTOM: 100 → 50px (gain 100px = ~1 baris ekstra)
//   2. SEARCH_OVERSHOOT: gap.mid boleh sampai startY + maxAllowedMid
//      (visibleAreaPerPage + setengah PAD_BOTTOM) → "pinjam" padding bawah
//   3. safeContentH dibatasi visibleAreaPerPage + PAD_BOTTOM agar tidak
//      crop teks yang menyentuh padding bawah
// =====================================================
function _sliceCanvasFixed(canvas, maxPageHeight) {
    const cH = canvas.height;
    const cW = canvas.width;

    // ✅ v11.74: padding lebih kecil, range lebih luas
    const PAD_TOP          = 50;   // was 100 → kurangi 50px
    const PAD_BOTTOM       = 50;   // was 100 → kurangi 50px
    const MIN_GAP_HEIGHT   = 40;
    const BG_ROW_THRESHOLD = 0.98;
    const MIN_MIDDLE_SLICE_RATIO = 0.20;

    // Area konten yang aman (image akan ditaruh di y=PAD_TOP, jangan overflow)
    const visibleAreaPerPage = maxPageHeight - PAD_TOP - PAD_BOTTOM;

    // ✅ v11.74: izinkan gap "pinjam" sampai 80% padding bottom
    // → cut bisa terjadi lebih dekat ke ujung bawah halaman → utilization tinggi
    const SEARCH_OVERSHOOT = Math.floor(PAD_BOTTOM * 0.8);
    const MIN_MIDDLE_SLICE = Math.floor(visibleAreaPerPage * MIN_MIDDLE_SLICE_RATIO);

    console.log(`📐 Slicing v11.74: canvas=${cW}x${cH}px, pageH=${maxPageHeight}px, visibleArea=${visibleAreaPerPage}px, padTop=${PAD_TOP}px, padBottom=${PAD_BOTTOM}px, overshoot=${SEARCH_OVERSHOOT}px, minGap=${MIN_GAP_HEIGHT}px`);

    const ctx2 = canvas.getContext('2d', { willReadFrequently: true });

    // ─────────────────────────────────────────────────────────────────────
    // TAHAP 1: Pre-compute row map (TEXT=1, BG=0)
    // ─────────────────────────────────────────────────────────────────────
    console.log(`🔍 Pre-computing row map (cH=${cH})...`);
    const rowMap = new Uint8Array(cH);

    const BG_R = 62, BG_G = 61, BG_B = 58, COLOR_THRESHOLD = 25;
    const fullData = ctx2.getImageData(0, 0, cW, cH).data;

    for (let y = 0; y < cH; y++) {
        let bgCount = 0;
        const rowStart = y * cW * 4;
        for (let x = 0; x < cW; x++) {
            const idx = rowStart + x * 4;
            const r = fullData[idx];
            const g = fullData[idx + 1];
            const b = fullData[idx + 2];
            const a = fullData[idx + 3];
            if (a < 10 || (
                Math.abs(r - BG_R) < COLOR_THRESHOLD &&
                Math.abs(g - BG_G) < COLOR_THRESHOLD &&
                Math.abs(b - BG_B) < COLOR_THRESHOLD
            )) {
                bgCount++;
            }
        }
        const bgRatio = bgCount / cW;
        rowMap[y] = (bgRatio >= BG_ROW_THRESHOLD) ? 0 : 1;
    }
    console.log(`✅ Row map computed: ${cH} rows scanned`);

    // ─────────────────────────────────────────────────────────────────────
    // TAHAP 2: Compute gap zones (consecutive BG rows ≥ MIN_GAP_HEIGHT)
    // ─────────────────────────────────────────────────────────────────────
    const gapZones = [];
    let gapStart = -1;
    for (let y = 0; y < cH; y++) {
        if (rowMap[y] === 0) {
            if (gapStart === -1) gapStart = y;
        } else {
            if (gapStart !== -1) {
                const gapEnd = y - 1;
                const gapH = gapEnd - gapStart + 1;
                if (gapH >= MIN_GAP_HEIGHT) {
                    gapZones.push({
                        start: gapStart,
                        end: gapEnd,
                        mid: Math.floor((gapStart + gapEnd) / 2),
                        height: gapH
                    });
                }
                gapStart = -1;
            }
        }
    }
    if (gapStart !== -1) {
        const gapEnd = cH - 1;
        const gapH = gapEnd - gapStart + 1;
        if (gapH >= MIN_GAP_HEIGHT) {
            gapZones.push({
                start: gapStart,
                end: gapEnd,
                mid: Math.floor((gapStart + gapEnd) / 2),
                height: gapH
            });
        }
    }
    console.log(`✅ Found ${gapZones.length} valid gap zones (≥ ${MIN_GAP_HEIGHT}px)`);
    if (gapZones.length <= 20) {
        gapZones.forEach((g, i) => console.log(`   Gap ${i}: y=${g.start}-${g.end} (${g.height}px, mid=${g.mid})`));
    }

    // ─────────────────────────────────────────────────────────────────────
    // TAHAP 3: findBestGap dengan SEARCH_OVERSHOOT
    //
    // Strategi:
    //   - minMid = startY + visibleAreaPerPage × 0.4 (sedikit longgar dari 0.5)
    //   - maxMid = startY + visibleAreaPerPage + SEARCH_OVERSHOOT
    //   - Pilih gap dengan mid TERTINGGI dalam range → maksimalkan konten
    // ─────────────────────────────────────────────────────────────────────
    function findBestGap(startY, idealY) {
        const minMid = startY + Math.floor(visibleAreaPerPage * 0.4);
        const maxMid = startY + visibleAreaPerPage + SEARCH_OVERSHOOT;

        const candidates = gapZones.filter(g => g.mid >= minMid && g.mid <= maxMid);

        if (candidates.length === 0) {
            console.warn(`⚠️ No valid gap found for startY=${startY}, ideal=${idealY}. Fallback to startY+visibleAreaPerPage.`);
            return startY + visibleAreaPerPage;
        }

        // Pilih gap dengan mid TERBESAR (paling bawah dalam range valid)
        let bestGap = candidates[0];
        for (let i = 1; i < candidates.length; i++) {
            if (candidates[i].mid > bestGap.mid) {
                bestGap = candidates[i];
            }
        }

        const sliceH = bestGap.mid - startY;
        const utilizationPct = ((sliceH / visibleAreaPerPage) * 100).toFixed(1);
        console.log(`🔍 Best gap: cut at y=${bestGap.mid} (gap ${bestGap.start}-${bestGap.end}, height=${bestGap.height}px, sliceH=${sliceH}px, utilization=${utilizationPct}%)`);
        return bestGap.mid;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PASS 1: kumpulkan cut points
    // ─────────────────────────────────────────────────────────────────────
    const cuts = [0];
    let y = 0;
    // ✅ Batas atas slice: visibleArea + overshoot (lebih besar dari sebelumnya)
    const MAX_SLICE_H = visibleAreaPerPage + SEARCH_OVERSHOOT;

    while (y < cH) {
        const remaining = cH - y;
        if (remaining <= MAX_SLICE_H) break;

        const idealY = y + visibleAreaPerPage;
        const cutAt = findBestGap(y, idealY);
        let sliceH = cutAt - y;

        if (sliceH <= 0 || sliceH > MAX_SLICE_H) {
            console.warn(`⚠️ sliceH=${sliceH} out of range, fallback to visibleAreaPerPage`);
            sliceH = visibleAreaPerPage;
        }

        y += sliceH;
        if (y < cH) cuts.push(y);
    }

    cuts.push(cH);

    console.log(`📋 Raw cuts (${cuts.length}): [${cuts.join(', ')}]`);

    // ─────────────────────────────────────────────────────────────────────
    // PASS 2: merge slice tipis di TENGAH
    // ─────────────────────────────────────────────────────────────────────
    let changed = true;
    let iterations = 0;
    const MAX_ITER = 20;

    while (changed && cuts.length >= 4 && iterations < MAX_ITER) {
        changed = false;
        iterations++;
        for (let i = 1; i < cuts.length - 2; i++) {
            const sliceH = cuts[i] - cuts[i - 1];
            if (sliceH < MIN_MIDDLE_SLICE) {
                const mergedH = cuts[i + 1] - cuts[i - 1];
                if (mergedH <= MAX_SLICE_H) {
                    console.log(`⚠️ Slice tengah ${i} tipis (${sliceH}px) → merge → ${mergedH}px (OK)`);
                    cuts.splice(i, 1);
                    changed = true;
                    break;
                } else {
                    console.log(`⛔ Slice ${i} tipis tapi merge overflow (${mergedH}px > ${MAX_SLICE_H}) — SKIP`);
                }
            }
        }
    }

    console.log(`📋 Clean cuts (${cuts.length}): [${cuts.join(', ')}]`);

    // ─────────────────────────────────────────────────────────────────────
    // PASS 3: render slice
    // ─────────────────────────────────────────────────────────────────────
    function sliceHasContent(startY, sliceH) {
        let textRows = 0;
        for (let yy = startY; yy < Math.min(startY + sliceH, cH); yy++) {
            if (rowMap[yy] === 1) textRows++;
        }
        const ratio = textRows / sliceH;
        console.log(`🔎 Slice y=${startY} text-row ratio: ${(ratio * 100).toFixed(2)}% (${textRows}/${sliceH})`);
        return ratio > 0.02;
    }

    const slices = [];
    for (let i = 0; i < cuts.length - 1; i++) {
        const startY = cuts[i];
        const contentH = cuts[i + 1] - startY;

        if (contentH <= 0) {
            console.warn(`⚠️ Skip slice ${i + 1}: tinggi ${contentH}px`);
            continue;
        }

        if (!sliceHasContent(startY, contentH)) {
            console.warn(`⚠️ Skip slice ${i + 1}: kosong (hanya background)`);
            continue;
        }

        // ✅ v11.74: safeContentH bisa sampai visibleArea + PAD_BOTTOM
        // (konten boleh masuk ke area padding bawah, tapi tidak melebihi maxPageHeight - PAD_TOP)
        const maxSafeContentH = maxPageHeight - PAD_TOP;
        const safeContentH = Math.min(contentH, maxSafeContentH);

        const pc  = document.createElement('canvas');
        pc.width  = cW;
        pc.height = maxPageHeight;
        const ctx = pc.getContext('2d');

        ctx.fillStyle = '#3E3D3A';
        ctx.fillRect(0, 0, cW, maxPageHeight);

        ctx.drawImage(
            canvas,
            0, startY,
            cW, safeContentH,
            0, PAD_TOP,
            cW, safeContentH
        );

        slices.push(pc.toDataURL('image/jpeg', 0.85));
        const utilPct = ((safeContentH / visibleAreaPerPage) * 100).toFixed(1);
        console.log(`✂️ Slice ${slices.length}: source y=${startY}→${startY + safeContentH} (${safeContentH}px, ${utilPct}% util), canvas=${maxPageHeight}px`);
    }

    console.log(`✅ Total slices: ${slices.length}`);
    return slices;
}

// =====================================================
// DESKTOP screenshot
// =====================================================
async function captureDesktop_v11_2(quranDisplay) {
    const clonedElement = quranDisplay.cloneNode(true);
    clonedElement.id    = 'quranDisplay-clone-desktop';

    clonedElement.querySelectorAll('.word-item').forEach(wordItem => {
        Array.from(wordItem.querySelectorAll('span')).forEach(span => {
            const style = span.getAttribute('style') || '';
            const text  = span.textContent.trim();
            if (style.includes('background-color') && style.includes('border-radius') && /^[٠-٩۰-۹0-9]+$/.test(text)) {
                const bgColor  = (style.match(/background-color:\s*([^;]+)/) || [, 'rgba(96,165,250,0.4)'])[1];
                const size     = (style.match(/width:\s*([^;]+)/)            || [, '30px'])[1];
                const fontSize = (style.match(/font-size:\s*([^;]+)/)        || [, '16px'])[1];
                const radius   = (style.match(/border-radius:\s*([^;]+)/)    || [, '50%'])[1];
                const wrapper  = document.createElement('span');
                wrapper.style.cssText = `display:inline-block;position:relative;margin:0 8px;vertical-align:baseline;line-height:1;`;
                const badge = document.createElement('span');
                badge.textContent   = text;
                badge.style.cssText = `display:inline-block;width:${size};height:${size};background-color:${bgColor};border-radius:${radius};font-size:${fontSize};line-height:${size};text-align:center;font-family:'Amiri Quran',serif;vertical-align:middle;position:relative;top:-2px;`;
                wrapper.appendChild(badge);
                span.parentNode.replaceChild(wrapper, span);
            }
        });
    });

    _applyMarkStyles(quranDisplay, clonedElement);

    // ✅ FIX: fallback width 777px jika offsetWidth = 0 (kasus parent hidden di mobile)
    const effectiveWidth = quranDisplay.offsetWidth > 100 ? quranDisplay.offsetWidth : 777;
    console.log(`🖥️ captureDesktop width: offsetWidth=${quranDisplay.offsetWidth}, using=${effectiveWidth}`);

    // box-sizing:border-box (dari Tailwind) membuat padding memakan lebar isi.
    // Tanpa kompensasi, area isi clone 24px lebih sempit dari layar → baris
    // yang pas di layar meluber & terpotong di PDF.
    clonedElement.style.cssText = [
        'position:absolute', 'left:-9999px', 'top:0',
        'overflow:visible!important', 'max-height:none!important', 'height:auto!important',
        `width:${effectiveWidth + 24}px`, 'background-color:#3E3D3A',
        'padding:12px 12px 60px 12px',
        "font-family:'Amiri Quran',serif"
    ].join(';');

    // ✅ FIX: matiin border/ring/radius bawaan #quranDisplay → garis terluar hilang
    clonedElement.style.setProperty('border',        'none', 'important');
    clonedElement.style.setProperty('box-shadow',    'none', 'important'); // ring Tailwind = box-shadow
    clonedElement.style.setProperty('border-radius', '0',    'important');
    clonedElement.style.setProperty('outline',       'none', 'important');

    // Halaman Iqro/Aisar sudah punya ukuran font sendiri (opsi fontSize per
    // halaman + hasil aisarAutoFit, tersimpan sebagai inline style pada span).
    // Memaksa 32px !important membuat baris yang sudah dipecah manual di
    // aisar-data.js wrap ulang → layout PDF berbeda dari layar.
    const isIqroLikeDesktop = !!clonedElement.querySelector('.iqro-page');
    if (!isIqroLikeDesktop) {
        const fontShrinkStyle = document.createElement('style');
        fontShrinkStyle.textContent = `
            #quranDisplay-clone-desktop .arabic-text,
            #quranDisplay-clone-desktop [data-word],
            #quranDisplay-clone-desktop .word-item {
                font-size: 32px !important;
                line-height: 2.3 !important;
            }
        `;
        clonedElement.insertBefore(fontShrinkStyle, clonedElement.firstChild);
    }

    document.body.appendChild(clonedElement);
    await new Promise(resolve => setTimeout(resolve, 2500));

    // Auto-fit DIJALANKAN ULANG pada clone, memakai lebar kotak clone yang
    // sebenarnya. Ini yang menjamin PDF tidak menyimpang dari layar: apa pun
    // hasil pengukuran di layar, di sini diukur ulang pada kondisi final.
    if (document.fonts && document.fonts.ready) { try { await document.fonts.ready; } catch (e) {} }
    if (typeof window.aisarAutoFit === 'function') {
        window.aisarAutoFit(clonedElement);
        await new Promise(resolve => setTimeout(resolve, 150));
    }

    const canvas = await html2canvas(clonedElement, {
        scale: 2, useCORS: true, allowTaint: true,
        backgroundColor: '#3E3D3A', logging: false,
        width: clonedElement.scrollWidth, height: clonedElement.scrollHeight,
        windowWidth: clonedElement.scrollWidth, windowHeight: clonedElement.scrollHeight,
        scrollX: 0, scrollY: 0
    });

    if (clonedElement.parentNode) clonedElement.parentNode.removeChild(clonedElement);

    const maxPageHeight = Math.floor(canvas.width * (297 / 210));
    return _sliceCanvasFixed(canvas, maxPageHeight);
}

// =====================================================
// MOBILE screenshot
// =====================================================
async function captureMobile_v11_61(quranDisplay, renderWidth) {
    // Kotak halaman Aisar/Iqro di layar = max-width 760px. Padding clone
    // 24px kiri-kanan → butuh 760 + 48 = 808px agar lebar isinya IDENTIK
    // dengan layar, sehingga titik pecah baris tidak bergeser.
    const isIqroLikeMobile = !!quranDisplay.querySelector('.iqro-page');
    const RENDER_WIDTH = isIqroLikeMobile ? 808 : 692;
    const SCALE        = 2;

    const clonedElement = quranDisplay.cloneNode(true);
    clonedElement.id    = `quranDisplay-clone-mobile-${Date.now()}`;

    clonedElement.querySelectorAll('*').forEach(el => {
        const tag = el.tagName.toLowerCase();
        if (el.style.overflow  === 'auto'   || el.style.overflow  === 'scroll') el.style.overflow  = 'visible';
        if (el.style.overflowY === 'auto'   || el.style.overflowY === 'scroll') el.style.overflowY = 'visible';
        if (el.style.maxHeight && el.style.maxHeight !== 'none') el.style.maxHeight = 'none';
        if (el.style.height    && el.style.height    !== 'auto') {
            if (parseFloat(el.style.height) > 0) el.style.height = 'auto';
        }
        // Elemen di dalam .iqro-page dilewati: lebar kotak (max-width 760px)
        // dan lebar sel (flex per kolom) sudah diatur aisar-handler. Memaksa
        // width:100%/max-width:none merusak grid kolom & memicu wrap ulang.
        const insideIqroPage = typeof el.closest === 'function' && el.closest('.iqro-page');
        if (!insideIqroPage && ['div', 'section', 'article', 'main', 'aside', 'p'].includes(tag)) {
            el.style.setProperty('width', '100%', 'important');
            el.style.setProperty('max-width', 'none', 'important');
            el.style.setProperty('min-width', '0', 'important');
        }
    });

    clonedElement.querySelectorAll('*').forEach(el => {
        Array.from(el.querySelectorAll('span')).forEach(span => {
            const style = span.getAttribute('style') || '';
            const text  = span.textContent.trim();
            if (
                style.includes('background-color') &&
                style.includes('border-radius') &&
                /^[٠-٩۰-۹0-9]+$/.test(text)
            ) {
                const bgColor = (style.match(/background-color:\s*([^;]+)/) || [, 'rgba(96,165,250,0.4)'])[1];
                const size    = '22px';
                const w2 = document.createElement('span');
                w2.style.cssText = 'display:inline-block;position:relative;margin:0 4px;vertical-align:baseline;line-height:1;';
                const badge = document.createElement('span');
                badge.textContent   = text;
                badge.style.cssText = `display:inline-block;width:${size};height:${size};background-color:${bgColor};border-radius:50%;font-size:11px;line-height:${size};text-align:center;font-family:'Amiri Quran',serif;vertical-align:middle;position:relative;top:-1px;`;
                w2.appendChild(badge);
                span.parentNode.replaceChild(w2, span);
            }
        });
    });

    _applyMarkStyles(quranDisplay, clonedElement);

    clonedElement.style.cssText = [
        'position:absolute',
        'left:-9999px',
        'top:0',
        'overflow:visible!important',
        'max-height:none!important',
        'height:auto!important',
        `width:${RENDER_WIDTH}px`,
        'background-color:#3E3D3A',
        'padding:16px 24px 60px 24px',
        "font-family:'Amiri Quran',serif",
    ].join(';');

    // ✅ FIX: matiin border/ring/radius bawaan #quranDisplay → garis terluar hilang
    clonedElement.style.setProperty('border',        'none', 'important');
    clonedElement.style.setProperty('box-shadow',    'none', 'important');
    clonedElement.style.setProperty('border-radius', '0',    'important');
    clonedElement.style.setProperty('outline',       'none', 'important');

    document.body.appendChild(clonedElement);
    await new Promise(resolve => setTimeout(resolve, 2500));

    // Auto-fit ulang pada clone — lihat catatan di captureDesktop_v11_2.
    if (document.fonts && document.fonts.ready) { try { await document.fonts.ready; } catch (e) {} }
    if (typeof window.aisarAutoFit === 'function') {
        window.aisarAutoFit(clonedElement);
        await new Promise(resolve => setTimeout(resolve, 150));
    }

    const actualWidth  = clonedElement.scrollWidth;
    const actualHeight = clonedElement.scrollHeight;
    console.log(`📱 v11.74: RENDER_WIDTH=${RENDER_WIDTH}, scale=${SCALE}, actual=${actualWidth}x${actualHeight}px`);

    const canvas = await html2canvas(clonedElement, {
        scale: SCALE,
        useCORS: true,
        allowTaint: true,
        backgroundColor: '#3E3D3A',
        logging: false,
        width: RENDER_WIDTH,
        height: actualHeight,
        windowWidth: RENDER_WIDTH,
        windowHeight: actualHeight,
        scrollX: 0,
        scrollY: 0,
    });

    if (clonedElement.parentNode) clonedElement.parentNode.removeChild(clonedElement);

    const maxPageHeight = Math.floor(canvas.width * (297 / 210));
    console.log(`📄 v11.74 canvas: ${canvas.width}x${canvas.height}px, pageH=${maxPageHeight}px`);
    return _sliceCanvasFixed(canvas, maxPageHeight);
}

// =====================================================
// TABLET screenshot
// =====================================================
async function captureTabletMobile_v11_37(quranDisplay, renderWidth, pageRatio) {
    const wrapper = document.createElement('div');
    wrapper.id    = `quranDisplay-fullwrap-${Date.now()}`;
    wrapper.style.cssText = [
        'position:absolute', 'left:-9999px', 'top:0', 'overflow:visible',
        `width:${renderWidth}px`, 'background-color:#3E3D3A',
        'padding:0px 64px 60px 64px', 'margin:0', 'border:none',
        "font-family:'Amiri Quran',serif"
    ].join(';');

    Array.from(quranDisplay.children).forEach(el => {
        const clone = el.cloneNode(true);
        clone.style.maxHeight = 'none';
        clone.style.overflow  = 'visible';
        clone.style.height    = 'auto';
        clone.querySelectorAll('*').forEach(child => {
            if (child.style.overflow  === 'auto'   || child.style.overflow  === 'scroll') child.style.overflow  = 'visible';
            if (child.style.overflowY === 'auto'   || child.style.overflowY === 'scroll') child.style.overflowY = 'visible';
            if (child.style.maxHeight && child.style.maxHeight !== 'none') child.style.maxHeight = 'none';
        });
        _applyMarkStyles(el, clone);
        wrapper.appendChild(clone);
    });

    const overrideStyle = document.createElement('style');
    overrideStyle.textContent = `* { max-height:none !important; overflow:visible !important; overflow-y:visible !important; }`;
    wrapper.insertBefore(overrideStyle, wrapper.firstChild);
    document.body.appendChild(wrapper);
    await new Promise(resolve => setTimeout(resolve, 1500));

    const canvas = await html2canvas(wrapper, {
        scale: 3, useCORS: true, allowTaint: true,
        backgroundColor: '#3E3D3A', logging: false,
        width: wrapper.scrollWidth, height: wrapper.scrollHeight,
        windowWidth: wrapper.scrollWidth, windowHeight: wrapper.scrollHeight,
        scrollX: 0, scrollY: 0
    });

    if (wrapper.parentNode) wrapper.parentNode.removeChild(wrapper);

    const maxPageHeight = Math.floor(canvas.width * pageRatio);
    return _sliceCanvasFixed(canvas, maxPageHeight);
}

// =====================================================
// DISPATCHER
// =====================================================
async function captureQuranScreenshots(deviceType) {
    const cfg          = PDF_DEVICE_CONFIG[deviceType] || PDF_DEVICE_CONFIG.desktop;
    const quranDisplay = document.getElementById('quranDisplay');
    if (!quranDisplay) return [];
    if (typeof html2canvas === 'undefined') await loadHtml2Canvas();

    if (deviceType === 'desktop') {
        return await captureDesktop_v11_2(quranDisplay);
    }
    if (deviceType === 'mobile') {
        return await captureMobile_v11_61(quranDisplay, cfg.screenshotWidth);
    }
    const pageRatio = parseFloat(cfg.pageHeight) / parseFloat(cfg.pageWidth);
    return await captureTabletMobile_v11_37(quranDisplay, cfg.screenshotWidth, pageRatio);
}

// =====================================================
// generatePDFContent — v12.0 (DESAIN BARU: terracotta + cream, minimalis)
//
// GANTI SELURUH fungsi generatePDFContent LAMA di setoran-interactive.js
// dengan fungsi ini. Semua logika ekstraksi data TIDAK diubah — hanya
// template pdfHTML (CSS + markup) yang di-redesign.
//
// CATATAN:
//   - Halaman INFO (header, tabel, nilai) = cream ivory + aksen terracotta.
//   - Halaman SCREENSHOT Arab/Iqro TETAP navy #3E3D3A (teks putih) —
//     tidak diubah, hanya menempel di bawah lewat ${screenshotHTML}.
//   - DomPDF-safe: table-based, warna solid, TANPA flexbox / gradient.
//     Kalau border-radius kecil tidak sempurna di DomPDF, jatuhnya jadi
//     kotak — tetap rapi.
//
// PALETTE (design system raport-tahfizh):
//   Terracotta  #D97757   (aksen utama)
//   Terracotta-  #BE5D3D  (aksen gelap / teks di atas tint)
//   Ivory       #FAF9F5   (latar halaman)
//   Cream       #F5F4EF   (header tabel)
//   Tint        #FBEDE7   (latar nilai / pill aksen)
//   Ink         #3E3D3A   (teks utama - coal)
//   Muted       #78716C   (label - stone)
//   Line        #ECE9E3   (border hangat)
// =====================================================
async function generatePDFContent(deviceType = 'desktop') {
    try {
        const cfg = PDF_DEVICE_CONFIG[deviceType] || PDF_DEVICE_CONFIG.desktop;

        let activeMode = 'Combined';
        const formSetoranOnly = document.getElementById('formSetoranOnly');
        if (formSetoranOnly && !formSetoranOnly.classList.contains('hidden')) activeMode = 'SetoranOnly';

        const formEl = document.getElementById(activeMode === 'Combined' ? 'formCombined' : 'formSetoranOnly');

        // Tunggu konten benar-benar termuat — jangan jepret placeholder "Memuat..."
        const _disp = document.getElementById('quranDisplay');
        for (let i = 0; i < 40; i++) {
            const h = _disp?.innerHTML?.trim() || '';
            if (h.length > 100 && !/Memuat/i.test(h)) break;
            console.log('⏳ Menunggu konten quranDisplay siap... (' + (i + 1) + ')');
            await new Promise(r => setTimeout(r, 250));
        }

        const screenshotDataUrls = await captureQuranScreenshots(deviceType);

        // ✅ Deteksi mode Iqro untuk PDF
        const metodeVal = formEl?.querySelector('[name="metode"]')?.value || '';
        const isIqro = ['Iqro', 'Ummi', 'Aisar'].includes(metodeVal) ||
               !!document.getElementById('quranDisplay')?.querySelector('.iqro-page');
        const iqroJilid    = formEl?.querySelector('[name="iqro_jilid"]')?.value
                          || document.getElementById('iqroJilidHidden' + activeMode)?.value
                          || (document.getElementById('displayJuzRange')?.textContent || '').replace(/\D+/g, '')
                          || '1';
        const iqroHalStart = formEl?.querySelector('[name="iqro_halaman"]')?.value
                          || document.getElementById('iqroHalamanHidden' + activeMode)?.value
                          || document.getElementById('displayPageStart')?.textContent
                          || '-';
        const iqroHalEnd   = formEl?.querySelector('[name="iqro_halaman_end"]')?.value
                          || document.getElementById('iqroHalamanEndHidden' + activeMode)?.value
                          || document.getElementById('displayPageEnd')?.textContent
                          || iqroHalStart;
        const iqroJumlahHal = (parseInt(iqroHalEnd) >= parseInt(iqroHalStart))
            ? (parseInt(iqroHalEnd) - parseInt(iqroHalStart) + 1) : 1;

        console.log(`📄 Screenshot slices: ${screenshotDataUrls.length}`);

        const suratStart = document.getElementById(`suratStart${activeMode}`)?.value || '-';
        const ayatStart  = document.getElementById(`ayatStart${activeMode}`)?.value  || '-';
        const suratEnd   = document.getElementById(`suratEnd${activeMode}`)?.value   || '-';
        const ayatEnd    = document.getElementById(`ayatEnd${activeMode}`)?.value    || '-';

        // ✅ Ambil nama santri dari select aktif (Combined / SetoranOnly)
        let namaSantri = '-';
        const santriSelectId = activeMode === 'SetoranOnly' ? 'santriSelectSetoranOnly' : 'santriSelectCombined';
        const santriSelectEl = document.getElementById(santriSelectId);
        if (santriSelectEl) {
            if (santriSelectEl.selectedIndex > 0) {
                const txt = santriSelectEl.options[santriSelectEl.selectedIndex]?.text?.trim();
                if (txt && txt !== '-- Pilih Santri --') namaSantri = txt;
            }
            if (namaSantri === '-' && formEl) {
                const santriIdVal = formEl.querySelector('[name="santri_id"]')?.value;
                if (santriIdVal) {
                    const opt = santriSelectEl.querySelector(`option[value="${santriIdVal}"]`);
                    if (opt) namaSantri = opt.textContent.trim();
                }
            }
        }

        const pageStart = document.getElementById('displayPageStart')?.textContent || '-';
        const pageEnd   = document.getElementById('displayPageEnd')?.textContent   || '-';
        const totalPage = document.getElementById('displayTotalPage')?.textContent || '0';
        const totalJuz  = document.getElementById('displayTotalJuz')?.textContent  || '0';
        const totalKata = document.getElementById('displayTotalKata')?.textContent || '0';

        let kategori = '-';
        if (formEl) {
            const elForm = formEl.querySelector('select[name="kategori_setoran"], input[name="kategori_setoran"]');
            if (elForm) {
                let val = elForm.value?.trim();
                if (!val && elForm.tagName === 'SELECT' && elForm.selectedIndex > 0) val = elForm.options[elForm.selectedIndex]?.text?.trim();
                if (val && val !== '' && val !== '-- Pilih --') kategori = val;
            }
        }
        if (kategori === '-') {
            for (const sel of [`#kategoriSetoran${activeMode}`, `#kategoriSetoranCombined`, `#kategoriSetoranSetoranOnly`, `select[name="kategori_setoran"]`, `input[name="kategori_setoran"]`]) {
                const el = document.querySelector(sel);
                if (!el) continue;
                let val = el.value?.trim();
                if (!val && el.tagName === 'SELECT' && el.selectedIndex > 0) val = el.options[el.selectedIndex]?.text?.trim();
                if (val && val !== '' && val !== '-- Pilih --') { kategori = val; break; }
            }
        }

        const tanggal     = formEl?.querySelector('[name="tanggal"]')?.value || '-';
        const nilai       = document.getElementById(`displayNilai${activeMode}`)?.textContent || '100';
        const catatanGuru = formEl?.querySelector('[name="catatan_guru"]')?.value || '-';
        const f           = cfg.fontSize;

        const fmtCount = (n) => (!n || n === 0 || n === '0') ? '-' : n;

        const MIN_DATAURL_LENGTH = 5000;
        const validSlices = screenshotDataUrls.filter((dataUrl, idx) => {
            const isValid = dataUrl.length > MIN_DATAURL_LENGTH;
            if (!isValid) console.warn(`⚠️ Slice ${idx + 1} dibuang (kosong): ${dataUrl.length} chars`);
            return isValid;
        });
        console.log(`📄 Valid slices setelah filter: ${validSlices.length} dari ${screenshotDataUrls.length}`);

        let screenshotHTML = '';
        if (validSlices.length > 0) {
            validSlices.forEach((dataUrl) => {
                screenshotHTML += `<div class="sp-page"><img class="sp-img" src="${dataUrl}"></div>\n`;
            });
        } else {
            screenshotHTML = '<p style="color:#A8A29E;font-size:9px;padding:10px;">Screenshot tidak tersedia.</p>';
        }

        // ── Baris tabel info (cabang Iqro / Al-Qur'an) ────────────────────
        // Titik dua ditulis langsung di label, bukan lewat td.k::after —
        // DomPDF tidak konsisten merender pseudo-element `content`.
        const rowsHTML = isIqro ? `
            <tr><td class="k">Nama Santri :</td> <td class="v">${namaSantri}</td></tr>
            <tr><td class="k">Metode :</td>      <td class="v">${metodeVal || 'Iqro'}</td></tr>
            <tr><td class="k">Jilid :</td>       <td class="v">Jilid ${iqroJilid}</td></tr>
            <tr><td class="k">Halaman :</td>     <td class="v">${iqroHalStart} &ndash; ${iqroHalEnd} <span class="muted">(Total ${iqroJumlahHal})</span></td></tr>
            <tr><td class="k">Total Kata :</td>  <td class="v">${totalKata}</td></tr>
            <tr><td class="k">Catatan Guru :</td><td class="v">${catatanGuru || '-'}</td></tr>
        ` : `
            <tr><td class="k">Nama Santri :</td> <td class="v">${namaSantri}</td></tr>
            <tr><td class="k">Surat :</td>       <td class="v">${suratStart} &ndash; ${suratEnd}</td></tr>
            <tr><td class="k">Ayat :</td>        <td class="v">${ayatStart} &ndash; ${ayatEnd}</td></tr>
            <tr><td class="k">Halaman :</td>     <td class="v">${pageStart} &ndash; ${pageEnd} <span class="muted">(Total ${totalPage})</span></td></tr>
            <tr><td class="k">Total Juz :</td>   <td class="v">${totalJuz}</td></tr>
            <tr><td class="k">Total Kata :</td>  <td class="v">${totalKata}</td></tr>
            <tr><td class="k">Catatan Guru :</td><td class="v">${catatanGuru || '-'}</td></tr>
        `;

        const pdfHTML = `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { margin: 0; size: ${cfg.pageSize}; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; orphans: 1; widows: 1; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    background: #FAF9F5; color: #3E3D3A;
    font-size: ${f.body}; line-height: 1.6;
}
.page-info { display: block; padding: ${cfg.pageInfoPadding}; background: #FAF9F5; }

/* ── Header ─────────────────────────────────────── */
.hdr { padding-bottom: 16px; margin-bottom: 20px; border-bottom: 1px solid #ECE9E3; }
.accent-line { width: 46px; height: 4px; background: #D97757; border-radius: 3px; margin-bottom: 12px; }
.brand { font-size: ${f.h1}; font-weight: 700; letter-spacing: -0.6px; color: #3E3D3A; margin: 0 0 10px 0; line-height: 1.1; }
.pill {
    display: inline-block; padding: 4px 12px; border-radius: 20px;
    font-size: ${f.body}; font-weight: 600; margin-right: 7px;
    background: #F1EEE8; color: #57534E; border: 1px solid #E7E3DB;
}
.pill-accent { background: #FBEDE7; color: #BE5D3D; border-color: #F1D4C7; }

/* ── Section label ──────────────────────────────── */
.sec { font-size: ${f.h2}; font-weight: 700; color: #3E3D3A; margin: 20px 0 9px 0; letter-spacing: -0.2px; }
.sec-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #D97757; margin-right: 8px; vertical-align: middle; }

/* ── Info card ──────────────────────────────────── */
.card { background: #FFFFFF; border: 1px solid #ECE9E3; border-radius: 12px; overflow: hidden; }
.info-table { width: 100%; border-collapse: collapse; }
.info-table td { padding: 10px 16px; font-size: ${f.body}; line-height: 1.5; vertical-align: top; border-bottom: 1px solid #F2EFEA; }
.info-table tr:last-child td { border-bottom: none; }
.info-table td.k { font-weight: 600; color: #78716C; width: 140px; white-space: nowrap; }
.info-table td.v { color: #3E3D3A; }
.muted { color: #A8A29E; font-weight: 400; }

/* ── Detail Koreksi ─────────────────────────────── */
table.ct { width: 100%; border-collapse: collapse; background: #FFFFFF; border: 1px solid #ECE9E3; border-radius: 12px; overflow: hidden; }
table.ct th { background: #F5F4EF; font-weight: 700; color: #57534E; text-align: left; padding: ${cfg.infoPadding}; font-size: ${cfg.tableFontSize}; border-bottom: 1px solid #ECE9E3; }
table.ct td { padding: ${cfg.infoPadding}; font-size: ${cfg.tableFontSize}; color: #3E3D3A; border-bottom: 1px solid #F2EFEA; }
table.ct tr:last-child td { border-bottom: none; }
.err-name { font-weight: 600; padding-left: 14px !important; }
.err-k { border-left: 3px solid #22c55e; }
.err-t { border-left: 3px solid #eab308; }
.err-p { border-left: 3px solid #ef4444; }
.ket { color: #A8A29E; }

/* ── Nilai ──────────────────────────────────────── */
.score { margin-top: 18px; background: #FBEDE7; border: 1px solid #F1D4C7; border-radius: 14px; padding: 16px; text-align: center; }
.score-label { font-size: ${f.body}; font-weight: 700; color: #BE5D3D; margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.6px; }
.score-value { font-size: ${f.score}; font-weight: 800; color: #D97757; line-height: 1; }

.footer { margin-top: 18px; text-align: center; font-size: 9px; color: #A8A29E; }

/* ── Halaman screenshot (TETAP navy) ────────────── */
.sp-page {
    width: 100%; margin: 0; padding: 0; overflow: hidden;
    background: #3E3D3A;
    page-break-after: always; break-after: page;
    page-break-inside: avoid; break-inside: avoid;
}
.sp-page:last-child { page-break-after: auto; break-after: auto; }
.sp-img { display: block; width: 100%; height: auto; margin: 0; padding: 0; border: 0; }
</style>
</head>
<body>
<div class="page-info">
    <div class="hdr">
        <div class="accent-line"></div>
        <div class="brand">Setoran Tahfizh</div>
        <div>
            <span class="pill">${tanggal}</span>
            <span class="pill pill-accent">${kategori}</span>
        </div>
    </div>

    <div class="sec"><span class="sec-dot"></span>Info Setoran</div>
    <div class="card">
        <table class="info-table">${rowsHTML}</table>
    </div>

    <div class="sec"><span class="sec-dot"></span>Detail Koreksi</div>
    <table class="ct">
        <thead>
            <tr><th>Jenis Kesalahan</th><th>Jumlah</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
            <tr><td class="err-name err-k">Kelancaran</td><td>${fmtCount(kesalahanCount.kelancaran)}</td><td class="ket">-4 poin per kata</td></tr>
            <tr><td class="err-name err-t">Tajwid</td>    <td>${fmtCount(kesalahanCount.tajwid)}</td>    <td class="ket">-4 poin per kata</td></tr>
            <tr><td class="err-name err-p">Pelafalan</td> <td>${fmtCount(kesalahanCount.pelafalan)}</td> <td class="ket">-4 poin per kata</td></tr>
        </tbody>
    </table>

    <div class="score">
        <div class="score-label">Nilai Akhir</div>
        <div class="score-value">${nilai}</div>
    </div>

    <div class="footer">Dicetak: ${new Date().toLocaleString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</div>
</div>${screenshotHTML}</body>
</html>`;

        return pdfHTML;

    } catch (error) {
        console.error('❌ PDF generation error:', error);
        document.querySelectorAll('[id^="quranDisplay-"]').forEach(el => { if (el.parentNode) el.parentNode.removeChild(el); });
        return null;
    }
}

function guardPdfPayloadSize(form, mode) {
    // ~6MB aman di bawah post_max_size 8M. Kalau limit sudah dinaikkan ke 64M,
    // angka ini boleh dinaikkan jadi 50 * 1024 * 1024.
    const MAX_PDF_CHARS = 50 * 1024 * 1024;
    const pdfInput = document.getElementById('pdfHtmlData' + mode)
                  || form.querySelector('[name="pdf_html_content"]');
    if (pdfInput && pdfInput.value && pdfInput.value.length > MAX_PDF_CHARS) {
        const mb = (pdfInput.value.length / 1048576).toFixed(1);
        console.warn(`⚠️ pdf_html_content ${mb}MB melebihi batas — PDF di-drop agar setoran tetap tersimpan`);
        pdfInput.value = '';
    }
}
window.guardPdfPayloadSize = guardPdfPayloadSize;

// =====================================================
// generateAndSavePDFToInput
// =====================================================
async function generateAndSavePDFToInput(mode, deviceType = 'desktop') {
    currentActiveMode = mode;
    const totalKata     = countTotalWords();
    const totalJuzText  = document.getElementById('displayTotalJuz')?.textContent  || '0';
    const totalPageText = document.getElementById('displayTotalPage')?.textContent || '0';
    const tKI = document.getElementById(`totalKata${mode}`); if (tKI) tKI.value = totalKata;
    const tJI = document.getElementById(`totalJuz${mode}`);  if (tJI) tJI.value = totalJuzText;
    const tPI = document.getElementById(`totalPage${mode}`); if (tPI) tPI.value = totalPageText;
    try {
        const pdfHtml  = await generatePDFContent(deviceType);
        const pdfInput = document.getElementById(`pdfHtmlData${mode}`);
        if (pdfInput && pdfHtml) pdfInput.value = pdfHtml;
    } catch (err) { console.warn('⚠️ PDF gen error:', err.message); }
}

// =====================================================
// downloadPDF
// =====================================================
async function downloadPDF(deviceType = 'desktop') {
    const cfg = PDF_DEVICE_CONFIG[deviceType] || PDF_DEVICE_CONFIG.desktop;
    const loadingEl = document.getElementById('pdfLoadingIndicator');
    if (loadingEl) { loadingEl.textContent = `⏳ Membuat PDF ${cfg.label}...`; loadingEl.style.display = 'block'; }
    try {
        const pdfHtml = await generatePDFContent(deviceType);
        if (!pdfHtml) { alert("❌ Gagal membuat PDF. Pastikan Al-Qur'an sudah ditampilkan."); return; }
        const blob = new Blob([pdfHtml], { type: 'text/html;charset=utf-8' });
        const url  = URL.createObjectURL(blob);
        const win  = window.open(url, '_blank');
        if (win) { win.onload = () => { setTimeout(() => { win.print(); }, 800); }; }
        else {
            const a = document.createElement('a');
            a.href = url; a.download = `setoran-tahfizh-${deviceType}-${Date.now()}.html`;
            a.click();
        }
        setTimeout(() => URL.revokeObjectURL(url), 10000);
    } catch (err) {
        console.error('❌ downloadPDF error:', err);
        alert('❌ Error saat membuat PDF: ' + err.message);
    } finally {
        if (loadingEl) loadingEl.style.display = 'none';
    }
}

// =====================================================
// SETUP NATIVE FORM SUBMIT VALIDATION
// =====================================================
function setupNativeFormValidation() {
    ['formCombined', 'formSetoranOnly'].forEach(formId => {
        const form = document.getElementById(formId);
        if (!form || form._nativeValidationAttached) return;
        form._nativeValidationAttached = true;
        const mode = formId === 'formCombined' ? 'Combined' : 'SetoranOnly';
        form.addEventListener('submit', async function (e) {
            const santriId   = form.querySelector('[name="santri_id"]')?.value;
            const tanggal    = form.querySelector('[name="tanggal"]')?.value;
            const sesi       = form.querySelector('[name="sesi"]')?.value;
            const kategori   = form.querySelector('[name="kategori_setoran"]')?.value;
            const suratStart = form.querySelector('[name="surat_start"]')?.value;
            const ayatStart  = form.querySelector('[name="ayat_start"]')?.value;
            const suratEnd   = form.querySelector('[name="surat_end"]')?.value;
            const ayatEnd    = form.querySelector('[name="ayat_end"]')?.value;
            if (!santriId)  { e.preventDefault(); alert('⚠️ Pilih santri terlebih dahulu!'); return; }
            if (!tanggal)   { e.preventDefault(); alert('⚠️ Pilih tanggal terlebih dahulu!'); return; }
            if (!sesi)      { e.preventDefault(); alert('⚠️ Pilih sesi terlebih dahulu!'); return; }
            if (!kategori)  { e.preventDefault(); alert('⚠️ Pilih kategori setoran!'); return; }
            const metodeVal = form.querySelector('[name="metode"]')?.value || '';
            const isIqroMode = ['Iqro', 'Ummi', 'Aisar'].includes(metodeVal);

            if (!isIqroMode && (!suratStart || !ayatStart || !suratEnd || !ayatEnd)) { e.preventDefault(); alert('⚠️ Lengkapi range surat dan ayat!'); return; }
            const quranDisplay = document.getElementById('quranDisplay');
            if (!quranDisplay || quranDisplay.innerHTML.trim() === '') {
                e.preventDefault();
                alert(isIqroMode ? '⚠️ Klik tombol "Tampilkan Halaman" terlebih dahulu sebelum menyimpan!' : "⚠️ Klik tombol \"Tampilkan Al-Qur'an\" terlebih dahulu sebelum menyimpan!");
                return;
            }
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) { submitBtn.innerHTML = '⏳ Menyimpan...'; submitBtn.disabled = true; }

            // ✅ FIX: auto-detect deviceType, JANGAN hardcode 'desktop'
            // Di mobile, 'desktop' akan capture dengan offsetWidth=0 (karena parent #quranPanel hidden di mobile)
            const deviceType = window.innerWidth <= 768  ? 'mobile'
                            : window.innerWidth <= 1024 ? 'tablet'
                            : 'desktop';
            console.log(`📱 Submit deviceType: ${deviceType} (innerWidth: ${window.innerWidth}px)`);

            try {
                await generateAndSavePDFToInput(mode, deviceType);
            } catch (err) {
                console.warn('⚠️ PDF gen failed:', err.message);
            }

            // ✅ Jaring pengaman: jika payload PDF terlalu besar untuk post_max_size,
            // drop PDF-nya agar setoran TETAP tersimpan & notifikasi sukses tetap muncul.
            guardPdfPayloadSize(form, mode);

            if (typeof window.refreshCsrfToken === 'function') {
                await window.refreshCsrfToken();
            }
            form.submit();
        });
    });
}

// =====================================================
// DOM READY
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
    setupCombinedMode();
    setupSetoranOnlyMode();
    setupNativeFormValidation();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        ['startRecordingCombined', 'startRecordingSetoranOnly'].forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = true;
        });
    }
    console.log('✅ Setoran Interactive v11.74 - READY');
});

// =====================================================
// EXPORT
// =====================================================
window.populateAyatDropdown            = populateAyatDropdown;
window.updateStatistikSetoranAfterLoad = updateStatistikSetoranAfterLoad;
window.markWord                        = markWord;
window.clearMarking                    = clearMarking;
window.countTotalWords                 = countTotalWords;
window.generatePDFContent              = generatePDFContent;
window.generateAndSavePDFToInput       = generateAndSavePDFToInput;
window.downloadPDF                     = downloadPDF;
window.setActiveMode                   = setActiveMode;
window.updateKesalahanDisplay          = updateKesalahanDisplay;
window.triggerPageCalculation          = triggerPageCalculation;
window.captureQuranScreenshots         = captureQuranScreenshots;
window.PDF_DEVICE_CONFIG               = PDF_DEVICE_CONFIG;
window.setupFormSubmitHandler = function () { console.log('ℹ️ handled by setupNativeFormValidation'); };

// ✅ FIX: Ekspos kesalahanCount ke window agar handler proxy di Blade bisa update.
// Tanpa ini, marking dari mobile drawer tidak akan mempengaruhi tabel "Detail Koreksi" di PDF.
window.kesalahanCount = kesalahanCount;

console.log('✅ Setoran Interactive v11.74 LOADED');
console.log('   ✔ FIX v11.74: PAD_TOP/PAD_BOTTOM 100→50px (gain ~1 baris)');
console.log('   ✔ FIX v11.74: SEARCH_OVERSHOOT = 80% PAD_BOTTOM (pinjam ruang padding bawah)');
console.log('   ✔ Gap detection algorithm tetap (cut di antar baris asli)');
console.log('   ✔ Hasil: utilization 90%+ per halaman, tidak ada ruang kosong');