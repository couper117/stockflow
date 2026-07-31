// Seeds TWO businesses so tenant isolation is visible from day one (SF-DOC-05 §7).
// Business A: several stock rooms + shops, 22 units, categories, ~40 products,
//   movements incl. transfers, requests in every status, users covering all four
//   roles (two sellers, each bound to a DIFFERENT shop, to exercise shop privacy).
// Business B: a smaller, deliberately different set using the SAME product codes,
//   so any cross-tenant leak is obvious.
//
// Implemented with the catalogue + stock tickets (SF-010…022). Placeholder for now.
async function main() {
  console.log('seed: not yet implemented — arrives with the catalogue tickets (SF-010+).');
}

void main();

export {};
