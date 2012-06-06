Giply
=====

Giply is a git-based deployment server/receiver written in PHP. It's very much early stage, being used on several
personal/small projects, so the options are pretty rudimentary at the moment.

The library is split into 2 parts. The Giply class that you can use in your own scripts and a simple index.php file
that can be used to manage a Giply server. Read on to learn more about both!

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

Server: index.php
-----------------

This server script is included since I use the same server to host several small projects. This script allows me to
have a single domain (i.e. deploy.myserver.com) that I can use to manage POST deployments for all of my projects on the
server.

The server expects pretty URLs, in the format: __action/projectName/securityHash__. The only action supported at the moment
is _pull_. The other options: _projectName_ is the name of the working directory (in your /var/www folder). The security
hash is a simple md5 hash of the string _/var/www/projectName_. Other hashes may be supported in future revisions, and
it is really only there to add a little bit of simple security.

So an example of a POST url for Bitbucket or Github for my server:

    http://deploy.myserver.com/pull/mysite/ff56634640221a6b2716d276361162cd

The server script is built around projects that I have on Github and Bitbucket. Both of these providers POST to the server
with a json string to the _payload_ key. The server stores the JSON string in a file: **giply_payload.json**. This provides
any of the *post_exec* scripts access to the payload data for processing.


References
----------

There are two blog posts that have directly inspired Giply:

 1. http://seancoates.com/blogs/deploy-on-push-from-github
 2. http://brandonsummers.name/blog/2012/02/10/using-bitbucket-for-automated-deployments