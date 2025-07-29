# MIW Travel Management System

## Heroku Deployment

This project is configured for deployment on Heroku with PostgreSQL database.

### Environment Variables

The following environment variables need to be set in Heroku:

- `DATABASE_URL`: Automatically provided by Heroku PostgreSQL addon
- `SMTP_HOST`: SMTP server for email functionality (default: smtp.gmail.com)
- `SMTP_USERNAME`: Email address for SMTP authentication
- `SMTP_PASSWORD`: Password or app-specific password for SMTP
- `SMTP_PORT`: SMTP port (default: 587)
- `SMTP_ENCRYPTION`: Encryption type (default: tls)
- `APP_ENV`: Application environment (default: production)
- `MAX_EXECUTION_TIME`: PHP max execution time (default: 300)
- `MAX_FILE_SIZE`: Maximum file upload size (default: 10M)

### Database Setup

The application uses PostgreSQL in production (Heroku) and MySQL in development (XAMPP).

### File Structure

- `config.heroku.php`: Heroku-specific configuration
- `config.php`: Local development configuration
- `Procfile`: Heroku process configuration
- `app.json`: Heroku app metadata and addon requirements

### Deployment

1. Ensure all environment variables are set in Heroku
2. Push code to Heroku Git repository
3. Run database migrations if needed
4. Configure email settings for notifications

### Features

- Customer registration for Haji and Umroh packages
- Document upload and management
- Admin dashboard for package management
- Invoice and manifest generation
- Email notifications
- File upload handling optimized for Heroku's ephemeral filesystem
