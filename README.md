# UniCore - University Management System

UniCore is a modern, full-stack web application designed for universities. It features a custom Role-Based Access Control (RBAC) system for Students, Staff, Course Representatives, and Administrators.

## Tech Stack
- **Frontend**: React, Vite, React Router v6, Axios, Bootstrap 5, Lucide React Icons.
- **Backend**: PHP (OOP MVC architecture), MySQL, PDO Prepared Statements.
- **Security**: bcrypt password hashing, JWT-like session tokens, OTP verification.

## Features
1. **Authentication**: Role-based registration and login with OTP verification.
2. **Lost & Found**: Report lost items and mark them as found.
3. **Marketplace**: Buy and sell academic materials.
4. **Notes Sharing**: Upload and download course PDFs.
5. **Peer Learning**: Students can request help; Course Reps manage and approve requests.
6. **Notifications**: System alerts for users.
7. **Admin Panel**: Manage students and manually assign Course Representatives.

## Setup Instructions

### 1. Database Setup
1. Open XAMPP and start **Apache** and **MySQL**.
2. Create a new database or just run the provided SQL script.
3. Open `phpMyAdmin` or use MySQL CLI to import the `database.sql` file located in the root directory.
   ```bash
   mysql -u root -p < database.sql
   ```

### 2. Backend Setup
1. Place the `uni_core` folder inside your `htdocs` (XAMPP) or `www` (WAMP) directory.
2. Ensure the backend is accessible at `http://localhost/uni_core/backend/`.
3. The database credentials are set to default XAMPP (`root`, no password). Edit `backend/config/Database.php` if needed.

### 3. Frontend Setup
1. Navigate to the `frontend` folder:
   ```bash
   cd frontend
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the Vite development server:
   ```bash
   npm run dev
   ```
4. Access the application at `http://localhost:5173`.

## Default Admin Credentials
- **Enrollment No**: `ADMIN001`
- **Password**: `admin123`

*(You can use the admin panel to search for registered students and promote them to Course Reps).*
