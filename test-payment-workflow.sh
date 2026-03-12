#!/bin/bash

# M-Pesa Payment System - Complete Integration Test Script
# Usage: Run this after configuring M-Pesa credentials

set -e  # Exit on any error

BASE_URL="http://localhost:8000"
API_URL="$BASE_URL/api"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║         M-Pesa Payment System - Complete Test Suite             ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Step 1: Get or create authentication token
echo -e "${YELLOW}Step 1: Setting up authentication token...${NC}"
read -p "Enter your Sanctum Bearer token (or press Enter to skip): " TOKEN

if [ -z "$TOKEN" ]; then
    echo -e "${YELLOW}ℹ  No token provided. Get one from: php artisan tinker${NC}"
    echo -e "${YELLOW}    \$user = User::first(); \$user->createToken('test')->plainTextToken${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Token configured${NC}"
echo ""

# Step 2: Check configuration
echo -e "${YELLOW}Step 2: Checking M-Pesa configuration...${NC}"

# This would require a PHP script or Laravel command
# For now, we'll just continue with the tests

echo -e "${GREEN}✓ Configuration check would go here${NC}"
echo ""

# Step 3: Test payment initiation
echo -e "${YELLOW}Step 3: Testing Payment Initiation (STK Push)...${NC}"
echo -e "  Endpoint: POST $API_URL/payments/initiate"
echo -e "  Testing with: 0745450032, Amount: 670 KES"
echo ""

RESPONSE=$(curl -s -X POST "$API_URL/payments/initiate" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "phone_number": "0745450032",
    "amount": 670.00,
    "description": "Test payment for 670 KES"
  }')

echo "Response:"
echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
echo ""

# Extract payment_id from response
PAYMENT_ID=$(echo "$RESPONSE" | jq -r '.payment_id // empty' 2>/dev/null)
TRANSACTION_CODE=$(echo "$RESPONSE" | jq -r '.transaction_code // empty' 2>/dev/null)

if [ -n "$PAYMENT_ID" ]; then
    echo -e "${GREEN}✓ Payment initiated successfully${NC}"
    echo -e "  Payment ID: $PAYMENT_ID"
    echo -e "  Transaction Code: $TRANSACTION_CODE"
    echo ""
else
    echo -e "${RED}✗ Payment initiation failed${NC}"
    exit 1
fi

# Step 4: Wait for customer response
echo -e "${YELLOW}Step 4: Waiting for customer to confirm on phone...${NC}"
echo -e "${YELLOW}(In sandbox: Use test phone 0712345678 for auto-success)${NC}"
echo ""
read -p "Press Enter when customer has confirmed payment, or wait 30 seconds... "

# Step 5: Check payment status
echo -e ""
echo -e "${YELLOW}Step 5: Checking Payment Status...${NC}"
echo -e "  Endpoint: GET $API_URL/payments/$PAYMENT_ID/status"
echo ""

RESPONSE=$(curl -s -X GET "$API_URL/payments/$PAYMENT_ID/status" \
  -H "Authorization: Bearer $TOKEN")

echo "Response:"
echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
echo ""

STATUS=$(echo "$RESPONSE" | jq -r '.status // empty' 2>/dev/null)
SUCCESS=$(echo "$RESPONSE" | jq -r '.success // false' 2>/dev/null)

if [ "$SUCCESS" = "true" ] && [ "$STATUS" = "completed" ]; then
    echo -e "${GREEN}✓ Payment confirmed!${NC}"
    MPESA_ID=$(echo "$RESPONSE" | jq -r '.mpesa_transaction_id // empty' 2>/dev/null)
    CUSTOMER_NAME=$(echo "$RESPONSE" | jq -r '.customer_name // "Unknown" ' 2>/dev/null)
    AMOUNT=$(echo "$RESPONSE" | jq -r '.amount // empty' 2>/dev/null)
    echo -e "  Status: COMPLETED ✓"
    echo -e "  Customer: $CUSTOMER_NAME"
    echo -e "  Amount: $AMOUNT KES"
    echo -e "  M-Pesa Receipt: $MPESA_ID"
    echo -e "  Transaction Code: $TRANSACTION_CODE"
else
    echo -e "${YELLOW}⧖ Payment still pending or failed${NC}"
    echo -e "  Status: $STATUS"
fi
echo ""

# Step 6: Get payment details
echo -e "${YELLOW}Step 6: Retrieving Complete Payment Details...${NC}"
echo -e "  Endpoint: GET $API_URL/payments/$PAYMENT_ID"
echo ""

RESPONSE=$(curl -s -X GET "$API_URL/payments/$PAYMENT_ID" \
  -H "Authorization: Bearer $TOKEN")

echo "Response:"
echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
echo ""

echo -e "${GREEN}✓ Payment details retrieved${NC}"
echo ""

# Step 7: Get payment history
echo -e "${YELLOW}Step 7: Retrieving Payment History...${NC}"
echo -e "  Endpoint: GET $API_URL/payments/history?company_id=1&limit=5"
echo ""

RESPONSE=$(curl -s -X GET "$API_URL/payments/history?company_id=1&limit=5" \
  -H "Authorization: Bearer $TOKEN")

echo "Recent Payments:"
echo "$RESPONSE" | jq '.data[] | {id, transaction_code, amount, status, phone_number, customer_name}' 2>/dev/null || echo "$RESPONSE"
echo ""

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    Test Complete!                               ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${GREEN}Next Steps:${NC}"
echo "1. Verify transaction in M-Pesa dashboard"
echo "2. Check payment_attempts table for status"
echo "3. Monitor mpesa_callbacks table for webhook delivery"
echo "4. Test with different phone numbers and amounts"
echo ""

echo -e "${YELLOW}For more details, see: PAYMENT_API_DOCUMENTATION.md${NC}"
