Thoom\Giply
===========

Giply is a PHP class library that powers Giply-server. It's very much early stage, being used on several
personal/small projects, so the options are pretty rudimentary at the moment.

The class is separated from the server component so that it could be integrated with other scripts.

Class: Giply.php
----------------

This class does all of the work pulling the latest data from the Git repo. It will look for a __giply.json__ file in the
project's working directory to overwrite any of the default variables. Additionally, if a __composer.json__ file exists
in the project's working directory, the class will attempt to download (if it's not already in the working directory)
and run the composer.phar file.

### giply.json
This JSON-based configuration file can overwrite basic information such as the log name, and can include an array
of executable strings that will be run after the git repo has been updated and composer has been run. For example,
you could remove a cache directory and re-add it.

    {
        "post_exec": [
            "rm -rf cache",
            "mkdir cache",
            "chmod 777 cache"
        ]
    }

####Note
All of the post_exec scripts are run through PHP's exec command, so any php scripts would be run from the command-line and
are not **include**'d in the script itself.

References
----------

There are two blog posts that have directly inspired Giply:

 1. http://seancoates.com/blogs/deploy-on-push-from-github
 2. http://brandonsummers.name/blog/2012/02/10/using-bitbucket-for-automated-deployments