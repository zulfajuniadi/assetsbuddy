Assets Buddy
============

Assets Buddy is an easy assets management class for PHP that enables developers to better manage their static client-side files such as Javascript, CSS and templates.

Features
--------
* Minifies and merges Javascript, CSS and Templates and places them into public-facing folders.
* Caches the merged and minified files for faster performance.
* Dev mode available where ?[random string] will be appended to the file names forcing the browser to fetch a new version every time.
* Automatically detects changed files and regenerates them.
* Automatically detects changes to the class configurations and regenerates the files. 

Installation / Usage
--------------------

1. Install composer https://github.com/composer/composer
2. Create a composer.json inside your application folder:

    ``` json
    {
        "require": {
            "zulfajuniadi/assetsbuddy": "dev-master"
        }
    }
    ```
3. Run the following code

    ``` sh
    $ php composer.phar install
    ```

Examples / Usage
----------------
For usage examples, please view index.php inside the test folder.
