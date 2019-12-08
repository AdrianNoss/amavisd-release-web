# amavisd-release-web
A small and simple Webinterface for the amavisd-release command

## How to install:

- copy everything to your http folder on your mailserver
- add the apache2 user (www-data for example) to the sudoer file and allow "sudo amavisd-release ID" without password:

  `www-data      ALL=NOPASSWD:   /usr/sbin/amavisd-release`

- copy file include/config.php.exampleto include/config.php and edit to suit your needs
- create a google recaptcha api key etc @ https://www.google.com/recaptcha and add the keys in include/config.php
- copy the amavisd-new templates from amavis-templ to your amavisd-new template or config folder
- customize the templates and the php files for your needs (title, links etc)
- activate the templates in /etc/amavisd/amavisd.conf (example)

  `$notify_virus_admin_templ = read_text("/etc/amavisd/template-virus-admin.txt");`

  `$notify_virus_recips_templ = read_text("/etc/amavisd/template-virus-recipient.txt");`

- have fun!

## How to use:

If the Mailuser get the amavis notification, he can click on the release link and can release the mail from quarantine after a captcha verification.

## Screenshots

![Mail from amavis](https://github.com/AdrianNoss/amavisd-release-web/blob/master/pics/mail.png?raw=true "Virus Alert with Link")
![Release Index](https://github.com/AdrianNoss/amavisd-release-web/blob/master/pics/main.png?raw=true "Release Webinterface")
![Captcha](https://github.com/AdrianNoss/amavisd-release-web/blob/master/pics/captcha.png?raw=true "reCaptcha")
![release OK](https://github.com/AdrianNoss/amavisd-release-web/blob/master/pics/release_ok.png?raw=true "Release successfull")
