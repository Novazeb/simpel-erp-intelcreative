import Big from 'big.js';

/**
 * Konversi nilai aman (string, number, null) ke objek Big.
 * Mencegah kebocoran presisi desimal JavaScript (umpanbalik4.md poin 3).
 *
 * @param {string|number|null|undefined} val
 * @returns {Big}
 */
export function toBig(val) {
  if (val === null || val === undefined || val === '') {
    return new Big(0);
  }
  try {
    return new Big(val);
  } catch {
    return new Big(0);
  }
}

/**
 * Penjumlahan presisi tinggi (a + b).
 */
export function add(a, b) {
  return toBig(a).plus(toBig(b)).toFixed(2);
}

/**
 * Pengurangan presisi tinggi (a - b).
 */
export function subtract(a, b) {
  return toBig(a).minus(toBig(b)).toFixed(2);
}

/**
 * Perkalian presisi tinggi (a * b).
 */
export function multiply(a, b) {
  return toBig(a).times(toBig(b)).toFixed(2);
}

/**
 * Pembagian presisi tinggi (a / b).
 */
export function divide(a, b) {
  const divisor = toBig(b);
  if (divisor.eq(0)) {
    return '0.00';
  }
  return toBig(a).div(divisor).toFixed(2);
}

/**
 * Menjumlahkan koleksi baris berdasarkan properti kunci secara presisi.
 *
 * @param {Array} items
 * @param {string|Function} keyOrAccessor
 * @returns {string}
 */
export function sumBy(items, keyOrAccessor) {
  if (!Array.isArray(items) || items.length === 0) {
    return '0.00';
  }

  const getter = typeof keyOrAccessor === 'function' 
    ? keyOrAccessor 
    : (item) => item?.[keyOrAccessor];

  const total = items.reduce((acc, curr) => {
    return acc.plus(toBig(getter(curr)));
  }, new Big(0));

  return total.toFixed(2);
}

/**
 * Format nominal finansial ke Rupiah dengan pemisah ribuan id-ID aman tanpa floating point loss.
 *
 * @param {string|number|null|undefined} val
 * @returns {string}
 */
export function formatCurrency(val) {
  const b = toBig(val);
  const fixed = b.toFixed(2);
  const [intPart, decPart] = fixed.split('.');

  // Format integer part dengan titik ribuan
  const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  if (decPart && decPart !== '00') {
    return `Rp ${formattedInt},${decPart}`;
  }
  return `Rp ${formattedInt}`;
}

/**
 * Contoh kalkulasi subtotal aman sesuai spesifikasi umpanbalik4.md
 */
export function calculateSubtotal(items) {
  if (!Array.isArray(items)) return '0.00';
  return items.reduce((total, item) => {
    const price = toBig(item.unit_price || 0);
    const qty = toBig(item.quantity || 0);
    return total.plus(price.times(qty));
  }, new Big(0)).toFixed(2);
}

export default Big;

