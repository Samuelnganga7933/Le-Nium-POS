# Le Nium POS — Complete Installation Guide

## Tech Stack
- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Blade templates + Tailwind CSS + Chart.js
- **Database:** MySQL 8+
- **Payments:** M-Pesa Daraja API, PesaPal, Flutterwave
- **SMS:** Africa's Talking
- **AI:** DeepSeek API (Leumas AI — Phase 3)

---

## 1. Project Setup

```bash
# Clone or extract the project
cd /var/www
git clone https://github.com/Samuelnganga7933/Le-Nium-POS.git lenium-pos
cd lenium-pos

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Install Node dependencies and build assets
npm install
npm run build
```

---

## 2. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE lenium_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Update .env with your DB credentials
# DB_DATABASE=lenium_pos
# DB_USERNAME=your_username
# DB_PASSWORD=your_password

# Run all migrations
php artisan migrate

# Seed demo data (creates super admin + demo company)
php artisan db:seed
```

---

## 3. Storage & Permissions

```bash
# Link storage
php artisan storage:link

# Set permissions (Linux)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## 4. Register Middleware

In `bootstrap/app.php` (Laravel 12 style):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'    => \App\Http\Middleware\RoleMiddleware::class,
        'company' => \App\Http\Middleware\EnsureCompanyAccess::class,
    ]);
})
```

---

## 5. M-Pesa Configuration

Get credentials from [Safaricom Developer Portal](https://developer.safaricom.co.ke):

```env
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_SHORTCODE=your_paybill_or_till
MPESA_PASSKEY=your_lipa_na_mpesa_passkey
MPESA_CALLBACK_URL=https://yourdomain.com/webhooks/mpesa/callback
MPESA_SANDBOX=false  # Set to false in production
```

**Webhook URL** — register this in your Daraja app:
```
https://yourdomain.com/webhooks/mpesa/callback
```

---

## 6. Africa's Talking (SMS)

```env
AT_USERNAME=your_username
AT_API_KEY=your_api_key
AT_SENDER_ID=LeNiumPOS
```

---

## 7. Default Login Credentials

After seeding:

| Role | Email | Password | PIN |
|------|-------|----------|-----|
| Super Admin | samuel@leniumpos.com | admin123 | — |
| Demo Owner | demo@minimart.co.ke | password | — |
| Demo Cashier | cashier@minimart.co.ke | password | 1234 |

**Change all passwords before going live.**

---

## 8. Business Type Registration Flow

1. Visit `/register`
2. Select business type (visual cards)
3. Enter company details + logo
4. Create owner account
5. Samuel receives notification and approves within 24 hours
6. 14-day free trial begins

---

## 9. Role System

| Role | Login Method | Access |
|------|-------------|--------|
| `super_admin` | Email + Password | All companies |
| `company_owner` | Email + Password | Own company, all branches |
| `branch_manager` | Email + Password | Own branch only |
| `cashier` | Employee No. + PIN | POS only |
| `inventory_clerk` | Employee No. + PIN | Stock management |
| `waiter` | Employee No. + PIN | Restaurant tables |
| `chef` | Employee No. + PIN | Kitchen display |
| `driver` | Employee No. + PIN | Deliveries |

---

## 10. Split-Unit Products (Eggs Example)

When adding a product, enable "Split-unit selling":
- **Bulk unit:** Tray (30 eggs, buying price KSh 400)
- **Sub-unit:** Egg (selling price KSh 15)

System automatically:
- Tracks stock in sub-units (eggs)
- Receives 2 trays = adds 60 eggs to stock
- Selling 5 eggs = deducts 5 from stock
- Shows "also per egg" on POS product tile

---

## 11. Restaurant Setup

1. Go to Settings → Business Type → Select restaurant type
2. Restaurant-specific nav items appear (Tables, Reservations, KDS)
3. Add tables in Table Management
4. Generate QR codes per table (customers scan to view menu)
5. Waiters log in with Employee Number + PIN
6. Orders flow: Waiter adds items → Send to Kitchen → KDS shows order → Mark Ready → Bill customer

---

## 12. Auto-Expense on Stock Purchase

When a purchase order is marked as "Received":
1. Stock is added to inventory automatically
2. An expense entry is created automatically with:
   - Category: Stock Purchase
   - Amount: total cost of purchase
   - Supplier: linked to purchase supplier
   - Marked as "Auto" (cannot be manually deleted)

---

## 13. Keyboard Shortcuts (POS)

| Key | Action |
|-----|--------|
| `F2` | Focus product search / barcode scan |
| `F4` | Process payment (charge) |
| `F8` | Hold current transaction |
| `Escape` | Close receipt / cancel |

---

## 14. Route Map Summary

```
/                       → Landing page
/register               → Sign up wizard
/login                  → Email login
/staff-login            → Employee number + PIN login
/invite/{token}         → Accept staff invitation

/super/*                → Super Admin (Samuel)
/owner/*                → Company Owner
/manager/*              → Branch Manager
/cashier/*              → Cashier dashboard
/pos                    → Point of Sale
/products               → Product catalog
/inventory              → Stock management
/suppliers              → Supplier directory
/purchases              → Purchase orders
/sales                  → Sales history
/customers              → Customer database
/expenses               → Expense tracking
/invoices               → Invoices & quotes
/loyalty                → Loyalty program
/reports                → Analytics
/messages               → Staff messaging
/settings               → Business settings
/subscription           → Billing & plan
/restaurant/tables      → Table management (F&B)
/webhooks/mpesa/*       → M-Pesa Daraja webhooks
/api/mpesa/*            → M-Pesa API endpoints
```

---

## 15. Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] Set `MPESA_SANDBOX=false`
- [ ] Configure real SMTP (not Mailpit)
- [ ] Set up SSL certificate (required for M-Pesa callbacks)
- [ ] Change all default passwords
- [ ] Set up cron job: `* * * * * php artisan schedule:run`
- [ ] Set up queue worker: `php artisan queue:work`
- [ ] Configure backup (spatie/laravel-backup recommended)

---

## 16. Scheduled Jobs to Register

In `routes/console.php` or `app/Console/Kernel.php`:

```php
// Clean expired held transactions every 5 minutes
Schedule::call(fn() => app(PosService::class)->cleanExpiredHolds())->everyFiveMinutes();

// Daily sales report at 8 PM
Schedule::command('report:daily')->dailyAt('20:00');

// Check expiring subscriptions daily
Schedule::command('subscriptions:check')->daily();
```

---

## 17. File Structure

```
app/
  Http/
    Controllers/
      Auth/           → Login, Register, Onboarding
      Admin/          → Super admin
      Owner/          → Company owner (Dashboard, Branches, Staff, Settings)
      Manager/        → Branch manager
      Cashier/        → Cashier dashboard
      Restaurant/     → Tables, Orders, KDS
      PosController   → Checkout engine
      ProductController, InventoryController, SupplierController
      PurchaseController, SaleController, CustomerController
      ExpenseController, InvoiceController, LoyaltyController
      ReportController, MpesaController, MessageController
    Middleware/
      RoleMiddleware          → Role-based route protection
      EnsureCompanyAccess     → Tenant isolation
  Models/             → All Eloquent models
  Services/
    PosService          → Checkout, hold, receipt
    MpesaService        → Daraja API integration
    LoyaltyService      → Points earn/redeem/tier
    InventoryService    → Restock, adjust, transfer
database/
  migrations/         → 8 migration files, 35+ tables
  seeders/            → Demo data seeder
resources/views/      → 30+ Blade templates
config/mpesa.php      → M-Pesa configuration
routes/web.php        → All routes
.env.example          → All environment variables
```
