# Hospital Management System

A comprehensive web-based management system designed for healthcare facilities to handle patient records, admissions, accounting, and departmental workflows.

## 🚀 Features
* **Patient Admission:** Streamlined registration and tracking.
* **Accounting Module:** Manage billing, invoicing, and facility expenses.
* **Departmental Isolation:** Separate control structures for administrative staff.

## 🔐 Security note
This project now supports environment-based database configuration. Copy `.env.example` to `.env` and set your credentials before deployment. The application also includes a basic login throttling mechanism to slow down brute-force attacks.

## 🛠️ Installation & Setup
1. Clone this repository into your XAMPP `htdocs` directory.
2. Copy `.env.example` to `.env` and update the values for your local environment.
3. Start Apache and MySQL via the **XAMPP Control Panel**.
4. Import the system database `.sql` file into your local `phpMyAdmin`.
5. Open your browser and navigate to `http://localhost/hospital_system`.

