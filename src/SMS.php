<?php
/**
 * SMS Library
 *
 * This is a PHP library for interacting with SMS functionality using Gammu.
 *
 * @package     SMS
 * @category    Library
 * @version     1.0.0
 * @link        https://github.com/kuasarx/php-sms
 * @license     MIT License
 * 
 * -----------------------------------------------------------------------
 * 
 * Developer: Juan Camacho
 * Email: kuasarx@gmail.com
 * 
 * -----------------------------------------------------------------------
 * 
 * Usage:
 * 
 * // Create an instance of the SMS class
 * $sms = new SMS('/path/to/gammu', '/path/to/config', 'section_name');
 * 
 * // Send an SMS
 * $number = '+1234567890';
 * $message = 'Hello, World!';
 * $response = '';
 * $sms->send($number, $message, $response);
 * 
 * // Delete an SMS
 * $folder = 'inbox';
 * $start = 1;
 * $stop = 10;
 * $deleteResponse = '';
 * $sms->delete($folder, $start, $stop, $deleteResponse);
 * 
 * // Get SMS messages
 * $messages = $sms->getMessages();
 * 
 * // Get phonebook contacts
 * $contacts = $sms->getPhoneBook();
 * 
 * -----------------------------------------------------------------------
 * 
 * This library requires PHP 5.6 or above and Gammu installed and configured.
 * 
 * @package     SMS
 * @category    Library
 * @version     1.0.0
 * @link        https://github.com/php-sms/sms-library
 * @license     MIT License
 */
namespace SMS;
class SMS {
    private $datetimeFormat = 'Y-m-d H:i:s';
    private $gammuBinLocation;
    private $error;
    private $gammuConfigFile;
    private $gammuConfigSection;

    /**
     * Constructor for the SMS class.
     *
     * @param string $gammuBinLocation   The location of the Gammu binary.
     * @param string $gammuConfigFile    The path to the Gammu configuration file.
     * @param string $gammuConfigSection The section name in the Gammu configuration file.
     */
    public function __construct($gammuBinLocation = '', $gammuConfigFile = '', $gammuConfigSection = '') {
        $this->gammuBinLocation = $gammuBinLocation ?: '/gammu';

        if (!file_exists($this->gammuBinLocation)) {
            $this->error("Cannot find {$this->gammuBinLocation} or Gammu is not installed.");
        }

        $this->gammuConfigFile = $gammuConfigFile ? " -c {$gammuConfigFile}" : '';
        $this->gammuConfigSection = $gammuConfigSection ? " -s {$gammuConfigSection}" : '';
    }

    /**
     * Executes the Gammu command with the specified options.
     *
     * @param string  $options The options to pass to the Gammu command.
     * @param boolean $break   Whether to return the output as an array or a string.
     * @return mixed The output of the Gammu command.
     */
    private function gammuExec($options = '--identify', $break = false) {
        $command = "{$this->gammuBinLocation}{$this->gammuConfigFile}{$this->gammuConfigSection} {$options}";
        exec($command, $output);

        if ($break) {
            return $output;
        }

        return implode("\r\n", $output);
    }

    /**
     * Checks if the response matches an error condition.
     *
     * @param string $response The response to check for errors.
     * @return bool Whether the response matches an error condition.
     */
    private function matchError($response) {
        return preg_match("/Error opening device|No configuration file found|Gammu is not installed/i", $response);
    }

    /**
     * Identifies the device and retrieves information about it.
     *
     * @param array $response The output of the identification process.
     * @return int Returns 1 on success, 0 on failure.
     */
    public function identify(&$response) {
        $output = $this->gammuExec('--identify', true);
        $response = $this->unbreak($output);

        if ($this->matchError($response)) {
            return 0;
        }

        $this->parseIdentifyResponse($output, $response);
        $this->parseMonitorResponse($response);

        return 1;
    }

    /**
     * Parses the output of the identify command and populates the response array.
     *
     * @param array $output   The output of the identify command.
     * @param array $response The response array to populate.
     */
    private function parseIdentifyResponse($output, &$response) {
        foreach ($output as $line) {
            if (preg_match("/^(.+):(.+)/", $line, $matches)) {
                $key = str_replace(" ", "_", trim($matches[1]));
                $value = trim($matches[2]);
                $response[$key] = $value;
            }
        }
    }

    /**
     * Parses the output of the monitor command and updates the response array.
     *
     * @param array $response The response array to update.
     */
    private function parseMonitorResponse(&$response) {
        $output = $this->gammuExec('--monitor 1', true);
        $this->parseIdentifyResponse($output, $response);
    }

    /**
     * Converts an array of strings into a single string with line breaks.
     *
     * @param array $lines The array of strings to concatenate.
     * @return string The concatenated string.
     */
    private function unbreak($lines) {
        return implode("\r\n", $lines);
    }

    /**
     * Sends an SMS message.
     *
     * @param string  $number   The phone number to send the message to.
     * @param string  $text     The content of the message.
     * @param string  $response The output of the send command.
     * @return int Returns 1 on success, 0 on failure.
     */
    public function send($number, $text, &$response) {
        $command = "--sendsms TEXT {$number} -len " . strlen($text) . " -text \"{$text}\"";
        $response = $this->gammuExec($command);

        return preg_match("/OK/i", $response) ? 1 : 0;
    }

    /**
     * Deletes an SMS message.
     *
     * @param string  $folder   The folder name where the message is located.
     * @param int     $start    The start location of the message.
     * @param int     $stop     The stop location of the message.
     * @param string  $response The output of the delete command.
     * @return int Returns 1 on success, 0 on failure.
     */
    public function delete($folder, $start, $stop, &$response) {
        $command = "--deletesms {$folder} {$start} {$stop}";
        $response = $this->gammuExec($command);

        return preg_match("/Invalid/i", $response) ? 0 : 1;
    }

    /**
     * Deletes all SMS messages in a folder.
     *
     * @param string  $folder   The folder name to delete messages from.
     * @param string  $response The output of the deleteallsms command.
     * @return int Returns 1 on success, 0 on failure.
     */
    public function deleteAll($folder, &$response) {
        $command = "--deleteallsms {$folder}";
        $response = $this->gammuExec($command);

        return preg_match("/Invalid/i", $response) ? 0 : 1;
    }

    /**
     * Retrieves the SMS messages.
     *
     * @return array The array containing the SMS messages.
     */
    public function getMessages() {
        $output = $this->gammuExec('--geteachsms 1', true);
        $data = [];
        $folder = '';
        $fid = '';
        foreach ($output as $line) {
            if (preg_match("/^SMS message/", $line)) {
                continue;
            }
            if (preg_match("/^Location (.+), folder \"(.+)\"/", $line, $matches)) {
                $folder = strtolower(trim($matches[2]));
                if ($folder === "outbox") {
                    $fid = $x;
                    $x++;
                }
                if ($folder === "inbox") {
                    $fid = $y;
                    $y++;
                }
                $data[$folder][$fid] = [
                    'location' => trim($matches[1])
                ];
            } elseif (preg_match("/(.+)Concatenated \(linked\) message, ID \((.+)\) (.+), part (.+) of (.+)/", $line, $matches)) {
                $data[$folder][$fid]['link'] = [
                    'coding' => trim($matches[2]),
                    'id' => trim($matches[3]),
                    'part' => trim($matches[4])
                ];
            } elseif (preg_match("/(.+): (.+)/si", $line, $matches)) {
                if (trim($matches[1]) === 'Sent') {
                    $date = trim($matches[2]);
                    $date = explode('(', $date);
                    $date = trim($date[0]);
                    $date = str_replace('/', '-', $date);
                    $date = strtotime($date);
                    $date = date($this->datetimeFormat, $date);
                    $matches[2] = $date;
                }
                if (trim($matches[1]) && trim($matches[2])) {
                    $data[$folder][$fid][strtolower(str_replace(" ", "_", trim($matches[1])))] = trim(trim($matches[2]), '"');
                }
            } else {
                if (trim($line)) {
                    $data[$folder][$fid]['body'] .= trim($line);
                }
            }
            $data[$folder][$fid]['ID'] = md5(serialize($data[$folder][$fid]));
        }
        if (empty($data['inbox'])) {
            $data = ['inbox' => 'empty'];
        }
        return $data;
    }

    /**
     * Retrieves the contacts from the phonebook.
     *
     * @param string $mem The memory location to retrieve contacts from. Defaults to 'SM' (Sim Card).
     * @return array The array containing the contacts.
     */
    public function getPhoneBook($mem = 'SM') {
        $output = $this->gammuExec("--getallmemory {$mem}", true);
        $data = [];
        $x = 0;
        $sx = 0;
        foreach ($output as $line) {
            if (preg_match("/^Memory (.+), Location (.+)/", $line, $matches)) {
                $x = $sx;
                if (!trim($matches[1])) {
                    continue;
                }
                $data[$x]['Location'] = trim($matches[2]);
                $data[$x]['MEM'] = trim($matches[1]);
                $sx++;
            }
            if (preg_match("/(^Email.+): (.+)/si", $line, $matches)) {
                $data[$x]['email'][] = trim(trim($matches[2]), '"');
            } elseif (preg_match("/(.+): (.+)/si", $line, $matches)) {
                $data[$x][strtolower(str_replace(" ", "_", trim($matches[1])))] = trim(trim($matches[2]), '"');
            }
        }
        return $data;
    }

    /**
     * Displays an error message.
     *
     * @param string  $message The error message to display.
     * @param boolean $exit    Whether to exit the script after displaying the error.
     */
    private function error($message, $exit = false) {
        echo $message . "\n";

        if ($exit) {
            exit;
        }
    }
}
