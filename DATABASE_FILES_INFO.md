# Database Files Available

## 📊 Database Options

Your repository now includes two database files for different purposes:

### 1. `connectpro_agency.sql` (✅ UPLOADED)
- **Purpose**: Complete database with sample data
- **Size**: ~78KB
- **Contains**: 
  - Full database structure (tables, indexes, constraints)
  - Sample users, admins, services, bookings
  - Sample payment records and chat data
  - Test cryptocurrency wallet configurations
- **Best for**: Development, testing, and demo purposes

### 2. `database_schema.sql`
- **Purpose**: Database structure only
- **Size**: ~33KB
- **Contains**: 
  - Empty table structures
  - Indexes and constraints
  - No sample data
- **Best for**: Production deployment

## 🚀 Import Instructions

### For Development/Testing:
```bash
mysql -u root -p
CREATE DATABASE connectpro_agency;
USE connectpro_agency;
SOURCE /path/to/connectpro_agency.sql;
```

### For Production:
```bash
mysql -u root -p
CREATE DATABASE connectpro_agency;
USE connectpro_agency;
SOURCE /path/to/database_schema.sql;
```

## 📋 Database Contents (connectpro_agency.sql)

### Sample Data Included:
- **Admin Users**: Multiple admin accounts with different roles
- **Regular Users**: Sample client accounts
- **Services**: Various business services with pricing
- **Bookings**: Sample service bookings in different statuses
- **Payments**: Cryptocurrency payment records
- **Chat System**: Sample chat rooms and messages

### Sample Login Credentials:
- Check the SQL file for admin account details
- Default passwords are hashed with bcrypt
- Create new admin accounts through the admin panel

## 🔄 Recent Updates:
- ✅ Full database uploaded to GitHub
- ✅ README updated with import instructions
- ✅ Both structure-only and data-inclusive options available
- ✅ Ready for immediate deployment and testing

Your repository is now complete with all necessary database files!
