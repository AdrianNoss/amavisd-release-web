# amavisd-release-web
A small and simple Webinterface for the amavisd-release command

## How to install:

- copy following to your apache's html folder on your mailserver
  - file: index.php
  - folders incl. files: css include php
- rename include/config.php.example to include/config.php and edit to suit your needs
- select Captcha service (Google reCaptcha or hCapture) and add the keys in include/config.php
  - Google: create a reCaptcha api and secret key @ https://www.google.com/recaptcha
  - hCapture: create site and secret key @ https://dashboard.hcaptcha.com/
- copy the amavisd-new templates from amavis-templ to your amavisd-new template or config folder
- customize the templates and the php files for your needs (title, links etc)
- activate the templates in /etc/amavisd/amavisd.conf (example)

  `$notify_virus_admin_templ = read_text("/etc/amavisd/template-virus-admin.txt");`

  `$notify_virus_recips_templ = read_text("/etc/amavisd/template-virus-recipient.txt");`

- add the apache user to the sudoer file and allow "sudo amavisd-release ID" without password:

  - example #1

  `www-data     ALL=NOPASSWD:/usr/sbin/amavisd-release`

  - example #2 (different user and path)

  `apache 	ALL=NOPASSWD:/bin/amavisd-release`

- optional: setup regular notification of content of quarantine, more details can be found in folder: cron
- have fun!

## How to use:

If the Mail user get the amavis notification, he can click on the release link and can release the mail from quarantine after a captcha verification.

## Screenshots

![Mail from amavis](https://github.com/AdrianNoss/amavisd-release-web/blob/master/pics/mail.png?raw=true "Virus Alert with Link")
![Release Index](https://github.com/AdrianNoss/amavisd-release-web/blob/master/pics/main.png?raw=true "Release Webinterface")
![Captcha](https://github.com/AdrianNoss/amavisd-release-web/blob/master/pics/captcha.png?raw=true "reCaptcha")
![release OK](https://github.com/AdrianNoss/amavisd-release-web/blob/master/pics/release_ok.png?raw=true "Release successfull")
