MailChimp API
=============

Super-simple, minimum abstraction MailChimp API v2 wrapper, in PHP.

I hate complex wrappers. This lets you get from the MailChimp API docs to the code as directly as possible.

Requires PHP 5.3 and a pulse. Abstraction is for chimps.

Installation
------------

You can install the mailchimp-api using Composer. Just add the following to your composer.json:

    {
        "require": {
            "drewm/mailchimp-api": "dev-master"
        }
    }

You will then need to:
* run ``composer install`` to get these dependencies added to your vendor directory
* add the autoloader to your application with this line: ``require("vendor/autoload.php")``

Alternatively you can just download the MailChimp.php file and include it manually.

Examples
--------

List lists (lists/list method)

	<?php
	$MailChimp = new \Drewm\MailChimp('abc123abc123abc123abc123abc123-us1');
	print_r($MailChimp->call('lists/list'));

Subscribe someone to a list

	<?php
	$MailChimp = new \Drewm\MailChimp('abc123abc123abc123abc123abc123-us1');
	$result = $MailChimp->call('lists/subscribe', array(
					'id'                => 'b1234346',
					'email'             => array('email'=>'davy@example.com'),
					'merge_vars'        => array('FNAME'=>'Davy', 'LNAME'=>'Jones'),
					'double_optin'      => false,
					'update_existing'   => true,
					'replace_interests' => false,
					'send_welcome'      => false,
				));
	print_r($result);


*Note for contributors:* This is not Code Golf.
