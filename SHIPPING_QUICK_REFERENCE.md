# Quick Reference: Shipping Company API

## Setup Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Seed delivery companies to `delivery_companies` table
- [ ] Connect carrier: `POST /api/companies/1/connect`
- [ ] Enable updates: `POST /api/companies/1/enable-updates`
- [ ] Test connection: `GET /api/companies/1/test-connection`

## API Endpoints Summary

### Carriers
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/companies` | List all carriers |
| GET | `/api/companies/{id}` | Get carrier details |
| POST | `/api/companies/{id}/connect` | Connect with credentials |
| POST | `/api/companies/{id}/disconnect` | Disconnect carrier |
| POST | `/api/companies/{id}/enable-updates` | Register webhook |
| POST | `/api/companies/{id}/disable-updates` | Unregister webhook |
| GET | `/api/companies/{id}/test-connection` | Test API connectivity |

### Shipments
| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/shipments` | List shipments |
| POST | `/api/shipments` | Create shipment/parcel |
| GET | `/api/shipments/{id}` | Get shipment details |
| PUT | `/api/shipments/{id}` | Update shipment status |
| DELETE | `/api/shipments/{id}` | Cancel shipment |
| GET | `/api/shipments/{id}/tracking` | Get tracking info |
| POST | `/api/shipments/webhook/{id}` | Receive webhook (public) |

## Common Workflows

### 1. First Time Setup
```bash
# Connect carrier
POST /api/companies/1/connect
{ "api_key": "xxx" }

# Enable automatic updates
POST /api/companies/1/enable-updates

# Test connection
GET /api/companies/1/test-connection
```

### 2. Create Shipment
```bash
POST /api/shipments
{
  "order_id": 123,
  "delivery_company_id": 1,
  "cod_amount": 599.99
}
```

### 3. Check Status
```bash
# Get current shipment status
GET /api/shipments/456

# Get tracking from carrier
GET /api/shipments/456/tracking
```

### 4. Cancel Shipment
```bash
DELETE /api/shipments/456
```

## Status Codes & Meanings

| Status | Meaning |
|--------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad request |
| 401 | Unauthorized |
| 403 | Forbidden (not admin) |
| 404 | Not found |
| 422 | Validation error |
| 500 | Server error |

## Response Format

### Success
```json
{
  "message": "Operation successful",
  "data": { /* resource */ }
}
```

### Error
```json
{
  "message": "Error description",
  "errors": { /* validation errors */ }
}
```

## Field Reference

### Shipment Statuses
- `pending` - Created, awaiting pickup
- `picked_up` - Collected by carrier
- `in_transit` - On the way
- `out_for_delivery` - Out for delivery
- `delivered` - Delivered ✓
- `returned` - Returned to sender
- `failed` - Failed or cancelled

### Query Filters

**Shipments:**
```
GET /api/shipments?order_id=123&status=in_transit&delivery_company_id=1
```

**Companies:**
```
GET /api/companies?active=true
```

## Useful Commands

```bash
# Check migrations
php artisan migrate:status

# Rollback migration
php artisan migrate:rollback --step=1

# Clear cache
php artisan cache:clear

# Test API locally
curl -X GET http://localhost:8000/api/companies \
  -H "Authorization: Bearer {token}"
```

## Common Errors & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| "Carrier not connected" | No API key stored | Run POST /connect endpoint first |
| "Shipment already exists" | Duplicate shipment | Check existing shipments with GET |
| "Unauthorized" | Missing auth token | Include Authorization header |
| "Not found" | Invalid ID | Verify shipment/company exists |
| "Validation error" | Missing required fields | Check request body format |

## Example cURL Requests

```bash
# List companies
curl -X GET http://localhost:8000/api/companies \
  -H "Authorization: Bearer {token}"

# Connect carrier
curl -X POST http://localhost:8000/api/companies/1/connect \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"api_key": "your_key"}'

# Create shipment
curl -X POST http://localhost:8000/api/shipments \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": 123,
    "delivery_company_id": 1,
    "cod_amount": 599.99
  }'

# Get shipment details
curl -X GET http://localhost:8000/api/shipments/456 \
  -H "Authorization: Bearer {token}"

# Get tracking info
curl -X GET http://localhost:8000/api/shipments/456/tracking \
  -H "Authorization: Bearer {token}"
```

## Files & Locations

- Controllers: `app/Http/Controllers/Api/`
  - `DeliveryCompanyController.php`
  - `ShipmentController.php`
- Models: `app/Models/`
  - `DeliveryCompany.php`
  - `Shipment.php`
  - `Order.php`
- Migrations: `database/migrations/`
  - `2026_05_20_120000_add_shipment_integration.php`
- Routes: `routes/api.php`
- Documentation: `SHIPPING_COMPANY_GUIDE.md`

## Key Implementation Details

✅ **Credentials Encryption**: All API keys encrypted with Laravel encryption  
✅ **Webhook Signature**: Optional signature verification for webhooks  
✅ **Status Mapping**: Carrier statuses automatically mapped to standard format  
✅ **Timestamp Tracking**: Auto-set `shipped_at` and `delivered_at`  
✅ **Transactions**: Database integrity with transactions  
✅ **Logging**: All operations logged for auditing  
✅ **Validation**: Comprehensive input validation  

---

For detailed documentation, see:
- `SHIPPING_COMPANY_GUIDE.md` - Full API reference
- `SHIPPING_ARCHITECTURE.md` - System architecture & flows
- `SHIPPING_IMPLEMENTATION_SUMMARY.md` - Implementation details
