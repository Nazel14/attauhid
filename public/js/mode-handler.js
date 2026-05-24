console.log('🎯 Mode Handler v2.3 - Loading...');

/**
 * ✅ MODE HANDLER v2.3
 * FIX: generateAndSavePDFToInput dipanggil dengan deviceType yang benar
 * Sebelumnya selalu pakai default 'desktop' → canvas 692px → 20-35 halaman
 */

function waitForQuranThenGeneratePDF(mode, maxWait = 30000) {
    const startTime = Date.now();
    const interval  = 500;

    const flagKey = '_pdfGenerating_' + mode;
    if (window[flagKey]) {
        console.log(`ℹ️ waitForQuranThenGeneratePDF(${mode}) already running, skip duplicate call`);
        return;
    }
    window[flagKey] = true;

    console.log(`⏳ Waiting for quranDisplay to be populated (mode: ${mode})...`);

    const timer = setInterval(() => {
        const quranDisplay = document.getElementById('quranDisplay');
        const elapsed      = Date.now() - startTime;
        const hasContent   = quranDisplay && quranDisplay.innerHTML.trim().length > 100;

        if (hasContent) {
            clearInterval(timer);
            window[flagKey] = false;
            console.log(`✅ quranDisplay populated after ${elapsed}ms — generating PDF for ${mode}`);

            if (typeof window.generateAndSavePDFToInput === 'function') {
                // ✅ FIX v2.3: detect deviceType dari lebar layar
                const deviceType = window.innerWidth <= 768  ? 'mobile'
                                 : window.innerWidth <= 1024 ? 'tablet'
                                 : 'desktop';
                console.log(`📱 Auto deviceType: ${deviceType} (innerWidth: ${window.innerWidth}px)`);
                window.generateAndSavePDFToInput(mode, deviceType);
            } else {
                console.warn('⚠️ generateAndSavePDFToInput not found — PDF will not be pre-generated');
            }
        } else if (elapsed >= maxWait) {
            clearInterval(timer);
            window[flagKey] = false;
            console.warn(`⚠️ Timeout waiting for quranDisplay (${maxWait}ms) — skipping PDF pre-generation`);
        }
    }, interval);
}

function loadQuranCombined() {
    console.log('📖 loadQuranCombined() called');

    if (typeof window.setActiveMode === 'function') window.setActiveMode('Combined');
    window.currentActiveMode  = 'Combined';
    window.currentFormContext = 'Combined';
    console.log('✅ Context set to: Combined');

    window.currentSuratStartId = 'suratStartCombined';
    window.currentAyatStartId  = 'ayatStartCombined';
    window.currentSuratEndId   = 'suratEndCombined';
    window.currentAyatEndId    = 'ayatEndCombined';

    const suratStart = document.getElementById('suratStartCombined')?.value;
    const ayatStart  = document.getElementById('ayatStartCombined')?.value;
    const suratEnd   = document.getElementById('suratEndCombined')?.value;
    const ayatEnd    = document.getElementById('ayatEndCombined')?.value;

    if (!suratStart || !ayatStart || !suratEnd || !ayatEnd) {
        alert('⚠️ Silakan lengkapi semua field (Surat & Ayat)');
        return;
    }

    if (typeof showQuranPanel === 'function') {
        showQuranPanel();
        console.log('✅ Quran panel shown');
    } else {
        const panelPetunjuk = document.getElementById('panelPetunjuk');
        const defaultView   = document.getElementById('defaultView');
        const quranPanel    = document.getElementById('quranPanel');
        if (panelPetunjuk) { panelPetunjuk.style.display = 'none'; panelPetunjuk.classList.add('hidden'); }
        if (defaultView)   { defaultView.style.display   = 'none'; defaultView.classList.add('hidden'); }
        if (quranPanel)    { quranPanel.style.display    = 'block'; quranPanel.classList.remove('hidden'); }
    }

    const quranDisplay = document.getElementById('quranDisplay');
    if (quranDisplay) quranDisplay.innerHTML = '';

    window['_pdfGenerating_Combined'] = false;

    if (typeof window.loadQuranWithValidation === 'function') {
        window.loadQuranWithValidation();
        console.log('✅ loadQuranWithValidation() called');
    } else if (typeof window.loadQuran === 'function') {
        window.loadQuran();
        console.log('✅ loadQuran() called (fallback)');
    } else {
        console.error('❌ No Quran loader function found!');
        alert('❌ Error: Fungsi load Quran tidak tersedia!');
        return;
    }

    waitForQuranThenGeneratePDF('Combined');
}

function loadQuranSetoranOnly() {
    console.log('📖 loadQuranSetoranOnly() called');

    if (typeof window.setActiveMode === 'function') window.setActiveMode('SetoranOnly');
    window.currentActiveMode  = 'SetoranOnly';
    window.currentFormContext = 'SetoranOnly';
    console.log('✅ Context set to: SetoranOnly');

    window.currentSuratStartId = 'suratStartSetoranOnly';
    window.currentAyatStartId  = 'ayatStartSetoranOnly';
    window.currentSuratEndId   = 'suratEndSetoranOnly';
    window.currentAyatEndId    = 'ayatEndSetoranOnly';

    const suratStart = document.getElementById('suratStartSetoranOnly')?.value;
    const ayatStart  = document.getElementById('ayatStartSetoranOnly')?.value;
    const suratEnd   = document.getElementById('suratEndSetoranOnly')?.value;
    const ayatEnd    = document.getElementById('ayatEndSetoranOnly')?.value;

    if (!suratStart || !ayatStart || !suratEnd || !ayatEnd) {
        alert('⚠️ Silakan lengkapi semua field (Surat & Ayat)');
        return;
    }

    if (typeof showQuranPanel === 'function') {
        showQuranPanel();
        console.log('✅ Quran panel shown');
    } else {
        const panelPetunjuk = document.getElementById('panelPetunjuk');
        const defaultView   = document.getElementById('defaultView');
        const quranPanel    = document.getElementById('quranPanel');
        if (panelPetunjuk) { panelPetunjuk.style.display = 'none'; panelPetunjuk.classList.add('hidden'); }
        if (defaultView)   { defaultView.style.display   = 'none'; defaultView.classList.add('hidden'); }
        if (quranPanel)    { quranPanel.style.display    = 'block'; quranPanel.classList.remove('hidden'); }
    }

    const quranDisplay = document.getElementById('quranDisplay');
    if (quranDisplay) quranDisplay.innerHTML = '';

    window['_pdfGenerating_SetoranOnly'] = false;

    if (typeof window.loadQuranWithValidation === 'function') {
        window.loadQuranWithValidation();
        console.log('✅ loadQuranWithValidation() called');
    } else if (typeof window.loadQuran === 'function') {
        window.loadQuran();
        console.log('✅ loadQuran() called (fallback)');
    } else {
        console.error('❌ No Quran loader function found!');
        alert('❌ Error: Fungsi load Quran tidak tersedia!');
        return;
    }

    waitForQuranThenGeneratePDF('SetoranOnly');
}

window.loadQuranCombined             = loadQuranCombined;
window.loadQuranSetoranOnly          = loadQuranSetoranOnly;
window.waitForQuranThenGeneratePDF   = waitForQuranThenGeneratePDF;

console.log('✅ Mode Handler v2.3 - READY');
console.log('   ✔ FIX: deviceType auto-detect dari window.innerWidth');
console.log('   ✔ mobile ≤768px → captureMobile_v11_60 (RENDER_WIDTH=1400px)');
console.log('   ✔ tablet ≤1024px → captureTabletMobile');
console.log('   ✔ desktop >1024px → captureDesktop');