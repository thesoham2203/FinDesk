### 1. Prerequisites
- **PHP 8.5+** 
- **Composer**
- **Node.js & NPM** (or Bun)
- **SQLite** (Default database connection, zero-config)
- **Groq API Key** (Get one for free at [console.groq.com](https://console.groq.com/keys))

### 2. Fresh Clone Setup
```bash
# Clone the repository
git clone <repo-url> findesk
cd agentdesk

# Install PHP and Node dependencies
composer install
npm install

# Setup environment variables
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Database & Seeding
AgentDesk comes with a rich seeder that provisions simulated Admins, Agents, Requesters, Categories, SLA Configs, Macros, Knowledge Base articles, and sample tickets.

```bash
# Create the SQLite database file
New-Item .\database\database.sqlite -ItemType File
# Run migrations and seed the initial data
php artisan migrate:fresh --seed
```

### 4. Groq Configuration
To enable the AI subsystem, you must provide a valid Groq API key. Open your `.env` file and append/update the following:

```env
# Tell the Laravel AI SDK to use Groq
AI_DEFAULT_PROVIDER=groq

# Your actual Groq API Key
GROQ_API_KEY=gsk_your_groq_api_key_here

# The model to use (recommended for agent tasks)
GROQ_MODEL=llama-3.3-70b-versatile
```

---

## 🏃‍♂️ Running the Application

AgentDesk relies on background queue workers for AI tasks and a scheduler for SLA monitoring. **To fully run the application locally, you should run these processes in separate terminal tabs:**

### Terminal 1: Web Server
```bash
php artisan serve
# or if you have Laravel Herd Installed then
add the project to herd and run agestdesk.test in your browser
```

### Terminal 2: Frontend Assets (Vite)
```bash
npm run dev
##  or if you have Laravel Herd Installed then
npm run build
```

### Terminal 3: Queue Worker (Crucial for AI)
AI operations (Triage & Reply Drafting) are dispatched to the database queue to keep the UI snappy. You **must** run the queue worker to process them.
```bash
php artisan queue:work --tries=3
```

### Terminal 4: Scheduler (Optional but recommended for SLA)
The scheduler checks for tickets breaching their Service Level Agreement (SLA) response and resolution targets.
```bash
php artisan schedule:work
```
*(Alternatively, you can manually trigger the specific job via Tinker: `php artisan tinker` -> `App\Jobs\CheckOverdueTargetsJob::dispatchSync();`)*

---
```bash
# Run the complete test suite (Pest feature & unit tests)
composer test
or
composer test --parallel (for faster execution)

# Run PHPStan (Strict Type Checking - Level Max)
composer test:types 
or 
composer test:types --parallel (for faster execution)

# Run Type Coverage Analysis
composer test:type-coverage

# Run Code Formatting (Pint, Rector)
composer lint
```
*Note: AI API calls are completely mocked/faked in the test suite, meaning running the tests will **not** consume your Groq quota.*

---