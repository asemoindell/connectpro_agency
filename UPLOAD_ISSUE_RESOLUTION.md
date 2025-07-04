# Upload Issue Resolution Guide

## Problem
Getting "Invalid JSON response" error with empty response when uploading payment proof.

## Root Cause
The original upload script (`payment/upload-payment-proof.php`) is suppressing all errors with `error_reporting(0)`, which means if there's a fatal error, the script dies silently and returns no output.

## Debugging Tools Created

### 1. **Basic Response Test**
- **File:** `test-simple-response.html`
- **Purpose:** Test if PHP is working at all
- **What it does:** Just outputs "TEST RESPONSE"

### 2. **Step-by-Step Debug**
- **File:** `upload-debug-steps.html`
- **Purpose:** Test each step of the upload process
- **What it does:** Tests basic response, GET request, POST request, and file upload

### 3. **Error-Visible Upload**
- **File:** `upload-with-errors.html`
- **Purpose:** Show all PHP errors and debug output
- **What it does:** Upload with full error reporting enabled

## How to Diagnose

### Step 1: Basic Test
1. Open `test-simple-response.html`
2. Click "Test Response"
3. You should see "Response: TEST RESPONSE"

### Step 2: Step-by-Step Test
1. Open `upload-debug-steps.html`
2. Run each test in order:
   - Test 1: Basic Response
   - Test 2: GET Request  
   - Test 3: POST Request
   - Test 4: File Upload (select a small file)

### Step 3: Error-Visible Test
1. Open `upload-with-errors.html`
2. Select a test file
3. Click "Upload with Debug"
4. Check the output for any PHP errors

## Expected Results

### If Working Correctly:
- Basic response: "TEST RESPONSE"
- GET request: Shows steps with "GET request"
- POST request: Shows steps with "POST request received"
- File upload: Shows debug steps and JSON response

### If Not Working:
- Empty response = PHP fatal error
- Error messages = Configuration issue
- Permission denied = Directory permissions
- File not found = Path issues

## Common Issues and Solutions

### 1. **Empty Response**
**Cause:** PHP fatal error
**Solution:** Check error logs or use error-visible test

### 2. **"Failed to save uploaded file"**
**Cause:** Directory permissions
**Solution:** 
```bash
chmod 755 /Applications/XAMPP/xamppfiles/htdocs/Agency/uploads/payment_proofs/
```

### 3. **"Missing required data"**
**Cause:** Form not sending data correctly
**Solution:** Check HTML form has correct enctype and field names

### 4. **"Unauthorized"**
**Cause:** Session not working
**Solution:** Check if session is started and user is logged in

### 5. **"File upload error"**
**Cause:** PHP upload settings
**Solution:** Check php.ini for:
- `file_uploads = On`
- `upload_max_filesize = 10M`
- `post_max_size = 10M`

## File Format Support

The upload now supports:
- **Images:** JPG, PNG, GIF, WebP, BMP, TIFF
- **Documents:** PDF, TXT, DOC, DOCX, RTF, XLS, XLSX
- **Size limits:** 5MB for images, 10MB for documents

## Next Steps

1. **Run the debugging tools** in order
2. **Report the exact error messages** you see
3. **Check browser console** for JavaScript errors
4. **Check server error logs** if responses are empty

This will help identify the exact point of failure and provide a targeted fix.

## Files to Test (in order)
1. `test-simple-response.html` - Basic PHP test
2. `upload-debug-steps.html` - Step-by-step debugging
3. `upload-with-errors.html` - Full error reporting
4. `troubleshoot-upload.html` - Complete system diagnosis

Once we identify where the failure occurs, we can fix the original upload script or provide a working replacement.
