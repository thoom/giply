<?php
/**
 * Giply
 *
 * @author Zach Peacock <zdp@thoomtech.com>
 * @copyright Copyright (c) 2012, Thoom Technologies LLC
 *
 */

namespace Thoom;

class Giply
{
    const LOG_DEBUG = 'DEBUG';
    const LOG_ERR = 'ERROR';
    const LOG_INFO = 'INFO';

    /**
     * The name of the file that will be used for logging deployments. Set to FALSE to disable logging.
     *
     * @var string
     */
    private $log = 'deployments.log';

    /**
     * Resource to the temp file where logs are stored until they are written at the end of the pull
     * @var null|resource
     */
    private $logHandle = null;

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

    /**
     * List of executable commands to be run after the repo has been updated
     *
     * @var array
     */
    private $post_exec = array();

    /**
     * Sets up defaults.
     *
     * @param  string  $directory  Directory where your website is located
     * @param  array   $options    Information about the deployment
     */
    public function __construct($directory, $options = array())
    {
        // Determine the directory path
        $this->directory = realpath($directory) . DIRECTORY_SEPARATOR;

        // Log handle to temp so that we don't get overwriting logs.
        $this->logHandle = fopen('php://temp', 'r+');

        $this->overwriteOptions($options);

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
        // Write the message into the temp log file
        // Format: time --- type: message
        fwrite($this->logHandle, date($this->date_format) . ' --- ' . $type . ': ' . $message . PHP_EOL);
    }

    /**
     * Executes the necessary commands to deploy the website.
     */
    public function pull()
    {
        try {
            // Make sure we're in the right directory
            chdir($this->directory);
            $i = 1;

            // Discard any changes to tracked files since our last deploy
            exec('git reset --hard HEAD', $output);
            $this->log('Resetting repository... ' . implode(' ', $output));

            // Update the local repository
            $output = array();
            exec('git pull ' . $this->remote . ' ' . $this->branch, $output);
            $this->log('Pulling in changes... ' . implode(' ', $output));

            $this->overwriteOptions();
            if (is_readable($this->directory . "composer.json")) {
                $output = array();

                $composer = $this->directory . "composer.phar";
                while (!file_exists($composer)) {
                    file_put_contents($composer, file_get_contents("http://getcomposer.org/installer"));
                    $this->log("Installing composer... ");
                }

                $this->log("Running composer... ");
                exec('rm -rf vendor'); //Remove the vendor directory since it continually has problems updating it
                exec("php $composer self-update", $output);
                exec("php $composer install", $output);

                $this->log("Composer output: " . implode("\n", $output));
            }

            if ($this->post_exec) {
                foreach ($this->post_exec as $post_exec) {
                    $this->log("Executing ($i): $post_exec", self::LOG_DEBUG);
                    exec($post_exec);
                    $i++;
                }
            }

            $this->log('Deployment successful.');
        } catch (\Exception $e) {
            $this->log($e, self::LOG_ERR);
        }

        if ($this->log) {
            $filename = $this->directory . $this->log;

            if (!file_exists($filename)) {
                // Create the log file
                file_put_contents($filename, '');

                // Allow anyone to write to log files
                chmod($filename, 0666);
            }

            fseek($this->logHandle, 0);
            file_put_contents($filename, stream_get_contents($this->logHandle));
            fclose($this->logHandle);
        }
    }


    private function overwriteOptions($options = array())
    {
        $json = $this->directory . "giply.json";
        if (is_readable($json)) {
            $this->log("Overwriting default options", self::LOG_DEBUG);
            $options = array_merge(json_decode(file_get_contents($json), true), $options);
        }

        $available_options = array('log', 'date_format', 'branch', 'remote', 'post_exec');

        foreach ($options as $option => $value) {
            if (in_array($option, $available_options)) {
                $this->$option = $value;
            }
        }
    }
}