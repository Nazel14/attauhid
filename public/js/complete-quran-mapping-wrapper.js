/**
 * ✅ COMPLETE QURAN MAPPING WRAPPER
 * Menjembatani complete-quran-mapping.js (yang virgin) dengan create-input (multi-context)
 * 
 * TIDAK MENGUBAH complete-quran-mapping.js sama sekali
 * Hanya membuat wrapper function
 * 
 * FIX v1.1: Hapus pemanggilan triggerStatisticsUpdate() untuk mencegah
 * double countTotalWords() — countTotalWords() sudah dipanggil dari
 * loadQuran() setTimeout 300ms di quran-api.js
 */

console.log('🔗 Complete Quran Mapping Wrapper loaded');

/**
 * ✅ Override updateAccuratePageDisplay untuk support multi-context
 */
if (typeof window.updateAccuratePageDisplay === 'function') {
    // Backup original function
    window.updateAccuratePageDisplay_original = window.updateAccuratePageDisplay;
    
    // Override dengan wrapper
    window.updateAccuratePageDisplay = function() {
        console.log('🔧 updateAccuratePageDisplay (WRAPPER) called');
        
        // Detect context
        const context = window.currentFormContext || detectCurrentContext();
        const suffix = context === 'SetoranOnly' ? 'SetoranOnly' : 'Combined';
        
        console.log('📋 Wrapper context:', context, '| suffix:', suffix);
        
        // ✅ Get element IDs based on context
        const suratStartEl = document.getElementById(`suratStart${suffix}`);
        const ayatStartEl = document.getElementById(`ayatStart${suffix}`);
        const suratEndEl = document.getElementById(`suratEnd${suffix}`);
        const ayatEndEl = document.getElementById(`ayatEnd${suffix}`);
        
        if (!suratStartEl || !ayatStartEl || !suratEndEl || !ayatEndEl) {
            console.error('❌ Elements not found for context:', context);
            return;
        }
        
        const suratStart = suratStartEl.value;
        const ayatStart = parseInt(ayatStartEl.value);
        const suratEnd = suratEndEl.value;
        const ayatEnd = parseInt(ayatEndEl.value);
        
        if (!suratStart || !ayatStart || !suratEnd || !ayatEnd) {
            console.warn('⚠️ Missing values');
            return;
        }
        
        const surahNumStart = parseInt(suratStart.match(/^(\d+)\./)?.[1]);
        const surahNumEnd = parseInt(suratEnd.match(/^(\d+)\./)?.[1]);
        
        if (!surahNumStart || !surahNumEnd) {
            console.error('❌ Invalid surat format');
            return;
        }
        
        // ✅ Calculate using complete-quran-mapping functions
        if (typeof window.getAccuratePageAndPortion !== 'function' || 
            typeof window.calculateTotalPages !== 'function') {
            console.error('❌ Required functions not found in complete-quran-mapping.js');
            return;
        }
        
        const dataStart = window.getAccuratePageAndPortion(surahNumStart, ayatStart);
        const dataEnd = window.getAccuratePageAndPortion(surahNumEnd, ayatEnd);
        
        if (!dataStart || !dataEnd) {
            console.error('❌ Failed to get page data');
            return;
        }
        
        const totalPages = window.calculateTotalPages(surahNumStart, ayatStart, surahNumEnd, ayatEnd);
        
        // ✅ Update hidden inputs
        const pageStartEl = document.getElementById(`pageStart${suffix}`);
        const pageEndEl = document.getElementById(`pageEnd${suffix}`);
        const pageStartPortionEl = document.getElementById(`pageStartPortion${suffix}`);
        const pageEndPortionEl = document.getElementById(`pageEndPortion${suffix}`);
        const totalPageEl = document.getElementById(`totalPage${suffix}`);
        
        if (pageStartEl) pageStartEl.value = dataStart.page;
        if (pageEndEl) pageEndEl.value = dataEnd.page;
        if (pageStartPortionEl) pageStartPortionEl.value = dataStart.portion;
        if (pageEndPortionEl) pageEndPortionEl.value = dataEnd.portion;
        if (totalPageEl) totalPageEl.value = totalPages.toFixed(2);
        
        // ✅ Update display elements
        const displayPageStart = document.getElementById('displayPageStart');
        const displayPageEnd = document.getElementById('displayPageEnd');
        const displayTotalPage = document.getElementById('displayTotalPage');
        
        if (displayPageStart) displayPageStart.textContent = dataStart.page;
        if (displayPageEnd) displayPageEnd.textContent = dataEnd.page;
        if (displayTotalPage) displayTotalPage.textContent = totalPages.toFixed(2);
        
        // ✅ Update Juz
        const juzStart = Math.ceil(dataStart.page / 20);
        const juzEnd = Math.ceil(dataEnd.page / 20);
        const totalJuz = (totalPages / 20).toFixed(2);
        
        const juzStartEl = document.getElementById(`juzStart${suffix}`);
        const juzEndEl = document.getElementById(`juzEnd${suffix}`);
        const totalJuzEl = document.getElementById(`totalJuz${suffix}`);
        const displayJuzRange = document.getElementById('displayJuzRange');
        const displayTotalJuz = document.getElementById('displayTotalJuz');
        
        if (juzStartEl) juzStartEl.value = juzStart;
        if (juzEndEl) juzEndEl.value = juzEnd;
        if (totalJuzEl) totalJuzEl.value = totalJuz;
        
        if (displayJuzRange) {
            displayJuzRange.textContent = juzStart === juzEnd 
                ? `${juzStart}` 
                : `${juzStart}-${juzEnd}`;
        }
        
        if (displayTotalJuz) {
            displayTotalJuz.textContent = totalJuz;
        }
        
        console.log('✅ Wrapper updated all fields:', {
            pages: `${dataStart.page}-${dataEnd.page}`,
            totalPages: totalPages.toFixed(2),
            juzStart,
            juzEnd,
            totalJuz,
            context
        });

        // ℹ️ FIX v1.1: triggerStatisticsUpdate() DIHAPUS dari sini
        // countTotalWords() sudah dipanggil dari loadQuran() setTimeout 300ms
        // Memanggil triggerStatisticsUpdate() di sini menyebabkan double count total kata
    };
    
    console.log('✅ updateAccuratePageDisplay wrapped successfully');
} else {
    console.error('❌ updateAccuratePageDisplay not found - complete-quran-mapping.js not loaded?');
}

/**
 * Helper function
 */
function detectCurrentContext() {
    const formCombined = document.getElementById('formCombined');
    const formSetoranOnly = document.getElementById('formSetoranOnly');
    
    if (formCombined && !formCombined.classList.contains('hidden')) {
        return 'Combined';
    } else if (formSetoranOnly && !formSetoranOnly.classList.contains('hidden')) {
        return 'SetoranOnly';
    }
    
    return 'Combined';
}

console.log('✅ Complete Quran Mapping Wrapper ready');
console.log('   ✔ FIX v1.1: triggerStatisticsUpdate() dihapus → tidak ada double countTotalWords()');