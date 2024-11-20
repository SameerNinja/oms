# ✨ Order Management System with Dynamic Pricing

A robust Order Management System built with **Laravel 10** and **MySQL**, designed to streamline your inventory tracking, sales, and order processes.

## 🌟 Key Features

- **Orders**
  - Create Orders
  - Pending Orders
  - Complete Orders
  - Pending Payments
- **Products Management**
- **Customer Records**
- **Supplier Management**
- **API's**
  - Products
  - Customers
  - Orders
  - Order Create
  - Get Discount
  - Order Pay
  - Order Status Update
  - Retrieve Order

## 🚀 Quick Start

Follow these steps to set up the project locally:

1. **Clone the repository:**

    ```bash
    git clone
    ```

2. **Navigate to the project folder:**

    ```bash
    cd
    ```

3. **Install PHP dependencies:**

    ```bash
    composer install
    ```

4. **Copy `.env` configuration:**

    ```bash
    cp .env.example .env
    ```

5. **Generate application key:**

    ```bash
    php artisan key:generate
    ```

6. **Configure the database in the `.env` file** with your local credentials.

7. **Run database migrations and seed sample data:**

    ```bash
    php artisan migrate:fresh --seed
    ```

8. **Link storage for media files:**

    ```bash
    php artisan storage:link
    ```

9. **Install JavaScript and CSS dependencies:**

    ```bash
    npm install && npm run dev
    ```

10. **Start the Laravel development server:**

    ```bash
    php artisan serve
    ```

11. **Login using the default admin credentials:**

    - **Email:** `admin@admin.com`
    - **Password:** `password`

## 🚀 API's

1. **Import Json collection to postman:**

    - `postman_collection.json file stored in public folder`

## 📄 License

Licensed under the [MIT License](LICENSE).
