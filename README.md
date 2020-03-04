# WHMCS PayPal Billing Agreements

### Features
- Automatically charge users the correct invoice amounts, eliminating accidental overpayments or missed/late payments
- Only attempts to automatically charge invoices with the payment method as "PayPal Billing Agreements"
- Customers can make one-time payments from the client area without being redirected to PayPal and back (after successfully setting up a billing agreement)
- Honors the "Process Days Before Due" and "Retry Every Week For" automation setting for when to charge a customer's PayPal account
- Honors the "Auto CC Processing" setting for individual customers
- Displays whether a customer has an active billing agreement or not in the admin area

### Prerequisites
For this addon to work correctly, you **must** have PayPal reference transactions enabled on your PayPal account. You will need to contact PayPal and request that this feature be enabled for your account. Usually you'll need to go through an approval process, which may include a personal credit pull.

### Installation
1. Rename the directory `/templates/six` to match the name of your current client area template
2. Upload all files to your WHMCS root directory
3. Run the following SQL statement to create the billing agreement table in your WHMCS database:
```sql
CREATE TABLE `paypal_billingagreement` (
    `id` varchar(32) NOT NULL,
    `client_id` int(10) NOT NULL,
    `status` varchar(32) NOT NULL,
    `created_at` int(10) NOT NULL,
    PRIMARY KEY (`id`),
);
```
4. Activate the module in WHMCS Admin => Payment Gateways
5. Enter your PayPal API Username, Password, and Signature in WHMCS Admin => Payment Gateways => PayPal Billing Agreement
6. *(Optional)* Set your IPN URL to `https://your.site/modules/gateways/callback/paypalbilling.php`. If you don't setup your IPN URL, consider enabling "Enable Cron Status Check" under the payment gateways module options. *(Warning: Running the cron status check with many active billing agreements may significantly extend the runtime of the cron job)*
7. Run a cron job at 11:00 PM every night:
`0 7 * * * php -q /path/to/whmcs/modules/gateways/paypalbilling/cron.php`

### Limitations
- Currently designed to make auto-payments on a separate cron job. It's best to run this cron job at a separate time from your normal WHMCS daily cron to avoid conflicts.
- Does not support e-checks (or rather, the addon will mark the invoice as PAID instantly regardless of whether the e-check clears). Recommended to disable accepting e-checks on your PayPal account to avoid this.
- Does not currently email a user when a PayPal billing agreement charge attempt fails
- Does not have support for shipping addresses when submitting a payment, making you ineligible for PayPal seller protection
- No auto-installation
- No ability to manage a customer's billing agreement settings through the admin area
