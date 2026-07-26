/**
 * Format number to Indonesian Rupiah currency
 * @param {number} amount - The amount to format
 * @param {boolean} showPrefix - Whether to show "Rp" prefix (default: true)
 * @returns {string} Formatted currency string
 * 
 * Examples:
 * formatCurrency(1000000) => "Rp 1.000.000"
 * formatCurrency(1500000.50) => "Rp 1.500.001"
 * formatCurrency(1000000, false) => "1.000.000"
 */
export const formatCurrency = (amount, showPrefix = true) => {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return showPrefix ? 'Rp 0' : '0';
    }
    
    const formatted = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Math.abs(amount));
    
    // Intl.NumberFormat returns "Rp1.000.000", we want "Rp 1.000.000" (with space)
    return showPrefix ? formatted.replace('Rp', 'Rp ') : formatted.replace('Rp', '').trim();
};

/**
 * Format number to short format with Rb/Jt suffix (Indonesian)
 * @param {number} num - The number to format
 * @returns {string} Formatted number string
 * 
 * Examples:
 * formatNumber(1500) => "1,5Rb"
 * formatNumber(1500000) => "1,5Jt"
 * formatNumber(500) => "500"
 */
export const formatNumber = (num) => {
    if (num === null || num === undefined || isNaN(num)) {
        return '0';
    }
    
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1).replace('.', ',') + 'Jt';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1).replace('.', ',') + 'Rb';
    }
    return num.toLocaleString('id-ID');
};

/**
 * Format number to Indonesian number format (with dot separator)
 * @param {number} num - The number to format
 * @returns {string} Formatted number string
 * 
 * Examples:
 * formatNumberID(1000000) => "1.000.000"
 */
export const formatNumberID = (num) => {
    if (num === null || num === undefined || isNaN(num)) {
        return '0';
    }
    return num.toLocaleString('id-ID');
};
