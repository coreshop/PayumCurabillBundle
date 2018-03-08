# CoreShop Curabill Payum Connector
This Bundle activates the Curabill PaymentGateway in CoreShop.
It requires the [dachcom-digital/payum-curabill](https://github.com/dachcom-digital/payum-curabill) repository which will be installed automatically.

## Installation

#### 1. Composer
    ```json
    "coreshop/payum-curabill-bundle": "dev-master"
    ```
#### 2. Activate
Enable the Bundle in Pimcore Extension Manager
#### 3. Setup
Go to Coreshop -> PaymentProvider and add a new Provider. Choose `curabill` from `type` and fill out the required fields.

