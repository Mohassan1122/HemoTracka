# HemoTracka: The Story Behind the Project

## 🩸 The Vision
**HemoTracka** is a centralized Blood Bank Management System designed to bridge the gap between **Hospitals** (Facilities) in desperate need of blood, **Blood Banks** that supply it, and the **Donors** who give the gift of life. It also employs a fleet of **Riders** to ensure safe and rapid transportation of blood units.

In many regions, patients die because hospitals cannot locate the right blood type in time. HemoTracka solves this by creating a real-time network where:
1.  **Hospitals** can search for blood and make urgent requests.
2.  **Blood Banks** can manage their inventory and fulfill these requests.
3.  **Riders** are dispatched to pick up and deliver the blood.
4.  **Donors** act as a safety net, notified when their specific blood type is critically needed.

---

## 🎭 The Cast (User Roles)

### 1. Facilities (Hospitals) 🏥
*   **Who they are**: Doctors, nurses, or admin staff at medical centers.
*   **Their Goal**: Get blood to a dying patient *fast*.
*   **Key Actions**:
    *   **Search**: Look for "O+ blood" within a 50km radius.
    *   **Request**: If not immediately available, create a "Blood Request" specifying the urgency (Safety, Critical, Routine).
    *   **Track**: Watch the delivery rider's location in real-time on a map.
    *   **Payment**: Pay for the processing and delivery fees securely.

### 2. Blood Banks 🩸
*   **Who they are**: Private or government blood banks.
*   **Their Goal**: Manage stock effectively and supply hospitals.
*   **Key Actions**:
    *   **Inventory Management**: Track every pint of blood, its expiration date, and storage location.
    *   **Fulfillment**: Receive requests from hospitals and "Accept" them, allocating the specific units.
    *   **Donations**: Record walk-in donations from donors to replenish stock.

### 3. Donors 👤
*   **Who they are**: Everyday heroes.
*   **Their Goal**: Donate blood to save lives.
*   **Key Actions**:
    *   **Profile**: Maintain their health profile and blood group (e.g., A+, O-).
    *   **Notifications**: Receive an SMS/Push notification when minimal stock is available for their blood type.
    *   **History**: See a log of all their past donations.

### 4. Riders 🏍️
*   **Who they are**: Logistics partners (bike/van drivers).
*   **Their Goal**: Transport blood safely from Bank to Hospital.
*   **Key Actions**:
    *   **Assignments**: Accept a delivery job.
    *   **Updates**: Mark "Picked Up", "In Transit", and "Delivered".

### 5. Admins 🛡️
*   **Who they are**: The platform overseers.
*   **Their Goal**: Ensure the system runs smoothly.
*   **Key Actions**:
    *   **Monitor**: View global stats (Total Blood Distributed, Most Needed Blood Type).
    *   **Manage**: Approve new Hospitals or Blood Banks joining the platform.

---

## 🔄 The Life Cycle of a Request

1.  **The Emergency**: A patient at *City General Hospital* needs 2 units of B- blood.
2.  **The Search**: The Hospital creates a **Blood Request** (`POST /facilities/blood-requests`).
3.  **The Match**: *Central Blood Bank* sees the request on their dashboard (`GET /blood-bank/requests`). They have the stock, so they **Submit an Offer** to supply it.
4.  **The Deal**: The Hospital **Accepts the Offer** and processes payment.
5.  **The Dispatch**: The system automatically assigns a **Rider**.
6.  **The Journey**:
    *   Rider gets notified.
    *   Rider goes to Blood Bank -> **Picked Up**.
    *   Rider drives to Hospital -> **In Transit**.
    *   Rider arrives -> **Delivered**.
7.  **The Closure**: The Hospital confirms receipt and rates the service.


---

## 🏗️ Technical Architecture Highlight: Entity Authentication

A key feature of HemoTracka is its robust **Entity Authentication System**.
*   **The Challenge**: In many systems, an Organization (like a Hospital) is just a profile attached to a single human user. This causes issues when staff changes occur or when multiple departments need access.
*   **The HemoTracka Solution**: We treat **Organizations** as primary identities.
    *   **Direct Login**: Hospitals and Blood Banks have their own login credentials (Email & Password), distinct from individual human users.
    *   **Unified Account**: Staff members can be added *under* this Organization account, inheriting its authority while maintaining individual audit logs.
    *   **Scalability**: This allows the platform to onboard massive institutions and regulatory bodies seamlessly, without being tied to a single employee's personal account.

---
*Created by the HemoTracka Development Team.*
