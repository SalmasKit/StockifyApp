# 📦 Stockify - Inventory Management System

![Stockify Dashboard](assets/screenshots/dashboard.png)

Stockify is a professional, high-performance inventory management system built with **Symfony 7.4** and **TailwindCSS**. It provides a comprehensive suite of tools for tracking products, categories, transactions, and managing stock operations like restocks and donations.

## ✨ Key Features

- **📊 Comprehensive Dashboard**: Real-time overview of your inventory status, including low-stock alerts and key metrics.
- **🚀 Product & Category Management**: Full CRUD capabilities for managing your inventory catalog.
- **♻️ Restock Operations**: Easily add inventory to existing products with automated transaction logging.
- **🤝 Donation Tracking**: Manage stock removal for donations with dedicated workflows and auditing.
- **📜 Transaction Ledger**: A detailed history of every stock change (RESTOCK, DONATION, etc.) for full accountability.
- **🔐 Secure User Management**: 
    - Role-based access control (Admin/User).
    - User registration and profile management.
    - Account activation/deactivation.
- **📱 Responsive Design**: Built with TailwindCSS 4.x for a premium, mobile-first experience.
- **📥 Data Export/Import**: Integration with PHPSpreadsheet for handling bulk data operations.

## 📸 Visual Walkthrough

| **Login Page** | **Dashboard** |
| :---: | :---: |
| ![Login Page](assets/screenshots/login.png) | ![Dashboard Overview](assets/screenshots/dashboard.png) |

## 🛠️ Technology Stack

- **Core**: PHP 8.2+, Symfony 7.4
- **Database**: Doctrine ORM (MySQL/PostgreSQL compatible)
- **Frontend**: Twig, TailwindCSS 4.x, Symfony UX Turbo & Stimulus
- **Tools**: PHPSpreadsheet, Composer

## 🚀 Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- A database engine (MySQL/MariaDB/PostgreSQL/SQLite)
- Symfony CLI (optional but recommended)

### Installation

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/SalmasKit/StockifyApp.git
    cd StockifyApp
    ```

2.  **Install dependencies**:
    ```bash
    composer install
    ```

3.  **Configure Environment**:
    Create a `.env.local` file and set your database connection:
    ```env
    DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/stockify?serverVersion=8.0.32&charset=utf8mb4"
    ```

4.  **Database Setup**:
    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

5.  **Setup Assets**:
    ```bash
    php bin/console assets:install
    php bin/console importmap:install
    ```

6.  **Run the Server**:
    Using Symfony CLI:
    ```bash
    symfony serve
    ```
    Or using PHP built-in server:
    ```bash
    php -S localhost:8000 -t public
    ```

## 📖 Usage

1.  **Login/Register**: Access the system via the login page. New accounts may require administrator activation depending on configuration.
2.  **Manage Catalog**: Add categories first, then create products associated with them.
3.  **Stock Operations**: Use the "Restock" page to add items and the "Donations" page to log outgoing stock.
4.  **Audit**: Review the "Transactions" page to see a complete history of all movements.

---
*Built with ❤️ using Symfony.*
