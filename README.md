# scale-pos

A modern, scalable Point of Sale system built with PHP.

## Features

- 🛒 **Sales Management** - Fast and intuitive POS interface
- 📦 **Inventory Tracking** - Real-time stock management
- 👥 **Customer Management** - Customer profiles and purchase history
- 📊 **Reporting** - Comprehensive sales and inventory reports
- 🔐 **Security** - JWT authentication, role-based access control
- 🌐 **REST API** - Full API for integrations
- 🎨 **Modern UI** - Responsive design for desktop and tablets
- ⚡ **Performance** - Optimized for high-volume transactions

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL 5.7+ / MariaDB 10.3+
- Web server (Apache/Nginx)

### Recommended PHP Extensions

- PDO
- mbstring
- JSON
- OpenSSL
- cURL

## Quick Start

### 1. Installation

```bash
# Clone the repository
git clone https://github.com/cyprian-c/scale-pos.git
cd scale-pos

# Install dependencies
composer install

# Setup environment
cp .env.example .env

# Generate application key
php cli/console.php key:generate

# Run migrations
php cli/console.php migrate

# Seed database (optional)
php cli/console.php db:seed
```

### 2. Configuration

Edit `.env` file with your database credentials:

```env
DB_DATABASE=scale_pos
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 3. Run Development Server

```bash
php -S localhost:8000 -t public
```

Visit: http://localhost:8000

## Project Structure

```
scale-pos/
├── app/                    # Application code
│   ├── Controllers/        # HTTP controllers
│   ├── Models/            # Data models
│   ├── Services/          # Business logic
│   ├── Repositories/      # Data access layer
│   └── Middleware/        # HTTP middleware
├── config/                # Configuration files
├── database/              # Migrations and seeders
├── public/                # Public assets
├── resources/             # Views and lang files
├── routes/                # Application routes
├── storage/               # Logs and cache
└── tests/                 # Test suites
```

## Testing

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Static analysis
composer analyse
```

## Security

- All inputs are validated and sanitized
- Passwords are hashed using bcrypt
- JWT tokens for API authentication
- CSRF protection enabled
- SQL injection prevention via PDO
- XSS protection in views

## API Documentation

API documentation is available at `/docs/api` after setup.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, email support@scale-pos.local or open an issue on GitHub.

## Roadmap

- [ ] Multi-store support
- [ ] Barcode scanning integration
- [ ] Mobile app (iOS/Android)
- [ ] Advanced analytics dashboard
- [ ] Third-party integrations (payment gateways)
- [ ] Cloud backup and sync

---

Made by Cyprian Ocharo & scale-pos team
