# 🔄 ATMICX Complete Workflow Implementation

## Overview
This document describes the complete implementation of the ATMICX workflow system: **Client Shop & Invest → Secretary → Client Payment → Manager Verification**

---

## 📁 Key Files Created

### 1. **workflow_api.php**
Complete API handling all workflow steps with the following endpoints:

#### Client Actions:
- `client_submit_request` - Submit service request
- `client_accept_quote` - Accept quotation
- `client_submit_payment` - Submit payment with proof
- `get_pending_for_client` - View quotations

#### Secretary Actions:
- `secretary_create_quote` - Create quotation from request
- `get_pending_for_secretary` - View pending requests

#### Manager Actions:
- `manager_verify_payment` - Approve/reject payment
- `get_pending_for_manager` - View pending payments

#### Utility:
- `get_workflow_status` - Check status of any quotation

### 2. **test_workflow_complete.php**
Interactive test page to demonstrate and test the complete workflow end-to-end.

### 3. **workflow_complete.php**
Visual workflow diagram showing the 4-step process.

---

## 🔄 Workflow Steps

### Step 1: Client Shops & Submits Request

**File**: Any client interface (clientNEW.php)

**Action**: Client browses products/services and submits a request

**API Call**:
```javascript
POST workflow_api.php
action=client_submit_request
client_id=1
service_type=Installation
description=Need new washing machine
location=Main Office
```

**Response**:
```json
{
  "success": true,
  "service_id": 5,
  "message": "Service request submitted successfully",
  "next_step": "Secretary will review your request and create a quotation"
}
```

**Status**: `Pending`

---

### Step 2: Secretary Reviews & Creates Quotation

**File**: armicxSECRETARY.php

**Action**: Secretary views pending requests and creates quotation

**API Calls**:

1. View Pending:
```javascript
GET workflow_api.php?action=get_pending_for_secretary
```

2. Create Quote:
```javascript
POST workflow_api.php
action=secretary_create_quote
client_id=1
service_id=5
package=Complete Installation Package
amount=50000.00
handling_fee=500.00
delivery_method=Standard
```

**Response**:
```json
{
  "success": true,
  "quotation_id": 12,
  "reference": "QT-2026-0012",
  "message": "Quotation created successfully",
  "next_step": "Client will review and accept the quotation"
}
```

**Status**: `Pending` (waiting for client acceptance)

---

### Step 3: Client Reviews, Accepts & Pays

**File**: clientNEW.php

**Action**: Client reviews quotation, accepts it, and submits payment

**API Calls**:

1. View Quotations:
```javascript
GET workflow_api.php?action=get_pending_for_client&client_id=1
```

2. Accept Quotation:
```javascript
POST workflow_api.php
action=client_accept_quote
client_id=1
quotation_id=12
```

3. Submit Payment:
```javascript
POST workflow_api.php (multipart/form-data)
action=client_submit_payment
client_id=1
quotation_id=12
amount_paid=50500.00
payment_method=Bank Transfer
payment_proof=[FILE]
```

**Response**:
```json
{
  "success": true,
  "message": "Payment submitted successfully",
  "proof_path": "uploads/payment_proofs/proof_1_12_1736595000.jpg",
  "next_step": "Manager will verify your payment"
}
```

**Status**: `Accepted` → `Payment Submitted`

---

### Step 4: Manager Verifies Payment

**File**: atmicxMANAGER.php

**Action**: Manager reviews payment proof and verifies

**API Calls**:

1. View Pending Payments:
```javascript
GET workflow_api.php?action=get_pending_for_manager
```

2. Verify Payment:
```javascript
POST workflow_api.php
action=manager_verify_payment
quotation_id=12
action_type=approve (or reject)
remarks=Payment verified, amount matches quotation
```

**Response (Approve)**:
```json
{
  "success": true,
  "message": "Payment verified successfully",
  "next_step": "Order processing complete"
}
```

**Response (Reject)**:
```json
{
  "success": true,
  "message": "Payment rejected",
  "next_step": "Client can resubmit payment"
}
```

**Status**: `Payment Submitted` → `Verified` or `Payment Rejected`

---

## 📊 Status Flow

```
Pending → Accepted → Payment Submitted → Verified → Completed
```

### Alternative Paths:
- `Pending` → `Declined` (Client rejects quotation)
- `Payment Submitted` → `Payment Rejected` → `Payment Submitted` (Resubmission)

---

## 🧪 Testing the Workflow

### Method 1: Use Test Page
1. Open `test_workflow_complete.php` in your browser
2. Follow the 4-step process on the page
3. Fill in the forms for each step
4. See real-time results

### Method 2: Use API Directly

```bash
# Step 1: Client submits request
curl -X POST http://localhost/ATMICX-Laundry-Machine-Trading/workflow_api.php \
  -d "action=client_submit_request" \
  -d "client_id=1" \
  -d "service_type=Installation" \
  -d "description=New machine needed" \
  -d "location=Office"

# Step 2: Secretary creates quote
curl -X POST http://localhost/ATMICX-Laundry-Machine-Trading/workflow_api.php \
  -d "action=secretary_create_quote" \
  -d "client_id=1" \
  -d "package=Installation Package" \
  -d "amount=50000"

# Step 3: Client accepts and pays
curl -X POST http://localhost/ATMICX-Laundry-Machine-Trading/workflow_api.php \
  -d "action=client_accept_quote" \
  -d "client_id=1" \
  -d "quotation_id=12"

curl -X POST http://localhost/ATMICX-Laundry-Machine-Trading/workflow_api.php \
  -F "action=client_submit_payment" \
  -F "client_id=1" \
  -F "quotation_id=12" \
  -F "amount_paid=50000" \
  -F "payment_proof=@payment.jpg"

# Step 4: Manager verifies
curl -X POST http://localhost/ATMICX-Laundry-Machine-Trading/workflow_api.php \
  -d "action=manager_verify_payment" \
  -d "quotation_id=12" \
  -d "action_type=approve" \
  -d "remarks=Payment verified"
```

---

## 💾 Database Changes

### Tables Used:

1. **service** - Stores client requests
   - `Service_ID`
   - `client_id`
   - `type`
   - `description`
   - `location`
   - `status`
   - `date_requested`

2. **quotation** - Stores quotations and payment info
   - `Quotation_ID`
   - `Client_ID`
   - `User_ID` (Secretary who created)
   - `Package`
   - `Amount`
   - `Date_Issued`
   - `Status`
   - `Delivery_Method`
   - `Handling_Fee`
   - `service_request_id`
   - `proof_file`
   - `amount_paid`
   - `payment_method`
   - `verified_by`
   - `verification_date`
   - `verification_remarks`

### Required Columns (may need to add):
```sql
ALTER TABLE quotation ADD COLUMN proof_file VARCHAR(255);
ALTER TABLE quotation ADD COLUMN amount_paid DECIMAL(10,2);
ALTER TABLE quotation ADD COLUMN payment_method VARCHAR(50);
ALTER TABLE quotation ADD COLUMN verified_by INT;
ALTER TABLE quotation ADD COLUMN verification_date DATETIME;
ALTER TABLE quotation ADD COLUMN verification_remarks TEXT;
```

---

## 🔐 Security Notes

1. **Session Management**: Each role should have proper session validation
2. **File Upload**: Payment proofs are stored in `uploads/payment_proofs/`
3. **Access Control**: Each API endpoint checks user role before processing
4. **Data Validation**: All inputs are validated and sanitized

---

## 📱 Integration with Existing Pages

### clientNEW.php
- Add service request form
- Add quotation list with accept/decline buttons
- Add payment submission form with file upload

### armicxSECRETARY.php
- Add pending requests view
- Add quotation creation form
- Link service requests to quotations

### atmicxMANAGER.php
- Add pending payments view
- Add payment verification interface
- Display payment proofs for review

---

## 🎯 Next Steps

1. **Test the workflow**: Open `test_workflow_complete.php`
2. **Add database columns**: Run the ALTER TABLE statements
3. **Update existing pages**: Integrate workflow_api.php calls
4. **Add notifications**: Email/SMS alerts at each step
5. **Add audit trail**: Log all workflow actions

---

## 📞 Support

For issues or questions:
- Test page: `test_workflow_complete.php`
- API endpoint: `workflow_api.php`
- Visual diagram: `workflow_complete.php`

---

*Last Updated: January 11, 2026*
