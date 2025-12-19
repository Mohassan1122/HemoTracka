# HemoTracka API Integration Guide (V3)

This guide is designed for frontend developers (Web & Mobile) to integrate with the HemoTracka backend. All requests should be sent to `http://localhost:8000/api`.

---

## 🔑 Global Configuration

### Base URL
`http://localhost:8000/api`

### Authentication
Most endpoints require a `Bearer` token.
- **Header**: `Authorization: Bearer {token}`
- **Accept Header**: `Accept: application/json`

### Standard Response Format
```json
{
    "success": true,
    "message": "Optional message",
    "data": { ... }
}
```

---

## 🚀 1. Authentication & User Profile

### [POST] /auth/register
Register a new user (Donor or Staff).
- **Body**:
  ```json
  {
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "password": "password123",
      "password_confirmation": "password123",
      "role": "donor", // donor, rider, facilities, blood_banks
      "phone": "08012345678"
  }
  ```

### [POST] /auth/login
Get API Token.
- **Body**: `{"email": "...", "password": "..."}`
- **Response**: `{ "token": "...", "user": { ... } }`

### [GET] /auth/profile
Get details of the currently authenticated user.

---

## 📱 2. Mobile Dashboard & Activity

### [GET] /activity-feed
Consolidated feed for the "Today's Activity" section on mobile.
- **Purpose**: Returns a mixed list of recent Donations, Requests, and Deliveries.
- **Response**:
  ```json
  "data": [
      {
          "type": "Donation",
          "title": "New Donation from John Doe",
          "subtitle": "A+ - 2 units",
          "time": "5 minutes ago",
          "timestamp": "2025-12-18T..."
      }
  ]
  ```

### [GET] /donor/dashboard
Specific stats for blood donors.
- **Data**: Total units, badge progress, next eligible date.
- **Includes**: `quick_actions` array for mobile button navigation.

---

## 💉 3. Multi-Offer Blood Request System

### [POST] /facilities/blood-requests
Create a request for blood (Hospital side).
- **Body**:
  ```json
  {
      "blood_group": "B+",
      "units_needed": 5,
      "urgency_level": "Urgent",
      "patient_name": "Alice Smith",
      "hospital_unit": "ICU"
  }
  ```

### [GET] /facilities/blood-requests/{id}/offers
**[New]** List all competitive offers received for this request.
- **Response**: List of offers from different blood banks with their respective fees.

### [POST] /blood-bank/blood-requests/{id}/submit-offer
**[New]** Submit an offer (Blood Bank side).
- **Body**:
  ```json
  {
      "product_fee": 5000,
      "shipping_fee": 1500,
      "card_charge": 200,
      "notes": "Premium refrigerated handling"
  }
  ```

### [POST] /facilities/offers/{offer}/accept
**[New]** Hospital accepts a specific bid.
- **Action**: Marks the offer as 'Accepted', rejects others, and initiates the **Delivery Rider** assignment.

---

## 💳 4. Payments & Subscriptions

### [POST] /facilities/payments/process
Simulate a payment for a blood request.
- **Body**:
  ```json
  {
      "blood_request_id": 10,
      "amount": 6700,
      "payment_method": "Card", // Card, Bank Transfer, POD
      "card_details": { "number": "4242...", "cvv": "123" }
  }
  ```

### [POST] /subscriptions/subscribe
Upgrade to a premium plan.
- **Body**: `{"plan_id": 1}`
- **Plans**: 1: Basic (Free), 2: Premium (Hospital), 3: Pharmacy Pro.

---

## 📦 5. Logistics & Tracking

### [GET] /deliveries/track/{code}
Public endpoint to track shipment status.
- **Response**: Includes the `status_history` timeline (Order Taken -> Picked Up -> In Transit).

### [POST] /facilities/deliveries/{id}/confirm-receipt
**[New]** Final confirmation by the Hospital that the blood has arrived safely.

---

## 💬 6. Mobile Messaging (Chat)

### [GET] /mobile/messages/conversations
List all active chat threads.
- **Data**: Shows other user's name, role, and the last message snippet.

### [GET] /mobile/messages/chat/{otherUserId}
Get full history of messages with a specific person.

---

## 📊 7. Inventory (Mobile Grid)

### [GET] /blood-bank/inventory
Returns inventory categorized for the mobile grid view.
- **Groups**: A+, A-, B+, B-, AB+, AB-, O+, O-.
- **Status**: 'High', 'Low', or 'Out of Stock'.

---

> [!NOTE]
> All timestamps are in ISO 8601 format. For binary data (logos, photos), use `multipart/form-data`.
