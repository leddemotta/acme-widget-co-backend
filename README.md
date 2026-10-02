# Acme Widget Co - Sales System

[![Tests](https://img.shields.io/badge/Tests-100%25%20Passing-brightgreen.svg)]()
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)]()
[![License](https://img.shields.io/badge/License-MIT-green.svg)]()
[![Version](https://img.shields.io/badge/Version-1.1.3-orange.svg)]()

A clean, modern PHP application implementing the **Acme Widget Co** shopping basket sales system. It handles product catalog management, promotional discount rules, and tiered delivery fee calculations with exact decimal precision.

---

## 📦 Products & Pricing

| Code | Product | Price |
| :--- | :--- | :--- |
| **R01** | Red Widget | $32.95 |
| **G01** | Green Widget | $24.95 |
| **B01** | Blue Widget | $7.95 |

---

## 🚚 Delivery Rules

Shipping charges are calculated based on the order's subtotal after promotional discounts:
- **Orders under $50.00**: $4.95
- **Orders under $90.00** ($50.00 – $89.99): $2.95
- **Orders of $90.00 or more**: Free delivery ($0.00)

---

## 🏷️ Special Offers

- **"Buy one red widget, get the second half price"**: Every second Red Widget (`R01`) in an order receives a 50% discount ($16.48 off).

---

## 🚀 How to Run

### Start the API Server
Start the local PHP server for the React frontend:
```bash
php -S localhost:8000 index.php
```

---

## 🌐 API Endpoints

### `GET /`
Returns the product catalog, delivery tiers, and promotional offer rules.

### `POST /`
Calculates totals for an array of product codes:
```json
{
  "items": ["B01", "G01"]
}
```

Response:
```json
{
  "status": "success",
  "data": {
    "items": [
      { "code": "B01", "name": "Blue Widget", "price": 7.95 },
      { "code": "G01", "name": "Green Widget", "price": 24.95 }
    ],
    "subtotal": 32.90,
    "discount": 0.00,
    "delivery": 4.95,
    "total": 37.85
  }
}
```

---

## 📂 Project Structure

```text
acme-widget-co-backend/
├── basket.php     # Core domain logic (Product, Catalog, Delivery, Offers, Basket)
├── test.php       # Automated test suite
├── cli.php        # CLI entry point
├── index.php      # Local HTTP server router
├── public/
│   └── index.php  # Public web entry point
└── README.md      # Application overview
```