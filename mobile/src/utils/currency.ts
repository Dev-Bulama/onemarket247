import { Money } from '../types';

/**
 * Every price the API returns already carries a server-formatted string
 * (see App\Support\Api\Money) — this exists only for the rare spot that
 * needs to build a string from a raw minor-unit amount (e.g. a live total
 * computed client-side before the server has echoed it back).
 */
export function formatMinorAmount(minorAmount: number, currency = 'NGN'): string {
  const symbol = CURRENCY_SYMBOLS[currency] ?? currency + ' ';
  return `${symbol}${(minorAmount / 100).toLocaleString('en-NG', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
}

export function money(value?: Money | null): string {
  return value?.formatted ?? '—';
}

const CURRENCY_SYMBOLS: Record<string, string> = {
  NGN: '₦',
  USD: '$',
  GBP: '£',
  EUR: '€',
};
