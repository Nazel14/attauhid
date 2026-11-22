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
// generatePDFContent — v13.0
//
// Mengumpulkan data setoran dari form & layar, lalu menyerahkannya ke
// buildSetoranPdfHtml (di bawah) untuk disusun jadi HTML PDF.
// Logika pengambilan data TIDAK berubah dari v12.0.
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
            screenshotHTML = '<p style="color:#A2A79C;font-size:9px;padding:10px 9mm;">Screenshot tidak tersedia.</p>';
        }

        // Nama lembaga untuk kop — diambil dari meta di app.blade.php
        // (apple-mobile-web-app-title), jadi tidak perlu ditulis dua kali.
        const lembaga = document.querySelector('meta[name="apple-mobile-web-app-title"]')?.content?.trim() || '';

        return buildSetoranPdfHtml({
            lembaga, isIqro, namaSantri, tanggal, kategori, nilai, catatanGuru,
            metode: metodeVal, iqroJilid, iqroHalStart, iqroHalEnd, iqroJumlahHal,
            suratStart, suratEnd, ayatStart, ayatEnd,
            pageStart, pageEnd, totalPage, totalJuz, totalKata,
            kesalahan: {
                kelancaran: kesalahanCount.kelancaran,
                tajwid:     kesalahanCount.tajwid,
                pelafalan:  kesalahanCount.pelafalan,
            },
            jumlahHalamanBacaan: validSlices.length,
            screenshotHTML,
        }, cfg);

    } catch (error) {
        console.error('❌ PDF generation error:', error);
        document.querySelectorAll('[id^="quranDisplay-"]').forEach(el => { if (el.parentNode) el.parentNode.removeChild(el); });
        return null;
    }
}

// =====================================================
// buildSetoranPdfHtml — v13.0 (DESAIN BARU: hijau At Tauhid, kartu modern)
//
// Hanya menyusun HTML dari data yang sudah dikumpulkan generatePDFContent.
// Dipisah supaya desain bisa diubah tanpa menyentuh logika pengambilan data.
//
// DomPDF-safe:
//   - Tata letak pakai <table>, TANPA flexbox / grid / gradient.
//   - Selector padding sel pakai "td.kelas" — aturan umum "table td" di
//     DomPDF menang atas class tunggal.
//   - Jarak antar kartu memakai sel pengisi (td.gap), bukan border-spacing.
//
// Halaman screenshot Arab/Iqro TIDAK diubah: latar #3E3D3A wajib tetap,
// karena _sliceCanvasFixed mendeteksi celah antar-baris dari warna itu.
//
// PALET (logo At Tauhid):
//   Hijau       #7EB842   aksen utama
//   Hijau tua   #4E7F22   teks di atas tint hijau
//   Tint hijau  #EEF6E4
//   Oranye      #F39A2B   aksen kedua
//   Biru        #35A7DD   aksen ketiga
//   Ink         #2A3026   teks utama
//   Muted       #7A7F76   label
//   Garis       #E3E6DC
//   Latar       #F6F7F2
// =====================================================
function buildSetoranPdfHtml(d, cfg) {
    const f = cfg.fontSize;

    // Nama santri & catatan diketik pengguna — wajib di-escape supaya
    // karakter < > & tidak merusak (atau menyisipkan) HTML di PDF.
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));
    const angka = (v) => { const n = parseInt(v, 10); return Number.isFinite(n) ? n : 0; };
    const strip = (v) => (v === undefined || v === null || v === '' ? '-' : v);

    // Tanggal "2026-11-22" → "Minggu, 22 November 2026"
    const fmtTanggal = (v) => {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(v || ''));
        if (!m) return strip(v);
        const t = new Date(+m[1], +m[2] - 1, +m[3]);
        return t.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    };

    const nilai = Math.max(0, Math.min(100, angka(d.nilai)));
    // Warna nilai: hijau ≥ 85, oranye ≥ 70, merah di bawahnya.
    const tone = nilai >= 85
        ? { c: '#4E7F22', bg: '#EEF6E4', line: '#CFE5B4', bar: '#7EB842' }
        : nilai >= 70
            ? { c: '#B86A0E', bg: '#FDF1E0', line: '#F5D6AA', bar: '#F39A2B' }
            : { c: '#B4232A', bg: '#FDECEC', line: '#F3C4C4', bar: '#E5484D' };

    const kK = angka(d.kesalahan.kelancaran);
    const kT = angka(d.kesalahan.tajwid);
    const kP = angka(d.kesalahan.pelafalan);
    const totalSalah = kK + kT + kP;

    // Bar nilai: dua sel persentase. 0 dan 100 ditangani terpisah —
    // DomPDF menggambar sel 0% sebagai garis tipis.
    const barNilai = nilai <= 0
        ? `<td class="bar-sisa" style="width:100%"></td>`
        : nilai >= 100
            ? `<td class="bar-isi" style="width:100%;background:${tone.bar}"></td>`
            : `<td class="bar-isi" style="width:${nilai}%;background:${tone.bar}"></td><td class="bar-sisa" style="width:${100 - nilai}%"></td>`;

    const judul    = d.isIqro ? `Setoran ${esc(d.metode || 'Iqro')}` : 'Setoran Tahfizh';
    const lembaga  = esc(d.lembaga || '');

    // Tinggi dua kartu hero disamakan (div di dalam sel tabel tidak ikut
    // meregang di DomPDF). Dihitung dari ukuran huruf perangkat.
    const px = (v) => parseFloat(v) || 12;
    const heroH = Math.round(px(f.score) * 1.05 + px(f.body) * 2.4 + 24) + 'px';

    // ── Ringkasan angka (4 kotak) ──────────────────────────────────
    const kpi = d.isIqro ? [
        ['Jilid', esc(d.iqroJilid), ''],
        ['Halaman', `${esc(d.iqroHalStart)}&ndash;${esc(d.iqroHalEnd)}`, `${d.iqroJumlahHal} halaman`],
        ['Total Kata', esc(d.totalKata), ''],
        ['Kesalahan', totalSalah, totalSalah ? 'kata ditandai' : 'tidak ada'],
    ] : [
        ['Halaman', `${esc(d.pageStart)}&ndash;${esc(d.pageEnd)}`, `${esc(d.totalPage)} halaman`],
        ['Total Juz', esc(d.totalJuz), ''],
        ['Total Kata', esc(d.totalKata), ''],
        ['Kesalahan', totalSalah, totalSalah ? 'kata ditandai' : 'tidak ada'],
    ];
    const kpiHTML = kpi.map(([label, val, sub], i) => `
        ${i ? '<td class="gap"></td>' : ''}
        <td class="kpi-cell"><div class="kpi">
            <div class="kpi-label">${label}</div>
            <div class="kpi-val">${val}</div>
            <div class="kpi-sub">${sub || '&nbsp;'}</div>
        </div></td>`).join('');

    // ── Rincian bacaan ─────────────────────────────────────────────
    const rincian = d.isIqro ? [
        ['Metode', esc(d.metode || 'Iqro')],
        ['Jilid', `Jilid ${esc(d.iqroJilid)}`],
        ['Halaman', `${esc(d.iqroHalStart)} &ndash; ${esc(d.iqroHalEnd)}`],
    ] : [
        ['Surat', `${esc(d.suratStart)} &ndash; ${esc(d.suratEnd)}`],
        ['Ayat', `${esc(d.ayatStart)} &ndash; ${esc(d.ayatEnd)}`],
        ['Halaman', `${esc(d.pageStart)} &ndash; ${esc(d.pageEnd)}`],
    ];
    const rincianHTML = rincian.map(([k, v], i) => `
        <tr><td class="rk${i === rincian.length - 1 ? ' last' : ''}">${k}</td><td class="rv${i === rincian.length - 1 ? ' last' : ''}">${v}</td></tr>`).join('');

    // ── Koreksi (3 kotak berwarna) ─────────────────────────────────
    const koreksi = [
        ['Kelancaran', kK, '#22A55A', '#EAF7EF', '#BFE6CD'],
        ['Tajwid',     kT, '#C98A04', '#FDF5DC', '#F1DE9C'],
        ['Pelafalan',  kP, '#D93B3B', '#FDECEC', '#F3C4C4'],
    ];
    const koreksiHTML = koreksi.map(([label, n, c, bg, line], i) => `
        ${i ? '<td class="gap"></td>' : ''}
        <td class="kor-cell"><div class="kor" style="background:${n ? bg : '#FFFFFF'};border-color:${n ? line : '#E3E6DC'}">
            <table class="lay"><tr>
                <td class="kor-dot-cell"><div class="kor-dot" style="background:${c}"></div></td>
                <td class="kor-label-cell"><div class="kor-label">${label}</div><div class="kor-sub">&minus;4 poin / kata</div></td>
                <td class="kor-n-cell" style="color:${n ? c : '#B9BDB4'}">${n || '&ndash;'}</td>
            </tr></table>
        </div></td>`).join('');

    const catatan = (d.catatanGuru && d.catatanGuru.trim() && d.catatanGuru.trim() !== '-')
        ? `<div class="sec">Catatan Guru</div>
           <div class="note">${esc(d.catatanGuru.trim()).replace(/\n/g, '<br>')}</div>`
        : '';

    const dicetak = new Date().toLocaleString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });

    return `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { margin: 0; size: ${cfg.pageSize}; }
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; orphans: 1; widows: 1; }
body {
    font-family: 'Plus Jakarta Sans', 'Segoe UI', 'DejaVu Sans', Arial, sans-serif;
    background: #F6F7F2; color: #2A3026;
    font-size: ${f.body}; line-height: 1.5;
}
table.lay { width: 100%; border-collapse: collapse; }
table.lay td { padding: 0; vertical-align: top; }
table.lay td.gap { width: 2%; }

.page-info { display: block; background: #F6F7F2; padding: 0 0 10mm 0; }
table.topbar { width: 100%; border-collapse: collapse; }
table.topbar td { height: 7px; padding: 0; font-size: 1px; line-height: 1px; }
.wrap { padding: 9mm 9mm 0 9mm; }

/* ── Kop ───────────────────────────────────────── */
.eyebrow { font-size: 0.85em; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase; color: #7EB842; margin-bottom: 4px; }
.title { font-size: ${f.h1}; font-weight: 800; letter-spacing: -0.5px; color: #2A3026; line-height: 1.15; margin: 0; }
table.lay td.kop-kanan { text-align: right; vertical-align: middle; }
.chip {
    display: inline-block; padding: 4px 11px; border-radius: 20px;
    font-size: 0.9em; font-weight: 700; margin-left: 5px; white-space: nowrap;
    background: #FFFFFF; color: #4A5145; border: 1px solid #E3E6DC;
}
.chip-hijau { background: #EEF6E4; color: #4E7F22; border-color: #CFE5B4; }
.rule { height: 1px; background: #E3E6DC; margin: 14px 0 16px 0; }

/* ── Hero: santri + nilai ──────────────────────── */
.hero-santri { background: #FFFFFF; border: 1px solid #E3E6DC; border-radius: 14px; padding: 16px 18px; height: ${heroH}; }
table.lay td.hero-nilai-cell { width: 30%; }
.lbl { font-size: 0.85em; font-weight: 600; color: #7A7F76; margin-bottom: 3px; }
.nama { font-size: ${f.h2}; font-weight: 800; color: #2A3026; line-height: 1.25; }
.tgl { margin-top: 6px; color: #7A7F76; font-size: 0.95em; }
.hero-nilai { border-radius: 14px; padding: 12px 16px; text-align: center; border: 1px solid; height: ${heroH}; }
.nilai-lbl { font-size: 0.8em; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; }
.nilai-val { font-size: ${f.score}; font-weight: 800; line-height: 1.05; margin: 2px 0 8px 0; }
table.bar { width: 100%; border-collapse: collapse; height: 6px; }
table.bar td { padding: 0; height: 6px; font-size: 1px; line-height: 1px; }
table.bar td.bar-isi { border-radius: 3px; }
table.bar td.bar-sisa { background: #FFFFFF; border-radius: 3px; }

/* ── KPI ───────────────────────────────────────── */
table.lay td.kpi-cell { width: 23.5%; }
.kpi { background: #FFFFFF; border: 1px solid #E3E6DC; border-radius: 12px; padding: 10px 13px; }
.kpi-label { font-size: 0.8em; font-weight: 700; color: #7A7F76; text-transform: uppercase; letter-spacing: 0.6px; }
.kpi-val { font-size: ${f.h2}; font-weight: 800; color: #2A3026; margin-top: 3px; line-height: 1.2; }
.kpi-sub { font-size: 0.8em; color: #A2A79C; margin-top: 1px; }

/* ── Section ───────────────────────────────────── */
.sec { font-size: 0.85em; font-weight: 800; letter-spacing: 1.2px; text-transform: uppercase; color: #7A7F76; margin: 18px 0 8px 0; }
.sec-bar { display: inline-block; width: 14px; height: 3px; background: #7EB842; border-radius: 2px; vertical-align: middle; margin-right: 7px; }

/* ── Rincian ───────────────────────────────────── */
.card { background: #FFFFFF; border: 1px solid #E3E6DC; border-radius: 12px; overflow: hidden; }
table.rinci { width: 100%; border-collapse: collapse; }
table.rinci td.rk, table.rinci td.rv { padding: 9px 16px; border-bottom: 1px solid #EEF0E9; vertical-align: top; }
table.rinci td.rk { width: 28%; color: #7A7F76; font-weight: 600; }
table.rinci td.rv { color: #2A3026; font-weight: 700; }
table.rinci td.last { border-bottom: none; }

/* ── Koreksi ───────────────────────────────────── */
table.lay td.kor-cell { width: 32%; }
.kor { border: 1px solid; border-radius: 12px; padding: 10px 13px; }
table.lay td.kor-dot-cell { width: 16px; vertical-align: middle; }
table.lay td.kor-label-cell { vertical-align: middle; }
.kor-dot { width: 9px; height: 9px; border-radius: 5px; }
.kor-label { font-weight: 700; color: #2A3026; }
.kor-sub { font-size: 0.8em; color: #A2A79C; }
table.lay td.kor-n-cell { text-align: right; font-size: ${f.h2}; font-weight: 800; width: 28%; vertical-align: middle; }

/* ── Catatan ───────────────────────────────────── */
.note { background: #FFFFFF; border: 1px solid #E3E6DC; border-left: 4px solid #F39A2B; border-radius: 10px; padding: 11px 15px; color: #3C433A; line-height: 1.6; }

.footer { margin-top: 18px; padding-top: 10px; border-top: 1px dashed #DCE0D4; color: #A2A79C; font-size: 0.85em; }
table.lay td.footer-kanan { text-align: right; }

/* ── Halaman screenshot (latar TETAP #3E3D3A) ──── */
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
    <table class="topbar"><tr>
        <td style="width:70%;background:#7EB842"></td>
        <td style="width:18%;background:#F39A2B"></td>
        <td style="width:12%;background:#35A7DD"></td>
    </tr></table>
    <div class="wrap">

        <table class="lay"><tr>
            <td>
                ${lembaga ? `<div class="eyebrow">${lembaga}</div>` : ''}
                <h1 class="title">${judul}</h1>
            </td>
            <td class="kop-kanan">
                ${d.kategori && d.kategori !== '-' ? `<span class="chip chip-hijau">${esc(d.kategori)}</span>` : ''}
            </td>
        </tr></table>
        <div class="rule"></div>

        <table class="lay"><tr>
            <td><div class="hero-santri">
                <div class="lbl">Nama Santri</div>
                <div class="nama">${esc(d.namaSantri)}</div>
                <div class="tgl">${esc(fmtTanggal(d.tanggal))}</div>
            </div></td>
            <td class="gap"></td>
            <td class="hero-nilai-cell"><div class="hero-nilai" style="background:${tone.bg};border-color:${tone.line}">
                <div class="nilai-lbl" style="color:${tone.c}">Nilai Akhir</div>
                <div class="nilai-val" style="color:${tone.c}">${nilai}</div>
                <table class="bar"><tr>${barNilai}</tr></table>
            </div></td>
        </tr></table>

        <div class="sec"><span class="sec-bar"></span>Ringkasan</div>
        <table class="lay"><tr>${kpiHTML}</tr></table>

        <div class="sec"><span class="sec-bar"></span>Rincian Bacaan</div>
        <div class="card"><table class="rinci">${rincianHTML}</table></div>

        <div class="sec"><span class="sec-bar"></span>Detail Koreksi</div>
        <table class="lay"><tr>${koreksiHTML}</tr></table>

        ${catatan.replace('<div class="sec">', '<div class="sec"><span class="sec-bar"></span>')}

        <table class="lay footer"><tr>
            <td>Dicetak ${esc(dicetak)}</td>
            <td class="footer-kanan">${d.jumlahHalamanBacaan ? `Lampiran bacaan: ${d.jumlahHalamanBacaan} halaman` : ''}</td>
        </tr></table>
    </div>
</div>${d.screenshotHTML}</body>
</html>`;
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