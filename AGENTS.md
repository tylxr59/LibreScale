# AGENTS.md - LibreScale Developer Guide for AI Agents

## Project Overview

**LibreScale** is a minimal, self-hosted weight tracking application built with PHP, SQLite, and vanilla JavaScript. It supports a single user tracking daily weight entries with goal tracking, historical charts, and statistics.

**Tech Stack:**
- PHP 7.4+ (backend, routing, authentication, API)
- SQLite 3 (database)
- Vanilla JavaScript (frontend interactivity)
- Chart.js 4.4.1 (data visualization)
- Material Design 3 (UI design system)
- Material Symbols Outlined (icon font)

**Core Principles:**
- Minimal file count (20 files total including PWA assets)
- No frameworks or complex dependencies
- Self-contained and portable
- Data privacy (self-hosted, single user)
- Timezone-aware with epoch timestamps
- Customizable theming (light/dark mode, 6 color schemes)
- Material Symbols icons (self-hosted)
- Progressive Web App (installable, offline-capable)

## Architecture

### Request Flow

```
Browser → index.php (router) → Page Components (home.php, entries.php, settings.php)
                             → AJAX Handlers (add_weight, get_chart_data, etc.)
                             → config.php (helper functions)
                             → Database (SQLite)
```

### Authentication Flow

1. User visits any page
2. `index.php` loads `config.php` which starts session
3. `config.php` checks if `librescale.db` exists (redirects to setup if missing)
4. `requireLogin()` checks `$_SESSION['user_id']` (redirects to login if not set)
5. Protected pages execute with user context

### Page Routing

- `index.php?page=login` → Login page (inline in index.php)
- `index.php?page=home` or `index.php` → includes `home.php`
- `index.php?page=entries` → includes `entries.php`
- `index.php?page=settings` → includes `settings.php`
- `index.php?page=logout` → destroys session, redirects to login

### AJAX Routing

All AJAX requests use `index.php?action=<action_name>`:
- `add_weight` → Creates new weight entry
- `edit_weight` → Updates existing entry
- `delete_weight` → Removes entry
- `get_entry` → Fetches single entry for editing
- `get_chart_data` → Returns daily averages for charting (with period parameter)
- `update_settings` → Updates user preferences and settings
- `export_csv` → Generates CSV download (not JSON response)

## File Structure & Responsibilities

### Core Files

**setup.php** (Run once)
- Creates SQLite database with fixed name: `librescale.db`
- Creates initial user account
- Self-checks: Won't run if `librescale.db` exists
- Database protected by `.htaccess` file blocking direct access

**index.php** (Main router)
- Handles authentication (login/logout)
- Routes page requests via `$_GET['page']`
- Routes AJAX requests via `$_GET['action']`
- Contains all AJAX handler functions:
  - `addWeight()`, `editWeight()`, `deleteWeight()`, `getEntry()`
  - `getChartData()`, `updateSettings()`, `exportCSV()`
- Includes page components based on routing

**config.php** (Database & utilities)
- Session management (starts session)
- Defines database path constant: `DB_PATH`
- Database connection: `getDB()` returns PDO instance
- Authentication helpers: `requireLogin()`, `getCurrentUser()`
- Data helpers: `getUserSettings()`, `getWeightEntries()`, `getDailyAverages()`
- Timezone helpers: `formatDateTime()`, `getStartOfDay()`
- Utility functions: `formatWeight()`, `e()` (HTML escape), `jsonResponse()`
- **Important:** All page components check `if (!defined('DB_PATH'))` to prevent direct access

### Page Components

**home.php** (Dashboard)
- Displays personalized greeting with `$user['display_name']`
- Shows reminder banner if `!hasEnteredWeightToday()`
- Renders Chart.js line chart with period tabs (week/month/year/all)
- Calculates and displays 3 statistics cards:
  - 7-day change (weight lost/gained in last 7 days)
  - 30-day change (weight lost/gained in last 30 days)
  - Total progress (with percentage to goal)
- Includes `nav.php`, `modal.php`, and floating + button
- Inline JavaScript handles chart rendering and tab switching

**entries.php** (Entry list)
- Fetches all entries via `getWeightEntries($user['id'])`
- Groups entries by date with headers
- Displays time, weight, diff from starting weight
- Shows edit/delete buttons per entry
- Color-codes diffs (positive=green, negative=red)
- Inline JavaScript for edit/delete actions
- Shows empty state if no entries

**settings.php** (User preferences)
- Form for updating user profile and preferences
- Detects browser timezone via JavaScript `Intl.DateTimeFormat()`
- Updates both `users` and `settings` tables
- CSV export button (triggers download)
- Logout link
- Inline JavaScript for form submission and timezone detection

### Component Includes

**nav.php** (Bottom navigation)
- Fixed bottom navigation bar
- Links to home, entries, settings
- Highlights active page based on `$page` variable
- Emoji icons with labels

**modal.php** (Weight entry form)
- Modal overlay with form
- Fields: date, time, weight, notes
- Shared by add and edit operations
- Form ID: `weightForm`, handled in `app.js`

### Assets

**styles.css** (Material Design 3)
- CSS custom properties for theming (`:root` variables)
- Material Symbols Outlined font-face declaration
- Color scheme: Purple primary (#5e35b1), Teal secondary (#00897b)
- Responsive design with mobile breakpoints (@media queries)
- Component classes organized by feature:
  - Login page styles
  - App layout (header, content, nav)
  - Forms and buttons
  - Cards (chart, stats, entries)
  - Modal system
  - Responsive utilities

**app.js** (Client-side logic)
- Service Worker registration for PWA
- Modal management: `openAddWeightModal()`, `openEditWeightModal()`, `closeWeightModal()`
- Form submission handler for weight entries
- Date/time formatting helpers
- Timezone detection: `detectTimezone()` using `Intl.DateTimeFormat()`
- Escape key handling for modal

**manifest.json** (PWA manifest)
- App metadata (name, description, colors)
- Standalone display mode
- Icon references for installation
- Relative paths for subdirectory compatibility

**service-worker.js** (Offline functionality)
- Caches core resources (HTML, CSS, JS, fonts, icons)
- Serves cached content when offline
- Cleans up old caches on activation
- Uses relative paths for subdirectory compatibility

### Configuration

**.htaccess** (Apache security)
- Disables directory browsing (`Options -Indexes`)
- Blocks direct access to `.db`, `.sqlite`, `.sqlite3` files
- Sets security headers (X-Frame-Options, X-XSS-Protection, etc.)

**.gitignore** (Version control)
- Excludes database file (`librescale.db`)
- Excludes OS and editor files

## Database Schema

### `users` Table
```sql
id INTEGER PRIMARY KEY AUTOINCREMENT
username TEXT UNIQUE NOT NULL
password TEXT NOT NULL                 -- bcrypt hash via password_hash()
display_name TEXT NOT NULL            -- Used for greeting
timezone TEXT DEFAULT "UTC"           -- PHP timezone identifier
weight_unit TEXT DEFAULT "kg"         -- "kg" or "lbs"
theme_mode TEXT DEFAULT "light"       -- "light" or "dark"
theme_color TEXT DEFAULT "purple"     -- "purple", "blue", "green", "red", "orange", "pink"
created_at INTEGER NOT NULL           -- Unix epoch timestamp
```

### `weights` Table
```sql
id INTEGER PRIMARY KEY AUTOINCREMENT
user_id INTEGER NOT NULL
weight REAL NOT NULL                  -- Numeric weight value
timestamp INTEGER NOT NULL            -- Unix epoch timestamp (UTC)
notes TEXT                            -- Optional user notes
FOREIGN KEY (user_id) REFERENCES users(id)

INDEX idx_weights_user_timestamp ON (user_id, timestamp)
INDEX idx_weights_timestamp ON (timestamp)
```

### `settings` Table
```sql
user_id INTEGER PRIMARY KEY
starting_weight REAL NOT NULL
target_weight REAL NOT NULL
FOREIGN KEY (user_id) REFERENCES users(id)
```

**Key Design Decisions:**
- Timestamps are Unix epoch (seconds since 1970-01-01 UTC) for timezone safety
- Multiple entries per day are allowed (averaged for display)
- `settings.user_id` is PRIMARY KEY (one setting row per user)
- Indexes on timestamp columns for query performance

## Critical Functions Reference

### config.php Functions

**Database & Auth:**
- `getDB()` → Returns PDO connection (SQLite with error mode EXCEPTION)
- `requireLogin()` → Redirects to login if not authenticated
- `getCurrentUser()` → Returns user row from session, or null
- `getUserSettings($user_id)` → Returns settings row for user

**Data Retrieval:**
- `getWeightEntries($user_id, $start_timestamp, $end_timestamp)` → Array of weight entries, DESC order
- `getDailyAverages($user_id, $timezone, $start, $end)` → Groups by date, averages multiple entries per day, returns `[{date, weight, count}, ...]` sorted ASC
- `hasEnteredWeightToday($user_id, $timezone)` → Boolean, checks if entry exists for current day in user's timezone

**Formatting:**
- `formatWeight($weight, $unit, $decimals)` → Returns formatted string like "75.5 kg"
- `formatDateTime($timestamp, $timezone, $format)` → Converts epoch to formatted date string in user's timezone
- `getStartOfDay($timestamp, $timezone)` → Returns epoch for 00:00:00 of given timestamp's date

**Utilities:**
- `e($string)` → HTML escape (use for all output)
- `jsonResponse($data, $status)` → Sets headers, echoes JSON, exits
- `getThemeClasses($user)` → Returns theme class string for body tag (e.g., "theme-dark theme-color-blue")

### index.php AJAX Handlers

**addWeight($user, $settings)**
- POST params: `weight`, `date`, `time`, `notes`
- Converts date+time to epoch using user's timezone
- Inserts into `weights` table
- Returns: `{success: true, id: <new_id>}`

**editWeight($user)**
- POST params: `id`, `weight`, `date`, `time`, `notes`
- Updates entry if owned by user
- Returns: `{success: true}`

**deleteWeight($user)**
- POST params: `id`
- Deletes entry if owned by user
- Returns: `{success: true}`

**getEntry($user)**
- GET params: `id`
- Returns single entry as JSON object
- Used by edit modal to populate form

**getChartData($user, $settings)**
- GET params: `period` (week/month/year/all)
- Calculates time range based on period
- Returns: `{data: [{date, weight, count}, ...], target_weight: X, unit: "kg"}`

**updateSettings($user)**
- POST params: `display_name`, `timezone`, `weight_unit`, `theme_mode`, `theme_color`, `starting_weight`, `target_weight`
- Validates theme values against allowed lists
- Updates both `users` and `settings` tables
- Returns: `{success: true}`

**exportCSV($user, $settings)**
- Sets CSV headers and outputs file
- Filename: `librescale_export_YYYY-MM-DD.csv`
- Columns: Date, Time, Weight (unit), Notes
- Exits after output

## Data Flow Patterns

### Adding a Weight Entry

1. User clicks floating + button
2. `openAddWeightModal()` in app.js shows modal, sets today's date/time
3. User fills form and submits
4. `app.js` captures submit, sends FormData to `index.php?action=add_weight`
5. `addWeight()` validates, converts date+time to epoch, inserts to DB
6. Success → `location.reload()` to refresh page data

### Displaying Chart

1. `home.php` loads, includes inline JS
2. JS calls `loadChartData('week')` on page load
3. `fetch('index.php?action=get_chart_data&period=week')`
4. `getChartData()` calculates time range, calls `getDailyAverages()`
5. Returns JSON with daily averages and target weight
6. JS creates Chart.js instance with weight line + target dotted line

### Timezone Handling

1. All timestamps stored as Unix epoch (UTC) in database
2. When displaying: `formatDateTime($timestamp, $user['timezone'], $format)`
3. When saving: JavaScript sends date+time strings, PHP creates DateTime object in user's timezone, converts to epoch
4. Browser detection: `Intl.DateTimeFormat().resolvedOptions().timeZone` in settings.php

### Multiple Entries Per Day

1. User can add multiple entries with same date, different times
2. `getDailyAverages()` groups by date (`Y-m-d` format in user's timezone)
3. Returns average weight for each date
4. Chart displays one point per day (the average)
5. Entries page shows all individual entries

## Common Modification Tasks

### Adding a New Statistic to Home Page

1. Calculate statistic in `home.php` PHP section (before HTML)
2. Add new `.stat-card` div in the `.stats-grid`
3. Style with existing classes (no CSS changes needed)

### Adding a New Setting

1. Add column to `users` or `settings` table (modify `setup.php` schema)
2. Add form field in `settings.php`
3. Update `updateSettings()` in `index.php` to handle new field
4. Update database query in `updateSettings()`

### Adding a New Page

1. Create `newpage.php` with `if (!defined('DB_PATH')) die(...)` check
2. Add route case in `index.php` switch statement
3. Add nav item in `nav.php`
4. Include required components (`nav.php`, etc.)

### Modifying Chart Appearance

1. Edit Chart.js options in `home.php` inline `<script>`
2. Chart instance created in `renderChart(data)` function
3. Modify datasets, colors, or options object
4. CSS styles: chart container is `.chart-container` (300px height)

### Adding a New AJAX Endpoint

1. Add function in `index.php` (after `handleAjaxRequest()`)
2. Add case in `handleAjaxRequest()` switch statement
3. Use `jsonResponse($data)` to return results
4. Call from JavaScript: `fetch('index.php?action=new_action')`

## Security Considerations

**Authentication:**
- Sessions managed by PHP (`session_start()` in config.php)
- `requireLogin()` must be called on all protected pages
- Password hashing: Use `password_hash()` and `password_verify()` only

**SQL Injection Prevention:**
- Always use prepared statements with PDO
- Example: `$stmt = $db->prepare('SELECT * FROM users WHERE id = ?'); $stmt->execute([$id]);`
- Never concatenate user input into SQL strings

**XSS Prevention:**
- Use `e($string)` function (alias for `htmlspecialchars()`) on all output
- Example: `<?php echo e($user['display_name']); ?>`
- JavaScript strings in HTML: Still use `e()` for PHP variables

**File Access:**
- `.htaccess` blocks direct access to database files
- All page components check `DB_PATH` constant to prevent direct access
- Database path is hardcoded in `config.php` for simplicity

**CSRF Protection:**
- Currently not implemented (single-user app with session cookies)
- If adding: Use tokens in forms and validate in POST handlers

## Testing Checklist

When making changes, verify:

**Basic Flow:**
- [ ] Setup works and creates database
- [ ] Login works with created credentials
- [ ] All three pages load (home, entries, settings)
- [ ] Bottom nav links work
- [ ] Logout works

**Weight Entry:**
- [ ] Can add weight entry (today's date)
- [ ] Can add entry with past date
- [ ] Can add multiple entries same day
- [ ] Can edit existing entry
- [ ] Can delete entry with confirmation

**Data Display:**
- [ ] Chart loads and displays data
- [ ] Tab switching works (week/month/year/all)
- [ ] Statistics calculate correctly
- [ ] Entries page shows all entries grouped by date
- [ ] Diff from starting weight displays correctly

**Settings:**
- [ ] Can update display name (greeting changes)
- [ ] Can change timezone (browser detection works)
- [ ] Can switch weight units
- [ ] Can update starting/target weight
- [ ] CSV export downloads file with data

**Edge Cases:**
- [ ] Empty state shows when no entries
- [ ] Reminder banner shows when no entry today
- [ ] Handles entries with no notes
- [ ] Handles single entry (chart doesn't break)
- [ ] Handles future dates gracefully

## Common Pitfalls

**❌ Don't:**
- Modify timestamps directly in database (timezone issues)
- Use `echo` for AJAX responses (use `jsonResponse()`)
- Access page components directly (use index.php routing)
- Store timezone-specific datetime strings in database
- Forget `requireLogin()` on new protected pages
- Mix SQL and user input without prepared statements
- Output user data without `e()` escaping

**✅ Do:**
- Use epoch timestamps for all datetime storage
- Use `formatDateTime()` for display conversions
- Include `config.php` and check `DB_PATH` in components
- Use prepared statements for all database queries
- Call `requireLogin()` early in protected pages
- Escape all user-generated content with `e()`
- Test timezone handling with different timezone settings

## Code Style Conventions

**PHP:**
- Use `snake_case` for variables and functions
- Use prepared statements for all queries
- Add docblock comments to functions
- Check for errors/validate before database operations
- Use `===` for comparisons (strict equality)

**JavaScript:**
- Use `camelCase` for variables and functions
- Use `const`/`let` (not `var`)
- Use arrow functions for callbacks
- Check for errors in `fetch()` with `.catch()`
- Use template literals for string interpolation

**CSS:**
- Use BEM-like naming (component-element pattern)
- Group related styles together
- Use CSS custom properties for theming
- Mobile-first responsive design

**HTML:**
- Use semantic HTML5 elements
- Include ARIA labels where appropriate
- Use proper form labels with `for` attribute
- Keep inline styles minimal (use classes)

## Debugging Tips

**Database Issues:**
- Check if `config_db.php` exists and has correct path
- Verify SQLite extension: `php -m | grep sqlite`
- Check file permissions on database file
- Use `try/catch` around PDO operations for error details

**Authentication Issues:**
- Check if session is started (`session_status() === PHP_SESSION_ACTIVE`)
- Verify `$_SESSION['user_id']` is set after login
- Clear browser cookies and try again
- Check session settings in `php.ini`

**Timezone Issues:**
- Verify timezone string is valid PHP timezone identifier
- Check `date_default_timezone_get()` in PHP
- Test with known timestamps (use online epoch converter)
- Console log JavaScript timezone detection result

**Chart Not Rendering:**
- Check browser console for JavaScript errors
- Verify Chart.js CDN is accessible (check Network tab)
- Ensure canvas element exists with correct ID
- Verify data format returned from AJAX call

**Modal Not Opening:**
- Check if modal functions are global (`window.functionName`)
- Verify modal element has correct ID
- Check for JavaScript errors preventing execution
- Ensure `app.js` is loaded after modal.php

## Performance Considerations

**Current Scale:**
- Designed for single user
- SQLite handles thousands of entries efficiently
- Indexes on timestamp columns for query performance

**If Scaling:**
- Daily averages calculation is O(n) - consider caching for large datasets
- Chart data could be cached with invalidation on new entry
- Consider pagination for entries page with 1000+ entries
- SQLite is fine for single user; PostgreSQL/MySQL if multi-user needed

## Future Enhancement Ideas

Documented for potential future development:

**Features:**
- Multiple users support (add authentication per user)
- Mobile app with API endpoints
- Photo progress tracking (store image paths in database)
- Body measurements (add new table)
- Goal milestones and celebrations
- Reminder notifications (requires email/push setup)
- Social sharing (generate shareable progress images)
- Data import from other apps (CSV upload)

**Technical:**
- Add CSRF protection
- Rate limiting on login attempts
- Database backups (automated)
- API endpoints for mobile apps
- Progressive Web App (PWA) support
- Dark mode toggle
- Internationalization (i18n)

## Version Information

**Created:** January 2026
**PHP Version:** 7.4+
**SQLite Version:** 3.x
**Chart.js Version:** 4.4.1
**Dependencies:** None (vanilla JS, no npm/composer)

---

## Quick Reference Commands

**Start Fresh (Re-run Setup):**
```bash
rm librescale.db
# Then visit setup.php in browser
```

**Check PHP Version:**
```bash
php -v
```

**Check SQLite Support:**
```bash
php -m | grep sqlite
```

**Manual Database Access:**
```bash
sqlite3 librescale.db
.tables
SELECT * FROM users;
.exit
```

**Check Apache Config:**
```bash
# Verify .htaccess is being read
cat /var/log/apache2/error.log | grep htaccess
```

---

This guide should provide any future AI agent with complete context to understand, modify, and extend LibreScale effectively. Always test changes thoroughly and maintain the minimal, self-contained philosophy of the project.
