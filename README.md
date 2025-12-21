# HemoTracka - Blood Bank Management System API

A comprehensive Laravel 10 backend API for managing blood donations, inventory, requests, and deliveries.


## 🚀 Quick Start & Deployment

For a detailed step-by-step guide on hosting this project (XAMPP or Production), please refer to **[DEPLOYMENT.md](file:///c:/xampp/htdocs/Sydani_Work/HemoTracka/DEPLOYMENT.md)**.

### Prerequisites
- PHP 8.1+
- Composer
- MySQL 5.7+

### Rapid Setup
1. `copy .env.example .env`
2. `php artisan key:generate`
3. `php artisan migrate --seed`
4. `php artisan serve`

The API will be available at `http://localhost:8000/api`

## 🔐 Default Login Credentials

| Role   | Email                    | Password     |
|--------|--------------------------|--------------|
| Admin  | admin@hemotracka.com     | password123  |
| Staff  | staff@hemotracka.com     | password123  |
| Donor  | donor@hemotracka.com     | password123  |
| Rider  | rider@hemotracka.com     | password123  |

## 📚 API Endpoints

### Authentication
| Method | Endpoint                | Description          |
|--------|-------------------------|----------------------|
| POST   | `/api/auth/register`    | Register new user    |
| POST   | `/api/auth/login`       | Login user           |
| POST   | `/api/auth/logout`      | Logout user          |
| GET    | `/api/auth/profile`     | Get user profile     |
| PUT    | `/api/auth/profile`     | Update profile       |
| POST   | `/api/auth/change-password` | Change password |

### Organizations
| Method | Endpoint                          | Description              |
|--------|-----------------------------------|--------------------------|
| GET    | `/api/organizations`              | List all organizations   |
| POST   | `/api/organizations`              | Create organization      |
| GET    | `/api/organizations/{id}`         | Get organization         |
| PUT    | `/api/organizations/{id}`         | Update organization      |
| DELETE | `/api/organizations/{id}`         | Delete organization      |
| GET    | `/api/organizations/{id}/stats`   | Get organization stats   |

### Donors
| Method | Endpoint                      | Description           |
|--------|-------------------------------|-----------------------|
| GET    | `/api/donors`                 | List all donors       |
| POST   | `/api/donors`                 | Create donor          |
| GET    | `/api/donors/{id}`            | Get donor             |
| PUT    | `/api/donors/{id}`            | Update donor          |
| DELETE | `/api/donors/{id}`            | Delete donor          |
| GET    | `/api/donors/{id}/donations`  | Get donor's donations |

### Donations
| Method | Endpoint               | Description        |
|--------|------------------------|--------------------|
| GET    | `/api/donations`       | List all donations |
| POST   | `/api/donations`       | Record donation    |
| GET    | `/api/donations/{id}`  | Get donation       |
| PUT    | `/api/donations/{id}`  | Update donation    |
| DELETE | `/api/donations/{id}`  | Delete donation    |

### Inventory
| Method | Endpoint                              | Description         |
|--------|---------------------------------------|---------------------|
| GET    | `/api/inventory`                      | List inventory      |
| POST   | `/api/inventory`                      | Create item         |
| GET    | `/api/inventory/{id}`                 | Get item            |
| PUT    | `/api/inventory/{id}`                 | Update item         |
| DELETE | `/api/inventory/{id}`                 | Delete item         |
| POST   | `/api/inventory/{id}/adjust-stock`    | Adjust stock        |
| GET    | `/api/inventory/summary`              | Get summary by blood group |

### Blood Requests
| Method | Endpoint                                | Description       |
|--------|-----------------------------------------|-------------------|
| GET    | `/api/blood-requests`                   | List requests     |
| POST   | `/api/blood-requests`                   | Create request    |
| GET    | `/api/blood-requests/{id}`              | Get request       |
| PUT    | `/api/blood-requests/{id}`              | Update request    |
| DELETE | `/api/blood-requests/{id}`              | Delete request    |
| POST   | `/api/blood-requests/{id}/approve`      | Approve request   |
| POST   | `/api/blood-requests/{id}/cancel`       | Cancel request    |

### Deliveries
| Method | Endpoint                                | Description        |
|--------|-----------------------------------------|--------------------|
| GET    | `/api/deliveries`                       | List deliveries    |
| GET    | `/api/deliveries/{id}`                  | Get delivery       |
| PUT    | `/api/deliveries/{id}`                  | Update delivery    |
| POST   | `/api/deliveries/{id}/assign-rider`     | Assign rider       |
| POST   | `/api/deliveries/{id}/pick-up`          | Mark picked up     |
| POST   | `/api/deliveries/{id}/in-transit`       | Mark in transit    |
| POST   | `/api/deliveries/{id}/complete`         | Mark delivered     |
| GET    | `/api/deliveries/track/{code}`          | Track by code (public) |

### Messages
| Method | Endpoint                      | Description           |
|--------|-------------------------------|-----------------------|
| GET    | `/api/messages/inbox`         | Get inbox             |
| GET    | `/api/messages/sent`          | Get sent messages     |
| POST   | `/api/messages`               | Send message          |
| GET    | `/api/messages/{id}`          | Get message           |
| DELETE | `/api/messages/{id}`          | Delete message        |
| GET    | `/api/messages/unread-count`  | Get unread count      |
| POST   | `/api/messages/mark-all-read` | Mark all as read      |

### Notifications
| Method | Endpoint                              | Description        |
|--------|---------------------------------------|--------------------|
| GET    | `/api/notifications`                  | List notifications |
| GET    | `/api/notifications/unread`           | Get unread         |
| GET    | `/api/notifications/unread-count`     | Get unread count   |
| POST   | `/api/notifications/{id}/mark-read`   | Mark as read       |
| POST   | `/api/notifications/mark-all-read`    | Mark all as read   |
| DELETE | `/api/notifications/{id}`             | Delete notification|

### Feedback
| Method | Endpoint                        | Description          |
|--------|---------------------------------|----------------------|
| GET    | `/api/feedback`                 | List feedback        |
| POST   | `/api/feedback`                 | Submit feedback      |
| GET    | `/api/feedback/{id}`            | Get feedback         |
| PUT    | `/api/feedback/{id}`            | Update feedback      |
| DELETE | `/api/feedback/{id}`            | Delete feedback      |
| GET    | `/api/feedback/average-rating`  | Get average rating   |

### Dashboard/Stats
| Method | Endpoint                              | Description              |
|--------|---------------------------------------|--------------------------|
| GET    | `/api/stats/dashboard`                | Dashboard stats          |
| GET    | `/api/stats/donations-by-blood-group` | Donations by blood group |
| GET    | `/api/stats/inventory-by-blood-group` | Inventory by blood group |
| GET    | `/api/stats/requests-by-status`       | Requests by status       |
| GET    | `/api/stats/monthly-donations`        | Monthly donation trends  |
| GET    | `/api/stats/low-stock-alerts`         | Low stock alerts         |

## 🔑 Authentication

All protected endpoints require a Bearer token. After login, include the token in headers:

```
Authorization: Bearer {your_token}
```

## 📦 Models & Relationships

```
Organization
├── hasMany Users
├── hasMany Donors
├── hasMany InventoryItems
├── hasMany BloodRequests
├── hasMany Donations
└── hasMany Settings

User
├── belongsTo Organization
├── hasOne Donor
├── hasOne Rider
├── hasMany Messages (sent/received)
└── hasMany Notifications

Donor
├── belongsTo User
├── belongsTo Organization
└── hasMany Donations

BloodRequest
├── belongsTo Organization
└── hasOne Delivery

Delivery
├── belongsTo BloodRequest
└── belongsTo Rider

Rider
├── belongsTo User
└── hasMany Deliveries
```

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

## 📧 Email Configuration

Configure your `.env` for email:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@hemotracka.com
MAIL_FROM_NAME="${APP_NAME}"
```

## 🔔 Notifications

The system includes these notifications:
- `NewBloodRequestNotification` - When a new blood request is created
- `DeliveryStatusNotification` - When delivery status changes
- `LowStockAlertNotification` - When inventory falls below threshold
- `DonationRecordedNotification` - Thank you notification for donors

## 📁 Project Structure

```
app/
├── Http/
│   └── Controllers/
│       └── Api/
│           ├── AuthController.php
│           ├── OrganizationController.php
│           ├── DonorController.php
│           ├── DonationController.php
│           ├── InventoryController.php
│           ├── BloodRequestController.php
│           ├── DeliveryController.php
│           ├── MessageController.php
│           ├── NotificationController.php
│           ├── FeedbackController.php
│           ├── RiderController.php
│           └── StatsController.php
├── Models/
│   ├── User.php
│   ├── Organization.php
│   ├── Donor.php
│   ├── Donation.php
│   ├── InventoryItem.php
│   ├── BloodRequest.php
│   ├── Delivery.php
│   ├── Rider.php
│   ├── Message.php
│   ├── Setting.php
│   └── Feedback.php
└── Notifications/
    ├── NewBloodRequestNotification.php
    ├── DeliveryStatusNotification.php
    ├── LowStockAlertNotification.php
    └── DonationRecordedNotification.php
```

## 📄 License

MIT License

---

Built with ❤️ using Laravel 10
