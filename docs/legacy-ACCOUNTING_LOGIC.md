# Accounting Logic — Net Profit Formula
## Rev 3 — CC transaction fee deducted from Provider Profit only

---

## 1. Core Formula

```
Net Profit (Provider) = Job Total − Material Cost (company-owned) − Lead Fee − Contractor Share − CC Fee
```

```
net_profit = job_total - material_cost - lead_fee - contractor_share - cc_fee
```

`cc_fee` applies only to Provider Profit. Contractor Share and Lead Fee are calculated on the full `job_total`.

---

## 2. Variable Definitions

| Variable | Source | Description |
|----------|--------|-------------|
| `job_total` | `jobs.job_total` | Manual price entered at job creation |
| `material_cost` | `SUM(job_materials.line_total) WHERE ownership = 'company'` | Cost of parts paid by the owner |
| `lead_fee` | Derived from `lead_sources` | Fee owed to the channel that sent the job |
| `contractor_share` | Derived from `contractors` | Amount owed to the subcontractor |
| `gross_profit` | `job_total - material_cost` | Revenue after parts cost |
| `cc_fee` | `IF payment_method = 'credit_card' THEN job_total * (system_meta.cc_fee_percentage / 100) ELSE 0` | Processing fee absorbed by Provider |
| `net_profit` | `gross_profit - lead_fee - contractor_share - cc_fee` | Owner take-home |

---

## 3. Component Calculations

### 3.1 Material Cost
`ownership = 'company'` → deducted from owner's profit (owner paid for the parts).
`ownership = 'contractor'` → NOT deducted (contractor paid; no impact on owner's profit).

```
material_cost = SUM(line_total)  WHERE ownership = 'company'
line_total    = qty * unit_cost  (computed and stored on each row at save time)
```

### 3.2 Lead Fee

```
IF lead_source assigned:
    IF fee_type = 'percent':  lead_fee = job_total * fee_value
    IF fee_type = 'flat':     lead_fee = fee_value

IF no lead source assigned:   lead_fee = 0.00
```

`fee_value` storage convention:
- Percent: stored as decimal — 15% → `0.1500`
- Flat: stored as dollar amount — $25 → `25.0000`

### 3.3 Contractor Share

```
IF contractor assigned:
    IF split_type = 'percent':  contractor_share = job_total * split_value
    IF split_type = 'flat':     contractor_share = split_value

IF no contractor assigned:      contractor_share = 0.00
```

`split_value` storage convention:
- Percent: stored as decimal — 40% → `0.4000`
- Flat: stored as dollar amount — $200 → `200.0000`

---

## 4. Full Calculation Sequence

```
Step 1:  material_cost    = SUM(jm.line_total)  WHERE jm.job_id = :id AND jm.ownership = 'company'
Step 2:  gross_profit     = job_total - material_cost
Step 3:  lead_fee         = resolveLeadFee(ls.fee_type, ls.fee_value, job_total)
Step 4:  contractor_share = resolveContractorShare(c.split_type, c.split_value, job_total)
Step 5:  cc_fee           = IF payment_method = 'credit_card' THEN job_total * (cc_fee_percentage / 100) ELSE 0
Step 6:  net_profit       = gross_profit - lead_fee - contractor_share - cc_fee
```

---

## 5. Worked Example

| Input | Value |
|-------|-------|
| Job Total (manual) | $1,000.00 |
| Company Materials | $120.00 (4 parts × $30.00) |
| Contractor Materials | $80.00 — NOT deducted from owner |
| Lead Source | Angi — 15% percent |
| Contractor | John D. — 40% split |

```
material_cost    = $120.00
gross_profit     = $1,000.00 − $120.00              = $880.00
lead_fee         = $1,000.00 × 0.15                 = $150.00
contractor_share = $1,000.00 × 0.40                 = $400.00
cc_fee           = $0.00  (cash payment)
net_profit       = $880.00 − $150.00 − $400.00 − $0.00 = $330.00
```

Owner nets **$330.00** (cash). With a 3% CC fee on credit card: `cc_fee = $30.00`, `net_profit = $300.00`.

---

## 6. Edge Cases

| Scenario | Behavior |
|----------|----------|
| No lead source | `lead_fee = 0.00` |
| No contractor (owner-operated job) | `contractor_share = 0.00` |
| All materials are contractor-owned | `material_cost = 0.00` |
| No materials logged | `material_cost = 0.00` |
| Net profit is negative | Stored as negative — flags a loss on that job |
| Partial payment | No effect on profit — profit is calculated on `job_total` (billed), not amount collected |
| Job total corrected after completion | Recalculate and overwrite `job_financials` row via `ON DUPLICATE KEY UPDATE` |

---

## 7. Snapshot Storage (job_financials)

Written once when job status transitions to `completed`. Overwrites if recalculated.

```sql
INSERT INTO job_financials
    (job_id, job_total, material_cost, lead_fee, contractor_share, gross_profit, net_profit, cc_fee)
VALUES
    (:job_id, :job_total, :material_cost, :lead_fee, :contractor_share, :gross_profit, :net_profit, :cc_fee)
ON DUPLICATE KEY UPDATE
    job_total        = VALUES(job_total),
    material_cost    = VALUES(material_cost),
    lead_fee         = VALUES(lead_fee),
    contractor_share = VALUES(contractor_share),
    gross_profit     = VALUES(gross_profit),
    net_profit       = VALUES(net_profit),
    cc_fee           = VALUES(cc_fee),
    calculated_at    = CURRENT_TIMESTAMP;
```

---

## 8. Reporting Aggregates

Weekly net profit (Dashboard widget):
```sql
SELECT SUM(jf.net_profit)
FROM   job_financials jf
JOIN   jobs j ON j.job_id = jf.job_id
WHERE  j.completed_at >= DATE(NOW()) - INTERVAL WEEKDAY(NOW()) DAY
  AND  j.completed_at <  DATE(NOW()) - INTERVAL WEEKDAY(NOW()) DAY + INTERVAL 7 DAY;
```

Pending payment total (Dashboard widget):
```sql
SELECT SUM(job_total - amount_paid)
FROM   jobs
WHERE  payment_status IN ('unpaid', 'partial')
  AND  status != 'cancelled';
```
