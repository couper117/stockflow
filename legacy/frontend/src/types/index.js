// Shared JSDoc typedefs (see ./README.md). This module has no runtime exports;
// the `export {}` just makes it a module so `import('@/types').Foo` resolves.

/**
 * The standard API response envelope (backend CLAUDE.md 8).
 *
 * @typedef {object} ApiResponse
 * @property {boolean} success
 * @property {*} [data]
 * @property {string} [message] Localized message.
 * @property {Record<string, string[]>} [errors] Field validation errors.
 */

/**
 * The authenticated user, as returned by GET /api/auth/me.
 *
 * @typedef {object} AuthUser
 * @property {number} id
 * @property {string} name
 * @property {string} email
 * @property {string} locale
 * @property {number|null} assigned_shop_id
 * @property {number|null} assigned_stock_id
 * @property {{ name: string, label_key: string }} [role]
 * @property {{ id: number, name: string, tin_number: string }} [company]
 */

export {};
