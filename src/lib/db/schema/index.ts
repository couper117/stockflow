// Drizzle schema barrel. Each table group lives in its own file and is re-exported
// here so `import * as schema from '@/lib/db/schema'` sees everything.
//
// Tables arrive with their migrations (see ../architecture/03-database-design.md §5):
//   001 tenancy/identity/RLS: businesses, users, sessions, password_reset_tokens,
//                             audit_log, business_settings
//   002 catalogue:            locations, units, categories, products,
//                             movement_reasons, user_locations
//   003 stock engine:         stock_levels, movements, stock_requests
//   004 billing [P2]:         plans, subscriptions, payments
//
// RLS (enable + force + policy) and role GRANT/REVOKE are hand-written raw SQL in
// ../../../drizzle/ because drizzle-kit does not model them.

export {};
