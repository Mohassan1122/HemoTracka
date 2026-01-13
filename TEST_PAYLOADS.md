# HemoTracka API Test Payloads & Endpoints

This document contains test credentials, API endpoints, and sample payloads for testing the HemoTracka application.

## 1. Base URL
`http://127.0.0.1:8000/api`

## 2. Test Credentials (Seeded Users)

| Role | Email | Password |
|------|-------|----------|
| **Super Admin** | `admin@hemotracka.com` | `password` |
| **Donor** | `donor_seed0@example.com` | `password` |
| **Rider** | `rider_seed0@example.com` | `password` |
| **Organization (Hospital)** | `contact@lagosstatehospital.com` | `password` |

> **Note:** For organizations, the email is derived from the seeded name. If the above doesn't work, check the `users` or `organizations` table for the exact email.

## 3. Authentication Endpoints

### Login
**Endpoint:** `POST /login`

**Payload:**
```json
{
    "email": "donor_seed0@example.com",
    "password": "password"
}
```

### Register (Donor)
**Endpoint:** `POST /register`

**Payload:**
```json
{
    "first_name": "Test",
    "last_name": "User",
    "email": "testuser@example.com",
    "phone": "08012345678",
    "password": "password",
    "password_confirmation": "password",
    "role": "donor",
    "blood_group": "O+",
    "genotype": "AA",
    "date_of_birth": "1995-05-20",
    "gender": "Male",
    "address": "123 Test Street, Lagos"
}
```

### Register (Organization)
**Endpoint:** `POST /register`

**Payload:**
```json
{
    "name": "Test Hospital",
    "email": "info@testhospital.com",
    "phone": "08098765432",
    "password": "password",
    "password_confirmation": "password",
    "role": "facilities",
    "type": "Hospital",
    "license_number": "LIC-TEST-001",
    "address": "456 Health Ave, Abuja"
}
```

## 4. Blood Requests

### Create Blood Request
**Endpoint:** `POST /blood-requests`
**Auth:** Requires Bearer Token (Organization/Hospital)

**Payload:**
```json
{
    "blood_group": "A+",
    "units_needed": 5,
    "urgency": "High",
    "is_emergency": true,
    "needed_by": "2026-02-01 12:00:00",
    "location": "Ward C, General Hospital",
    "notes": "Urgent need for surgery patient",
    "type": "Whole Blood",
    "request_source": "blood_banks" 
}
```

### List Blood Requests
**Endpoint:** `GET /blood-requests`
**Auth:** Requires Bearer Token

## 5. User Profile

### Get Profile
**Endpoint:** `GET /profile`
**Auth:** Requires Bearer Token

### Update Profile (Donor)
**Endpoint:** `POST /profile` (or `PUT /profile`)

**Payload:**
```json
{
    "first_name": "UpdatedName",
    "phone": "08011112222",
    "blood_group": "O-"
}
```

## 6. Testing Flow
1.  **Login** as an Organization (or Register a new one).
2.  **Copy the `token`** from the response.
3.  Use the token in the `Authorization` header: `Bearer <token>` for subsequent requests.
4.  **Create a Blood Request**.
5.  **Login** as a different user (e.g., Donor or Admin) to view the request via `GET /blood-requests`.
