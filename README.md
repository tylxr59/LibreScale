# LibreScale 🏋️

A minimal, self-hosted weight tracking application built with PHP and SQLite. Perfect for data-driven individuals who want full control over their fitness data.

## Features

- **Interactive Charts** - Visualize your weight progress over time (weekly, monthly, yearly, all-time)
- **Goal Tracking** - Set and monitor progress toward your target weight
- **Flexible Entry Management** - Add, edit, and delete weight entries with date/time support
- **Timezone Support** - Automatic browser timezone detection with manual override
- **Unit Flexibility** - Support for both kilograms and pounds
- **Statistics** - Track 7-day and 30-day weight changes
- **CSV Export** - Backup your data anytime
- **Material Design** - Clean, modern UI with Material Symbols icons
- **Dark Mode** - Light and dark theme with instant switching
- **Custom Colors** - Choose from 6 color schemes (Purple, Blue, Green, Red, Orange, Pink)
- **Progressive Web App** - Install on desktop or mobile, works offline
- **Secure & Private** - Self-hosted

## Requirements

- PHP 7.4 or higher with SQLite support
- Apache web server (with mod_rewrite recommended)
- Modern web browser with WOFF2 font support
- HTTPS (required for PWA installation in production)

## Installation

1. **Clone or download** this repository to your web server directory:
   ```bash
   git clone https://github.com/yourusername/LibreScale.git
   cd LibreScale
   ```

2. **Set permissions** (if needed):
   ```bash
   chmod 755 .
   chmod 644 *.php *.css *.js
   ```

3. **Run setup** by navigating to `setup.php` in your browser:
   ```
   http://your-domain.com/LibreScale/setup.php
   ```

4. **Complete the setup form** with:
   - Username and password for your account
   - Display name (how you'll be greeted)
   - Timezone
   - Preferred weight unit (kg or lbs)
   - Starting weight
   - Target weight

5. **Login** and start tracking!

## PWA Installation

LibreScale can be installed as a Progressive Web App:

1. Visit LibreScale in Chrome, Edge, or Safari
2. Look for the "Install" button in the address bar
3. Or use browser menu → "Install LibreScale"
4. The app will work offline and feel like a native application

**Note:** PWA installation requires HTTPS in production (works on localhost for development)

## Usage

### Adding Weight Entries

- Click the floating **+** button (bottom right)
- Enter your weight, date, and time
- Optionally add notes
- Multiple entries per day are averaged for display

### Viewing Progress

- **Home Page**: See your latest progress with charts and statistics
- **Entries Page**: Review all historical entries with date breakdown
- **Settings Page**: Update your profile and goals

### Exporting Data

Navigate to Settings and click "Export CSV" to download all your weight data in CSV format for backup or analysis.

## File Structure

```
LibreScale/
├── setup.php          # Initial setup script (run once)
├── index.php          # Main application entry point
├── config.php         # Database path and helper functions
├── home.php           # Home page with charts
├── entries.php        # Entries list page
├── settings.php       # Settings page
├── nav.php            # Bottom navigation component
├── modal.php          # Weight entry modal
├── styles.css         # Material Design styles
├── app.js             # JavaScript functionality
├── manifest.json      # PWA manifest
├── service-worker.js  # Offline functionality
├── MaterialSymbolsOutlined.woff2  # Material Icons font
├── favicon.ico        # Browser favicon
├── icon-192.png       # PWA icon (192x192)
├── icon-512.png       # PWA icon (512x512)
├── .htaccess          # Apache security configuration
└── librescale.db      # SQLite database (auto-generated)
```

## Database Schema

### `users` Table
- User credentials and preferences
- Stores: username, password (hashed), display_name, timezone, weight_unit, theme_mode, theme_color

### `weights` Table
- Weight entries with timestamps (Unix epoch)
- Supports multiple entries per day
- Optional notes field

### `settings` Table
- Starting weight and target weight per user

## Security

- Passwords are hashed using PHP's `password_hash()` 
- `.htaccess` protects database files from direct access
- Session-based authentication
- SQL injection prevention via prepared statements
- XSS prevention via output escaping

## Timezone Handling

- Weight entry timestamps stored as Unix epoch (UTC)
- Display converted to user's configured timezone
- Browser timezone automatically detected with option to use or override
- Multiple entries per day averaged for chart display

## Customization

### Changing Theme

LibreScale includes built-in theming:
- **Dark Mode**: Toggle between light and dark themes in Settings
- **Color Schemes**: Choose from 6 built-in color schemes (Purple, Blue, Green, Red, Orange, Pink)
- Settings are saved per-user and apply across all pages

### Advanced Color Customization

To add more color schemes, edit the theme color variations in [styles.css](styles.css):

```css
.theme-color-custom {
    --primary: #your-color;
    --primary-dark: #darker-shade;
    --primary-light: #lighter-shade;
    --secondary: #accent-color;
    --secondary-light: #lighter-accent;
}
```

## License

MIT License - Feel free to modify and distribute as needed.

## Contributing

This is a personal project, but suggestions and improvements are welcome! Feel free to open issues or submit pull requests.

## Acknowledgments

- Chart.js for beautiful data visualization
- Material Design for UI inspiration
- Material Icons for UI elements
