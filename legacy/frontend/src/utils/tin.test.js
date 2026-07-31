import { describe, expect, it } from 'vitest';

import { isValidTin } from './tin';

describe('isValidTin', () => {
  it('accepts exactly nine digits', () => {
    expect(isValidTin('123456789')).toBe(true);
  });

  it('trims surrounding whitespace', () => {
    expect(isValidTin('  123456789  ')).toBe(true);
  });

  it('rejects the wrong number of digits', () => {
    expect(isValidTin('12345678')).toBe(false);
    expect(isValidTin('1234567890')).toBe(false);
  });

  it('rejects non-digit characters and empty values', () => {
    expect(isValidTin('12345678a')).toBe(false);
    expect(isValidTin('')).toBe(false);
    expect(isValidTin(null)).toBe(false);
  });
});
