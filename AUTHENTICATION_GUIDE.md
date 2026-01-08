# HemoTracka Authentication & Authorization Guide

## Overview

HemoTracka supports multiple entity types with distinct authentication models. The system now uses a consistent `role` field approach for both User and Organization models to enable proper frontend differentiation.

---

## Authentication Models

### 1. **User Model** (Individual Users)
Users authenticate via the **User model** with a `role` field:

```json
{
  "id": 1,
  "email": "donor@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "08012345678",
  "role": "donor",
  "organization_id": null,
  "token": "1|abc123..."
}
```

**User Roles:**
- `donor` - Blood donors
- `rider` - Delivery/logistics personnel
- `admin` - System administrators
- `facilities` - Hospital staff (tied to Organization)
- `blood_banks` - Blood bank staff (tied to Organization)

**Endpoints:** `/api/auth/login` (User)

---

### 2. **Organization Model** (Institutional Users)
Organizations authenticate directly as institutional entities with `type` and `role` fields:

```json
{
  "id": 1,
  "email": "admin@bloodbank.com",
  "name": "Central Blood Bank Lagos",
  "type": "Blood Bank",
  "role": "blood_banks",
  "phone": "08022223333",
  "license_number": "BB-2025-001",
  "status": "Active",
  "token": "1|def456..."
}
```

**Organization Types & Roles:**
| Type | Role | Use Case |
|------|------|----------|
| Blood Bank | `blood_banks` | Blood collection/storage facilities |
| Hospital | `facilities` | Medical facilities requesting blood |
| Regulatory Body | `regulatory_body` | Government health authorities |

**Endpoints:** `/api/auth/login` (Organization)

---

## Frontend Differentiation Strategy

### How to Identify the Authenticated Entity

**In the Response Token/User Object:**

```php
// Frontend received data after login
{
  "role": "donor" // or "facilities", "blood_banks", "regulatory_body", "rider", "admin"
}
```

### Decision Tree

```
Is role present in response?
├─ YES
│  ├─ role = "donor" → Individual Donor Dashboard
│  ├─ role = "rider" → Delivery Person Dashboard
│  ├─ role = "admin" → Admin Console
│  ├─ role = "regulatory_body" → Regulatory Body Dashboard
│  └─ role = "facilities" OR "blood_banks" → Check if it came from Organization model
│     ├─ facilities → Hospital Dashboard
│     └─ blood_banks → Blood Bank Dashboard
└─ NO → Handle error
```

### Implementation Example (Vue/React)

```javascript
// After login API call
const response = await loginUser(email, password);
const { role, token, type } = response.data;

switch(role) {
  case 'donor':
    router.push('/donor-dashboard');
    break;
  case 'blood_banks':
    router.push('/blood-bank-dashboard');
    break;
  case 'facilities':
    router.push('/hospital-dashboard');
    break;
  case 'regulatory_body':
    router.push('/regulatory-dashboard');
    break;
  default:
    router.push('/login');
}
```

---

## API Endpoints by Role

### Donor (User Model)
```
GET  /api/donor/profile
PUT  /api/donor/profile
GET  /api/donor/donations
POST /api/donor/donate
GET  /api/appointments
```

### Blood Bank (Organization Model)
```
GET  /api/blood-bank/dashboard
GET  /api/blood-bank/inventory
PUT  /api/blood-bank/profile
GET  /api/blood-bank/requests
POST /api/blood-bank/requests/{id}/accept
```

### Hospital/Facilities (Organization Model)
```
GET  /api/facilities/dashboard
PUT  /api/facilities/profile
POST /api/facilities/blood-requests
GET  /api/facilities/blood-requests
GET  /api/facilities/deliveries
```

### Regulatory Body (User Model)
```
GET  /api/regulatory-body/profile
GET  /api/regulatory-body/blood-banks
POST /api/regulatory-body/compliance-monitoring
GET  /api/regulatory-body/compliance-requests
```

---

## Login Endpoint Behavior

**Single Endpoint:** `POST /api/auth/login`

The login endpoint automatically detects whether credentials belong to a User or Organization:

```json
// Request
{
  "email": "admin@bloodbank.com",
  "password": "password"
}

// Response (Organization - Blood Bank)
{
  "user": {
    "id": 1,
    "email": "admin@bloodbank.com",
    "name": "Central Blood Bank",
    "type": "Blood Bank",
    "role": "blood_banks",
    "token": "1|abc123..."
  }
}

// Response (User - Donor)
{
  "user": {
    "id": 5,
    "email": "donor@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "role": "donor",
    "token": "1|xyz789..."
  }
}
```

---

## Token Usage

All authenticated requests use Bearer token:

```
Authorization: Bearer {token}

Example:
Authorization: Bearer 1|abc123def456...
```

---

## Role-Based Access Control (RBAC)

Middleware checks verify role at the controller level:

```php
// BloodBankController
public function updateProfile(Request $request)
{
    $organization = $request->user(); // Could be Organization or User
    
    if ($organization->role !== 'blood_banks') {
        return response()->json(['message' => 'Access denied'], 403);
    }
    // Process update...
}
```

---

## Database Schema

### users table
```
id, first_name, last_name, email, phone, password, role, organization_id
```

### organizations table
```
id, name, type, role, email, password, license_number, status, ...
```

### Key Difference:
- **User.role** → Identifies individual user type
- **Organization.role** → Identifies organizational type (mirrors User.role values)
- **Organization.type** → Descriptive type (Blood Bank, Hospital)

---

## Migration & Data Update

Run migration to add `role` field to organizations:

```bash
php artisan migrate
```

This automatically populates roles:
- Blood Bank → `role = 'blood_banks'`
- Hospital → `role = 'facilities'`

---

## Summary

| Aspect | User | Organization |
|--------|------|--------------|
| Model | `App\Models\User` | `App\Models\Organization` |
| Identifier Field | `role` | `role` (+ `type` for description) |
| Examples | donor, rider, admin | blood_banks, facilities, regulatory_body |
| Auth Endpoint | `/api/auth/login` | `/api/auth/login` (same) |
| Dashboard Route | Role-based | role-based |

**Frontend Rule:** Always check the `role` field in the login response to determine which dashboard to display, regardless of whether it came from User or Organization model.
