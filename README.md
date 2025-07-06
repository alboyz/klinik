# 🐾 Pet Clinic Management System

A comprehensive desktop application built with PHP Native for managing a pet shop clinic. This system provides complete management capabilities for veterinary clinics including patient records, doctor management, examinations, medicine inventory, and payment tracking.

## ✨ Features

### 🏠 Main Dashboard
- Beautiful main page with cat and dog images
- Date display and secure login system
- Comprehensive statistics overview
- Quick access to all modules

### 👥 User Management
- **Admin Management**: Complete CRUD operations for doctors, animals, and animal owners
- **Role-based Access Control**: Admin, Doctor, and Staff roles
- **Secure Authentication**: Password hashing and session management

### 🩺 Medical Management
- **Doctor Module**: Examination management and medical record keeping
- **Animal Records**: Complete animal profiles with identifying signs, species, race, age, gender, and weight
- **Owner Management**: Customer information with contact details and animal associations
- **Examination System**: Detailed medical examinations with history tracking

### 💊 Inventory & Finance
- **Medicine Management**: Complete inventory system with stock tracking and expiry monitoring
- **Payment System**: Comprehensive payment tracking with multiple payment methods
- **Financial Reports**: Revenue tracking and payment status monitoring

### 📊 Advanced Features
- **Medical Records**: Complete examination history with diagnosis and treatment plans
- **Prescription Management**: Medicine prescription tracking with dosage instructions
- **Low Stock Alerts**: Automatic notifications for medicines running low
- **Responsive Design**: Works on desktop and mobile devices

## 🛠️ System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache or Nginx
- **Extensions**: PDO MySQL, mbstring, openssl
- **Storage**: Minimum 100MB free space

## 📦 Installation

### Method 1: Quick Setup (Recommended)

1. **Download and Extract**
   ```bash
   # Extract the application to your web server directory
   # For XAMPP: htdocs/pet_clinic_app
   # For WAMP: www/pet_clinic_app
   ```

2. **Run Setup Script**
   - Open your web browser
   - Navigate to `http://localhost/pet_clinic_app/setup.php`
   - Follow the setup wizard:
     - Configure database connection
     - Create administrator account
     - Choose to install sample data (recommended for testing)

3. **Complete Installation**
   - Click "Complete Setup"
   - Login with your admin credentials
   - Start using the system!

### Method 2: Manual Installation

1. **Create Database**
   ```sql
   CREATE DATABASE pet_clinic CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import Schema**
   ```bash
   mysql -u username -p pet_clinic < database/schema.sql
   ```

3. **Import Sample Data (Optional)**
   ```bash
   mysql -u username -p pet_clinic < database/sample_data.sql
   ```

4. **Configure Database**
   - Copy `config/database.php.example` to `config/database.php`
   - Update database credentials

## 🚀 Usage

### Default Login Credentials

**Administrator Account:**
- Username: `admin`
- Password: `admin123`

**Sample Doctor Accounts:**
- Username: `dr.johnson` / Password: `doctor123`
- Username: `dr.chen` / Password: `doctor123`
- Username: `dr.rodriguez` / Password: `doctor123`

**Staff Account:**
- Username: `staff` / Password: `staff123`

### Navigation

The application features a sidebar navigation with the following modules:

1. **📊 Dashboard** - Overview and statistics
2. **👥 Manage Admin** - User and system administration
3. **👨‍⚕️ Doctor** - Examination management and medical records
4. **🐕 Animal** - Animal profile management
5. **👤 Animal Owner** - Customer management
6. **🔍 Examination** - Examination history and records
7. **💊 Medicine** - Inventory and medicine management
8. **💰 Payment** - Financial tracking and payment management

### Key Workflows

#### Adding a New Patient
1. Go to **Animal Owner** → Add new owner
2. Go to **Animal** → Add new animal (link to owner)
3. Go to **Doctor** → Create new examination
4. Add **Payment** for services rendered

#### Managing Examinations
1. **Doctor Module** → Add Examination
2. Fill in examination details:
   - Animal information
   - Disease history and allergies
   - Diagnosis and treatment
   - Prescribed medicines
   - Action taken and notes

#### Inventory Management
1. **Medicine Module** → Add medicines
2. Monitor stock levels and expiry dates
3. Update stock quantities as needed
4. Receive low stock alerts automatically

#### Payment Tracking
1. **Payment Module** → Add payments
2. Link payments to examinations
3. Track payment status (Paid, Pending, Partial, Cancelled)
4. Generate financial reports

## 🗂️ Database Structure

### Core Tables
- **users** - System users and authentication
- **doctors** - Doctor profiles and specializations
- **animal_owners** - Customer information
- **animals** - Pet profiles and medical information
- **examinations** - Medical examination records
- **medicines** - Medicine inventory
- **payments** - Financial transactions
- **prescription_medicines** - Prescribed medication tracking

### Key Relationships
- Animals belong to owners (one-to-many)
- Examinations link animals and doctors (many-to-many)
- Payments can be linked to examinations
- Prescriptions link examinations and medicines

## 🔧 Configuration

### Application Settings
Edit `config/database.php` to modify:
- Database connection settings
- Application name and version
- Session timeout settings
- File upload limits
- Currency settings

### Security Features
- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session-based authentication
- Role-based access control

## 📱 Mobile Compatibility

The application is fully responsive and works on:
- Desktop computers
- Tablets
- Mobile phones
- Touch-enabled devices

## 🎨 Customization

### Styling
- Main stylesheet: `assets/css/style.css`
- Responsive design with CSS Grid and Flexbox
- Professional color scheme with blue and white theme
- Custom icons and visual elements

### Adding Features
The modular structure allows easy extension:
1. Create new module in `modules/` directory
2. Add navigation link in sidebar
3. Follow existing patterns for CRUD operations
4. Update database schema if needed

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check database credentials in `config/database.php`
- Ensure MySQL service is running
- Verify database exists and user has permissions

**Permission Denied**
- Check file permissions on config directory
- Ensure web server can write to application directories

**Session Issues**
- Check PHP session configuration
- Verify session directory is writable
- Clear browser cookies and try again

**Styling Issues**
- Clear browser cache
- Check CSS file paths
- Verify web server serves static files

### Error Logging
- Application errors are logged to system error log
- Enable PHP error reporting for development
- Check web server error logs for issues

## 📄 File Structure

```
pet_clinic_app/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   └── images/
├── config/
│   ├── config.php
│   └── database.php
├── database/
│   ├── schema.sql
│   └── sample_data.sql
├── modules/
│   ├── admin/
│   ├── animal/
│   ├── doctor/
│   ├── examination/
│   ├── medicine/
│   ├── owner/
│   └── payment/
├── templates/
├── index.php
├── dashboard.php
├── login.php
├── logout.php
├── setup.php
└── README.md
```

## 🤝 Support

For support and questions:
1. Check this README file
2. Review the troubleshooting section
3. Check application logs for errors
4. Verify system requirements are met

## 📝 License

This Pet Clinic Management System is provided as-is for educational and commercial use. Feel free to modify and distribute according to your needs.

## 🔄 Version History

**Version 1.0.0**
- Initial release
- Complete clinic management system
- User authentication and role management
- Animal and owner management
- Examination and medical records
- Medicine inventory system
- Payment tracking
- Responsive web design
- Sample data for testing

---

**Built with ❤️ for veterinary professionals**

