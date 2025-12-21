# HemoTracka API Integration Guide (v3)

This guide documents the complete API layer for HemoTracka.
**Base URL**: `http://localhost:8000/api`

---

## 🔐 1. Authentication (Global)
*UI Pages: Login Screen, Register Screen, Forgot Password Screen, Profile Settings.*

#### [POST] http://localhost:8000/api/auth/register
**Option 1: Individual Registration (Donors, Riders)**
- **Payload**:
  ```json
  {
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@test.com",
    "password": "password",
    "password_confirmation": "password",
    "role": "donor|rider",
    "blood_group": "O+"   // Optional (for Donors)
  }
  ```

**Option 2: Organization Registration (Hospitals, Blood Banks)**
- **Payload**:
  ```json
  {
    "name": "City General Hospital",
    "license_number": "LIC-12345",
    "address": "123 Main St",
    "email": "admin@cityhospital.com", // Used for Login
    "phone": "08012345678",
    "password": "password",
    "password_confirmation": "password",
    "type": "Hospital|Blood Bank",
    "role": "facilities|blood_banks" // Triggers Org Registration
  }
  ```
- **Expected Result**: `{ "message": "Registration successful", "token": "...", "user": { ... } }`
- **UI Usage**: **Register Screen (separate tabs or forms for Organization vs Individual)**.

#### [POST] http://localhost:8000/api/auth/login
- **Description**: Authenticates a user and returns a Sanctum token.
- **Payload**: `{"email": "...", "password": "..."}`
- **Expected Result**: `{ "token": "...", "user": { ... } }`
- **UI Usage**: **Login Screen**.

#### [POST] http://localhost:8000/api/auth/logout
- **Description**: Invalidates the current session token.
- **Payload**: None (Requires Bearer Token)
- **Expected Result**: `{ "message": "Logged out successfully" }`
- **UI Usage**: **Sidebar / Profile Dropdown -> Logout Button**.

#### [POST] http://localhost:8000/api/auth/forgot-password
- **Description**: Sends a password reset link to the user's email.
- **Payload**: `{"email": "..."}`
- **Expected Result**: `{ "message": "Reset link sent" }`
- **UI Usage**: **Forgot Password Screen**.

#### [POST] http://localhost:8000/api/auth/reset-password
- **Description**: Resets the password using the token from email.
- **Payload**: `{"email": "...", "token": "...", "password": "...", "password_confirmation": "..."}`
- **Expected Result**: `{ "message": "Password reset successfully" }`
- **UI Usage**: **Reset Password Screen** (from Email Link).

#### [GET] http://localhost:8000/api/auth/profile
- **Description**: Fetches current authenticated user details.
- **Payload**: None
- **Expected Result**: User Object.
- **UI Usage**: **Profile Page**, **Header (User Name/Avatar)**.

#### [PUT] http://localhost:8000/api/auth/profile
- **Description**: Updates basic user profile info (Name, Avatar).
- **Payload**: `{"first_name": "...", "last_name": "...", "avatar": (file)}`
- **Expected Result**: Updated User Object.
- **UI Usage**: **Profile Settings -> Edit Profile**.

#### [POST] http://localhost:8000/api/auth/change-password
- **Description**: Changes the password for a logged-in user.
- **Payload**: `{"current_password": "...", "password": "...", "password_confirmation": "..."}`
- **Expected Result**: `{ "message": "Password updated" }`
- **UI Usage**: **Profile Settings -> Security Tab**.

---

## 🏥 2. Facilities (Hospitals)
*UI Pages: Hospital Dashboard, Request Blood Modal, Staff Management, Payments.*

#### [POST] http://localhost:8000/api/facilities/blood-requests
- **Description**: Creates a new emergency or standard blood request.
- **Payload**:
  ```json
  {
    "blood_group": "A+",
    "units_needed": 2,
    "urgency_level": "Critical|High|Normal",
    "type": "Emergent|Planned",
    "needed_by": "2025-12-30 12:00:00"
  }
  ```
- **Expected Result**: Created Request Object.
- **UI Usage**: **"Request Blood" Modal / Form**.

#### [GET] http://localhost:8000/api/facilities/blood-requests
- **Description**: Lists all active requests made by this facility.
- **Payload**: None
- **Expected Result**: Array of Requests.
- **UI Usage**: **Dashboard -> "My Requests" Table**.

#### [POST] http://localhost:8000/api/facilities/blood-requests/{id}/cancel
- **Description**: Cancels an active request.
- **Payload**: None
- **Expected Result**: `{ "message": "Request cancelled" }`
- **UI Usage**: **Request Details -> "Cancel/Withdraw" Button**.

#### [GET] http://localhost:8000/api/facilities/inventory-search
- **Description**: Searches for blood availability across connected Blood Banks.
- **Payload**: Query Params: `?blood_group=A+&distance=50`
- **Expected Result**: List of accessible Inventory Items with Blood Bank info.
- **UI Usage**: **Search Blood Page**, **"Check Availability" Widget**.

#### [GET] http://localhost:8000/api/facilities/users
- **Description**: Lists all staff members under this hospital account.
- **Payload**: None
- **Expected Result**: Array of Users.
- **UI Usage**: **Settings -> Staff Management**.

#### [POST] http://localhost:8000/api/facilities/users
- **Description**: Adds a new staff member.
- **Payload**: User Registration fields (minus Organization ID, implied).
- **Expected Result**: Created User.
- **UI Usage**: **Staff Management -> "Add Staff" Modal**.

#### [GET] http://localhost:8000/api/facilities/reports
- **Description**: Gets summary stats for the facility (Total Requests, fulfilled, pending).
- **Payload**: None
- **Expected Result**: JSON with stats counters.
- **UI Usage**: **Hospital Dashboard -> Top Analytics Cards**.

#### [POST] http://localhost:8000/api/facilities/payments/process
- **Description**: Initiates payment for a blood request fulfillment.
- **Payload**: `{"blood_request_id": 1, "amount": 5000, "payment_method": "Card"}`
- **Expected Result**: Payment/Transaction Object.
- **UI Usage**: **Checkout / Payment Modal** after accepting an offer.

#### [GET] http://localhost:8000/api/facilities/blood-requests/{id}/offers
- **Description**: Lists offers from Blood Banks for a specific request.
- **Payload**: None
- **Expected Result**: Array of Offers (with Price, ETA).
- **UI Usage**: **Request Details -> "View Offers" Panel**.

#### [POST] http://localhost:8000/api/facilities/offers/{id}/accept
- **Description**: Accepts a Blood Bank's offer.
- **Payload**: None
- **Expected Result**: Transaction creation / Status update.
- **UI Usage**: **Offers Panel -> "Accept Offer" Button**.

---

## 🩸 3. Blood Bank
*UI Pages: Bank Dashboard, Inventory Management, Requests Feed, Delivery Management.*

#### [GET] http://localhost:8000/api/blood-bank/dashboard
- **Description**: Dashboard metrics (Stock levels, Pending requests, Revenue).
- **Payload**: None
- **Expected Result**: Stats Object.
- **UI Usage**: **Bank Dashboard -> Overview**.

#### [GET] http://localhost:8000/api/blood-bank/inventory
- **Description**: Lists current blood stock.
- **Payload**: None
- **Expected Result**: List of Stock Items (Group, Quantity, Expiry).
- **UI Usage**: **Inventory Page -> Stock Table**.

#### [POST] http://localhost:8000/api/blood-bank/inventory/{id}/adjust-stock
- **Description**: Manually updates stock count (add donation or remove expired/used).
- **Payload**: `{"adjustment": 5, "reason": "Restock"}` (Negative for removal).
- **Expected Result**: Updated Inventory Item.
- **UI Usage**: **Inventory Page -> "Adjust Stock" Modal**.

#### [GET] http://localhost:8000/api/blood-bank/requests
- **Description**: Feed of incoming blood requests from nearby Hospitals.
- **Payload**: None
- **Expected Result**: List of Open Requests.
- **UI Usage**: **"Marketplace" / Incoming Requests Feed**.

#### [POST] http://localhost:8000/api/blood-bank/blood-requests/{id}/submit-offer
- **Description**: Submits a price/logistics offer to fulfill a hospital's request.
- **Payload**: `{"product_fee": 5000, "shipping_fee": 1000, "notes": "..."}`
- **Expected Result**: Created Offer Object.
- **UI Usage**: **Request Details -> "Submit Quote" Form**.

#### [POST] http://localhost:8000/api/blood-requests/{id}/approve
- **Description**: Approves a request for Pickup (if no negotiation needed).
- **Payload**: `{"pickup_location": "..."}`
- **Expected Result**: Status Update.
- **UI Usage**: **Request Details -> "Approve for Pickup" Button**.

#### [POST] http://localhost:8000/api/blood-bank/requests/{id}/confirm-delivery
- **Description**: Confirms the blood has been handed over to a Rider.
- **Payload**: None
- **Expected Result**: Delivery Object updated.
- **UI Usage**: **Order Management -> "Confirm Handover" Button**.

#### [PUT] http://localhost:8000/api/blood-bank/donors/{id}/health
- **Description**: Updates a donor's eligibility status after a checkup.
- **Payload**: `{"status": "Eligible|Ineligible", "notes": "..."}`
- **Expected Result**: Donor Status updated.
- **UI Usage**: **Donor Check-in / Screening Screen**.

#### [GET] http://localhost:8000/api/blood-bank/settings
- **Description**: Fetches bank configuration (Operating hours, Location).
- **Payload**: None
- **Expected Result**: Settings Object.
- **UI Usage**: **Settings Page**.

---

## 👤 4. Donors
*UI Pages: Donor Home, Find Bank, My Donations, Badges.*

#### [GET] http://localhost:8000/api/donor/dashboard
- **Description**: Donor's personalized home (Next eligible date, total donations).
- **Payload**: None
- **Expected Result**: Dashboard Stats.
- **UI Usage**: **Donor Home Screen**.

#### [GET] http://localhost:8000/api/donor/eligibility
- **Description**: Checks if the donor can donate today.
- **Payload**: None
- **Expected Result**: `{ "eligible": true, "days_remaining": 0 }`
- **UI Usage**: **Home Screen -> Eligibility Card**.

#### [GET] http://localhost:8000/api/donor/blood-banks/nearby
- **Description**: Finds closest blood banks for donation.
- **Payload**: Query (Lat/Long implied or stored).
- **Expected Result**: List of Banks with distance.
- **UI Usage**: **"Locate Blood Bank" Map/List**.

#### [POST] http://localhost:8000/api/donor/appointments
- **Description**: Books a donation slot.
- **Payload**: `{"organization_id": 1, "appointment_date": "2025-12-30 09:00:00"}`
- **Expected Result**: Appointment Object.
- **UI Usage**: **Book Appointment Screen**.

#### [GET] http://localhost:8000/api/donor/donations
- **Description**: History of past donations.
- **Payload**: None
- **Expected Result**: List of Donation Records.
- **UI Usage**: **My Donations Page**.

#### [GET] http://localhost:8000/api/donor/badges
- **Description**: Lists earned gamification badges.
- **Payload**: None
- **Expected Result**: List of Badges.
- **UI Usage**: **"My Achievements" / Profile Page**.

---

## 🏍️ 5. Riders
*UI Pages: Delivery Map, Active Jobs, History.*

#### [GET] http://localhost:8000/api/rider/available
- **Description**: Toggles or checks rider availability.
- **Payload**: None (Toggle logic)
- **Expected Result**: `{ "is_available": true }`
- **UI Usage**: **Home Screen -> "Go Online" Switch**.

#### [GET] http://localhost:8000/api/rider/deliveries
- **Description**: Lists assigned or available delivery jobs.
- **Payload**: None
- **Expected Result**: List of Deliveries.
- **UI Usage**: **"New Jobs" Tab**.

#### [POST] http://localhost:8000/api/rider/deliveries/{id}/pick-up
- **Description**: Marks delivery as picked up from Blood Bank.
- **Payload**: None
- **Expected Result**: Status 'In Transit'.
- **UI Usage**: **Active Delivery Screen -> "Confirm Pickup" Button**.

#### [POST] http://localhost:8000/api/rider/deliveries/{id}/complete
- **Description**: Marks delivery as delivered to Hospital.
- **Payload**: `{"proof_image": (optional)}`
- **Expected Result**: Status 'Delivered'.
- **UI Usage**: **Active Delivery Screen -> "Complete Delivery" Button**.

#### [POST] http://localhost:8000/api/rider/update-location
- **Description**: Updates rider's real-time coords.
- **Payload**: `{"latitude": 12.34, "longitude": 56.78}`
- **Expected Result**: `{ "status": "ok" }`
- **UI Usage**: **Background Location Service**.

---

## 🛡️ 6. Admin
*UI Pages: Super Admin Dashboard, User Tables, Reports.*

#### [GET] http://localhost:8000/api/admin/dashboard
- **Description**: Platform-wide statistics.
- **Payload**: None
- **Expected Result**: Global Stats.
- **UI Usage**: **Admin Dashboard**.

#### [GET] http://localhost:8000/api/admin/organizations
- **Description**: Lists all registered Hospitals and Banks.
- **Payload**: None
- **Expected Result**: List of Orgs.
- **UI Usage**: **Organization Management Table**.

#### [PUT] http://localhost:8000/api/admin/organizations/{id}/status
- **Description**: Approves or Suspends an organization.
- **Payload**: `{"status": "Approved|Suspended"}`
- **Expected Result**: Updated Org.
- **UI Usage**: **Org Details -> "Approve/Reject" Actions**.

#### [GET] http://localhost:8000/api/admin/stats/donations-by-blood-group
- **Description**: Analytics chart data.
- **Payload**: None
- **Expected Result**: `{ "A+": 100, "O-": 50 ... }`
- **UI Usage**: **Analytics Page -> Blood Group Chart**.

#### [GET] http://localhost:8000/api/admin/stats/low-stock-alerts
- **Description**: System-wide low stock warnings.
- **Payload**: None
- **Expected Result**: List of Critical Items.
- **UI Usage**: **Dashboard -> Alert Widget**.

---

## 📨 7. Shared / Mobile
*UI Pages: Chat, Notifications, Sidebar.*

#### [GET] http://localhost:8000/api/messages/inbox
- **Description**: User's message inbox.
- **Payload**: None
- **Expected Result**: List of Threads/Messages.
- **UI Usage**: **Chat Screen -> Inbox Tab**.

#### [POST] http://localhost:8000/api/mobile/messages/send/{userId}
- **Description**: Sends a chat message (Mobile optimized).
- **Payload**: `{"message": "Hello there"}`
- **Expected Result**: Message Object.
- **UI Usage**: **Chat Screen -> Input Field**.

#### [GET] http://localhost:8000/api/notifications
- **Description**: User's notification list.
- **Payload**: None
- **Expected Result**: List of Notifications.
- **UI Usage**: **Notification Screen / Dropdown**.

#### [POST] http://localhost:8000/api/notifications/mark-all-read
- **Description**: Clears unread badge.
- **Payload**: None
- **Expected Result**: Success.
- **UI Usage**: **Notification Screen -> "Mark All Read"**.

#### [POST] http://localhost:8000/api/feedback
- **Description**: Submits app feedback or support ticket.
- **Payload**: `{"rating": 5, "comment": "Great app!"}`
- **Expected Result**: Created Feedback.
- **UI Usage**: **"Help & Support" Modal**.
