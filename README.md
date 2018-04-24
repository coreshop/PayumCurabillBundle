# CoreShop Curabill Payum Connector
This Bundle activates the Curabill PaymentGateway in CoreShop.
It requires the [dachcom-digital/payum-curabill](https://github.com/dachcom-digital/payum-curabill) repository which will be installed automatically.

## Installation

#### 1. Composer

```json
    "coreshop/payum-curabill-bundle": "~1.0.0"
```

#### 2. Activate
Enable the Bundle in Pimcore Extension Manager

#### 3. Setup
Go to Coreshop -> PaymentProvider and add a new Provider. Choose `curabill` from `type` and fill out the required fields.

## How-To

### Confirm Payment
If you have selected `Manually process Invoice after a redirect (Accept Terms Page)` in `Processing Type`
you're able to inform the gateway if your order is ready so they can process the invoice.

To confirm a payment you need to apply the `paid` transition of curabill.
After that yuo need to create a shipment and apply the `ship` transition.
In the Curabill Back-Office the Invoice gets changed to `PROCESSED`.
You'll see a success log in the order history log section.

### Cancel Payment
If you need to cancel a payment just cancel the payment itself.
You'll see a success log in the order history log section.
**Note**: This works only if a payment hasn't processed yet.

### Refund Payment
If you need to refund a payment just refund the payment itself.
You'll see a success log in the order history log section.
**Note**: This works only if a payment hasn't processed yet.

