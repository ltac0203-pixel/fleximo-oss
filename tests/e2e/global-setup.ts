import { execSync } from 'child_process';

export default function globalSetup() {
  console.log('Running E2E test seeder...');
  execSync('php artisan db:seed --class=E2ETestSeeder --force', {
    cwd: process.cwd(),
    stdio: 'inherit',
  });
  console.log('E2E test seeder completed.');
}
