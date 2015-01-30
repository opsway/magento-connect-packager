# magento-connect-packager
Automatically pack magento extension code to archive for Magento Connect 2.0

## Notes
Based on https://github.com/astorm/MagentoTarToConnect tool

## Usage

Firstly, put in your repo extension config file with Magento Connect required fields information.
Example config here: https://github.com/opsway/magento-connect-packager/blob/master/example-config.php

For pack exstension:
 - Clone your extension repository
```bash
git clone [REPO_URL]
cd [REPO_FOLDER]
```
 - Download and put packager script
```bash
wget https://raw.githubusercontent.com/opsway/magento-connect-packager/master/packager.php
```
 - Run packager script with config name file
```
php packager.php example-config.php
```
- Take created archive extension and upload to Magento Connect. Enjoy!
