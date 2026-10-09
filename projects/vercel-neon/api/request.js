import { neon } from '@neondatabase/serverless';
export default async function handler(req, res) {
  res.setHeader('Cache-Control', 'no-store');
  if (req.method !== 'POST') { res.setHeader('Allow', 'POST'); return res.status(405).json({ error: 'Use POST.' }); }
  const origin = req.headers.origin;
  const host = req.headers['x-forwarded-host'] || req.headers.host;
  try {
    if (!origin || new URL(origin).host !== host) return res.status(403).json({ error: 'Submit from this project website.' });
  } catch { return res.status(403).json({ error: 'Invalid request origin.' }); }
  let body = req.body;
  if (typeof body === 'string') { try { body = JSON.parse(body); } catch { return res.status(400).json({ error: 'Send valid JSON.' }); } }
  if (!body || typeof body !== 'object' || Array.isArray(body)) return res.status(400).json({ error: 'Send a JSON object.' });
  const values = {};
  for (const [field, limit] of Object.entries({ name: 80, email: 254, message: 1000 })) {
    if (typeof body[field] !== 'string' || !body[field].trim() || body[field].trim().length > limit)
      return res.status(422).json({ error: `Check the ${field} field.` });
    values[field] = body[field].trim();
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) return res.status(422).json({ error: 'Enter a valid email address.' });
  if (!process.env.DATABASE_URL) return res.status(503).json({ error: 'The database is not configured.' });
  try {
    const sql = neon(process.env.DATABASE_URL);
    const rows = await sql`INSERT INTO requests (name, email, message) VALUES (${values.name}, ${values.email}, ${values.message}) RETURNING id`;
    return res.status(201).json({ id: rows[0].id });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ error: 'Could not save the request.' });
  }
}
