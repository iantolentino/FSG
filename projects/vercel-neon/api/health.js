import { neon } from '@neondatabase/serverless';
export default async function handler(req, res) {
  res.setHeader('Cache-Control', 'no-store');
  if (req.method !== 'GET') return res.status(405).json({ error: 'Use GET.' });
  if (!process.env.DATABASE_URL) return res.status(503).json({ error: 'Configure DATABASE_URL and redeploy.' });
  try {
    const sql = neon(process.env.DATABASE_URL);
    await sql`SELECT 1 AS connected`;
    return res.json({ database: 'connected' });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ error: 'Database connection failed. Check private function logs.' });
  }
}
