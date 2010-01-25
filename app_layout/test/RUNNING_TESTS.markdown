You can execute tests by simply running the php file:

    php test/akelos/active_support/cases/inflector.php

or by using ./makelos ("php makelos" on Windows)

    ./makelos test:case active_support/inflector

The advantage of using makelos is that you can run multiple
cases at once providing the suite name like:

    ./makelos test:case active_support


This will runn all the cases available at

    test/appname/active_support/cases/*.php

Using makelos you can also override default constants like

    ./makelos test:case active_support AK_ENVIRONMENT=development

and even define your custom test reporter

    ./makelos test:case active_support reporter=JUnitXMLReporter


## Testing models

By default Akelos will use the details under the testing section of your
config/database.yml file. You might use another database configuration
file by passing the db option to "makelos tests"

    ./makelos test:case active_record db=sqlite

will look for the database settings defined in config/sqlite.yml


## Testing controllers with Apache2

When testing controllers, Akelos will use the default host
"akelos.tests", you can override this by defining AK_TESTING_URL in
your config/environment.php file.

You will have to add the line:

    127.0.0.1   akelos.tests

To your hosts file wich might be located at:

*Windows*   C:\WINDOWS\drivers\etc\hosts
*MacOS*     /private/etc/hosts
*Linux*     /etc/hosts

Then you'll have to add a virtual host in your apache config:

    NameVirtualHost *:80

    <VirtualHost *:80>
        DocumentRoot "path/to/akelos/test"
        ServerName akelos.tests
        ServerAlias akelos.tests
    </VirtualHost>

Using Apache the testing folder is can only be accessed from
the locahost machine as defined in test/.httaccess


## Running akelos core tests

You can Run Akelos core tests on a fresh copy by calling

    php makelos tests:units

This will use sqlite are