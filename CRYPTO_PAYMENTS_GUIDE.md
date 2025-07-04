# USDT and Bitcoin Payment Integration Guide

## Overview

The ConnectPro Agency system now supports USDT (Tether) and Bitcoin cryptocurrency payments in addition to traditional payment methods (Stripe, PayPal, Bank Transfer). This document explains the implementation and how to use the new features.

## Features Added

### 1. Payment Method Support
- **USDT (Tether)**: TRC20 network support
- **Bitcoin**: Main network support
- Seamless integration with existing payment workflow
- Automatic wallet address management
- Real-time payment processing simulation

### 2. Database Structure

#### Updated Tables:
- **payments**: Enhanced to support crypto payment methods
- **crypto_wallets**: New table for managing cryptocurrency addresses
- **payment_fees**: New table for configuring processing fees

#### New Views:
- **payment_method_stats**: Aggregated statistics by payment method

### 3. Admin Features

#### Payment Management Dashboard (`/admin/payments.php`)
- View all payments with filtering by method, status, date
- Payment method breakdown with statistics
- Crypto payment tracking and monitoring
- Payment status management
- Export functionality for reporting

#### Dashboard Enhancements
- Cryptocurrency payment statistics
- Revenue breakdown by payment method
- Visual indicators for crypto transactions

### 4. User Experience

#### Payment Selection
Users can choose from:
- Credit/Debit Card (Stripe)
- PayPal
- **USDT (Tether)** ⭐ NEW
- **Bitcoin** ⭐ NEW
- Bank Transfer

#### Crypto Payment Process
1. User selects USDT or Bitcoin
2. System displays wallet address and amount
3. User copies address and sends payment
4. User confirms payment in system
5. Admin can verify and approve payment

## Technical Implementation

### File Changes Made

#### 1. Payment Processing (`payment.php`)
- Added USDT and Bitcoin as selectable payment methods
- Enhanced UI with crypto-specific icons and descriptions
- Updated form handling for crypto payments

#### 2. Payment Confirmation (`process-payment.php`)
- Enhanced crypto payment interface
- Real-time wallet address fetching from database
- Crypto amount calculation (with exchange rate simulation)
- Copy-to-clipboard functionality
- Detailed payment instructions
- Security warnings and network information

#### 3. Admin Dashboard (`admin/dashboard.php`)
- Added crypto payment statistics
- Revenue breakdown including crypto payments
- Enhanced performance with optimized queries

#### 4. Admin Payments Page (`admin/payments.php`)
- Complete payment management interface
- Filtering by payment method including crypto
- Payment method statistics with icons
- Status update functionality
- Export capabilities

#### 5. Database Migration (`admin/migrate-crypto-payments.php`)
- Automated database schema updates
- Crypto wallet setup
- Payment fee configuration
- Performance indexes
- Statistical views

### Database Schema

```sql
-- Enhanced payments table
ALTER TABLE payments 
MODIFY COLUMN payment_method ENUM('stripe', 'paypal', 'usdt', 'bitcoin', 'bank_transfer');

-- New crypto wallets table
CREATE TABLE crypto_wallets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    currency ENUM('usdt', 'bitcoin') NOT NULL,
    network VARCHAR(50) DEFAULT 'mainnet',
    address VARCHAR(200) NOT NULL UNIQUE,
    label VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- New payment fees table
CREATE TABLE payment_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_method ENUM('stripe', 'paypal', 'usdt', 'bitcoin', 'bank_transfer'),
    fee_type ENUM('percentage', 'fixed', 'combined'),
    percentage_fee DECIMAL(5,4),
    fixed_fee DECIMAL(10,2),
    is_active BOOLEAN DEFAULT TRUE
);
```

## Configuration

### 1. Wallet Addresses
Update crypto wallet addresses in the `crypto_wallets` table:

```sql
-- Update Bitcoin address
UPDATE crypto_wallets 
SET address = 'your_real_bitcoin_address' 
WHERE currency = 'bitcoin';

-- Update USDT address
UPDATE crypto_wallets 
SET address = 'your_real_usdt_trc20_address' 
WHERE currency = 'usdt';
```

### 2. Processing Fees
Configure payment processing fees in the `payment_fees` table:

```sql
-- Example: Update Bitcoin fee to 1%
UPDATE payment_fees 
SET percentage_fee = 0.0100 
WHERE payment_method = 'bitcoin';
```

### 3. Exchange Rates
For production use, integrate with a cryptocurrency exchange API for real-time rates:
- CoinGecko API
- CryptoCompare API
- Binance API

## Security Considerations

### 1. Wallet Security
- Use hardware wallets for storing large amounts
- Implement multi-signature wallets for enterprise use
- Regular address rotation for privacy
- Monitor transactions for suspicious activity

### 2. Verification Process
- Manual verification recommended for large amounts
- Implement blockchain confirmation checking
- Set minimum confirmation requirements
- Use webhook notifications for payment updates

### 3. Compliance
- Check local regulations for cryptocurrency acceptance
- Implement KYC/AML procedures if required
- Maintain transaction records for tax reporting
- Consider regulatory requirements in your jurisdiction

## Testing

The system includes comprehensive testing capabilities:

```bash
# Run crypto payment tests
php admin/test-crypto-payments.php

# Run database migration
php admin/migrate-crypto-payments.php
```

## Production Deployment

### 1. Replace Demo Addresses
- Update `crypto_wallets` table with real addresses
- Test with small amounts first
- Verify network compatibility (TRC20 for USDT)

### 2. Exchange Rate Integration
```php
// Example: Integrate real-time rates
function getCryptoRate($currency) {
    $api_url = "https://api.coingecko.com/api/v3/simple/price?ids={$currency}&vs_currencies=usd";
    $response = file_get_contents($api_url);
    $data = json_decode($response, true);
    return $data[$currency]['usd'];
}
```

### 3. Webhook Integration
Consider implementing blockchain webhook services:
- BlockCypher
- Alchemy
- Moralis

## Support and Maintenance

### 1. Monitoring
- Track transaction confirmations
- Monitor wallet balances
- Set up alerts for failed payments
- Regular backup of payment data

### 2. User Support
- Provide clear payment instructions
- Include network fee information
- Offer customer support for crypto payments
- Maintain FAQ for common issues

## Troubleshooting

### Common Issues:
1. **Wrong Network**: Ensure users select correct network (TRC20 for USDT)
2. **Insufficient Fees**: Guide users on appropriate network fees
3. **Address Validation**: Implement address format validation
4. **Confirmation Delays**: Educate users about blockchain confirmation times

## Future Enhancements

### Potential Additions:
- More cryptocurrencies (Ethereum, Litecoin, etc.)
- Automatic payment detection via blockchain APIs
- QR code generation for mobile payments
- Multi-signature wallet support
- Stablecoin alternatives (USDC, DAI)
- Lightning Network support for Bitcoin

---

## Summary

The USDT and Bitcoin payment integration provides:
✅ Full cryptocurrency payment support
✅ Comprehensive admin management
✅ Secure wallet address management
✅ Detailed payment statistics
✅ User-friendly payment interface
✅ Production-ready architecture

The system is now ready to accept cryptocurrency payments alongside traditional payment methods, providing users with more payment flexibility while maintaining security and administrative control.
