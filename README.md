<div align="center">

<img src="https://mrms.xo.je/assets/images/logo_blue.png" alt="Meghdoot Resort Logo" height="80"/>

# Meghdoot Resort Management System

### A full-stack web-based hotel management platform

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![SSLCommerz](https://img.shields.io/badge/Payment-SSLCommerz-00A650?style=flat-square)](https://sslcommerz.com)
[![License](https://img.shields.io/badge/License-Academic-blue?style=flat-square)](#)
[![Live Demo](https://img.shields.io/badge/Live%20Demo-mrms.xo.je-1d4ed8?style=flat-square&logo=vercel&logoColor=white)](https://mrms.xo.je)

**[Live Demo](https://mrms.xo.je) · [Report Bug](https://github.com) · [Request Feature](https://github.com)**

</div>

---

## Overview

MRMS (Meghdoot Resort Management System) is a complete, role-based hotel management platform built for **Meghdoot Resort, Kishoreganj, Bangladesh**. It digitises every touchpoint of the guest experience — from room browsing and online booking to in-stay services, billing, and operations management — in a single unified system.

Built as a group project for the **Information Systems Design** course at Kishoreganj University (Group 06, Session 2021–22).

---

## Live Site

> **[https://mrms.xo.je](https://mrms.xo.je)**

---

## Features

### Guest
- Browse rooms with live availability status
- View room details, photos, and guest reviews
- Book a room with date selection and double-booking prevention
- Pay 30% advance online via SSLCommerz payment gateway
- Pay remaining due amount before check-in
- Cancel booking with automatic tiered refund (0–80%)
- My Stay portal — 4-tab dashboard for bookings, service requests, food orders, and request history
- View live running bill during stay
- Leave star ratings and written reviews for rooms and food items
- Update profile, view reviews, delete account

### Receptionist
- Reception dashboard with live check-in / check-out counts
- Search and filter all bookings by status, date, guest, room
- Process guest check-in with confirmation modal
- Process check-out with live bill: room + services + 5% tax
- Add and remove service charges (for phone/verbal orders)
- Apply discount to due amount before checkout
- Auto-generate and print invoices

### Manager
- Hotel overview dashboard with KPI cards and live charts
- Manage bookings, rooms, billing, and finance
- View revenue, transaction logs, discount audit, refund tracking
- Operations sidebar navigation across all manager pages

### Admin
- Full system control: users, rooms, food menu, announcements
- Approve or reject guest refund requests
- Complete finance report — revenue by source, tax, discounts, refunds
- Month-by-month revenue table with date range filters
- Manage announcements with expiry dates, categories, and PDF attachments

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8 (no framework) |
| Database | MySQL 8 (mysqli, prepared statements) |
| Frontend | HTML5, Bootstrap 5.3, Vanilla JavaScript |
| Payment | SSLCommerz (sandbox) |
| Auth | PHP Session-based, 4 roles |
| Charts | Chart.js 4 |

---

## Project Structure

```
mrms/
├── auth.php                    # Session auth — root login redirect for all subfolders
├── db.php                      # Shared DB connection
├── navbar.php                  # Role-aware navigation, depth-aware paths
├── footer.php                  # Depth-aware logo path
├── index.php                   # Homepage
├── login.php / register.php    # Auth pages
├── rooms.php / room_details.php
├── food_menu.php
├── announcement.php
├── booking_manager.php
├── service_submit.php
├── review_widget.php
│
├── user/                       # Guest self-service pages
│   ├── dashboard.php
│   ├── my_stay.php             # 4-tab stay portal
│   ├── payment.php / payment_success.php / payment_fail.php
│   ├── cancel_booking.php / refund_request.php
│   ├── current_bill.php
│   ├── review_submit.php
│   ├── update_profile.php / update_action.php
│   ├── my_reviews.php
│   ├── delete_account.php
│   └── sidebar.php
│
├── receptionist/               # Front-desk operations
│   ├── reception.php           # Tabbed dashboard
│   ├── checkin_content.php
│   ├── checkout_content.php
│   ├── manage_bookings_content.php
│   └── generate_invoice.php
│
├── admin/                      # Admin panel
│   ├── dashboard.php
│   ├── finance.php
│   ├── manage_rooms.php
│   ├── manage_bookings.php
│   ├── manage_users.php
│   ├── manage_food.php
│   ├── manage_announcements.php
│   └── reports.php
│
├── manager/                    # Manager operations panel
│   ├── dashboard.php
│   ├── bookings.php
│   ├── rooms.php
│   ├── billing.php
│   ├── finance.php
│   └── sidebar.php
│
├── sslcommerz/                 # Payment gateway callbacks
│   ├── init.php
│   ├── success.php
│   ├── fail.php
│   └── cancel.php
│
└── assets/
    └── images/
        ├── rooms/
        ├── food/
        └── users/
```

---

## Roles

| Role | Access |
|---|---|
| **Guest** | Public pages, booking, payment, My Stay portal, reviews, profile |
| **Receptionist** | Check-in, check-out, service charge management, invoice generation |
| **Manager** | Hotel overview, bookings, rooms, billing, finance dashboard |
| **Admin** | Full system access — users, rooms, food, announcements, finance, refunds |

---

## Payment Flow

```
Guest books room
      │
      ▼
Pay 30% advance ──► SSLCommerz ──► success.php
      │                                 │
      │                          Booking → Confirmed
      │                          Room   → Booked
      │
      ▼
Receptionist checks in guest
      │
      ▼
Guest stay (services, food orders)
      │
      ▼
Receptionist checks out
      │  applies discount + adds charges
      ▼
Remaining 70% (± discount ± extra charges + 5% tax)
      │
      ├── Paid online before checkout
      └── Collected as cash at desk
            │
            ▼
        Invoice generated
```

---

## Database Schema

Core tables: `user`, `booking`, `room`, `payment`, `invoice`, `service_request`, `food_menu`, `announcement`, `review`

**Key design decision:** `pay_amount` stores the fixed grand total (set at booking, never changed). `due_amount` starts equal to `pay_amount` and is decremented by each payment received. Actual amount paid at any point = `pay_amount - due_amount`.

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-username/mrms.git

# 2. Move to your web server root
mv mrms /var/www/html/

# 3. Import the database
mysql -u root -p < mrms_db.sql

# 4. Configure DB connection
# Edit db.php with your credentials:
#   $host = 'localhost';
#   $user = 'root';
#   $pass = '';
#   $db   = 'mrms_db';

# 5. Configure SSLCommerz
# Edit sslcommerz/init.php:
#   $store_id     = 'your_store_id';
#   $store_passwd = 'your_store_password';
#   $base_url     = 'http://localhost/mrms';

# 6. Visit http://localhost/mrms
```

---

## Group Members

| # | Name | Student ID |
|---|---|---|
| 01 | Prothoma Akter | 202223104002 |
| 02 | Nadira Khanom | 202223104003 |
| 03 | Forhadurzzaman | 202223104022 |
| 04 | Shafiul Mujnibeen | 202223104030 |

**Course:** Information Systems Design  
**Group:** 06  
**Institution:** Kishoreganj University  
**Session:** 2021–22

---

<div align="center">

Built with PHP, MySQL, Bootstrap 5 and SSLCommerz &nbsp;·&nbsp; Meghdoot Resort, Kishoreganj, Bangladesh

</div>
