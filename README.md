# Script to update Virtualmin usage in WHMCS

This script is a workaround if your WHMCS and Virtualmin disk and traffic stats are out of sync.

It makes a call to the Virtualmin API as the root user with the `?program=list-domains&multiline` parameters, and loops over the output to extract usage information.

To avoid running the script repeatedly a `readfile` flag can be commented out to read the results from disk instead. This is useful for debugging as reading from a Virtualmin server can sometimes take up to 1 minute and 30 seconds.

## Installation

Copy the script into your WHMCS CRONS directory. Substitute `/home/mydomain.com/public_html` below with your prefix.

    php -q /home/mydomain.com/public_html/crons/virtualmin_usage_whmcs.php

A good interval is hourly or every 4 hours. Once a day should also be fine.

By default the script outputs the domains updated on the command line which is useful for debugging. If you want to suppress output, add ` >> /dev/null 2>&1` to the end of the CRON. 

## Reasons why you should use this script

- WHMCS's script is broken:

  - Domains' disk space and usage stats appear out of order and usage mismatched with domains
  - Many domains' usage are simply zero when in Virtualmin there is definitely usage
  
Bug `PSV-878180` has been logged with WHMCS.com but no fix released yet as of 16 July 2019.

## Notes

- WHMCS recommends when doing a usage update, that it be done with a mass query. This script loops. This should be fine up to a few 100 domains and depends on your server spec. It would be nice to do the update in one shot.
- It would be nice to be able to specify server ID on the command line, and then read the Virtualmin credentials from WHMCS instead. At present it just updates usage based on domain name, and this may be a problem if you have duplicate domains in the system. Please note suspended / cancelled domains will also be updated.
- The debug flags could be combined flags and be read from the command line, and be called something like "cache"