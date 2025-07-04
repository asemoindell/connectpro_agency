# ConnectPro Agency - Business Service Management Platform

A comprehensive web-based platform for managing business services, client bookings, payments, and communications. Built with PHP, MySQL, and modern web technologies.

## 🚀 Features

### Core Functionality
- **Service Management**: Complete service catalog with categories and pricing
- **Client Booking System**: User-friendly booking interface with multiple urgency levels
- **Payment Processing**: Cryptocurrency-only payment system (Bitcoin, USDT)
- **Admin Dashboard**: Comprehensive management interface for admins and agents
- **Real-time Chat**: Integrated chat system for client-agent communication
- **Mobile Responsive**: Fully responsive design for all devices

### Payment System
- **Crypto-Only Payments**: Bitcoin and USDT supported
- **QR Code Generation**: Automatic wallet QR codes for payments
- **Payment Tracking**: Real-time payment status updates
- **Admin Verification**: Manual payment verification workflow
- **Secure Processing**: No sensitive payment data stored locally

### Admin Features
- **Multi-level Access**: Super Admin, Service Admin, and Agent roles
- **Booking Management**: Complete booking lifecycle management
- **Client Management**: Comprehensive client profiles and history
- **Payment Oversight**: Payment verification and tracking
- **Reporting**: Financial and operational reporting
- **Agent Assignment**: Flexible agent assignment system

### Technical Features
- **Mobile-First Design**: Responsive CSS framework
- **PHP 8+ Compatible**: Modern PHP with error handling
- **MySQL Database**: Robust relational database structure
- **Security**: Input validation, SQL injection prevention
- **Error Handling**: Comprehensive error logging and display

## 📋 Requirements

### Server Requirements
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled
- GD extension for image processing
- PDO MySQL extension

### Development Requirements
- XAMPP/WAMP/MAMP (for local development)
- Git for version control
- Modern web browser
- Code editor (VS Code recommended)

## 🛠 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/connectpro-agency.git
cd connectpro-agency
```

### 2. Database Setup
1. Create a MySQL database named `connectpro_agency`
2. Import the database:
   - **With sample data**: Import `connectpro_agency.sql` (recommended for testing)
   - **Structure only**: Import `database_schema.sql` (for production)
3. Configure database connection in `config/database.php`

### 3. Configuration
1. Copy `config/database.php.example` to `config/database.php`
2. Update database credentials:
```php
$host = 'localhost';
$dbname = 'connectpro_agency';
$username = 'your_username';
$password = 'your_password';
```

### 4. File Permissions
```bash
chmod 755 uploads/
chmod 755 logs/
chmod 644 config/database.php
```

### 5. Admin Account
Create an admin account through the database or use the setup script:
```sql
INSERT INTO admins (first_name, last_name, email, password, role, status) 
VALUES ('Admin', 'User', 'admin@example.com', '$2y$10$...', 'super-admin', 'active');
```

## 📱 Mobile Responsiveness

The platform is fully mobile-responsive with:
- **Adaptive Layouts**: Optimized for phones, tablets, and desktops
- **Touch-Friendly UI**: Large buttons and easy navigation
- **Mobile-First CSS**: Progressive enhancement approach
- **Cross-Device Testing**: Tested on multiple screen sizes

## 🔒 Security Features

- **Input Validation**: All user inputs validated and sanitized
- **SQL Injection Prevention**: Prepared statements used throughout
- **XSS Protection**: Output escaping and content security
- **Session Management**: Secure session handling
- **Password Security**: Bcrypt hashing for all passwords
- **Access Control**: Role-based permissions system

## 🧪 Recent Fixes & Improvements

### Database & SQL Fixes
- ✅ Fixed all ambiguous column errors in SQL queries
- ✅ Resolved SQLSTATE[23000] integrity constraint violations
- ✅ Updated table aliases for proper column references
- ✅ Fixed payment status tracking and updates

### PHP Error Fixes
- ✅ Fixed all `number_format()` parameter type errors
- ✅ Implemented `formatCurrency()` helper function
- ✅ Resolved undefined variable warnings
- ✅ Fixed array key existence checks

### Mobile Responsiveness
- ✅ Complete mobile-responsive implementation
- ✅ Updated all major CSS files for mobile support
- ✅ Created comprehensive mobile testing suite
- ✅ Fixed navigation and layout issues on small screens

### Payment System Updates
- ✅ Removed payment proof upload functionality
- ✅ Implemented "Submit Payment" and "Cancel Payment" actions
- ✅ Fixed payment status flow and enum values
- ✅ Updated admin payment verification workflow

## 📁 Project Structure

```
connectpro-agency/
├── admin/                  # Admin panel files
│   ├── includes/          # Admin layout and functions
│   ├── booking-details.php
│   ├── bookings.php
│   ├── payments.php
│   └── ...
├── user/                   # User interface files
│   ├── includes/          # User helpers and functions
│   ├── dashboard.php
│   ├── book-service.php
│   └── ...
├── payment/               # Payment processing files
├── api/                   # API endpoints
├── css/                   # Stylesheets
│   ├── style.css
│   ├── admin.css
│   ├── mobile-responsive.css
│   └── ...
├── js/                    # JavaScript files
├── uploads/               # File uploads
├── config/                # Configuration files
└── docs/                  # Documentation
```

## 🚀 Deployment

### Production Deployment
1. Upload files to web server
2. Configure virtual host
3. Set up SSL certificate
4. Configure database connection
5. Set proper file permissions
6. Test all functionality

### Environment Variables
Consider using environment variables for:
- Database credentials
- API keys
- Encryption keys
- Debug settings

## 📊 Database Schema

The platform uses a normalized MySQL database with tables for:
- `users` - Client accounts
- `admins` - Admin and agent accounts
- `services` - Service catalog
- `service_bookings` - Client bookings
- `payments` - Payment records
- `chat_rooms` - Chat functionality
- `chat_messages` - Chat messages

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- Create an issue in the repository
- Email: support@connectpro.com
- Documentation: See the `/docs` folder

## 🔧 Troubleshooting

### Common Issues
1. **Database Connection**: Check credentials in `config/database.php`
2. **File Permissions**: Ensure uploads directory is writable
3. **PHP Errors**: Enable error reporting for debugging
4. **Mobile Issues**: Test responsive design on actual devices

### Debug Mode
Enable debug mode in development:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

**Built with ❤️ for efficient business service management**
