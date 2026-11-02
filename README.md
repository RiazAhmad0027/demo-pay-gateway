# Demo Pay Gateway for WooCommerce (practice project)

A custom WooCommerce payment gateway that shows the full flow most payment providers use:

1. The customer chooses "Pay by card (Demo Pay)" and places the order
2. The order is created as **Pending payment** and the customer is sent to a hosted payment page
3. The provider sends an **HMAC-SHA256 signed webhook** to `/wp-json/demo-pay/v1/webhook`
4. The plugin checks the signature and sets the order to **Processing** (paid) or **Failed**

## Features
- Settings: title, description, merchant ID, API key, webhook secret, test mode
- Signed webhook: messages with a wrong signature are refused (HTTP 401)
- Duplicate webhooks are ignored (an order is never processed twice)
- Works in both the **classic checkout** and the new **Block checkout**
- Declares compatibility with WooCommerce **HPOS** (new order storage)

## Note
"Demo Pay" is a fake provider for practice. Its payment page only works in test mode.
For a real provider (for example TribePay), the fake provider is replaced by the provider's API.
**Do not use this plugin on a live store.**