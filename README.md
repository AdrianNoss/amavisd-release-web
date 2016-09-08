# amavisd-release-web
A small and simple Webinterface for the amavisd-release command

## How to install:

- copy everything to your http folder on your mailserver
- add the apache2 user (www-data for example) to the sudoer file and allow "sudo amavisd-release ID" without password:

  www-data      ALL=NOPASSWD:   /usr/sbin/amavisd-release

- create a google recaptcha api key etc @ https://www.google.com/recaptcha and add the key to the index.php and php/release.php
- copy the amavisd-new templates from amavis-templ to your amavisd-new template folder
- customize the templates and the php files for your needs (title, links etc)
- have fun!

## How to use:

If the Mailuser get the amavis notification, he can click on the release link and can release the mail from quarantine afte a captcha verification.
