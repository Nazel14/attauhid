/**
 * Simple Page and Juz Calculator
 * File: public/js/page-juz-calculator.js
 */

const PAGES_PER_JUZ = 20; // Rata-rata halaman per juz

/**
 * Calculate page and juz from surat and ayat
 * This function is called by auto-fill-page.js
 * 
 * IMPORTANT: This delegates to complete-quran-mapping.js which already has accurate calculations
 */
window.calculatePageAndJuz = function(suratNumber, ayatNumber) {
    console.log(`🔍 calculatePageAndJuz called: Surat ${suratNumber}, Ayat ${ayatNumber}`);
    
    // Strategy: Use the existing accurate calculation from complete-quran-mapping.js
    // by triggering calculatePageFromSuratAyat which is already working
    
    // First, check if we can get the page info from DOM elements (already calculated)
    const pageStartInput = document.getElementById('pageStart');
    const pageEndInput = document.getElementById('pageEnd');
    
    // If complete-quran-mapping.js already calculated it, use those values
    if (pageStartInput && pageStartInput.value && parseFloat(pageStartInput.value) > 0) {
        const page = parseFloat(pageStartInput.value);
        const juz = Math.ceil(page / PAGES_PER_JUZ);
        console.log(`✅ Using pre-calculated value from complete-quran-mapping.js: Page ${page}, Juz ${juz}`);
        return { page: Math.floor(page), juz: juz };
    }
    
    // Try to use window.calculatePageFromSuratAyat if available
    if (typeof window.calculatePageFromSuratAyat === 'function') {
        console.log('✅ Using calculatePageFromSuratAyat from complete-quran-mapping.js');
        
        try {
            // This function should return page info
            const result = window.calculatePageFromSuratAyat(suratNumber, ayatNumber, suratNumber, ayatNumber);
            
            if (result && result.pageStart) {
                const page = Math.floor(result.pageStart);
                const juz = Math.ceil(page / PAGES_PER_JUZ);
                console.log(`✅ Found via calculatePageFromSuratAyat: Page ${page}, Juz ${juz}`);
                return { page: page, juz: juz };
            }
        } catch (error) {
            console.error('❌ Error using calculatePageFromSuratAyat:', error);
        }
    }
    
    // Try findPageForVerse if available
    if (typeof window.findPageForVerse === 'function') {
        console.log('✅ Trying findPageForVerse from complete-quran-mapping.js');
        
        try {
            const pageNumber = window.findPageForVerse(suratNumber, ayatNumber);
            
            if (pageNumber && pageNumber > 0) {
                const juzNumber = Math.ceil(pageNumber / PAGES_PER_JUZ);
                console.log(`✅ Found via findPageForVerse: Page ${pageNumber}, Juz ${juzNumber}`);
                return { page: pageNumber, juz: juzNumber };
            }
        } catch (error) {
            console.error('❌ Error using findPageForVerse:', error);
        }
    }
    
    // Check alternative: direct quranMapping object
    if (typeof window.quranMapping === 'object' && window.quranMapping) {
        console.log('✅ Trying quranMapping object from complete-quran-mapping.js');
        
        try {
            const mapping = window.quranMapping;
            let foundPage = null;
            
            // Search through all pages
            for (let page = 1; page <= 604; page++) {
                const pageData = mapping[page];
                if (!pageData) continue;
                
                // Handle different possible structures
                const verses = pageData.verses || pageData.v || pageData;
                if (!Array.isArray(verses)) continue;
                
                for (let verse of verses) {
                    const verseSurah = verse.surah || verse.s || verse.surat;
                    const verseStart = verse.start || verse.a || verse.ayat;
                    const verseEnd = verse.end || verse.e || verseStart;
                    
                    if (verseSurah === suratNumber && 
                        ayatNumber >= verseStart && 
                        ayatNumber <= verseEnd) {
                        foundPage = page;
                        break;
                    }
                }
                
                if (foundPage) break;
            }
            
            if (foundPage) {
                const juzNumber = Math.ceil(foundPage / PAGES_PER_JUZ);
                console.log(`✅ Found via quranMapping: Page ${foundPage}, Juz ${juzNumber}`);
                return { page: foundPage, juz: juzNumber };
            }
        } catch (error) {
            console.error('❌ Error using quranMapping:', error);
        }
    }
    
    // Fallback: Use estimation if nothing else works
    console.warn('⚠️ Using estimation fallback - complete-quran-mapping.js not available');
    const avgPagePerSurat = 604 / 114;
    const estimatedPage = Math.floor(suratNumber * avgPagePerSurat);
    const estimatedJuz = Math.ceil(estimatedPage / PAGES_PER_JUZ);
    
    return { 
        page: estimatedPage, 
        juz: estimatedJuz 
    };
};

/**
 * Hitung total page dengan support partial page
 */
function calculateTotalPage() {
    const pageStart = parseFloat(document.getElementById('pageStart')?.value) || 0;
    const pageEnd = parseFloat(document.getElementById('pageEnd')?.value) || 0;
    const pageStartPortion = parseFloat(document.getElementById('pageStartPortion')?.value) || 0;
    const pageEndPortion = parseFloat(document.getElementById('pageEndPortion')?.value) || 100;
    
    if (!pageStart || !pageEnd || pageEnd < pageStart) {
        return 0;
    }
    
    // Jika halaman sama (satu halaman saja)
    if (pageStart === pageEnd) {
        const totalPage = (pageEndPortion - pageStartPortion) / 100;
        return Math.max(0, Math.round(totalPage * 100) / 100);
    }
    
    // Jika beda halaman
    const firstPagePortion = (100 - pageStartPortion) / 100;
    const lastPagePortion = pageEndPortion / 100;
    const middlePages = Math.max(0, pageEnd - pageStart - 1);
    const totalPage = firstPagePortion + middlePages + lastPagePortion;
    
    return Math.round(totalPage * 100) / 100;
}

/**
 * Hitung juz berdasarkan halaman
 */
function calculateJuz() {
    const pageStart = parseFloat(document.getElementById('pageStart')?.value) || 0;
    const pageEnd = parseFloat(document.getElementById('pageEnd')?.value) || 0;
    
    if (!pageStart || !pageEnd) {
        return { start: 0, end: 0, total: 0 };
    }
    
    // Juz = ceil(page / 20)
    const juzStart = Math.ceil(pageStart / PAGES_PER_JUZ);
    const juzEnd = Math.ceil(pageEnd / PAGES_PER_JUZ);
    
    // Total juz dalam desimal
    const totalPage = calculateTotalPage();
    const totalJuz = Math.round((totalPage / PAGES_PER_JUZ) * 100) / 100;
    
    return {
        start: juzStart,
        end: juzEnd,
        total: totalJuz
    };
}

/**
 * Update tampilan statistik
 */
function updatePageJuzDisplay() {
    // Total Page
    const totalPage = calculateTotalPage();
    const displayTotalPage = document.getElementById('displayTotalPage');
    if (displayTotalPage) {
        displayTotalPage.textContent = totalPage.toFixed(2);
    }
    
    // Juz calculations
    const juzData = calculateJuz();
    
    // Juz Range
    const displayJuzRange = document.getElementById('displayJuzRange');
    if (displayJuzRange) {
        if (juzData.start === juzData.end && juzData.start > 0) {
            displayJuzRange.textContent = `Juz ${juzData.start}`;
        } else if (juzData.start > 0) {
            displayJuzRange.textContent = `Juz ${juzData.start} - ${juzData.end}`;
        } else {
            displayJuzRange.textContent = '-';
        }
    }
    
    // Total Juz
    const displayTotalJuz = document.getElementById('displayTotalJuz');
    if (displayTotalJuz) {
        displayTotalJuz.textContent = juzData.total.toFixed(2);
    }
    
    // Update target progress
    updateTargetProgress(juzData.total);
    
    console.log('📊 Calculated:', {
        totalPage: totalPage.toFixed(2),
        juzStart: juzData.start,
        juzEnd: juzData.end,
        totalJuz: juzData.total.toFixed(2)
    });
}

/**
 * Update progress target juz
 */
function updateTargetProgress(totalJuz) {
    const targetJuzInput = document.getElementById('targetJuz');
    const targetProgress = document.getElementById('targetProgress');
    const targetPercentage = document.getElementById('targetPercentage');
    const targetProgressBar = document.getElementById('targetProgressBar');
    
    if (!targetJuzInput || !targetProgress) return;
    
    const targetJuz = parseFloat(targetJuzInput.value) || 0;
    
    if (targetJuz > 0 && totalJuz > 0) {
        const percentage = Math.min(100, (totalJuz / targetJuz) * 100);
        
        targetProgress.classList.remove('hidden');
        targetPercentage.textContent = percentage.toFixed(1) + '%';
        targetProgressBar.style.width = percentage + '%';
        
        // Change color based on achievement
        if (percentage >= 100) {
            targetProgressBar.className = 'bg-green-500 h-2 rounded-full transition-all duration-300';
            targetPercentage.className = 'font-semibold text-green-600';
        } else if (percentage >= 75) {
            targetProgressBar.className = 'bg-blue-500 h-2 rounded-full transition-all duration-300';
            targetPercentage.className = 'font-semibold text-blue-600';
        } else if (percentage >= 50) {
            targetProgressBar.className = 'bg-yellow-500 h-2 rounded-full transition-all duration-300';
            targetPercentage.className = 'font-semibold text-yellow-600';
        } else {
            targetProgressBar.className = 'bg-red-500 h-2 rounded-full transition-all duration-300';
            targetPercentage.className = 'font-semibold text-red-600';
        }
    } else {
        targetProgress.classList.add('hidden');
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Page & Juz Calculator Loaded (with calculatePageAndJuz function)');
    
    const pageStart = document.getElementById('pageStart');
    const pageEnd = document.getElementById('pageEnd');
    const targetJuz = document.getElementById('targetJuz');
    
    if (pageStart) {
        pageStart.addEventListener('input', updatePageJuzDisplay);
    }
    
    if (pageEnd) {
        pageEnd.addEventListener('input', updatePageJuzDisplay);
    }
    
    if (targetJuz) {
        targetJuz.addEventListener('input', updatePageJuzDisplay);
    }
    
    // Initial calculation
    updatePageJuzDisplay();
});