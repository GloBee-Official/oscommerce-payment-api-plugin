# osCommerce Plugin

The osCommerce plugin that uses the GloBee Payment API.

Support for osCommerce Version 2.0 and newer.

## How to install on your website

Download the latest zip copy of the plugin from the [osCommerce Plugin Page](https://www.globee.com/oscommerce/).
- Extract the zip.
- Copy the `globee_callback.php` file and the `globee` directory to the root of the osCommerce
 website installation.
 - Copy the `includes/modules/payment/globee.php` file to the `includes/modules/payment/` directory.
 
## How to setup with GloBee

- In the osCommerce site Admin Panel, navigate to `Modules > Payment`.
- Find and install the `GloBee Cryptocurrency Payments` plugin.
- Click on `Edit` and paste your Payment API Key that you can find on your GloBee > API > Payment API page.
- Setup the other options as desired and click on `Save`.

Your users will now be able pay with Crypto using GloBee.com.

## FAQ

If you don't see GloBee as a Payment Option on the osCommerce Checkout Page, it could be because:

-  You have not enabled the GloBee payment option on the plugin settings page.
- The store currency is not supported by GloBee.

### Server requirements
PHP > 5.5 with the following PHP plugins enabled:
* OpenSSL
* GMP or BCMATH
* JSON
* CURL

## License
This software is open-sourced software licensed under the [GNU General Public Licence version 3](https://www.gnu.org/licenses/) or later