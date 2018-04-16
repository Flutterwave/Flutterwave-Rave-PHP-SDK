# RavePHP

This package provides a very simple interface for integrating the Rave Payment Gateway to your PHP application. It is an extension of the [Flutterwave-Rave-PHP-SDK](https://github.com/Flutterwave/Flutterwave-Rave-PHP-SDK) package originally developed by [Femi Olanipekun](https://github.com/iolufemi).

<br/>

1. [Getting Started](#getting-started)
   - [Installation](#installation)
   - [Configuration](#configuration)
   - [Setting Up for Payment](#setting-up-for-payment)
2. [Advanced Usage](#advanced-usage)
   - [Custom Payment Fields](#custom-payment-fields)
   - [Payment Metadata](#payment-metadata)
   - [Event Handlers](#event-handlers)
3. [License](#license)

<br/>

## Getting Started

### Installation

This package can be installed as a dependency in your project using Composer.

```sh
composer require gladcodes/ravephp
```

This adds the RavePHP package to the `vendor` folder of your project root directory. You can then require the autoloader our PHP scripts to use the package as follows:

```php
<?php

/**
 * REQUIRE AUTOLOADER
 *
 * Ensure that the require path resolves correctly to:
 * {PROJECT_ROOT_DIR}/vendor/autoload.php
 *
 * [Hint]
 * You could choose to define a constant for {PROJECT_ROOT_DIR}
 * And then make it available to all your PHP scripts.
 */

require_once dirname(__DIR__).'/vendor/autoload.php';

```

<br/>

### Configuration

After the installation, you can set config variables for your PHP app. This package requires that a `.env` file exists in the root directory of your project that contains all the config variables. The following config variables are required for Rave integration.

| Variable | Description | Required |
| -------- | ----------- | -------- |
| `RAVE_APP_NAME` | The name of your app or business. | Optional. Defaults to `MY_APP_NAME` if not defined. |
| `RAVE_STAGING_PUBLIC_KEY` | Your `staging` environment public key. | Required for `staging` |
| `RAVE_STAGING_SECRET_KEY` | Your `staging` environment secret key. | Required for `staging` |
| `RAVE_LIVE_PUBLIC_KEY` | Your `live` environment public key. | Required for `live` |
| `RAVE_LIVE_SECRET_KEY` | Your `live` environment secret key. | Required for `live` |

Create a new `.env` file in your project root directory (or edit the file if it already exists) with the following content. Ensure to replace them with your own config values.

```ini
# RAVE CONFIG VARIABLES (BEGIN)

RAVE_APP_NAME='YOUR_APP_NAME'

RAVE_STAGING_PUBLIC_KEY='FLWPUBK-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-X'
RAVE_STAGING_SECRET_KEY='FLWSECK-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-X'

RAVE_LIVE_PUBLIC_KEY='FLWPUBK-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-X'
RAVE_LIVE_SECRET_KEY='FLWSECK-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx-X'

# RAVE CONFIG VARIABLES (END)
```
<br/>

> **Version Control and `.env` file**<br/>
It is adviced that you remove the `.env` file from version control since it usually contains some sensitive information like _API keys_ which should not be visible to the outside world. If you are using Git for version control, remember to add the `.env` entry to the `.gitignore` file of your project.


<br/>

### Setting Up for Payment

**Payment Form**

To make payment, you will need to setup a **payment form** and a PHP **payment script**. The payment form is expected to send a form-encoded `POST` request to the payment script when submitted. In other words, the `action` of the payment form should be the payment script. Check the [payment form example](https://github.com/gladchinda/rave-php/blob/master/example/form.php) for a sample payment form snippet. The payment form by default should contain the following fields.

| Field | Description |
| ----- | ----------- |
| `amount` | The transaction payment amount |
| `payment_method` | Payment method can be `card`, `account` or `both` |
| `description` | The transaction description |
| `logo` | Your app logo URL |
| `title` | The transaction title |
| `country` | The transaction country |
| `currency` | The transaction payment currency |
| `email` | The customer's email |
| `firstname` | The customer's firstname |
| `lastname` | The customer's lastname |
| `phonenumber` | The customer's phone number |
| `pay_button_text` | The text you want displayed on the payment button |
| `ref` | The transaction reference. **Must be unique per transaction**. By default, a unique transaction reference is generated automatically for each transaction. You can override this behaviour when initializing `Rave`. |

<br/>

**Payment Script**

The payment script is where you initialize and run `Rave` to process the payment request sent from the payment form. `Rave` fetches the payload of the payment request from the PHP `$_POST` superglobal. Below is the snippet for a basic payment script. The snippet assumes that the payment script is located at the root directory of your project.

```php
// Assuming you are in the project root directory
require_once __DIR__.'/vendor/autoload.php';

// Use the Rave class provided by the Rave namespace
use Rave\Rave;

/**
 * RAVE CONFIG
 *
 * env (string)
 * The Rave environment.
 * Default 'staging'
 *
 * autoRefs (bool)
 * Automatically generate transaction reference.
 * Default: true
 */

$config = [
  'env' => 'staging',
  'autoRefs' => false
];

/**
 * RAVE INITIALIZATION
 *
 * The Rave::init() method takes an optional $config array
 * And returns a Rave instance
 *
 * Rave cannot be instantiated using the `new` keyword.
 */

$rave = Rave::init($config)->run();
```

<br/>

## Advanced Usage

### Custom Payment Fields

You can setup `Rave` to look for custom payment field names other than the default names in your payment form. For example, say your payment form uses a `payment_amount` field for the transaction payment amount and a `payment_currency` field for the currency. You can map the custom fields to the default fields using the `fields()` method of the `Rave` instance.

```php
$fieldMappings = [
  'amount' => 'payment_amount',
  'currency' => 'payment_currency'
];

$rave = Rave::init()->fields($fieldMappings);

$rave->run();
```

> **Note:**
The custom field names must only contain _alphanumeric characters_ (0-9A-Za-z) and _underscores_ (_). Also, the field name must not start with a digit. Hence, `'2nd_address'` and `'address-2'` are invalid, but `'address_2'` is valid.

Your payment form should reflect the custom fields:

```html
<input type="hidden" name="payment_amount" value="100">

<input type="hidden" name="payment_currency" value="USD">
```

<br/>

### Payment Metadata

Sometimes you may want to send some metadata alongside the payment request to further describe the payment. You can setup `Rave` to look for metadata field names in your payment form. For example, say you want to send the `customer_ip_address` and `customer_user_agent` alongside the payment data. You can specify metadata fields using the `meta()` method of the `Rave` instance.

```php
$metaFields = [ 'customer_ip_address', 'customer_user_agent' ];

$rave = Rave::init()->meta($metaFields);

$rave->run();
```

> **Note:**
The metadata field names must only contain _alphanumeric characters_ (0-9A-Za-z) and _underscores_ (_). Also, the field name must not start with a digit. Hence, `'2nd_address'` and `'address-2'` are invalid, but `'address_2'` is valid.

Your payment form should include the metadata fields:

```html
<input type="hidden" name="customer_ip_address" value="127.0.0.1">

<input type="hidden" name="customer_user_agent" value="Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36">
```

<br/>

### Event Handlers

An instance of the `Rave\Event\BaseEventHandler` is used as the default event handler for the `Rave` instance. This event handler simply echoes text to the output for each event. You may define your own custom event handler to use instead of the default one.
Here are a few guidelines for implementing your own event handler.

1. **Must implement `Rave\Event\EventHandlerInterface`**<br/>
 Your event handler must implement the following methods of the `Rave\Event\EventHandlerInterface`:

 | Method | Description |
 | ------ | ----------- |
 | `onInit($initializationData)` | This is called when a transaction is initialized. |
 | `onSuccessful($transactionData)` | This is called only when a transaction is successful. |
 | `onFailure($transactionData)` | This is called only when a transaction failed. |
 | `onRequery($transactionReference)` | This is called when a transaction is requeried from the payment gateway. |
 | `onRequeryError($requeryResponse)` | This is called when a transaction requery returns with an error. |
 | `onCancel($transactionReference)` | This is called when a transaction is cancelled by the user. |
 | `onTimeout($transactionReference, $data)` | This is called when a transaction doesn't return with a success or a failure response. This can be a timedout transaction on the Rave server or an abandoned transaction by the customer. |
 
 <br/>

2. **Attach an instance of the event handler to the `Rave` instance using the `listener()` method**<br/>
 Create a new instance of your custom event handler and attach it to the `Rave` instance using the `listener()` method of the `Rave` instance.

 ```php
 use Rave\Rave;
 use Rave\Event\EventHandlerInterface;

 class MyEventHandler implements EventHandlerInterface
 {
    /**
     * This is called when a transaction is initialized
     * @param object $initializationData (This is the initial transaction data as passed)
     **/
    public function onInit($initializationData) {}

    /**
     * This is called only when a transaction is successful
     * @param object $transactionData (This is the transaction data as returned from the Rave payment gateway)
     **/
    public function onSuccessful($transactionData) {}

    /**
     * This is called only when a transaction failed
     * @param object $transactionData (This is the transaction data as returned from the Rave payment gateway)
     **/
    public function onFailure($transactionData) {}

    /**
     * This is called when a transaction is requeried from the payment gateway
     * @param string $transactionReference (This is the transaction reference as returned from the Rave payment gateway)
     **/
    public function onRequery($transactionReference) {}

    /**
     * This is called when a transaction requery returns with an error
     * @param string $requeryResponse (This is the error response gotten from the Rave payment gateway requery call)
     **/
    public function onRequeryError($requeryResponse) {}

    /**
     * This is called when a transaction is cancelled by the user
     * @param string $transactionReference (This is the transaction reference as returned from the Rave payment gateway)
     **/
    public function onCancel($transactionReference);

    /**
     * This is called when a transaction doesn't return with a success or a failure response.
     * @param string $transactionReference (This is the transaction reference as returned from the Rave payment gateway)
     * @param object $data (This is the data returned from the requery call)
     **/
    public function onTimeout($transactionReference,$data);
 }

 $eventHandler = new MyEventHandler;

 $rave = Rave::init()->listener($eventHandler);

 $rave->run();
 ```


<br/>

## License

This package is covered by the `MIT` license. Below is a copy of the license (please read carefully).

---

```[plain]

MIT License

Copyright (c) 2018 Glad Chinda

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

```

