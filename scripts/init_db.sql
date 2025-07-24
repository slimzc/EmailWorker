CREATE TABLE IF NOT EXISTS email_suppressions (
  email TEXT PRIMARY KEY,
  type TEXT NOT NULL,
  ts TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS email_logs (
  id SERIAL PRIMARY KEY,
  to_addr TEXT NOT NULL,
  subject TEXT NOT NULL,
  body TEXT NOT NULL,
  headers JSONB,
  sent_at TIMESTAMP,
  status TEXT,
  error_msg TEXT
);
