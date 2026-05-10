# FoldNest - Smart Foldable Furniture E-Commerce 🛋️✨

**FoldNest** is a full-stack, responsive e-commerce platform designed specifically for space-saving and foldable furniture. It features a modern user storefront, a powerful administrative dashboard, and an integrated AI-powered chatbot.

---

## 🚀 Key Features

### 🛒 Storefront (User Side)
- **Responsive Design**: Premium UI optimized for mobile, tablet, and desktop.
- **Smart Catalog**: Browse and search foldable furniture with real-time stock updates.
- **AI Chatbot**: Gemini-powered AI assistant that knows the current inventory and can answer customer queries.
- **Theme Toggle**: Sleek animated Dark/Light mode switcher.
- **Cart & Checkout**: Complete shopping experience from cart management to order placement.
- **Order Tracking**: Real-time status tracking and order history for registered users.
- **Contact System**: SMTP-integrated contact form using PHPMailer.

### 🛠️ Admin Dashboard (Management Side)
- **Real-time Analytics**: Dashboard overview of sales, revenue, and customer counts.
- **Inventory Management**: Full CRUD (Create, Read, Update, Delete) for products.
- **Order Management**: Process, update, and resolve customer orders.
- **Query Resolution**: Manage and respond to customer contact inquiries via email.
- **Secure Authentication**: Admin-only access with secure session management.

---

## 💻 Tech Stack

- **Frontend**: HTML5, CSS3 (Vanilla), JavaScript (ES6+)
- **Backend**: PHP (API-based architecture)
- **Database**: MySQL (PDO for secure connections)
- **AI Engine**: Google Gemini API (1.5/2.5 Flash)
- **Email Engine**: PHPMailer (SMTP via Gmail)
- **Dev Environment**: WAMP/XAMPP

---

## 🛠️ Installation & Setup

### 1. Prerequisites
- **WAMP Server** or **XAMPP** installed.
- **Google Gemini API Key** (Get it from [Google AI Studio](https://aistudio.google.com/)).
- **Gmail App Password** (for SMTP functionality).

### 2. Database Configuration
1. Open **phpMyAdmin** and create a database named `ecommerce_db`.
2. Import the `ecommerce_db.sql` file provided in the repository.

### 3. Environment Setup
1. Create a `.env` file in the root directory.
2. Add the following credentials:
   ```env
   DB_HOST="localhost"
   DB_NAME="ecommerce_db"
   DB_USER="root"
   DB_PASS=""

   GEMINI_API_KEY="YOUR_GEMINI_API_KEY_HERE"

   SMTP_HOST="smtp.gmail.com"
   SMTP_USER="your-email@gmail.com"
   SMTP_PASS="your-app-password"
   ```

### 4. Local Deployment
1. Copy the project folder to `C:\wamp64\www\foldnest`.
2. Access the Storefront: `http://localhost/foldnest/frontend/index.html`
3. Access the Admin Panel: `http://localhost/foldnest/admin/index.html`

---

## 📂 Project Structure

```text
📁 foldnest/
├── 📁 admin/        # Administrative Dashboard UI
├── 📁 backend/      # PHP API logic & Database config
│   ├── 📁 api/      # API Endpoints (Chatbot, Auth, Products)
│   ├── 📁 config/   # DB & Mail configurations
│   └── 📁 vendor/   # PHPMailer & Dependencies
├── 📁 frontend/     # User Storefront UI
│   ├── 📁 assets/   # Images & Icons
│   ├── 📁 css/      # Modular Stylesheets
│   └── 📁 js/       # Frontend logic & API calls
└── .env             # Environment Variables (Keep Secure!)
```

---

## 🌍 Hosting (Free Tier)
To host this project for free, it is recommended to use **000webhost** (by Hostinger):
1. Zip the folders and upload to `public_html`.
2. Import the database via 000webhost Database Manager.
3. Update `frontend/js/config.js` with your live URL.
4. Update `backend/config/database.php` with the host's DB credentials.

---

## 📜 License
This project is developed as part of a Final Year Project. Feel free to use and modify it for educational purposes.

---

**Developed with ❤️ by the FoldNest Team.**
