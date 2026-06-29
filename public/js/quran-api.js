/**
 * Quran API Integration - Amiri Quran Version
 * Version: 4.1 - Marking shape: word-item inline-block + line-height 1.4 +
 *                border-radius 16px (samakan tampilan tumpul dengan Iqro).
 *                THEME AWARE: warna teks Arab pakai CSS var (--q-fg/--q-num/--q-box-border)
 *                dgn fallback #fff → light/dark di layar, PDF tetap putih (var fallback).
 */

const BASMALLAH_TEXT = 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';
const BASMALLAH_STANDARD = "بِسْمِ ٱللَّهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ";

// ✅ KONSTANTA: bobot pengurangan nilai per kesalahan
const POIN_PER_KESALAHAN = 4;

const surahMapping = {
    '1. Al-Fatihah': { number: 1, verses: 7 },
    '2. Al-Baqarah': { number: 2, verses: 286 },
    '3. Ali \'Imran': { number: 3, verses: 200 },
    '4. An-Nisa\'': { number: 4, verses: 176 },
    '5. Al-Ma\'idah': { number: 5, verses: 120 },
    '6. Al-An\'am': { number: 6, verses: 165 },
    '7. Al-A\'raf': { number: 7, verses: 206 },
    '8. Al-Anfal': { number: 8, verses: 75 },
    '9. At-Taubah': { number: 9, verses: 129 },
    '10. Yunus': { number: 10, verses: 109 },
    '11. Hud': { number: 11, verses: 123 },
    '12. Yusuf': { number: 12, verses: 111 },
    '13. Ar-Ra\'d': { number: 13, verses: 43 },
    '14. Ibrahim': { number: 14, verses: 52 },
    '15. Al-Hijr': { number: 15, verses: 99 },
    '16. An-Nahl': { number: 16, verses: 128 },
    '17. Al-Isra\'': { number: 17, verses: 111 },
    '18. Al-Kahf': { number: 18, verses: 110 },
    '19. Maryam': { number: 19, verses: 98 },
    '20. Taha': { number: 20, verses: 135 },
    '21. Al-Anbiya\'': { number: 21, verses: 112 },
    '22. Al-Hajj': { number: 22, verses: 78 },
    '23. Al-Mu\'minun': { number: 23, verses: 118 },
    '24. An-Nur': { number: 24, verses: 64 },
    '25. Al-Furqan': { number: 25, verses: 77 },
    '26. Asy-Syu\'ara\'': { number: 26, verses: 227 },
    '27. An-Naml': { number: 27, verses: 93 },
    '28. Al-Qasas': { number: 28, verses: 88 },
    '29. Al-\'Ankabut': { number: 29, verses: 69 },
    '30. Ar-Rum': { number: 30, verses: 60 },
    '31. Luqman': { number: 31, verses: 34 },
    '32. As-Sajdah': { number: 32, verses: 30 },
    '33. Al-Ahzab': { number: 33, verses: 73 },
    '34. Saba\'': { number: 34, verses: 54 },
    '35. Fatir': { number: 35, verses: 45 },
    '36. Yasin': { number: 36, verses: 83 },
    '37. As-Saffat': { number: 37, verses: 182 },
    '38. Sad': { number: 38, verses: 88 },
    '39. Az-Zumar': { number: 39, verses: 75 },
    '40. Ghafir': { number: 40, verses: 85 },
    '41. Fussilat': { number: 41, verses: 54 },
    '42. Asy-Syura': { number: 42, verses: 53 },
    '43. Az-Zukhruf': { number: 43, verses: 89 },
    '44. Ad-Dukhan': { number: 44, verses: 59 },
    '45. Al-Jasiyah': { number: 45, verses: 37 },
    '46. Al-Ahqaf': { number: 46, verses: 35 },
    '47. Muhammad': { number: 47, verses: 38 },
    '48. Al-Fath': { number: 48, verses: 29 },
    '49. Al-Hujurat': { number: 49, verses: 18 },
    '50. Qaf': { number: 50, verses: 45 },
    '51. Az-Zariyat': { number: 51, verses: 60 },
    '52. At-Tur': { number: 52, verses: 49 },
    '53. An-Najm': { number: 53, verses: 62 },
    '54. Al-Qamar': { number: 54, verses: 55 },
    '55. Ar-Rahman': { number: 55, verses: 78 },
    '56. Al-Waqi\'ah': { number: 56, verses: 96 },
    '57. Al-Hadid': { number: 57, verses: 29 },
    '58. Al-Mujadilah': { number: 58, verses: 22 },
    '59. Al-Hasyr': { number: 59, verses: 24 },
    '60. Al-Mumtahanah': { number: 60, verses: 13 },
    '61. As-Saff': { number: 61, verses: 14 },
    '62. Al-Jumu\'ah': { number: 62, verses: 11 },
    '63. Al-Munafiqun': { number: 63, verses: 11 },
    '64. At-Taghabun': { number: 64, verses: 18 },
    '65. At-Talaq': { number: 65, verses: 12 },
    '66. At-Tahrim': { number: 66, verses: 12 },
    '67. Al-Mulk': { number: 67, verses: 30 },
    '68. Al-Qalam': { number: 68, verses: 52 },
    '69. Al-Haqqah': { number: 69, verses: 52 },
    '70. Al-Ma\'arij': { number: 70, verses: 44 },
    '71. Nuh': { number: 71, verses: 28 },
    '72. Al-Jinn': { number: 72, verses: 28 },
    '73. Al-Muzzammil': { number: 73, verses: 20 },
    '74. Al-Muddassir': { number: 74, verses: 56 },
    '75. Al-Qiyamah': { number: 75, verses: 40 },
    '76. Al-Insan': { number: 76, verses: 31 },
    '77. Al-Mursalat': { number: 77, verses: 50 },
    '78. An-Naba\'': { number: 78, verses: 40 },
    '79. An-Nazi\'at': { number: 79, verses: 46 },
    '80. \'Abasa': { number: 80, verses: 42 },
    '81. At-Takwir': { number: 81, verses: 29 },
    '82. Al-Infitar': { number: 82, verses: 19 },
    '83. Al-Mutaffifin': { number: 83, verses: 36 },
    '84. Al-Insyiqaq': { number: 84, verses: 25 },
    '85. Al-Buruj': { number: 85, verses: 22 },
    '86. At-Tariq': { number: 86, verses: 17 },
    '87. Al-A\'la': { number: 87, verses: 19 },
    '88. Al-Ghasyiyah': { number: 88, verses: 26 },
    '89. Al-Fajr': { number: 89, verses: 30 },
    '90. Al-Balad': { number: 90, verses: 20 },
    '91. Asy-Syams': { number: 91, verses: 15 },
    '92. Al-Lail': { number: 92, verses: 21 },
    '93. Ad-Duha': { number: 93, verses: 11 },
    '94. Asy-Syarh': { number: 94, verses: 8 },
    '95. At-Tin': { number: 95, verses: 8 },
    '96. Al-\'Alaq': { number: 96, verses: 19 },
    '97. Al-Qadr': { number: 97, verses: 5 },
    '98. Al-Bayyinah': { number: 98, verses: 8 },
    '99. Az-Zalzalah': { number: 99, verses: 8 },
    '100. Al-\'Adiyat': { number: 100, verses: 11 },
    '101. Al-Qari\'ah': { number: 101, verses: 11 },
    '102. At-Takasur': { number: 102, verses: 8 },
    '103. Al-\'Asr': { number: 103, verses: 3 },
    '104. Al-Humazah': { number: 104, verses: 9 },
    '105. Al-Fil': { number: 105, verses: 5 },
    '106. Quraisy': { number: 106, verses: 4 },
    '107. Al-Ma\'un': { number: 107, verses: 7 },
    '108. Al-Kausar': { number: 108, verses: 3 },
    '109. Al-Kafirun': { number: 109, verses: 6 },
    '110. An-Nasr': { number: 110, verses: 3 },
    '111. Al-Masad': { number: 111, verses: 5 },
    '112. Al-Ikhlas': { number: 112, verses: 4 },
    '113. Al-Falaq': { number: 113, verses: 5 },
    '114. An-Nas': { number: 114, verses: 6 }
};

function getFormElementIds() {
    const context = window.currentFormContext || 'Combined';

    if (context === 'SetoranOnly') {
        return {
            suratStart: 'suratStartSetoranOnly', ayatStart: 'ayatStartSetoranOnly',
            suratEnd: 'suratEndSetoranOnly', ayatEnd: 'ayatEndSetoranOnly',
            pageStart: 'pageStartSetoranOnly', pageEnd: 'pageEndSetoranOnly',
            pageStartPortion: 'pageStartPortionSetoranOnly', pageEndPortion: 'pageEndPortionSetoranOnly',
            totalPage: 'totalPageSetoranOnly',
            juzStart: 'juzStartSetoranOnly', juzEnd: 'juzEndSetoranOnly', totalJuz: 'totalJuzSetoranOnly',
            totalKata: 'totalKataSetoranOnly',
            kesalahanKelancaran: 'kesalahanKelancaranSetoranOnly',
            kesalahanTajwid: 'kesalahanTajwidSetoranOnly',
            kesalahanPelafalan: 'kesalahanPelafalanSetoranOnly',
            nilai: 'nilaiSetoranOnly',
            displayKelancaran: 'displayKelancaranSetoranOnly',
            displayTajwid: 'displayTajwidSetoranOnly',
            displayPelafalan: 'displayPelafalanSetoranOnly',
            displayNilai: 'displayNilaiSetoranOnly',
            displayTotalKata: 'displayTotalKata',
            displayTotalJuz: 'displayTotalJuz',
            displayTotalPage: 'displayTotalPage'
        };
    } else {
        return {
            suratStart: 'suratStartCombined', ayatStart: 'ayatStartCombined',
            suratEnd: 'suratEndCombined', ayatEnd: 'ayatEndCombined',
            pageStart: 'pageStartCombined', pageEnd: 'pageEndCombined',
            pageStartPortion: 'pageStartPortionCombined', pageEndPortion: 'pageEndPortionCombined',
            totalPage: 'totalPageCombined',
            juzStart: 'juzStartCombined', juzEnd: 'juzEndCombined', totalJuz: 'totalJuzCombined',
            totalKata: 'totalKataCombined',
            kesalahanKelancaran: 'kesalahanKelancaranCombined',
            kesalahanTajwid: 'kesalahanTajwidCombined',
            kesalahanPelafalan: 'kesalahanPelafalanCombined',
            nilai: 'nilaiCombined',
            displayKelancaran: 'displayKelancaranCombined',
            displayTajwid: 'displayTajwidCombined',
            displayPelafalan: 'displayPelafalanCombined',
            displayNilai: 'displayNilaiCombined',
            displayTotalKata: 'displayTotalKata',
            displayTotalJuz: 'displayTotalJuz',
            displayTotalPage: 'displayTotalPage'
        };
    }
}

function removeBasmalahFromAyah(text, surahNumber, ayahNumber) {
    if (surahNumber === 1 || surahNumber === 9) return text;
    if (ayahNumber !== 1) return text;

    let teksBersih = text.trim();

    if (teksBersih.includes(BASMALLAH_STANDARD)) {
        teksBersih = teksBersih.replace(BASMALLAH_STANDARD, '').trim();
    } else if (teksBersih.includes(BASMALLAH_TEXT)) {
        teksBersih = teksBersih.replace(BASMALLAH_TEXT, '').trim();
    } else {
        teksBersih = teksBersih.split(' ').slice(4).join(' ');
    }

    return teksBersih.replace(/^[\u0610-\u061A\u064B-\u065F\u0671\u06D6-\u06DC\u06DF-\u06E8\u06EA-\u06ED]+/, '').trim();
}

function wrapWaqafSymbols(text) {
    return text;
}

// ✅ KONSTANTA bentuk marking (samakan dengan Iqro: kotak membulat / tumpul)
const MARK_BORDER_RADIUS = '16px';
const MARK_PADDING       = '3px 8px';

function markWord(element) {
    let currentState = parseInt(element.getAttribute('data-mark-state')) || 0;
    currentState = (currentState + 1) % 4;
    element.setAttribute('data-mark-state', currentState);

    element.style.background   = '';
    element.style.borderRadius = '';
    element.style.padding      = '';
    element.style.boxShadow    = '';
    element.style.transform    = '';

    if (currentState === 1) {
        element.style.background   = 'linear-gradient(135deg, rgba(34, 197, 94, 0.25) 0%, rgba(34, 197, 94, 0.35) 100%)';
        element.style.borderRadius = MARK_BORDER_RADIUS;
        element.style.padding      = MARK_PADDING;
        element.style.boxShadow    = '0 2px 8px rgba(34, 197, 94, 0.3), inset 0 1px 2px rgba(255, 255, 255, 0.1)';
        element.style.transform    = 'translateY(-1px)';
    } else if (currentState === 2) {
        element.style.background   = 'linear-gradient(135deg, rgba(234, 179, 8, 0.3) 0%, rgba(234, 179, 8, 0.4) 100%)';
        element.style.borderRadius = MARK_BORDER_RADIUS;
        element.style.padding      = MARK_PADDING;
        element.style.boxShadow    = '0 2px 8px rgba(234, 179, 8, 0.35), inset 0 1px 2px rgba(255, 255, 255, 0.15)';
        element.style.transform    = 'translateY(-1px)';
    } else if (currentState === 3) {
        element.style.background   = 'linear-gradient(135deg, rgba(239, 68, 68, 0.25) 0%, rgba(239, 68, 68, 0.35) 100%)';
        element.style.borderRadius = MARK_BORDER_RADIUS;
        element.style.padding      = MARK_PADDING;
        element.style.boxShadow    = '0 2px 8px rgba(239, 68, 68, 0.3), inset 0 1px 2px rgba(255, 255, 255, 0.1)';
        element.style.transform    = 'translateY(-1px)';
    }

    updateErrorCounters();
}

/**
 * ✅ UPDATE ERROR COUNTERS — RUMUS BARU: -4 POIN PER KESALAHAN
 *
 * Total kata < 100  → nilai = 100 - (totalKesalahan × 4)
 * Total kata >= 100 → nilai = 100 - ((totalKesalahan × 4) / totalKata) × 100
 *
 * Hasil di-clamp ke range [0, 100]
 */
function updateErrorCounters() {
    const elementIds = getFormElementIds();

    const quranDisplay = document.getElementById('quranDisplay');
    const scope = quranDisplay || document;

    const kelancaranCount = scope.querySelectorAll('[data-mark-state="1"]').length;
    const tajwidCount     = scope.querySelectorAll('[data-mark-state="2"]').length;
    const pelafalanCount  = scope.querySelectorAll('[data-mark-state="3"]').length;

    const kelancaranInput = document.getElementById(elementIds.kesalahanKelancaran);
    const tajwidInput     = document.getElementById(elementIds.kesalahanTajwid);
    const pelafalanInput  = document.getElementById(elementIds.kesalahanPelafalan);

    if (kelancaranInput) kelancaranInput.value = kelancaranCount;
    if (tajwidInput)     tajwidInput.value     = tajwidCount;
    if (pelafalanInput)  pelafalanInput.value  = pelafalanCount;

    const displayKelancaran = document.getElementById(elementIds.displayKelancaran);
    const displayTajwid     = document.getElementById(elementIds.displayTajwid);
    const displayPelafalan  = document.getElementById(elementIds.displayPelafalan);

    if (displayKelancaran) displayKelancaran.textContent = kelancaranCount;
    if (displayTajwid)     displayTajwid.textContent     = tajwidCount;
    if (displayPelafalan)  displayPelafalan.textContent  = pelafalanCount;

    // ✅ HITUNG NILAI DENGAN BOBOT -4 POIN PER KESALAHAN
    const totalKesalahan = kelancaranCount + tajwidCount + pelafalanCount;
    const totalKata = quranDisplay ? quranDisplay.querySelectorAll('.word-item').length : 0;
    const totalPenalti = totalKesalahan * POIN_PER_KESALAHAN;

    let nilai = 100;
    if (totalKata > 0) {
        nilai = totalKata < 100
            ? 100 - totalPenalti
            : 100 - (totalPenalti / totalKata) * 100;
        nilai = Math.max(0, Math.min(100, Math.round(nilai)));
    }

    const nilaiInput   = document.getElementById(elementIds.nilai);
    const displayNilai = document.getElementById(elementIds.displayNilai);

    if (nilaiInput)   nilaiInput.value        = nilai;
    if (displayNilai) displayNilai.textContent = nilai;

    // Sync ke variabel global untuk PDF generation
    if (typeof window.kesalahanCount !== 'undefined') {
        window.kesalahanCount.kelancaran = kelancaranCount;
        window.kesalahanCount.tajwid     = tajwidCount;
        window.kesalahanCount.pelafalan  = pelafalanCount;
    }

    console.log('📊 Error counters updated:', { kelancaranCount, tajwidCount, pelafalanCount, totalKata, nilai });
}

function resetAllMarkings() {
    const quranDisplay = document.getElementById('quranDisplay');
    const scope = quranDisplay || document;

    scope.querySelectorAll('.word-item, [data-mark-state]').forEach(word => {
        word.setAttribute('data-mark-state', '0');
        word.style.background      = '';
        word.style.backgroundColor = '';
        word.style.borderRadius    = '';
        word.style.padding         = '';
        word.style.boxShadow       = '';
        word.style.transform       = '';
    });

    const elementIds = getFormElementIds();

    const kelancaranInput = document.getElementById(elementIds.kesalahanKelancaran);
    const tajwidInput     = document.getElementById(elementIds.kesalahanTajwid);
    const pelafalanInput  = document.getElementById(elementIds.kesalahanPelafalan);
    const nilaiInput      = document.getElementById(elementIds.nilai);

    if (kelancaranInput) kelancaranInput.value = 0;
    if (tajwidInput)     tajwidInput.value     = 0;
    if (pelafalanInput)  pelafalanInput.value  = 0;
    if (nilaiInput)      nilaiInput.value      = 100;

    const displayKelancaran = document.getElementById(elementIds.displayKelancaran);
    const displayTajwid     = document.getElementById(elementIds.displayTajwid);
    const displayPelafalan  = document.getElementById(elementIds.displayPelafalan);
    const displayNilai      = document.getElementById(elementIds.displayNilai);

    if (displayKelancaran) displayKelancaran.textContent = '0';
    if (displayTajwid)     displayTajwid.textContent     = '0';
    if (displayPelafalan)  displayPelafalan.textContent  = '0';
    if (displayNilai)      displayNilai.textContent      = '100';
}

let _countTotalWordsPending = false;

function countTotalWords() {
    if (_countTotalWordsPending) return 0;

    _countTotalWordsPending = true;
    setTimeout(function () { _countTotalWordsPending = false; }, 1000);

    const quranDisplay = document.getElementById('quranDisplay');

    if (!quranDisplay) {
        _countTotalWordsPending = false;
        return 0;
    }

    let words = quranDisplay.querySelectorAll('.word-item');
    if (words.length === 0) {
        words = quranDisplay.querySelectorAll('[data-word]');
    }

    const elementIds = getFormElementIds();
    const totalWords = words.length;

    const globalDisplay = document.getElementById('displayTotalKata');
    if (globalDisplay) globalDisplay.textContent = totalWords;

    const totalInput = document.getElementById(elementIds.totalKata);
    if (totalInput) totalInput.value = totalWords;

    return totalWords;
}

async function loadMultipleSurahsFromAPI(surahStartName, ayatStart, surahEndName, ayatEnd) {
    const surahStartInfo = surahMapping[surahStartName];
    const surahEndInfo   = surahMapping[surahEndName];

    if (!surahStartInfo || !surahEndInfo) throw new Error('Surah not found in mapping');

    const startNumber = surahStartInfo.number;
    const endNumber   = surahEndInfo.number;

    let allAyahs   = [];
    let surahNames = [];

    for (let surahNum = startNumber; surahNum <= endNumber; surahNum++) {
        const res  = await fetch(`https://api.alquran.cloud/v1/surah/${surahNum}`);
        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

        const json = await res.json();
        if (json.code !== 200 || !json.data) throw new Error('Invalid API response');

        const surahData = json.data;

        surahNames.push({
            number:     surahNum,
            name:       surahData.englishName,
            nameArabic: surahData.name
        });

        let ayahsToAdd = [];

        if (surahNum === startNumber) {
            ayahsToAdd = surahData.ayahs.filter(a => a.numberInSurah >= ayatStart);
        } else if (surahNum === endNumber) {
            ayahsToAdd = surahData.ayahs.filter(a => a.numberInSurah <= ayatEnd);
        } else {
            ayahsToAdd = surahData.ayahs;
        }

        ayahsToAdd.forEach(ayah => {
            ayah.surahNumber     = surahNum;
            ayah.surahName       = surahData.englishName;
            ayah.surahNameArabic = surahData.name;
        });

        if (ayahsToAdd.length > 0 && surahNum > startNumber) {
            ayahsToAdd[0].isNewSurah = true;
        }

        allAyahs = allAyahs.concat(ayahsToAdd);
    }

    return {
        success:         true,
        surahName:       surahNames.length === 1 ? surahNames[0].name : `${surahNames[0].name} - ${surahNames[surahNames.length - 1].name}`,
        surahNameArabic: surahNames.length === 1 ? surahNames[0].nameArabic : `${surahNames[0].nameArabic} - ${surahNames[surahNames.length - 1].nameArabic}`,
        surahNumber:     startNumber,
        surahRange:      surahNames,
        ayahs:           allAyahs,
        isMultiSurah:    surahNames.length > 1
    };
}

function renderQuranText(data) {
    const display = document.getElementById('quranDisplay');

    const firstSurahName   = data.surahRange ? data.surahRange[0].name      : data.surahName;
    const firstSurahArabic = data.surahRange ? data.surahRange[0].nameArabic : data.surahNameArabic;

    // ✅ THEME: warna pakai var(--q-fg,#fff) → terang/gelap di layar, putih saat PDF (fallback)
    let html = `
        <div class="text-center mb-6" style="text-align: center; color: var(--q-fg, #fff);">
            <p style="font-size: 38px; font-family: 'Amiri Quran', serif; margin-bottom: 8px; color: var(--q-fg, #fff);">${firstSurahArabic}</p>
            <p style="font-size: 16px; opacity: 0.7; font-family: 'Amiri Quran', serif; margin-top: 8px; color: var(--q-fg, #fff);">${firstSurahName}</p>
        </div>
    `;

    let currentSurahNumber = null;
    let surahAyahs         = [];

    data.ayahs.forEach((ayah, index) => {
        if (currentSurahNumber !== null && ayah.surahNumber !== currentSurahNumber) {
            html += renderSurahBox(surahAyahs);
            surahAyahs = [];
        }
        currentSurahNumber = ayah.surahNumber;
        surahAyahs.push(ayah);

        if (index === data.ayahs.length - 1) {
            html += renderSurahBox(surahAyahs);
        }
    });

    display.innerHTML = html;
}

function renderSurahBox(ayahs) {
    if (!ayahs || ayahs.length === 0) return '';

    const firstAyah     = ayahs[0];
    const isNotFirstBox = firstAyah.isNewSurah;

    let html = '';

    if (isNotFirstBox) {
        html += `
            <div style="margin: 30px 0 20px 0; text-align: center;">
                <p style="font-size: 36px; font-family: 'Amiri Quran', serif; margin-bottom: 4px; color: var(--q-fg, #fff); font-weight: normal;">${firstAyah.surahNameArabic}</p>
                <p style="font-size: 16px; opacity: 0.75; font-family: 'Amiri Quran', serif; color: var(--q-fg, #fff); margin: 0; font-weight: normal;">${firstAyah.surahName}</p>
            </div>
        `;
    }

    html += `
        <div style="border: 1.5px solid var(--q-box-border, rgba(255, 255, 255, 0.8)); border-radius: 0px; padding: 25px; margin: ${isNotFirstBox ? '15px' : '0'} auto 20px auto; max-width: 1000px; background-color: transparent;">
            <div id="quranText" style="font-size: 34px; color: var(--q-fg, #ffffff); line-height: 2.6; direction: rtl; text-align: justify; font-family: 'Amiri Quran', serif;">
    `;

    ayahs.forEach((ayah) => {
        let ayahText = removeBasmalahFromAyah(ayah.text.trim(), ayah.surahNumber, ayah.numberInSurah);
        const words  = ayahText.split(/\s+/);

        words.forEach((word, wordIndex) => {
            if (word.trim() !== '') {
                const processedWord = wrapWaqafSymbols(word);
                html += `<span class="word-item" data-word="${word}" data-mark-state="0" style="display: inline-block; line-height: 1.4; cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);" onclick="markWord(this)">${processedWord}</span>`;
                if (wordIndex < words.length - 1) html += ' ';
            }
        });

        html += `
            <span style="display: inline; margin: 0 6px; vertical-align: middle; font-size: 24px; color: var(--q-num, rgba(255,255,255,0.55)); font-family: 'Amiri Quran', serif;">(${convertToArabicNumber(ayah.numberInSurah)})</span>
        `;
    });

    html += `
            </div>
        </div>
    `;

    return html;
}

function addScrollbarStyles() {
    if (!document.getElementById('quran-scrollbar-style')) {
        const style = document.createElement('style');
        style.id    = 'quran-scrollbar-style';
        style.textContent = `
            #quranDisplay { max-height: 600px !important; overflow-y: auto !important; }
            #quranDisplay::-webkit-scrollbar { width: 12px; }
            #quranDisplay::-webkit-scrollbar-track { background: rgba(255,255,255,0.1); border-radius: 6px; margin: 4px; }
            #quranDisplay::-webkit-scrollbar-thumb { background: linear-gradient(180deg,#667eea 0%,#764ba2 100%); border-radius: 6px; border: 2px solid rgba(30,58,95,0.3); }
            #quranDisplay::-webkit-scrollbar-thumb:hover { background: linear-gradient(180deg,#764ba2 0%,#667eea 100%); border: 2px solid rgba(30,58,95,0.5); }
            #quranDisplay { scrollbar-width: thin; scrollbar-color: #667eea rgba(255,255,255,0.1); }
            .word-item { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
            .word-item:hover { opacity: 0.85; transform: translateY(-1px); }
            .word-item:active { transform: translateY(0); }

            /* ✅ PAKSA bentuk marking membulat — kebal terhadap markWord mana pun
               (inline style tanpa !important kalah oleh CSS !important ini) */
            #quranDisplay .word-item[data-mark-state="1"],
            #quranDisplay .word-item[data-mark-state="2"],
            #quranDisplay .word-item[data-mark-state="3"],
            #quranDisplayDrawer .word-item[data-mark-state="1"],
            #quranDisplayDrawer .word-item[data-mark-state="2"],
            #quranDisplayDrawer .word-item[data-mark-state="3"] {
                border-radius: 16px !important;
                padding: 12px 14px !important;
            }
        `;
        document.head.appendChild(style);
    }
}

function convertToArabicNumber(num) {
    const ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    return num.toString().split('').map(n => ar[n]).join('');
}

async function loadQuranFromAPI(surahName, ayatStart, ayatEnd) {
    const surahInfo = surahMapping[surahName];
    if (!surahInfo) throw new Error(`Surah ${surahName} not found in mapping`);

    const res  = await fetch(`https://api.alquran.cloud/v1/surah/${surahInfo.number}`);
    if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

    const json = await res.json();
    if (json.code !== 200 || !json.data) throw new Error('Invalid API response');

    return {
        success:         true,
        surahName:       json.data.englishName,
        surahNameArabic: json.data.name,
        surahNumber:     json.data.number,
        surahRange:      [{ number: json.data.number, name: json.data.englishName, nameArabic: json.data.name }],
        ayahs:           json.data.ayahs
            .filter(a => a.numberInSurah >= ayatStart && a.numberInSurah <= ayatEnd)
            .map(ayah => ({
                ...ayah,
                surahNumber:     json.data.number,
                surahName:       json.data.englishName,
                surahNameArabic: json.data.name
            })),
        isMultiSurah: false
    };
}

async function loadQuran() {
    _countTotalWordsPending = false;

    resetAllMarkings();

    const elementIds = getFormElementIds();

    const suratStartElement = document.getElementById(elementIds.suratStart);
    const ayatStartElement  = document.getElementById(elementIds.ayatStart);
    const suratEndElement   = document.getElementById(elementIds.suratEnd);
    const ayatEndElement    = document.getElementById(elementIds.ayatEnd);

    if (!suratStartElement || !ayatStartElement || !suratEndElement || !ayatEndElement) {
        throw new Error('Required form elements not found');
    }

    const suratStart = suratStartElement.value;
    const ayatStart  = parseInt(ayatStartElement.value);
    const suratEnd   = suratEndElement.value;
    const ayatEnd    = parseInt(ayatEndElement.value);

    if (!suratStart || !ayatStart || !suratEnd || !ayatEnd) {
        throw new Error('Missing required values');
    }

    try {
        const display     = document.getElementById('quranDisplay');
        display.innerHTML = '<p class="text-center" style="color: var(--q-fg, #fff); font-family: system-ui;">⏳ Memuat Al-Qur\'an...</p>';

        let data;

        if (suratStart === suratEnd) {
            data = await loadQuranFromAPI(suratStart, ayatStart, ayatEnd);
        } else {
            data = await loadMultipleSurahsFromAPI(suratStart, ayatStart, suratEnd, ayatEnd);
        }

        renderQuranText(data);
        addScrollbarStyles();

        setTimeout(() => {
            countTotalWords();
            updateErrorCounters();
        }, 300);

        setTimeout(() => {
            if (typeof window.autoFillPageFromAyat === 'function') {
                window.autoFillPageFromAyat();
            } else {
                calculateFallbackStatistics(suratStart, ayatStart, suratEnd, ayatEnd);
            }
        }, 500);

    } catch (e) {
        console.error('❌ Error loading Quran:', e);
        throw e;
    }
}

function calculateFallbackStatistics(suratStart, ayatStart, suratEnd, ayatEnd) {
    const elementIds = getFormElementIds();

    const quranDisplay = document.getElementById('quranDisplay');
    const totalWords   = quranDisplay ? quranDisplay.querySelectorAll('.word-item').length : 0;

    const suratStartNumber = surahMapping[suratStart]?.number || 1;
    const suratEndNumber   = surahMapping[suratEnd]?.number   || 1;

    const displayKata = document.getElementById(elementIds.displayTotalKata);
    const inputKata   = document.getElementById(elementIds.totalKata);
    if (displayKata) displayKata.textContent = totalWords;
    if (inputKata)   inputKata.value         = totalWords;

    const avgPagePerSurat    = 604 / 114;
    const estimatedPageStart = Math.floor(suratStartNumber * avgPagePerSurat);
    const estimatedPageEnd   = Math.floor(suratEndNumber * avgPagePerSurat) + Math.ceil((ayatEnd - ayatStart + 1) / 15);

    const pageStartEl = document.getElementById(elementIds.pageStart);
    const pageEndEl   = document.getElementById(elementIds.pageEnd);

    if (pageStartEl) pageStartEl.value = estimatedPageStart;
    if (pageEndEl)   pageEndEl.value   = estimatedPageEnd;
}

const link    = document.createElement('link');
link.rel      = 'stylesheet';
link.href     = 'https://fonts.googleapis.com/css2?family=Amiri+Quran&display=swap';
document.head.appendChild(link);

window.loadQuran                   = loadQuran;
window.loadQuranFromAPI            = loadQuranFromAPI;
window.loadMultipleSurahsFromAPI   = loadMultipleSurahsFromAPI;
window.renderQuranText             = renderQuranText;
window.renderSurahBox              = renderSurahBox;
window.countTotalWords             = countTotalWords;
window.markWord                    = markWord;
window.updateErrorCounters         = updateErrorCounters;
window.resetAllMarkings            = resetAllMarkings;
window.removeBasmalahFromAyah      = removeBasmalahFromAyah;
window.convertToArabicNumber       = convertToArabicNumber;
window.addScrollbarStyles          = addScrollbarStyles;
window.wrapWaqafSymbols            = wrapWaqafSymbols;
window.getFormElementIds           = getFormElementIds;
window.calculateFallbackStatistics = calculateFallbackStatistics;
window.surahMapping                = surahMapping;
window.POIN_PER_KESALAHAN          = POIN_PER_KESALAHAN;

console.log('✅ quran-api.js v4.1 LOADED — marking radius 16px (inline-block + line-height 1.4) + bobot -4 poin/kesalahan');