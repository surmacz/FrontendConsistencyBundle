Frontend Consistency Bundle
=====================

Have you ever had problems with frontend consistency on your website, these annoying situations when frontend developer change code in one place and unintentionally broke view in another one or on another browser? If so, this bundle is for you. It provides a tool which maintains frontend consistency.

It supports different web browsers, with different configurations (i.e. all that Selenium can handle), for example:
- Firefox for Windows/Linux,
- Chrome for Windows/Linux,
- Safari for Windows/Mac,
- Mobile browsers,
- etc.

**Way of working**:
Bundle uses Selenium server(s) to generate screenshots (with simple phpunit tests) on different environments (browsers). Up-to-date screenshots must be generated locally after user makes some changes and before commit, to ensure consistency. Bundle compares new-generated screenshots with the ones saved in repository and if there's inconsistency it informs user about it. User has to decide if view changes are valid or invalid. User is aware of which view has changed.

**Warning**: to make this bundle work you have to have all the requested Selenium environments configurated. For more information visit Selenium website.

Installation
------------
Add to your Symfony composer.json the following code:

``` json
# composer.json
{
    "require": {
        "surmacz/frontend-consistency-bundle": "dev-master"
    },
    "repositories" : [{
        "type" : "vcs",
        "url" : "https://github.com/surmacz/FrontendConsistencyBundle.git"
    }]
}
```

Then update your vendors with composer update command.

To register new bundle add this line of code into your AppKernel class:

``` php
# app/AppKernel.php

new Surmacz\FrontendConsistencyBundle\FrontendConsistencyBundle()
```

It is required to install ImageMagick package on your machine. Download version specific for your system:
http://www.imagemagick.org/script/binary-releases.php
Unpack and save (and compile if needed) files on your disk ("compare" and "identify" commands will be needed).

Configuration
-------------
Prepare new configuration into your config.yml file:

``` yml
# app/config/config.yml

#Frontend Consistency Bundle Configuration
frontend_consistency:
    browser_prefix_url: 'http://localhost' #prefix url of the browser - begining of each selenium test
    screenshot_global_path: 'consistency/global' #directory in Symfony root where global screenshots will be saved
    screenshot_local_path:  'consistency/local' #directory in Symfony root where local screenshots will be saved; ignore this directory in git
    php_unit_path: 'phpunit' #command to run phpunit
    php_unit_params: '-c app/phpunit.xml' #parameters for phpunit command (optional)
    image_compare_path: '/usr/local/bin/compare' #path to compare command from ImageMagick package
    image_identify_path: '/usr/local/bin/identify' #path to identify command from ImageMagick package
    environments: #below you define selenium servers envoronments parameters
        pc_firefox: #unique environment name
            path: 'pc/firefox' #screenshot subdirectory in screenshot global and local path
            browser: '*firefox' #selenium webdriver, read selenium doc for more
            host: '127.0.0.1' #server host
            port: 4444 #server port
            timeout: 30
        pc_ie:
            path: 'pc/ie'
            browser: '*iexplore'
            host: '127.0.0.1'
            port: 4444
            timeout: 30
```
 
Change above settings according to your needs and add as many environments as you like (and have configured).

Next step is to write some selenium tests to generate screenshots. First you need to create directory "ConsistencyTests" in bundle, in which you want to have consistency tests.
Below I attach example test for standard Acme Bundle:

``` php
# src/Acme/DemoBundle/ConsistencyTests/Controller/DemoControllerTest.php

namespace Acme\DemoBundle\ConsistencyTests\Controller;

use Surmacz\FrontendConsistencyBundle\Lib\ConsistencyBase;

class DemoControllerTest extends ConsistencyBase
{
    public function testDemo()
    {
        $this->open('/demo');
        $this->finalize();
    }

    public function testHello()
    {
        $this->open('/demo/hello/Wojtek');
        $this->finalize();
    }

    public function testContact()
    {
        $this->open('/demo/contact');
        $this->finalize();
    }
}
```

Each test looks similar and need to open page on which we want take screenshot. Then you need to run "finalize()" method which generates screenshot. For more information about writing these test read here (https://phpunit.de/manual/3.7/en/selenium.html).

Usage
-----
After that you can run your first verification which generates screenshots and place them into global directory:
``` bash
php app/console consistency:verify --init
```

"init" param should be used only when there are no screenshots in global directory (i.e. at the begining of your adventure with this bundle). Later use only the following code to verify frontend consistency:
``` bash
php app/console consistency:verify
```

You can run verification in "no verbose" mode - it is recommended for continuous integration processes. It returns "0" for inconsistency or "1" for consistency:
``` bash
php app/console consistency:verify --no-verbose
```
