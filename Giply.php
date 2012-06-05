<?php
/**
 * Giply
 *
 * @author Zach Peacock <zdp@thoomtech.com>
 * @copyright Copyright (c) 2012, Thoom Technologies LLC
 *
 */

class Giply
{
    const LOG_DEBUG = 'DEBUG';
    const LOG_ERR = 'ERROR';
    const LOG_INFO = 'INFO';

    /**
     * A callback function to call after the deploy has finished.
     *
     * @var callback
     */
    public $post_deploy;


    private $composer = '/usr/local/bin/composer.phar';

    /**
     * The name of the file that will be used for logging deployments. Set to
     * FALSE to disable logging.
     *
     * @var string
     */
    private $log = 'deployments.log';

    /**
     * The timestamp format used for logging.
     *
     * @link    http://www.php.net/manual/en/function.date.php
     * @var     string
     */
    private $date_format = 'Y-m-d H:i:sP';

    /**
     * The name of the branch to pull from.
     *
     * @var string
     */
    private $branch = 'master';

    /**
     * The name of the remote to pull from.
     *
     * @var string
     */
    private $remote = 'origin';

    /**
     * The directory where your website and git repository are located, can be
     * a relative or absolute path
     *
     * @var string
     */
    private $directory;


    private $exec;

    /**
     * Sets up defaults.
     *
     * @param  string  $directory  Directory where your website is located
     * @param  array   $data       Information about the deployment
     */
    public function __construct($directory, $options = array())
    {
        // Determine the directory path
        $this->directory = realpath($directory) . DIRECTORY_SEPARATOR;

        if (is_readable($this->directory . "giply.json"))
            $options = array_merge(json_decode(file_get_contents($this->directory . "Giply.json")), $options);

        $available_options = array('log', 'date_format', 'branch', 'remote', 'composer', 'exec');

        foreach ($options as $option => $value) {
            if (in_array($option, $available_options)) {
                $this->$option = $value;
            }
        }

        $this->log('Attempting deployment...');
    }

    /**
     * Writes a message to the log file.
     *
     * @param  string  $message  The message to write
     * @param  string  $type     The type of log message (e.g. INFO, DEBUG, ERROR, etc.)
     */
    public function log($message, $type = self::LOG_INFO)
    {
        if ($this->log) {
            // Set the name of the log file
            $filename = $this->directory . $this->log;

            if (!file_exists($filename)) {
                // Create the log file
                file_put_contents($filename, '');

                // Allow anyone to write to log files
                chmod($filename, 0666);
            }

            // Write the message into the log file
            // Format: time --- type: message
            file_put_contents($filename, date($this->date_format) . ' --- ' . $type . ': ' . $message . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Executes the necessary commands to deploy the website.
     */
    public function execute()
    {
        try {
            // Make sure we're in the right directory
            chdir($this->directory);

            // Discard any changes to tracked files since our last deploy
            exec('git reset --hard HEAD', $output);
            $this->log('Resetting repository... ' . implode(' ', $output));

            // Update the local repository
            exec('git pull ' . $this->remote . ' ' . $this->branch, $output);
            $this->log('Pulling in changes... ' . implode(' ', $output));

            // Secure the .git directory
            exec('chmod -R og-rx .git', $output);
            $this->log('Securing .git directory... ');

            if (is_readable($this->directory . "composer.json")) {
                exec("php $this->composer self-update", $output);
                $this->log("Running composer... ");

                if (!file_exists($this->directory . "composer.lock"))
                    exec("php $this->composer install", $output);
                else
                    exec("php $this->composer update", $output);

                $this->log("Composer output: " . implode(' ', $output));
            }

            if ($this->exec) {
                foreach ($this->exec as $exec){
                    $this->log("Executing: $exec", self::LOG_DEBUG);
                    exec($exec, $output);
                }
            }

            if (is_callable($this->post_deploy))
                call_user_func($this->post_deploy);

            $this->log('Deployment successful.');
        } catch (Exception $e) {
            $this->log($e, self::LOG_ERR);
        }
    }

}