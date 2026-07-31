import { execSync } from 'node:child_process';

// Drops and recreates the schema, then re-applies all migrations. Convenience for
// local dev and the tenant-isolation "break it on purpose" check. Uses the migrate
// role. Full drop logic lands with migration 001 (SF-003).
function run(cmd: string) {
  execSync(cmd, { stdio: 'inherit' });
}

console.log('db:reset — dropping and re-migrating…');
// Placeholder: once migration 001 exists, DROP SCHEMA public CASCADE; CREATE SCHEMA public;
run('npm run db:migrate');
console.log('db:reset complete.');
