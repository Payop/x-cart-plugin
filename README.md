X-Cart PayOp Payment Gateway
=====================

## Brief Description

Add the ability to accept payments in X-Cart via Payop.com.

## Requirements

-  X-Cart 5.4+

## Installation
1. Download latest [release](https://github.com/Payop/x-cart-plugin/releases)
2. Log in to your X-Cart admin panel, navigate to the **My Addons** menu.
3. Click on **Upload plugin** button. 
4. Click on **Select file** and choose release archive. After installation module will be activated automatically

## Enabling payment method
1. Log in to your X-cart admin panel.
2. Open **Store setup** -> **Payment methods**
3. Click on **Add payment method** in **Online methods** section
4. Find **Payop** in the list or search by word 'payop' and press **Add**. Settings page will appear after succsessful installation.

## Module configuration
You can issue **Public key**, **JWT Token** and **Secret key** after registering as merchant on PayOp.com.
Use the following parameters to configure your PayOp project:

* **Callback/IPN URL**: https://{replace-with-your-domain}/?target=callback

## Support

* [Open an issue](https://github.com/Payop/x-cart-plugin/issues) if you are having issues with this plugin.
* [PayOp FAQ](https://payop.com/en/faq)
* [PayOp API](https://github.com/Payop/payop-api-doc)
* [Contact PayOp support](https://payop.com/en/contact-us/)
  
**TIP**: When contacting support, it will be helpful if you also provide some additional information, such as:

* X-Cart Version
* Other plugins you have installed
  * Some plugins do not play nice
* Configuration settings for the plugin (Most merchants take screenshots)
* Log files
  * X-Cart logs
  * Web server error logs
* Screenshots of error message if applicable.

## Contribute

Would you like to help with this project?  Great!  You don't have to be a developer, either.
If you've found a bug or have an idea for an improvement, please open an
[issue](https://github.com/Payop/x-cart-plugin/issues) and tell us about it.

If you *are* a developer wanting to contribute an enhancement, bugfix or other patch to this project,
please fork this repository and submit a pull request detailing your changes.  We review all PRs!

This open source project is released under the [MIT license](http://opensource.org/licenses/MIT)
which means if you would like to use this project's code in your own project you are free to do so.


## License

Please refer to the 
[LICENSE](https://github.com/Payop/x-cart-plugin/blob/master/LICENSE)
file that come with this project.