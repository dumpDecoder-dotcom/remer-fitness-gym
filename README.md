# Remer Fitness Gym Management System

A web-based gym management system built with PHP and MySQL. It handles memberships, class bookings, a supplement shop, payment verification and notifications for members, staff and admins.

## Features

- Member registration with email verification (OTP) and password reset
- Role-based access for admin, staff, cashier and members
- Class scheduling, booking and instructor assignment
- Product shop with cart, promo codes and restocking
- Payment submission and verification, with PDF receipts
- QR code check-in and attendance tracking
- Membership renewal and expiry reminders
- In-app notifications
- Member progress tracking and workout assignment

## Tech Stack

PHP, MySQL, JavaScript, HTML/CSS, PHPMailer (email), Dompdf (PDF receipts), Composer

## Setup (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start Apache and MySQL.
2. Copy this project into `C:\xampp\htdocs\remer-fitness-gym`.
3. Open `http://localhost/phpmyadmin`, create a database, and import `remer_fitness_db.sql`.
4. Check the database name in `Actions/dbconnect.php` and make it match.
5. Run `composer install` in the project folder to download the dependencies.
6. Copy `Actions/mail_config.example.php` to `Actions/mail_config.php` and enter a Gmail address and a Gmail App Password. This file is ignored by Git, so it is never uploaded.
7. Open `http://localhost/remer-fitness-gym`.

## Demo Login

The SQL file contains one demo admin account for testing only.

- Email: YOUR_DEMO_EMAIL
- Password: set your own by using Forgot Password, or replace the hash in the database

## Notes

- Member payment proofs are not included in this repository (`uploads/payments` is empty).
- Product photos in `uploads/products` are sample data.

## Screenshots

Add screenshots here.
