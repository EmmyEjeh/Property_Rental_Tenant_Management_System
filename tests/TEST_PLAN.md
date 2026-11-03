# Test plan based on Chapter Three requirements

Use synthetic/demo records only during development. Record the actual result and evidence for every test before claiming it passed.

| Test ID | Area | Test | Expected result |
|---|---|---|---|
| T01 | Authentication | Sign in with valid manager credentials | Manager session starts and dashboard opens |
| T02 | Authentication | Submit incorrect password | Sign-in fails without revealing which field was incorrect |
| T03 | Authorization | Tenant attempts to open manager-only module | Request is denied server-side |
| T04 | Tenancy | Create tenant and assign unit/lease | Tenant and tenancy data remain linked consistently |
| T05 | Payments | Record a valid payment | Payment saved and receipt number is unique |
| T06 | Payments | Record partial rent payment | Outstanding balance reflects the amount paid |
| T07 | Maintenance | Tenant submits a fault description and evidence | Request is timestamped and attached to the correct tenancy |
| T08 | Maintenance | Manager updates request status | Status and audit history are updated |
| T09 | Deposit dispute | Upload before/after evidence | Files are validated and stored outside public web root |
| T10 | Deposit dispute | AI component returns a recommendation | Recommendation and justification are saved for manager review; no automatic final decision |
| T11 | Reports | Generate occupancy/payment summaries | Totals match the underlying test records |
| T12 | Security | Submit invalid CSRF token | State-changing request is rejected |
| T13 | Security | Try SQL injection strings in inputs | Prepared statements treat values as data |
| T14 | Backup | Run database backup script | A dated SQL dump is created and can be restored in a test database |
