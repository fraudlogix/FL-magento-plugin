# FL-magento-plugin


## Requirements

- Magento **2.3.x** (Open Source / Commerce) and higher 
- PHP **version supported by your Magento** (match your Magento minor release)  
- CLI access to `bin/magento`

> Tip: Production stores should run in **production mode**. Check with:
```bash
bin/magento deploy:mode:show
```

## Installation
### Manual (app/code)
1. Create the directory in root of the project:
```
app/code/FraudLogix/Core
```
2. Copy the module code into that folder (must include registration.php and etc/module.xml).
3. Continue with "Enable & Register".

## Enable & Register
Run from Magento root:
```bash
php bin/magento module:enable FraudLogix_Core
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```
Production mode (recommended for live sites):
```bash
bin/magento setup:static-content:deploy
```

## Post-Install Checks
Confirm the module is enabled:
```bash
bin/magento module:status | grep -i fraudlogix
```
Sign in to Admin and verify FraudLogix configuration appears (see below).

## Configuration
**Admin path:**
```
Stores → Configuration → FraudLogix → Core
```
- General Settings
    - Enable FraudLogix - Enable or disable the FraudLogix service.
    - API Key - API key to access FraudLogix (see [API Documentation](https://ipui.fraudlogix.com/documentation) to get it)
- Actions regarding registration, order or login events (each option here represents action for certain level of risk for the event)
    - Registration Low Level Risk
    - Registration Medium Level Risk
    - Registration High Level Risk
    - Registration Extreme Level Risk
    - Order Low Level Risk
    - Order Medium Level Risk
    - Order High Level Risk
    - Order Extreme Level Risk
    - Login Low Level Risk
    - Login Medium Level Risk
    - Login High Level Risk
    - Login Extreme Level Risk
- Logging Settings
    - Enable Logging
    - Log File Path - path of file inside Magento root ./var/log/ directory
    - Log Level
- Development Settings
    - Enable Development Mode - enable or disable sandbox mode
    - Development IP - ip that will be seen for each request during developer mode

## Updating
### Manual installs:
Replace code in ``` app/code/FraudLogix/Core ``` with the new version, then:

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```
Production mode (recommended for live sites):
```bash
bin/magento setup:static-content:deploy
```

## Uninstalling
> **Backup first.** Ensure no other modules depend on FraudLogix_Core.
### Manual:
```bash
bin/magento module:disable FraudLogix_Core
rm -rf app/code/FraudLogix/Core
bin/magento setup:upgrade
bin/magento cache:flush
Production mode (recommended for live sites):
```bash
bin/magento setup:static-content:deploy
```
```

## Troubleshooting
### Module not found / not listed
- Check path: ``` app/code/FraudLogix/Core ```
- Ensure ``` registration.php ``` and ``` etc/module.xml ``` exist and use "FraudLogix_Core". (For manual install)
- Run ``` bin/magento setup:upgrade ``` and clear caches.

### DI compile errors
```bash
rm -rf generated/* 2>/dev/null || true
bin/magento setup:di:compile
```

### Permissions/ownership
```bash
find var generated vendor pub/static pub/media app/etc -type f -exec chmod u+w {} +
find var generated vendor pub/static pub/media app/etc -type d -exec chmod u+w {} +
chmod u+x bin/magento
```

### Memory limits
use ``` php -d memory_limit=-1 ```