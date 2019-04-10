# Easy Digital Downloads CoinGate Plugin

Accept Bitcoin and 50+ Cryptocurrencies on your Easy Digital Downloads store.

Read the plugin installation instructions below to get started with CoinGate Cryptocurrency payment gateway on your shop. Accept Bitcoin, Litecoin, Ethereum and other coins hassle-free - and receive settlements in Cryptocurrencies or in Euros or USD to your bank.


## Install

Sign up for CoinGate account at <https://coingate.com> for production and <https://sandbox.coingate.com> for testing (sandbox) environment.

Create your API credentials in your CoinGate Dashboard (https://support.coingate.com/en/42/how-can-i-create-coingate-api-credentials).
 
Please take note, that for "Test" mode you **must** generate separate API credentials on <https://sandbox.coingate.com>. API credentials generated on <https://coingate.com> will **not** work for "Test" mode.

Also note, that *Receive Currency* parameter in your module configuration window defines the currency of your settlements from CoinGate. Set it to *BTC*, *USDT*, *EUR*, *USD* or *Do not convert*, depending on how you wish to receive payouts. To receive settlements in **Euros** or **U.S. Dollars** to your bank, you have to verify as a merchant on CoinGate (login to your CoinGate account and click *Verification*). If you set your receive currency to **Bitcoin** or **USDT**, verification is not needed.


### via WordPress FTP Uploader

1. Download [edd-coingate-payments-1.0.0.zip](https://github.com/coingate/easydigitaldownloads-plugin/releases/download/v1.0.0/edd-coingate-payments-1.0.0.zip).

2. Go to *Admin » Plugins » Add New* in admin panel.

3. Upload *edd-coingate-payments-1.0.0.zip* in *Upload Plugin*

4. Activate the plugin through the **Plugins** menu in WordPress.

5. Activate the payment gateway in your Easy Digital Downloads panel by going to *Settings » Payment Gateways* and checking the CoinGate option. Please note that you wil have to check the *Test Mode* option if you wish to use CoinGate with a sandbox account.

6. Enter you *Api Auth Token* and *Receive currency* settings in the *CoinGate Payments* tab of *Payment Gateways* and click *Save Changes*.

### via FTP

1. Download [edd-coingate-payments-1.0.0.zip](https://github.com/coingate/easydigitaldownloads-plugin/releases/download/v1.0.0/edd-coingate-payments-1.0.0.zip).

2. Unzip and upload **edd-coingate-payments/** directory to **/wp-content/plugins/** through FTP.

3. Activate the plugin through the **Plugins** menu in WordPress.

4. Activate the payment gateway in your Easy Digital Downloads panel by going to *Settings » Payment Gateways* and checking the CoinGate option. Please note that you wil have to check the *Test Mode* option if you wish to use CoinGate with a sandbox account.

5. Enter you *Api Auth Token* and *Receive currency* settings in the *CoinGate Payments* tab of *Payment Gateways* and click *Save Changes*.
