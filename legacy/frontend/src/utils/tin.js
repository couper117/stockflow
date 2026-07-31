// Rwanda TIN = exactly 9 digits. Mirrors the backend App\Rules\TinNumber so the
// client can give immediate feedback (the server remains the source of truth).
export function isValidTin(value) {
  return /^\d{9}$/.test(String(value ?? '').trim());
}
