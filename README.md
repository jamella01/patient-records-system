# School Clinic Records System

A web-based CRUD application for managing a school clinic's patient records, clinic visits and medicine inventory.
Built with **PHP 8, MySQL, HTML, CSS and Bootstrap 5** for the Web Systems Technologies final project.

**Live demo:** _add your InfinityFree link here_
**Default login:** `admin` / `admin123` (admin) · `nurse` / `nurse123` (nurse)

---

## Features

| Module | What it does |
|---|---|
| **Dashboard** | Counts of patients, visits today and this month, and low-stock medicines; recent visits; medicine alerts (low stock / expiring in 30 days); most common cases |
| **Patients** | Add, view, edit and delete students, faculty and staff; search by name or ID; filter by type; pagination; full visit history per patient |
| **Clinic Visits** | Record consultations with vital signs, diagnosis, treatment, disposition and medicines dispensed; filter by date range and disposition; printable visit slip |
| **Medicines** | Inventory CRUD with stock level bars, reorder alerts and expiry warnings |
| **Staff Accounts** | Admin-only management of login accounts (admin / nurse roles) |

### Extra features
- Medicine stock is **deducted automatically** when medicine is dispensed. Editing or deleting a visit returns the stock, and everything runs in a **database transaction**, so over-dispensing is blocked.
- **Allergy alerts** appear on the visit form, patient profile and visit slip.
- **Fever badge** shows for temperatures of 37.5 °C and above.
- **Printable** clinic visit slip with a signature line.
- **Live search** that runs as you type.
- **Responsive** layout: the sidebar becomes a slide-in menu on phones.
- **Security:** prepared statements (prevents SQL injection), `htmlspecialchars` output escaping (prevents XSS), CSRF tokens on every form, bcrypt password hashing, deletes only via POST, and `.htaccess` blocking direct access to config, includes and the SQL file.

---

## Database design (`clinic_db`)

```
users ──────────────┐ attended_by (SET NULL)
                    ▼
patients ──1:N──► visits ──1:N──► visit_medicines ◄──N:1── medicines
        (CASCADE)        (CASCADE)                 (RESTRICT)
```

| Table | Purpose |
|---|---|
| `users` | Clinic staff accounts (username, bcrypt password, role) |
| `patients` | Patient profile: ID number (unique), name, type, course, sex, birthdate, contacts, blood type, allergies, conditions |
| `visits` | Each consultation: date, complaint, temperature, BP, pulse, diagnosis, treatment, disposition |
| `medicines` | Inventory: name, dosage form, unit, stock, reorder level, expiry date |
| `visit_medicines` | Junction table: which medicines, and how many, were given in each visit |

Foreign-key rules:
- Deleting a patient also deletes their visits.
- Deleting a staff account keeps their visits.
- A medicine that has already been dispensed cannot be deleted.

---

## Project structure

```
patient-records-system/
├── config/db_connect.php      ← database credentials (edit for deployment)
├── includes/                  ← functions.php, header.php, sidebar.php, footer.php
├── assets/css/style.css
├── database/clinic_db.sql     ← schema + sample data
├── patients/                  ← index, create, edit, view, delete (+ _patient.php, _form.php)
├── visits/                    ← index, create, edit, view, delete (+ _visit.php, _form.php)
├── medicines/                 ← index, create, edit, delete (+ _medicine.php)
├── users/                     ← index, create, edit, delete (+ _user.php)
├── dashboard.php, login.php, logout.php, index.php
```

---

## Run locally (XAMPP)

1. Copy the folder to `C:\xampp\htdocs\patient-records-system`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open <http://localhost/phpmyadmin> and create a database named `clinic_db`.
4. Select `clinic_db`, open **Import**, choose `database/clinic_db.sql` and click **Go**.
5. Visit <http://localhost/patient-records-system/> and log in with `admin` / `admin123`.

## Deploy to InfinityFree

1. Create an account at <https://www.infinityfree.net> and create a **Hosting Account**.
2. Go to **Control Panel → MySQL Databases** and create a database. Note the **hostname**, **username**, **password** and **database name**.
3. Edit `config/db_connect.php`:
   ```php
   define('DB_HOST', 'sqlXXX.infinityfree.com');
   define('DB_USER', 'if0_XXXXXXXX');
   define('DB_PASS', 'your_vpanel_password');
   define('DB_NAME', 'if0_XXXXXXXX_clinic_db');
   define('BASE_URL', '');   // empty because the files go straight into htdocs/
   ```
4. Open **File Manager** and upload the **contents** of the project folder into `htdocs/`. Upload the `.htaccess` files too.
5. Open **phpMyAdmin** from the control panel, select your database and import `database/clinic_db.sql`.
6. Open your site (e.g. `https://yourname.infinityfreeapp.com`) and test all CRUD operations.
7. Log in and change the default passwords under **Staff Accounts**.
