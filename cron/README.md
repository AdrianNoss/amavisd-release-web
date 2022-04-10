# amavisd-quarantine-notify.pl
A small and simple cron job related to amavisd-release-web

## How to install:

- store cron job into local sbin directory, e.g.

  `/usr/local/sbin/amavisd-quarantine-notify.pl`

- create whitelist file: /etc/amavisd/amavisd-release-web-recipients
  ```
  # whitelisted user
  user@want-to-be-informed.domain.example
  # whitelisted domain
  @want-to-be-informed.domain.example
  # blacklisted user
  !user@dont-care-about-rejected-spams.domain.example
  # blacklisted domain
  !@dont-care-about-rejected-spams.domain.example
  ```

  see also latest online-help:

  `/usr/local/sbin/amavisd-quarantine-notify.pl -h`

## How to test:

- run manual test (debug/trace/summmary/24 hours scope)

  `/usr/local/sbin/amavisd-quarantine-notify.pl -d -t -c -H 24`

## How to regular use:

- create cron job running every 4 hours in e.g. file /etc/cron.d/amavis

  `59 */4 * * * amavis /usr/local/sbin/amavisd-quarantine-notify.pl`
