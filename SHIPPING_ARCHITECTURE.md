# Shipping Company Workflow Architecture

## Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         GROWAI SYSTEM                                │
└─────────────────────────────────────────────────────────────────────┘

                              ADMIN
                                │
                    ┌───────────┴──────────────┐
                    │                          │
                    ▼                          ▼
            [1] CONNECT CARRIER          [2] ENABLE UPDATES
            ┌──────────────────┐         ┌─────────────────┐
            │ POST /companies  │         │ POST /companies │
            │ {api_key, ...}   │         │ {}/enable-updates
            └────────┬─────────┘         └────────┬────────┘
                     │                           │
                     ▼                           ▼
            ┌──────────────────┐         ┌─────────────────┐
            │ STORE CREDENTIALS│         │ REGISTER WEBHOOK│
            │ (Encrypted)      │         │ with Carrier    │
            └────────┬─────────┘         └────────┬────────┘
                     │                           │
                     └───────────┬───────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │ CARRIER CONNECTED & READY
                    └────────────┬────────────┘
                                 │
                    ┌────────────▼────────────┐
            [3] CREATE SHIPMENT             │
            ┌──────────────────────────────┐│
            │ POST /shipments              ││
            │ {order_id, company_id, ...} ││
            └──────────┬───────────────────┘│
                       │                    │
                       ▼                    │
            ┌──────────────────────────────┐│
            │ CALL CARRIER API             ││
            │ createParcel()               ││
            └──────────┬───────────────────┘│
                       │                    │
                       ▼                    │
            ┌──────────────────────────────┐│
            │ GET TRACKING NUMBER          ││
            │ from Carrier Response        ││
            └──────────┬───────────────────┘│
                       │                    │
                       ▼                    │
            ┌──────────────────────────────┐│
            │ SAVE SHIPMENT                ││
            │ - tracking_number            ││
            │ - status = picked_up         ││
            │ - shipped_at = now()         ││
            └──────────┬───────────────────┘│
                       │                    │
                       ▼                    │
            ┌──────────────────────────────┐│
            │ LINK TO ORDER                ││
            │ orders.shipment_id = shipment_id
            └──────────┬───────────────────┘│
                       │                    │
                       │ RESPONSE:          │
                       │ {shipment with     │
                       │  tracking_number}  │
                       │                    │
                       └────────────────────┘

                            ┌──────────────────────┐
                            │ AUTOMATIC PROCESS    │
                            └──────────┬───────────┘
                                       │
                    ┌──────────────────┴──────────────────┐
                    │                                     │
                    ▼                                     ▼
        ┌───────────────────────┐         ┌───────────────────────┐
        │ [4] CARRIER UPDATES   │         │ USER CAN TRACK        │
        │ Events happen         │         │ GET /shipments/{id}/  │
        │ - Picked up           │         │     tracking          │
        │ - In transit          │         │                       │
        │ - Out for delivery    │         └───────────────────────┘
        │ - Delivered           │
        └──────────┬────────────┘
                   │
                   ▼
    ┌──────────────────────────────────────┐
    │ CARRIER SENDS WEBHOOK                │
    │ POST /api/shipments/webhook/{id}     │
    │ {tracking_number, status, notes}     │
    └──────────────┬───────────────────────┘
                   │
                   ▼
    ┌──────────────────────────────────────┐
    │ VERIFY & PROCESS WEBHOOK             │
    │ - Find shipment                      │
    │ - Map status                         │
    │ - Update timestamps                  │
    │ - Log changes                        │
    └──────────────┬───────────────────────┘
                   │
                   ▼
    ┌──────────────────────────────────────┐
    │ SHIPMENT STATUS UPDATED              │
    │ - Database updated                   │
    │ - Timestamps recorded                │
    │ - History logged                     │
    └──────────────┬───────────────────────┘
                   │
                   ▼
    ┌──────────────────────────────────────┐
    │ USER SEES LIVE TRACKING              │
    │ GET /shipments/{id}                  │
    │ Shows latest status from carrier     │
    └──────────────────────────────────────┘
```

## Sequence Diagram

```
┌──────┐        ┌────────────────┐        ┌──────────┐        ┌────────────┐
│Admin │        │GROWAI System   │        │Carrier   │        │Order       │
│      │        │                │        │API       │        │Database    │
└──┬───┘        └────────┬───────┘        └─────┬────┘        └─────┬──────┘
   │                      │                      │                   │
   │ 1. Connect           │                      │                   │
   ├─────────────────────>│                      │                   │
   │ {api_key}            │                      │                   │
   │                      │ Encrypt & Store      │                   │
   │                      ├─────────────────────────────────────────>│
   │                      │                      │                   │
   │ 2. Enable Updates    │                      │                   │
   ├─────────────────────>│                      │                   │
   │                      │ Register Webhook     │                   │
   │                      ├──────────────────────>│                   │
   │                      │ {webhook_url}        │                   │
   │                      │<──────────────────────┤                   │
   │                      │ {webhook_id}         │                   │
   │                      │ Webhook Status       │                   │
   │                      ├─────────────────────────────────────────>│
   │                      │                      │                   │
   │ 3. Create Shipment   │                      │                   │
   ├─────────────────────>│                      │                   │
   │ {order_id, company}  │ Call API             │                   │
   │                      ├──────────────────────>│                   │
   │                      │ {parcel_data}        │                   │
   │                      │<──────────────────────┤                   │
   │                      │ {tracking_number}    │                   │
   │                      │ Store Shipment       │                   │
   │                      ├─────────────────────────────────────────>│
   │                      │ Link to Order        │                   │
   │                      ├─────────────────────────────────────────>│
   │<─────────────────────┤                      │                   │
   │ {shipment_with_      │                      │                   │
   │  tracking}           │                      │                   │
   │                      │                      │ ≈ LATER ≈         │
   │                      │        Webhook Event │                   │
   │                      │<──────────────────────┤                   │
   │                      │ {status_update}      │                   │
   │                      │ Process & Update     │                   │
   │                      ├─────────────────────────────────────────>│
   │                      │ Update Status        │                   │
   │                      │ Set Timestamps       │                   │
   │                      │                      │                   │
   │ 4. Get Tracking      │                      │                   │
   ├─────────────────────>│                      │                   │
   │ {shipment_id}        │ Query API/DB         │                   │
   │                      ├─────────────────────────────────────────>│
   │<─────────────────────┤ {current_status,     │                   │
   │ {tracking_info}      │  history}            │                   │
   │                      │                      │                   │
```

## Data Flow

### Request Flow: Create Shipment
```
CLIENT
  │
  └─→ POST /api/shipments
       {order_id, delivery_company_id, ...}
        │
        ├─→ VALIDATE (StoreShipmentRequest)
        │
        ├─→ ShipmentController@store()
        │    │
        │    ├─→ Load Order from DB
        │    │
        │    ├─→ Load DeliveryCompany from DB
        │    │
        │    ├─→ Check if connected
        │    │
        │    ├─→ DB Transaction:
        │    │    ├─→ Create Shipment record
        │    │    │
        │    │    ├─→ DeliveryCompany@createParcel()
        │    │    │    ├─→ Encrypt API Key
        │    │    │    ├─→ Call HTTP POST to Carrier API
        │    │    │    └─→ Return tracking_number
        │    │    │
        │    │    └─→ Update Shipment with tracking_number
        │    │
        │    └─→ Link Order to Shipment
        │
        └─→ RESPONSE: {shipment}
```

### Webhook Flow: Status Update
```
CARRIER
  │
  └─→ POST /api/shipments/webhook/{companyId}
       {tracking_number, status, notes}
        │
        ├─→ VERIFY signature (optional)
        │
        ├─→ ShipmentController@handleWebhook()
        │    │
        │    ├─→ Extract tracking_number from payload
        │    │
        │    ├─→ Find Shipment in DB
        │    │
        │    ├─→ Map carrier status to standard status
        │    │
        │    ├─→ Update Shipment:
        │    │    ├─→ status = mapped_status
        │    │    ├─→ delivery_notes = from payload
        │    │    ├─→ shipped_at = now() [if picked_up]
        │    │    └─→ delivered_at = now() [if delivered]
        │    │
        │    └─→ Log update
        │
        └─→ RESPONSE: {success}
```

## Error Handling Flow

```
REQUEST
  │
  ├─→ Validation Error
  │    └─→ 422 {validation_errors}
  │
  ├─→ Not Found
  │    └─→ 404 {message}
  │
  ├─→ Unauthorized
  │    └─→ 403 {message}
  │
  ├─→ Business Logic Error
  │    ├─→ Carrier not connected
  │    │    └─→ 422 {message}
  │    ├─→ API Call Failed
  │    │    ├─→ Log error
  │    │    └─→ 422 {message with reason}
  │    └─→ Shipment in final state
  │         └─→ 422 {message}
  │
  └─→ Server Error
       └─→ 500 {message}
```

## Status Lifecycle

```
┌──────────┐
│ pending  │  (Shipment created, awaiting carrier pickup)
└─────┬────┘
      │
      ▼
┌──────────────┐
│ picked_up    │  (Carrier collected shipment)
└─────┬────────┘
      │
      ▼
┌──────────────┐
│ in_transit   │  (Shipment on its way)
└─────┬────────┘
      │
      ▼
┌──────────────────┐
│ out_for_delivery │  (Shipment out for delivery today)
└─────┬────────────┘
      │
      ├─→ ┌──────────┐
      │   │ delivered│  (✓ Successfully delivered)
      │   └──────────┘
      │
      ├─→ ┌──────────┐
      │   │ returned │  (Returned to sender)
      │   └──────────┘
      │
      └─→ ┌──────────┐
          │ failed   │  (Delivery failed or cancelled)
          └──────────┘
```

## Database Relationships

```
┌─────────────┐
│   Orders    │
├─────────────┤
│ id          │◄──┐
│ client_id   │   │ FK
│ shipment_id ├───┤
│ ...         │   │
└─────────────┘   │
                  │ 1:1
              ┌───┴────────────────┐
              │                    │
         ┌────▼──────┐    ┌────────▼──┐
         │ Shipments │    │ Can have   │
         ├───────────┤    │ multiple   │
         │ id        │    │ shipments  │
         │ order_id  │◄───┤ (1:many)   │
         │ company_id├───┐│            │
         │ ...       │   ││            │
         └───┬───────┘   ││            │
             │           ││            │
             │      FK───┘└────────────┘
             │ 1:many
             │
         ┌───▼──────────────┐
         │ DeliveryCompanies│
         ├──────────────────┤
         │ id               │
         │ name             │
         │ api_key          │
         │ api_url          │
         │ webhook_enabled  │
         │ ...              │
         └──────────────────┘
```

---

This architecture provides a robust, scalable system for managing delivery integrations with full automation and real-time tracking.
