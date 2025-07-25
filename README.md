# mi-email-worker

mi-email-worker is a bare‑metal PHP 7.4 project that provides a small email queue worker using AWS SES. Messages are queued in Redis, sent respecting SES quotas and bounces/complaints are handled via Amazon SNS. Logs and suppressions are stored in PostgreSQL.

## Requirements

- PHP 7.4+
- Composer
- Redis
- PostgreSQL
- AWS account with SES and SNS

## Installation

1. Clone this repository and install dependencies:
   ```bash
   composer install --ignore-platform-reqs --no-interaction
   ```
2. Create a `.env` file at the project root with these variables:
   ```dotenv
   AWS_KEY=your-key
   AWS_SECRET=your-secret
   AWS_REGION=us-east-1
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   QUEUE_NAME=email_queue
   DB_HOST=localhost
   DB_PORT=5432
   DB_NAME=email_worker
   DB_USER=user
   DB_PASS=pass
   ```
3. Initialize the database tables:
   ```bash
   psql -h $DB_HOST -U $DB_USER -d $DB_NAME -f scripts/init_db.sql
   ```

## Queueing Emails

Use `scripts/enqueue.php` to add an email job:
```bash
php scripts/enqueue.php --to=user@example.com --subject="Hello" --body="Test"
```

You can also integrate the static facade `SisProing\Helper\Mail` in another project to enqueue messages programmatically:
```php
use SisProing\Helper\Mail;

Mail::enqueue('user@example.com', 'Subject', 'Body');
```
Make sure the `.env` file is present so the queue configuration can be loaded.

## Running the Worker

The worker consumes the queue, applies rate limits and logs results. Run it manually:
```bash
php scripts/worker-runner.php
```

A Supervisor configuration is provided in `supervisor/email-worker.conf` to keep the worker running in production. To enable it:

1. Install Supervisor (`sudo apt-get install supervisor` on Debian/Ubuntu).
2. Copy the file to `/etc/supervisor/conf.d/` and reload:
   ```bash
   sudo cp supervisor/email-worker.conf /etc/supervisor/conf.d/
   sudo supervisorctl reread
   sudo supervisorctl update
   ```
3. The config sets `autorestart=true`, so Supervisor will restart the worker if it crashes. Logs go to `/var/log/email-worker.out.log` and `/var/log/email-worker.err.log`.
4. Check status with `sudo supervisorctl status email-worker`. You can manually start or stop it with `sudo supervisorctl start email-worker` and `sudo supervisorctl stop email-worker`.
If you clone the project into a different directory, update the `command` path inside `supervisor/email-worker.conf` accordingly.

## Handling Bounces and Complaints

Configure an SNS topic for SES notifications and point its HTTPS subscription to `public/sns-handler.php`. This endpoint confirms the subscription and inserts bounced/complaining addresses into the `email_suppressions` table.

## Tests

To run the unit tests:
```bash
./vendor/bin/phpunit --configuration tests/phpunit.xml.dist
```

## Using in Other Projects

Install this library with Composer so the `SisProing\\` namespace is autoloaded automatically.

1. If published on Packagist you can simply run:
   ```bash
   composer require sisproing/mi-email-worker
   ```
2. Otherwise add the repository to your `composer.json`:
   ```json
   {
       "repositories": [
           {"type": "vcs", "url": "https://github.com/your-org/mi-email-worker"}
       ]
   }
   ```
   Then install with:
   ```bash
   composer require sisproing/mi-email-worker:dev-main
   ```

After installation the `SisProing\\Helper\\Mail` facade can be used directly:
```php
use SisProing\Helper\Mail;

Mail::enqueue('user@example.com', 'Subject', 'Body');
```
Ensure Redis, PostgreSQL and the `.env` configuration are available. The worker
and SNS handler can run separately from your application, providing background
email processing.
Add this project as a dependency (or copy the `src/` directory) and include the `SisProing\Helper\Mail` facade in your application. Call `Mail::enqueue()` to queue emails; ensure Redis, PostgreSQL and the `.env` configuration are accessible. The worker and SNS handler can run separately from your application, providing background email processing.
