
# CF7 to HubSpot Addon 📨

This plugin integrates HubSpot forms with Contact Form 7 forms. It's a fork from this [plugin](https://wordpress.org/plugins/cf7-hubspot-forms-add-on-for-contact-form-7/)



## Installation

Download and copy folder 'cf7-to-hubspot-add-on' in  'wp-content/plugins'

    
## Usage

Once installed and activated, each CF7 form will have a separate settings section where you can add HubSpot form credentials. i.e, Portal ID, Form ID, Form Fields, etc.

Once the plugin is activated you will have the option to enable/disable the form integration. In enabled mode, the status code and response from HubSpot will be stored for the last form submission so you can debug if there are any issues.

Note: This plugin uses the PHP library cURL. Please make sure that the cURL library is enabled on your server.
## FAQ

#### What details do I need to start using CF7 form with HubSpot Forms?

The mandatory fields for the integration to work are **Portal ID**, **Form ID** and the **Field Names**.

#### Where can I find those details?

Once you are in your HubSpot dashboard, navigate to Contacts > Forms. Once there, either select an existing form or create a new one and navigate to the Embed section where you can find the portalid and formid. There is a screenshot in the CF7 \"HubSpot Form Integration\" section which displays a sample form settings.

## Support 

For support, email diego@devacid.com 


## License

[![GPLv3 License](https://img.shields.io/badge/License-GPL%20v3-yellow.svg)](https://opensource.org/licenses/)
