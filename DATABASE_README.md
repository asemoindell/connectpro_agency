# ConnectPro Agency - Full-Stack Website with Database Integration

A comprehensive agency website that helps clients find and book various professional services with a complete admin dashboard and MySQL database integration.

## 🌟 Features

### Public Website
- **Modern responsive design** with mobile optimization
- **Services showcase** with detailed descriptions and pricing
- **Contact forms** that save to database
- **About page** with company information and team
- **Newsletter subscription**
- **Professional service categories** (Travel, Legal, Tax, Engineering, etc.)

### Admin Dashboard
- **Complete authentication system** with email/password login
- **Real-time dashboard** with statistics from database
- **Content management** for website pages
- **Services management** with full CRUD operations
- **Inquiries management** with status tracking
- **User management** for admin accounts
- **Responsive admin interface**

### Database Integration
- **MySQL database** with complete schema
- **RESTful API** endpoints for all operations
- **Secure authentication** with password hashing
- **Contact form submissions** saved to database
- **Real-time data updates** in admin dashboard

## 🚀 Quick Setup

### Prerequisites
- **XAMPP** (Apache + MySQL + PHP)
- Modern web browser

### 1. Install XAMPP
1. Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Start **Apache** and **MySQL** services from XAMPP Control Panel

### 2. Setup Database
**Option A - Automatic Setup (Recommended):**
1. Open your browser and go to: `http://localhost/Agency/setup.php`
2. This will automatically create the database and tables with sample data

**Option B - Manual Setup:**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Import the SQL file: `database/schema.sql`

### 3. Access the Website
- **Main Website**: `http://localhost/Agency/`
- **Admin Login**: `http://localhost/Agency/admin-login.html`
- **Admin Dashboard**: `http://localhost/Agency/admin-dashboard.html`

## 🔑 Default Admin Credentials

### Super Admin
- **Email**: `admin@connectpro.com`
- **Password**: `password`

### Content Admin
- **Email**: `content@connectpro.com`
- **Password**: `password`

## 📁 Project Structure

```
Agency/
├── index.html              # Homepage
├── about.html              # About page
├── services.html           # Services page
├── contact.html            # Contact page
├── admin-login.html        # Admin login
├── register.html           # Admin registration
├── admin-dashboard.html    # Admin dashboard
├── setup.php              # Database setup script
├── config/
│   └── database.php       # Database connection
├── api/
│   └── index.php          # RESTful API endpoints
├── database/
│   └── schema.sql         # Database schema
├── css/
│   ├── style.css          # Main website styles
│   └── admin.css          # Admin interface styles
├── js/
│   ├── script.js          # Main website JavaScript
│   ├── auth.js            # Authentication logic
│   └── admin.js           # Admin dashboard logic
└── images/
    ├── hero-image.svg     # Hero section image
    ├── about-us.svg       # About page image
    └── client*.svg        # Testimonial images
```

## 🛠 Database Schema

### Tables
- **admin_users** - Admin authentication and user management
- **services** - Professional services catalog
- **content_pages** - Website content management
- **contact_inquiries** - Contact form submissions
- **site_settings** - Website configuration

## 🔌 API Endpoints

### Authentication
- `POST /api/auth?action=login` - Admin login
- `POST /api/auth?action=register` - Admin registration

### Services
- `GET /api/services` - Get all services
- `GET /api/services/{id}` - Get specific service
- `POST /api/services` - Create new service
- `PUT /api/services/{id}` - Update service
- `DELETE /api/services/{id}` - Delete service

### Content
- `GET /api/content` - Get all content
- `PUT /api/content/{id}` - Update content

### Inquiries
- `GET /api/inquiries` - Get all inquiries
- `POST /api/inquiries` - Create new inquiry
- `PUT /api/inquiries/{id}` - Update inquiry status

### Dashboard
- `GET /api/dashboard` - Get dashboard statistics

## 🎨 How to Use

### 1. Managing Services
1. Login to admin dashboard
2. Navigate to "Services" section
3. Add, edit, or delete services
4. Changes appear immediately on public website

### 2. Managing Content
1. Go to "Content Management" in admin dashboard
2. Edit existing content sections
3. Update homepage, about page, or any text content

### 3. Viewing Customer Inquiries
1. Go to "Inquiries Management"
2. View all contact form submissions
3. Update inquiry status (new, in-progress, resolved, closed)
4. Export data for follow-up

### 4. Dashboard Analytics
- View real-time statistics
- Monitor new inquiries
- Track active services
- Manage admin users

## 🔧 Configuration

### Database Settings
Edit `config/database.php` to change database connection:

```php
private $host = 'localhost';
private $db_name = 'connectpro_agency';
private $username = 'root';
private $password = '';
```

### Customization
- **Styling**: Edit `css/style.css` and `css/admin.css`
- **Content**: Use admin dashboard for all content changes
- **Services**: Add/modify services through admin interface
- **Settings**: Configure site-wide settings in admin panel

## 📱 Responsive Design
- Mobile-optimized public website
- Touch-friendly admin dashboard
- Cross-device compatibility
- Modern UI/UX design

## 🔒 Security Features
- Password hashing with PHP `password_hash()`
- SQL injection prevention with prepared statements
- Input validation and sanitization
- Session management
- CSRF protection headers

## 🚨 Troubleshooting

### Database Connection Issues
1. Ensure XAMPP MySQL service is running
2. Check database credentials in `config/database.php`
3. Run setup script: `http://localhost/Agency/setup.php`

### Admin Login Problems
1. Use default credentials: `admin@connectpro.com` / `password`
2. Ensure database is set up correctly
3. Check browser console for errors

### Contact Form Not Working
1. Verify Apache and MySQL are running
2. Check API endpoint in browser: `http://localhost/Agency/api/`
3. Review database table `contact_inquiries`

## 🌐 Browser Support
- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers

## 📋 Included Demo Data
- 6 professional services with pricing
- 2 default admin accounts
- Sample website content
- Contact form examples

## 🆕 Future Enhancements
- Email notifications for new inquiries
- Payment gateway integration
- Advanced analytics and reporting
- Customer user accounts and login
- Service booking calendar system
- Multi-language support
- Advanced search and filtering
- Real-time chat support

## 📞 Support
For issues or questions:
1. Check the troubleshooting section above
2. Verify XAMPP services are running
3. Review browser console for errors
4. Check database connection and setup

---

**ConnectPro Agency** - Professional service booking made simple with full database integration and admin management.
