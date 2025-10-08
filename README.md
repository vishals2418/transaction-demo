# Transaction Demo

A Laravel-based money transfer system with real-time updates using Pusher and Vue.js (Inertia.js).

## Features

-   Instant money transfers between users
-   Real-time balance updates via Pusher
-   Transaction history with balance tracking
-   Secure transactions with database locking
-   1.5% commission fee on transfers
-   Add money to account functionality
-   Real-time receiver validation

## Requirements

-   PHP 8.2 or higher
-   MySQL 8.0 or higher
-   Node.js 18+ and npm
-   Composer
-   Pusher account (for real-time features)

## Installation

1. Clone the repository

````bash
git clone https://github.com/your-username/your-project.git
cd transaction-demo
````

2. Install PHP dependencies

```bash
composer install
````

3. Install Node dependencies

```bash
npm install --legacy-peer-deps
```

4. Copy the example environment file and generate the application key
```bash
cp .env.example .env
php artisan key:generate
```


5. Configure database in `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=transaction_demo
DB_USERNAME=root
DB_PASSWORD=
```

6. Configure Pusher in `.env` (Get credentials from https://pusher.com)

```env
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=your-cluster

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

7. Configure Sanctum stateful domains in `.env`

```env
SANCTUM_STATEFUL_DOMAINS=localhost:8000,localhost,127.0.0.1:8000
SESSION_DOMAIN=null
```

8. Create database

```bash
mysql -u root -p
CREATE DATABASE transaction_demo;
exit;
```

9. Run migrations and seed database

```bash
php artisan migrate:fresh --seed
```

10. Build frontend assets

```bash
npm run dev
```

11. Start the development server (in a new terminal)

```bash
php artisan serve
```

12. Visit the application

```
http://localhost:8000
```

## Test Credentials

The seeder creates 10 test users with consistent IDs:

| ID  | Name           | Email               | Password |
| --- | -------------- | ------------------- | -------- |
| 1   | John Doe       | john@example.com    | password |
| 2   | Jane Smith     | jane@example.com    | password |
| 3   | Bob Johnson    | bob@example.com     | password |
| 4   | Alice Williams | alice@example.com   | password |
| 5   | Charlie Brown  | charlie@example.com | password |
| 6   | David Miller   | david@example.com   | password |
| 7   | Emma Davis     | emma@example.com    | password |
| 8   | Frank Wilson   | frank@example.com   | password |
| 9   | Grace Taylor   | grace@example.com   | password |
| 10  | Henry Anderson | henry@example.com   | password |

**Note:** All users have random balances between $1,000 - $10,000

## Usage

1. **Login** with any test user credentials (e.g., john@example.com / password)
2. **View Balance** - See your current balance on the dashboard
3. **Send Money** - Click "Send Money" button
    - Enter receiver's User ID (1-10)
    - Enter amount to send
    - System shows total deduction (amount + 1.5% commission)
    - Real-time validation checks if receiver exists
4. **Add Money** - Click "Add Money" to add funds to your account
5. **Transaction History** - View all sent and received transactions with balance tracking
6. **Real-time Updates** - Balance updates automatically when receiving money (via Pusher)

## Technical Details

### Commission

-   1.5% commission fee is charged on all transfers
-   Example: If User A sends $100 to User B:
    -   User A is debited: $101.50 (amount + commission)
    -   User B is credited: $100.00
    -   Transaction records both amounts

### Race Condition Prevention

-   Database row-level locking (`lockForUpdate()`) prevents concurrent transaction issues
-   Atomic database transactions ensure data consistency
-   No balance can be modified without proper locking

### Real-time Updates

-   Pusher broadcasts balance updates to connected clients
-   Private channels ensure users only receive their own updates
-   Events are fired after successful database transactions
-   Both sender and receiver get real-time balance updates

### Security

-   Session-based authentication via Laravel Sanctum
-   CSRF protection on all API requests
-   Private broadcasting channels with authorization
-   Validation prevents sending to self
-   Insufficient balance checks before transaction

## API Endpoints

All endpoints require authentication (`auth:sanctum` + session middleware)

-   `GET /api/transactions` - Get user's transaction history and balance
-   `POST /api/transactions` - Create a new money transfer
    -   Body: `{ receiver_id: int, amount: float }`
-   `GET /api/validate-receiver/{id}` - Validate receiver user exists
-   `POST /api/add-money` - Add money to account
    -   Body: `{ amount: float }`

## Database Schema

### Users Table

-   `id` - Primary key
-   `name` - User's name
-   `email` - User's email (unique)
-   `password` - Hashed password
-   `balance` - Current balance (decimal 15,2)
-   `created_at`, `updated_at` - Timestamps

### Transactions Table

-   `id` - Primary key
-   `sender_id` - Foreign key to users (on delete cascade)
-   `receiver_id` - Foreign key to users (on delete cascade)
-   `amount` - Transfer amount (decimal 15,2)
-   `commission_fee` - Commission charged (decimal 15,2)
-   `total_debited` - Total amount debited from sender (decimal 15,2)
-   `balance_after` - Sender's balance after transaction (decimal 15,2)
-   `status` - Transaction status (default: 'completed')
-   `created_at`, `updated_at` - Timestamps
-   Indexes on: `(sender_id, created_at)`, `(receiver_id, created_at)`

## Troubleshooting

### Real-time updates not working

1. Verify Pusher credentials in `.env`
2. Check browser console for connection errors
3. Ensure `npm run dev` is running
4. Clear config cache: `php artisan config:clear`

### Authentication issues

1. Ensure `SANCTUM_STATEFUL_DOMAINS` includes your domain
2. Clear all caches: `php artisan optimize:clear`
3. Check session driver is set to `database` in `.env`
4. Ensure session table exists: `php artisan migrate`

### Database errors

1. Ensure database exists and credentials are correct
2. Run migrations: `php artisan migrate:fresh --seed`
3. Check MySQL is running

### Frontend not loading

1. Ensure `npm run dev` is running
2. Clear browser cache (Ctrl+Shift+R)
3. Rebuild assets: `npm run build`

## Development Commands

```bash
# Fresh install with seed data
php artisan migrate:fresh --seed

# Clear all caches
php artisan optimize:clear

# Watch for frontend changes
npm run dev

# Build for production
npm run build

# Run tests (if implemented)
php artisan test
```

## Project Structure

```
transaction-demo/
├── app/
│   ├── Events/
│   │   └── BalanceUpdated.php          # Pusher event
│   ├── Http/Controllers/Api/
│   │   └── TransactionController.php   # API endpoints
│   ├── Models/
│   │   ├── User.php
│   │   └── Transaction.php
│   └── Services/
│       └── TransactionService.php      # Business logic
├── database/
│   ├── migrations/
│   └── seeders/
│       └── UserSeeder.php              # Test users
├── resources/
│   └── js/
│       └── Pages/
│           ├── Dashboard.vue           # Main UI
│           └── Welcome.vue
├── routes/
│   ├── api.php                         # API routes
│   ├── web.php                         # Web routes
│   └── channels.php                    # Broadcasting
└── README.md
```
