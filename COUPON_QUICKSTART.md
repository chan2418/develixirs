# Quick Start Guide - Offers & Coupons System

## Step 1: Run Database Migration

Open your browser and navigate to:
```
http://your-domain/create_coupons_schema.php
```

You should see success messages for all 4 tables being created.

## Step 2: Access Admin Dashboard

1. Login to admin panel
2. Click on "Offers & Coupons" in the navigation menu
3. Click "Create New Coupon" to add your first coupon

## Step 3: Test on Client Side

1. Login as a regular user
2. Add products to cart
3. Go to cart page
4. Enter a coupon code in the "Have a promo code?" section
5. Click "Apply" to see the discount

## Example Coupons to Create

### Universal 10% Off
- Code: SAVE10
- Type: Percentage
- Value: 10
- Offer Type: Universal
- Valid: Today to 1 month from now

### First-Time User ₹50 Off
- Code: FIRST50
- Type: Flat Amount
- Value: 50
- Offer Type: First User Offer
- Valid: Today to 1 month from now

### Cart Value Offer
- Code: BIG500
- Type: Flat Amount
- Value: 500
- Offer Type: Cart Value Offer
- Min Purchase: 5000
- Valid: Today to 1 month from now

## Troubleshooting

**Issue**: Can't see "Offers & Coupons" menu
- **Solution**: Clear browser cache and refresh

**Issue**: Coupon not applying
- **Solution**: Check:
  - Coupon is Active
  - Current date is within validity period
  - Cart total meets minimum purchase requirement
  - User meets offer type criteria

**Issue**: Database error
- **Solution**: Make sure you ran create_coupons_schema.php first

## Support

For detailed documentation, see the walkthrough.md file.
