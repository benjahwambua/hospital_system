# Manual Test Plan - Hospital Management System

This document provides step-by-step testing procedures that can be performed manually in a browser.

## Pre-Test Checklist

- [ ] XAMPP is running (Apache & MySQL green)
- [ ] Application is accessible at `http://localhost/hospital_system`
- [ ] Test database `hms_db_test` is populated
- [ ] Browser console open (F12) to catch JavaScript errors
- [ ] Test user credentials ready

---

## TEST SUITE 1: Authentication & Access Control

### TEST 1.1: Valid Login

**Steps:**
1. Navigate to `http://localhost/hospital_system/auth/login.php`
2. Enter username: `admin`
3. Enter password: `admin123`
4. Click "Login"

**Expected Result:**
- ✅ Redirected to dashboard
- ✅ Session cookie set (visible in DevTools > Storage > Cookies)
- ✅ User name displayed in top-right corner

**Failure Cases:**
- ❌ Login page refreshes without error message → Check database connectivity
- ❌ "Access denied" → User role not configured correctly

---

### TEST 1.2: Invalid Credentials

**Steps:**
1. Navigate to login page
2. Enter username: `admin`
3. Enter password: `wrongpassword`
4. Click "Login"

**Expected Result:**
- ✅ Error message: "Invalid username or password"
- ✅ Page does not redirect
- ✅ Session is not created

**Failure Cases:**
- ❌ Error message missing → Check error handling in auth code
- ❌ Redirect to dashboard → Password validation broken

---

### TEST 1.3: CSRF Token Protection on Login

**Steps:**
1. Open login page
2. Open Browser DevTools (F12)
3. Go to Console tab
4. Execute:
   ```javascript
   document.querySelector('input[name="csrf_token"]').value = 'invalid_token';
   document.querySelector('form').submit();
   ```
5. Observe the result

**Expected Result:**
- ✅ Error: "Invalid security token" or similar
- ✅ Page does not process login

**Failure Cases:**
- ❌ Login succeeds with invalid token → CSRF protection broken

---

### TEST 1.4: Session Timeout & Re-authentication

**Steps:**
1. Login as admin
2. Wait 30 minutes (or modify session timeout in `includes/session.php` to 1 minute for testing)
3. Try to navigate to a protected page
4. Observe behavior

**Expected Result:**
- ✅ Redirected to login page
- ✅ Message: "Your session has expired. Please login again."

**Failure Cases:**
- ❌ Able to access protected pages → Session timeout not enforced

---

## TEST SUITE 2: Patient Registration

### TEST 2.1: Full Patient Registration

**Steps:**
1. Login as admin
2. Navigate to Patients > New Patient
3. Fill in form:
   - Full Name: `John Test Patient`
   - Gender: `Male`
   - DOB: `01/01/1990`
   - Phone: `0712345678`
   - Address: `123 Test Street`
   - Age: `34`
   - Doctor: `Dr. Sample` (if available)
4. Click "Register Patient"

**Expected Result:**
- ✅ Patient created successfully
- ✅ Patient number assigned (e.g., `PAT-00123`)
- ✅ Redirected to patient dashboard
- ✅ Patient appears in patient list

**Failure Cases:**
- ❌ Form validation errors missing → Validation logic broken
- ❌ Duplicate patient number assigned → ID generation broken
- ❌ Patient list doesn't update → Caching issue or DB error

---

### TEST 2.2: Maternity Patient Restriction

**Steps:**
1. Navigate to patient registration
2. Select Gender: `Male`
3. Select Clinical Type: `ANC` (Antenatal Care)
4. Click "Register"

**Expected Result:**
- ✅ Error message: "Maternity registrations should be recorded as Female patients"
- ✅ Form does not submit

**Failure Cases:**
- ❌ Male patient registered for maternity → Gender validation missing

---

### TEST 2.3: Walk-in Patient Registration

**Steps:**
1. Navigate to patient registration
2. Check "Walk-in Treatment"
3. Fill minimal info (Name, Gender)
4. Select Clinical Type: `General`
5. Click "Register"

**Expected Result:**
- ✅ Walk-in patient created with `WLK` patient number
- ✅ Invoice created automatically
- ✅ Consultation charge of 200 KES added (if configured)
- ✅ Patient marked as `is_walkin = 1`

**Failure Cases:**
- ❌ Walk-in patient not marked correctly → Flag not set
- ❌ No invoice created → Billing integration broken
- ❌ Duplicate consultation charges → Charge logic error

---

### TEST 2.4: Duplicate Patient Prevention

**Steps:**
1. Register a patient: `Jane Smith`, Phone: `0700000001`
2. Try to register another patient with same phone
3. Observe result

**Expected Result:**
- ✅ Either error message OR unique ID generated (system design choice)
- ✅ No duplicate patient created

**Failure Cases:**
- ❌ Duplicate patient created → No validation

---

## TEST SUITE 3: Billing & Invoicing

### TEST 3.1: Invoice Creation & Item Addition

**Steps:**
1. Register a patient: `Test Invoice Patient`
2. Navigate to patient dashboard
3. Find "Services" section
4. Add a service (e.g., "Consultation")
5. Add amount: 500 KES
6. Click "Add to Invoice"
7. Verify invoice appears

**Expected Result:**
- ✅ Service added to invoice
- ✅ Invoice total updated to 500 KES
- ✅ Service status shows "Pending"
- ✅ Invoice date recorded

**Failure Cases:**
- ❌ Invoice not created → Invoice creation logic broken
- ❌ Amount not added correctly → Calculation error

---

### TEST 3.2: Invoice Total Reconciliation

**Steps:**
1. View an invoice with multiple items
2. Note the header total
3. Open database tool (phpMyAdmin)
4. Query: `SELECT SUM(total) FROM invoice_items WHERE invoice_id = [ID]`
5. Compare with header total

**Expected Result:**
- ✅ Header total = Item total (within 0.01 KES)
- ✅ No discrepancies

**Failure Cases:**
- ❌ Totals don't match → Reconciliation failed
- ❌ Difference > 0.01 → Rounding or calculation error

---

### TEST 3.3: Payment Recording - Cash

**Steps:**
1. Go to an unpaid invoice (Outstanding = 1000 KES)
2. Click "Record Payment"
3. Select payment mode: "Cash"
4. Enter amount: 500 KES
5. Click "Receive Payment"

**Expected Result:**
- ✅ Payment recorded
- ✅ Invoice status updated to "Partial"
- ✅ Outstanding balance now 500 KES
- ✅ Payment appears in payment history with date/time

**Failure Cases:**
- ❌ Payment not recorded → Database error
- ❌ Balance not updated → Calculation broken

---

### TEST 3.4: Payment Validation - Overpayment Prevention

**Steps:**
1. Go to unpaid invoice (Outstanding = 1000 KES)
2. Click "Record Payment"
3. Enter amount: 1500 KES (more than outstanding)
4. Click "Receive Payment"

**Expected Result:**
- ✅ Error message: "Payment cannot exceed the outstanding balance"
- ✅ Payment not recorded
- ✅ Invoice balance unchanged

**Failure Cases:**
- ❌ Payment accepted → Validation missing
- ❌ Balance becomes negative → Critical bug

---

### TEST 3.5: Full Payment & Invoice Lock

**Steps:**
1. Go to invoice with 500 KES outstanding
2. Click "Record Payment"
3. Enter amount: 500 KES
4. Click "Receive Payment"
5. Try to edit the paid invoice

**Expected Result:**
- ✅ Invoice status: "Paid"
- ✅ Outstanding balance: 0 KES
- ✅ "Record Payment" button disabled or hidden
- ✅ Cannot edit paid invoice (or edit shows warning)

**Failure Cases:**
- ❌ Can add more items to paid invoice → Critical bug
- ❌ Invoice still shows as unpaid → Status update failed

---

### TEST 3.6: Refund Processing

**Steps:**
1. Go to an invoice with a payment (e.g., 500 KES paid)
2. Click payment history or "Refund"
3. Enter refund amount: 200 KES
4. Reason: "Customer request"
5. Click "Approve Refund"

**Expected Result:**
- ✅ Refund recorded
- ✅ Net paid = 300 KES (500 - 200)
- ✅ Outstanding balance recalculated
- ✅ Refund shows in payment history

**Failure Cases:**
- ❌ Refund not deducted from balance → Calculation broken
- ❌ Outstanding balance not updated → Critical financial bug

---

## TEST SUITE 4: Clinical Operations

### TEST 4.1: Service Order Creation

**Steps:**
1. Patient dashboard
2. Go to "Services" tab
3. Click "Add Service"
4. Select service: "Consultation"
5. Click "Add"

**Expected Result:**
- ✅ Service added to patient services list
- ✅ Status: "Pending"
- ✅ Service appears in invoice if billable

**Failure Cases:**
- ❌ Service not added → Database error

---

### TEST 4.2: Lab Order Duplicate Prevention

**Steps:**
1. Patient dashboard
2. Add lab service: "Blood Test"
3. Try to add same lab service again in same visit
4. Observe result

**Expected Result:**
- ✅ Either:
  - Warning message: "This test has already been ordered"
  - OR: Duplicate order created but flagged for review
- ✅ No silent duplicates

**Failure Cases:**
- ❌ Silent duplicate created → Critical quality issue

---

### TEST 4.3: Lab Request Ordering

**Steps:**
1. Patient dashboard
2. Go to "Lab" tab
3. Click "Request Lab Test"
4. Select test without clinical notes
5. Submit

**Expected Result:**
- ✅ Warning displayed: "Please provide clinical indication"
- ✅ Form still submits (optional field)

**Failure Cases:**
- ❌ No warning → Clinical guidance missing

---

### TEST 4.4: Service Order Status Transitions

**Steps:**
1. Create a service order
2. Verify initial status
3. Update status through UI (if available)
4. Check status changes

**Expected Result:**
- ✅ Status transitions: Pending → In Progress → Completed
- ✅ Cannot transition backward
- ✅ Completed orders cannot be modified

**Failure Cases:**
- ❌ Status can be changed arbitrarily → Workflow broken

---

## TEST SUITE 5: Financial Integrity Audits

### TEST 5.1: Run Invoice Reconciliation

**Steps:**
1. Open terminal/command line
2. Navigate to project directory
3. Run: `php tests/audit/billing_reconciliation_audit.php`
4. Review output

**Expected Result:**
```
✅ PASS: Invoice #123 total reconciled (3 items, 1500 KES)
✅ PASS: All payments within bounds
⚠️  WARNING: Invoice #456 has 3 partial payments (review strategy)
✅ PASS: No negative balances

Summary: 127 invoices audited, 125 pass, 2 warnings, 0 failures
```

**Failure Cases:**
- ❌ Reconciliation fails → Run detailed audit
- ❌ Negative balances detected → Critical bug

---

### TEST 5.2: Aged Receivables Report

**Steps:**
1. Navigation > Finance > Aged Receivables
2. Review aging buckets (0-30, 30-60, 60-90, 90+ days)
3. Click on an aged invoice
4. Verify balance calculation

**Expected Result:**
- ✅ Invoices grouped correctly by age
- ✅ Totals match payment calculations
- ✅ Can drill down to invoice details

**Failure Cases:**
- ❌ Incorrect age calculation → Query bug
- ❌ Totals don't match → Financial data integrity issue

---

### TEST 5.3: Payment Method Distribution

**Steps:**
1. Navigate to Accounting > Reconciliation
2. Review payment breakdown by method (Cash, M-Pesa, Other)
3. Verify totals

**Expected Result:**
- ✅ Cash payments totaled correctly
- ✅ M-Pesa payments shown
- ✅ Total = Cash + M-Pesa + Other

**Failure Cases:**
- ❌ Totals don't add up → Calculation error

---

## TEST SUITE 6: Security Validation

### TEST 6.1: SQL Injection Attempt

**Steps:**
1. Go to patient search
2. Enter in search box: `' OR '1'='1`
3. Press Enter

**Expected Result:**
- ✅ Search returns filtered results (no SQL execution)
- ✅ Error message OR empty results
- ✅ No database error shown to user

**Failure Cases:**
- ❌ SQL error displayed → Injection successful, critical vulnerability

---

### TEST 6.2: XSS Prevention

**Steps:**
1. Go to patient notes or comments field
2. Enter: `<script>alert('XSS')</script>`
3. Save
4. View the saved note

**Expected Result:**
- ✅ Script tags displayed as text (escaped)
- ✅ No alert popup
- ✅ Raw HTML visible in page source, not executed

**Failure Cases:**
- ❌ Alert popup appears → XSS vulnerability

---

### TEST 6.3: Unauthorized Module Access

**Steps:**
1. Login as `receptionist` user
2. Try to access Finance URL directly: `/hospital_system/cashier/index.php`
3. Observe result

**Expected Result:**
- ✅ Error: "Access Denied" or "Forbidden"
- ✅ Redirected to dashboard or login
- ✅ No financial data exposed

**Failure Cases:**
- ❌ Finance page loads → Access control broken, critical security issue

---

## Test Result Summary Template

**Date:** ___________  
**Tester:** ___________  
**Environment:** Windows/Mac/Linux, XAMPP Version: ___________  

| Test ID | Test Name | Status | Notes |
|---------|-----------|--------|-------|
| 1.1 | Valid Login | ✅ PASS / ❌ FAIL | ______________ |
| 1.2 | Invalid Credentials | ✅ PASS / ❌ FAIL | ______________ |
| 2.1 | Full Patient Reg | ✅ PASS / ❌ FAIL | ______________ |
| 3.1 | Invoice Creation | ✅ PASS / ❌ FAIL | ______________ |
| 3.4 | Overpayment Prevention | ✅ PASS / ❌ FAIL | ______________ |

**Critical Issues Found:** ___________  
**Recommendations:** ___________  

---

## Next Steps if Tests Fail

1. **Check error logs:**
   ```bash
   tail -f /path/to/xampp/logs/apache_error.log
   tail -f /path/to/xampp/htdocs/hospital_system/error_log
   ```

2. **Enable debug mode in config:**
   ```php
   // config/config.php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

3. **Check database:**
   ```sql
   -- phpMyAdmin console
   DESCRIBE users;
   SHOW TABLES;
   SELECT * FROM users LIMIT 1;
   ```

4. **Document failures** and create GitHub issues
