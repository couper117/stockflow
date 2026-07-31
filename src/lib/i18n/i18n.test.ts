import { describe, expect, it } from 'vitest';

import en from './messages/en.json';
import rw from './messages/rw.json';

// CI guard (SF-DOC prompt 4 / FR-UIX-07): en and rw must have IDENTICAL key sets.
// A key present in one and not the other fails the build — this is the check that
// stops the two catalogues drifting apart.
function flatKeys(obj: unknown, prefix = ''): string[] {
  if (obj === null || typeof obj !== 'object') return [prefix];
  return Object.entries(obj as Record<string, unknown>).flatMap(([k, v]) =>
    flatKeys(v, prefix ? `${prefix}.${k}` : k),
  );
}

describe('i18n message catalogues', () => {
  it('en and rw have identical key sets', () => {
    const enKeys = new Set(flatKeys(en));
    const rwKeys = new Set(flatKeys(rw));

    const missingInRw = [...enKeys].filter((k) => !rwKeys.has(k));
    const missingInEn = [...rwKeys].filter((k) => !enKeys.has(k));

    expect(missingInRw, `keys missing in rw.json: ${missingInRw.join(', ')}`).toEqual([]);
    expect(missingInEn, `keys missing in en.json: ${missingInEn.join(', ')}`).toEqual([]);
  });
});
