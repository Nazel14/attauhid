// ====================================
// AUTO FILL PAGE & JUZ FROM AYAT
// ✅ MULTI CONTEXT SUPPORT
// ====================================

console.log('📄 auto-fill-page.js loaded - MULTI CONTEXT VERSION');

/**
 * ✅ CRITICAL FIX: Detect current form context automatically
 */
function detectCurrentContext() {
    // Check which form is visible
    const formCombined = document.getElementById('formCombined');
    const formSetoranOnly = document.getElementById('formSetoranOnly');
    
    if (formCombined && !formCombined.classList.contains('hidden')) {
        return 'Combined';
    } else if (formSetoranOnly && !formSetoranOnly.classList.contains('hidden')) {
        return 'SetoranOnly';
    }
    
    // Fallback: check window.currentFormContext
    if (window.currentFormContext) {
        return window.currentFormContext;
    }
    
    // Default fallback
    return 'Combined';
}

/**
 * ✅ FIXED: Get element IDs based on context
 */
function getElementIds(context) {
    const suffix = context === 'SetoranOnly' ? 'SetoranOnly' : 'Combined';
    
    return {
        suratStart: `suratStart${suffix}`,
        ayatStart: `ayatStart${suffix}`,
        suratEnd: `suratEnd${suffix}`,
        ayatEnd: `ayatEnd${suffix}`,
        pageStart: `pageStart${suffix}`,
        pageEnd: `pageEnd${suffix}`,
        totalPage: `totalPage${suffix}`,
        juzStart: `juzStart${suffix}`,
        juzEnd: `juzEnd${suffix}`,
        totalJuz: `totalJuz${suffix}`
    };
}

/**
 * ✅ FIXED: Auto-fill page berdasarkan context yang aktif
 */
window.autoFillPageFromAyat = function() {
    console.log('🔄 autoFillPageFromAyat called');
    
    // ✅ Detect context
    const context = detectCurrentContext();
    console.log('📋 Detected context:', context);
    
    // ✅ Get element IDs
    const ids = getElementIds(context);
    console.log('🆔 Using element IDs:', ids);
    
    // ✅ Get values with null checks
    const suratStartEl = document.getElementById(ids.suratStart);
    const ayatStartEl = document.getElementById(ids.ayatStart);
    const suratEndEl = document.getElementById(ids.suratEnd);
    const ayatEndEl = document.getElementById(ids.ayatEnd);
    
    if (!suratStartEl || !ayatStartEl || !suratEndEl || !ayatEndEl) {
        console.error('❌ Elements not found:', {
            suratStart: !!suratStartEl,
            ayatStart: !!ayatStartEl,
            suratEnd: !!suratEndEl,
            ayatEnd: !!ayatEndEl
        });
        return;
    }
    
    const suratStart = suratStartEl.value;
    const ayatStart = parseInt(ayatStartEl.value);
    const suratEnd = suratEndEl.value;
    const ayatEnd = parseInt(ayatEndEl.value);
    
    if (!suratStart || !ayatStart || !suratEnd || !ayatEnd) {
        console.warn('⚠️ Missing surat or ayat values');
        return;
    }
    
    // Extract surat number dari format "1. Al-Fatihah"
    const suratNumberStart = parseInt(suratStart.split('.')[0]);
    const suratNumberEnd = parseInt(suratEnd.split('.')[0]);
    
    console.log(`📖 Calculating page for Surat ${suratNumberStart}:${ayatStart} - ${suratNumberEnd}:${ayatEnd}`);
    
    // ✅ CRITICAL: Trigger complete-quran-mapping calculation
    // Yang ini sudah benar dan tidak perlu diubah
    if (typeof window.updateAccuratePageDisplay === 'function') {
        console.log('✅ Using accurate page calculation from complete-quran-mapping.js');
        
        // Set context untuk complete-quran-mapping
        window.currentFormContext = context;
        
        // Trigger calculation
        window.updateAccuratePageDisplay();
        
        console.log('✅ Page calculation triggered successfully');
    } else {
        console.error('❌ updateAccuratePageDisplay not found! complete-quran-mapping.js not loaded?');
    }
};

// Export
window.detectCurrentContext = detectCurrentContext;
window.getElementIds = getElementIds;

console.log('✅ auto-fill-page.js ready - MULTI CONTEXT SUPPORT enabled');