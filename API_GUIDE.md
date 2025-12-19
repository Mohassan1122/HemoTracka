# HemoTracka API Integration Guide (V3)

This guide details the available API endpoints for the HemoTracka system, designed for Frontend (Web & Mobile) integration.

**Base URL**: `http://localhost:8000/api`
**Authentication**: Bearer Token required for most endpoints.
**Headers**:
- `Accept: application/json`
- `Content-Type: application/json`
- `Authorization: Bearer {token}`

---

## 1. Authentication
| Method | Endpoint | Description | Payload |
|---|---|---|---|
| POST | `/auth/register` | Register new user | `{first_name, last_name, email, password, phone, role}` |
| POST | `/auth/login` | Login | `{email, password}` |
| POST | `/auth/logout` | Logout | - |
| GET | `/auth/profile` | Get current user | - |
| PUT | `/auth/profile` | Update profile | `{first_name, last_name, phone, ...}` |
| POST | `/auth/change-password` | Change password | `{current_password, password, password_confirmation}` |
| POST | `/auth/forgot-password` | Request reset link | `{email}` |
| POST | `/auth/reset-password` | Reset password | `{email, token, password}` |

## 2. Blood Requests (Facility/Hospital)
| Method | Endpoint | Description | Payload |
|---|---|---|---|
| POST | `/facilities/blood-requests` | Create Request | `{blood_group, units_needed, urgency_level, needed_by, ...}` |
| GET | `/facilities/request-history` | List Requests | - |
| POST | `/facilities/blood-requests/{id}/cancel` | Cancel Request | - |
| GET | `/facilities/blood-requests/{id}/offers` | View Offers | - |
| POST | `/facilities/offers/{id}/accept` | Accept Offer | - |

## 3. Blood Bank Operations
| Method | Endpoint | Description | Payload |
|---|---|---|---|
| GET | `/blood-bank/inventory` | View Inventory | - |
| POST | `/blood-bank/inventory/{id}/adjust-stock` | Update Stock | `{adjustment, reason}` |
| GET | `/blood-bank/requests` | View Incoming Requests | - |
| POST | `/blood-bank/blood-requests/{id}/submit-offer` | Submit Offer | `{product_fee, shipping_fee, notes}` |
| POST | `/blood-bank/requests/{id}/confirm-delivery` | Confirm Delivery Handover | - |

## 4. Delivery & Logistics (Rider)
| Method | Endpoint | Description | Payload |
|---|---|---|---|
| GET | `/rider/deliveries` | Pending/Active Deliveries | - |
| POST | `/rider/deliveries/{id}/pick-up` | Mark Picked Up | - |
| POST | `/rider/deliveries/{id}/in-transit` | Mark In Transit | - |
| POST | `/rider/deliveries/{id}/complete` | Mark Delivered | - |
| POST | `/rider/update-location` | Update GPS | `{latitude, longitude}` |

## 5. Global/Shared
| Method | Endpoint | Description | Payload |
|---|---|---|---|
| GET | `/deliveries/track/{code}` | Public Tracking | - |
| GET | `/notifications` | List Notifications | - |
| POST | `/notifications/{id}/mark-read` | Mark Notification Read | - |
| GET | `/messages/inbox` | List Messages | - |

## 6. Admin
| Method | Endpoint | Description |
|---|---|---|
| GET | `/admin/dashboard` | Platform Overview |
| GET | `/admin/organizations` | Manage Organizations |
| GET | `/admin/users` | Manage Users |
| GET | `/admin/stats/*` | Analytics endpoints |

> [!NOTE]
> Detailed error messages are returned in `{"message": "..."}` format with 4xx/5xx status codes.
