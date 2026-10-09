# Optional Vercel + Neon exercise

This is the lesson 12 database connection and insert exercise. The complete authenticated CRUD project is in `projects/full-stack/` and runs on PHP/SQLite. This optional exercise uses Node.js functions and PostgreSQL; it is not a drop-in PHP deployment or the full admin dashboard.

1. Import this folder into Vercel as the project root. Use Framework Other, no build command, and public as the output directory.
2. Connect Neon through Vercel Marketplace. Confirm the server environment contains DATABASE_URL; copy the provider connection string into that variable if the integration used a different name. Keep it out of browser JavaScript.
3. Run schema.sql once in Neon's SQL editor.
4. Redeploy so the new environment is applied.
5. Open /api/health and expect {"database":"connected"}.
6. Submit fictional data from the landing page and look for the row in the Neon SQL editor.

For local work, install dependencies and use Vercel CLI's `vercel dev` with a Development DATABASE_URL. The SQLite CLI is not used for this PostgreSQL exercise. Review provider free-plan limits in the dashboard before provisioning; a free database tier does not make all hosting usage free.

Use fictional data. This small public insert endpoint still needs appropriate abuse protection and privacy handling before collecting real inquiries. Read, update and delete remain authenticated features of the core PHP project; implementing those controls for a serverless adapter is a further exercise.
