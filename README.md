MailChimp API
=============

Super-simple, minimum abstraction MailChimp API v3 wrapper, in PHP.

I hate complex wrappers. This lets you get from the MailChimp API docs to the code as directly as possible.

Requires PHP 5.3 and a pulse. Abstraction is for chimps.

*API v3:* If you are using this v3 branch successfully, please let me know so I can consider making it the master branch.

Installation
------------

You can install mailchimp-api v3 dev branch using Composer:

```
composer require drewm/mailchimp-api:dev-api-v3
```

You will then need to:
* run ``composer install`` to get these dependencies added to your vendor directory
* add the autoloader to your application with this line: ``require("vendor/autoload.php")``

Alternatively you can just download the MailChimp.php file and include it manually:

```php
include('./Mailchimp.php'); 
```

Examples
--------

Start by `use`-ing the class and creating an instance with your API key

```php
use \DrewM\MailChimp\MailChimp;

$MailChimp = new MailChimp('abc123abc123abc123abc123abc123-us1')
```

Then, list all the mailing lists (with a `get` on the `lists` method)

```php
$result = $MailChimp->get('lists');

print_r($result);
```

Subscribe someone to a list (with a `post` to the `list/{listID}/members` method):

```php
$list_id = 'b1234346';

$result = $MailChimp->post("lists/$list_id/members", [
				'email_address' => 'davy@example.com',
				'status'        => 'subscribed',
			]);

print_r($result);
```

Update a list member with more information (using `patch` to update):

```php
$list_id = 'b1234346';
$subscriber_hash = $MailChimp->subscriberHash('davy@example.com');

$result = $MailChimp->patch("lists/$list_id/members/$subscriber_hash", [
				'merge_fields' => ['FNAME'=>'Davy', 'LNAME'=>'Jones'],
				'interests'    => ['2s3a384h' => true],
			]);

print_r($result);
```

Remove a list member using the `delete` method:

```php
$list_id = 'b1234346';
$subscriber_hash = $MailChimp->subscriberHash('davy@example.com');

$MailChimp->delete("lists/$list_id/members/$subscriber_hash");
```

Troubleshooting
---------------

If your server's CA root certificates are not up to date you may find that SSL verification fails and you don't get a response. The correction solution for this [is not to disable SSL verification](http://snippets.webaware.com.au/howto/stop-turning-off-curlopt_ssl_verifypeer-and-fix-your-php-config/). The solution is to update your certificates. If you can't do that, there's an option at the top of the class file. Please don't just switch it off without at least attempting to update your certs -- that's lazy and dangerous. You're not a lazy, dangerous developer are you?