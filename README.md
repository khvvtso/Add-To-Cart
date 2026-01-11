Project Management Web App

A simple PHP-based web application for browsing products, managing stock, and handling a shopping cart workflow. The project separates concerns clearly by splitting HTML, CSS, JavaScript, and PHP into dedicated folders, making it easy to understand, maintain, and extend.

Features

Customer

Browse available products

View product images, descriptions, prices, and stock

Add items to a cart

Update cart quantities

View cart summary

Admin / System

Stock management support

Database table checks and migrations

Clean separation of backend logic and frontend presentation

Tech Stack

Backend: PHP (PDO)

Frontend: HTML, CSS, JavaScript

Database: MySQL / MariaDB

Assets: Static images and data files

Project Structure

management/
│── index.php
│── migrate_table.php
│── check_table.php
│
├── css/
│   └── style.css
│
├── js/
│   └── update.js
│
├── html/
│   └── (HTML templates)
│
├── php/
│   └── customer/
│       ├── index.php
│       ├── add_to_cart.php
│       ├── update_cart.php
│       └── view_cart.php
│
├── assets/
│   ├── images/
│   └── data/
│       └── products.json

Setup Instructions

1. Prerequisites

PHP 8.x or later

MySQL / MariaDB

Apache or Nginx (XAMPP, WAMP, or MAMP works fine)

2. Clone the Repository

git clone https://github.com/your-username/your-repo-name.git
cd your-repo-name

3. Database Setup

Create a database

Update your database credentials in the PHP config files

Run the migration script:

php migrate_table.php

Verify tables:

php check_table.php

4. Run the App

Place the project in your web server root (e.g. htdocs)

Start your web server

Open in browser:

http://localhost/management

Notes

This project follows a no-PHP-in-HTML approach where possible

JavaScript handles UI updates, PHP handles business logic

Product images are stored locally in assets/images

Future Improvements

Authentication (admin vs customer)

Persistent cart using sessions or database

Order checkout and payment integration

Admin dashboard for stock control

Replace JSON product source with full database usage

License

This project is for learning and demonstration purposes. Use and modify freely.

