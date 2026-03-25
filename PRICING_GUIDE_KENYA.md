# Pricing Guide - Kenya POS System

## 🇰🇪 Kenya's VAT (Value Added Tax) System

**Standard VAT Rate:** 16%

### Pricing Model Used in This System

All prices in your POS are entered as **VAT-INCLUSIVE** (tax already included):

```
Buying Price (cost_price)     = VAT-INCLUSIVE (16% tax already included in this amount)
Selling Price (price)         = VAT-INCLUSIVE (16% tax already included in this amount)
Tax Percentage                = 16% (Kenya standard)
```

### Example

When you enter:
| Item | Amount |
|------|--------|
| **Buying Price** | KES 5.00 |
| **Selling Price** | KES 20.00 |

This means:
- You paid supplier: KES 5.00 **(this includes 16% VAT)**
  - Base cost (excluding tax): KES 5.00 ÷ 1.16 = KES 4.31
  - Tax paid to supplier: KES 0.69

- Customer pays: KES 20.00 **(this includes 16% VAT)**
  - Base price (excluding tax): KES 20.00 ÷ 1.16 = KES 17.24
  - Tax collected: KES 2.76

- Your actual profit: KES 17.24 - KES 4.31 = **KES 12.93**

### Key Points

✅ **Prices already include VAT** - Enter the total amount customer will pay
✅ **VAT is embedded** - No need to multiply by 1.16
✅ **What you see is what customers pay** - KES 20 price = customer pays KES 20
✅ **Profit calculation** - After removing VAT from both prices

### System Behavior

- **Default tax percentage:** 16% (Kenya standard)
- **New products:** Automatically default to 16% tax
- **Existing products:** Keep their previously set tax percentage
- **At checkout:** Prices charged as-is (already include VAT)
- **Tax tracking:** System calculates tax amount = `price - (price / 1.16)`

### For Tax Compliance

- **Tax Amount per Product** = `Quantity × (Price - Price/1.16)`
- **Total Tax on Invoice** = Sum of tax for all line items
- **Invoice Total** = Sum of all product prices (VAT already included)

Example on invoice:
```
Item 1: KES 20.00 (includes KES 2.76 tax)
Item 2: KES 50.00 (includes KES 6.90 tax)
─────────────────
Total: KES 70.00 (includes KES 9.66 tax)
```

---

**Note:** This pricing model aligns with Kenya Revenue Authority (KRA) requirements. All prices you enter should be the final amount customers will pay (VAT-inclusive).

