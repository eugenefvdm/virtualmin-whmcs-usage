# Script to update Virtualmin usage in WHMCS

This script is a workaround if your WHMCS and Virtualmin disk and traffic stats are out of sync.

It makes a call to the Virtualmin API as the root user with the `?program=list-domains&multiline` parameters, and loops over the output to extract usage information.

To avoid running the script repeatedly a `readfile` flag can be commented out to read the results from disk instead. This is useful for debugging as reading from a Virtualmin server can sometimes take up to 1 minute and 30 seconds.

## Reasons why you should use this script

- WHMCS's script is broken:

  - Domains' disk space and usage stats appear out of order and usage mismatched with domains
  - Many domains' usage are simply zero when in Virtualmin there is definitely usage
  
Bug `PSV-878180` has been logged with WHMCS.com but no fix released yet.

## Reasons why you shouldn't use this script

- The script uses the Virtualmin root username and password in clear text to work and that's not good if you don't have secure access to your scripts (which you should, but anyway)
- Turning off error checking is bad, but was needed in order to get around warnings due to unknown output and so that the script can continue in spite of the unknown output

## Possible future improvements

- WHMCS recommends when doing a usage update, that it be done with a mass query. This script instead loops. This will probably be fine up to a few 100 domains and depends on your server spec. On a small server it 100%.
- It would be nice to be able to specify server ID on the command line
- The flag could be combined flags and be read from the command line, and be called from like "cache"