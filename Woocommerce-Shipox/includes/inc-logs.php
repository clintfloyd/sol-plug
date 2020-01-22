<?php
/**
 * Created by Shipox.
 * User: Shipox
 * Date: 11/8/2017
 * Time: 2:41 PM
 */

class Shipox_Log
{

    public $ext = '.log';

    /**
     * API_HELPER constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $filename
     * @param $status
     * @param $date
     * @return null|string
     */
    public function check_file_name($filename, $status, $date)
    {
        if (empty($filename))
            return $status . "-" . $date . $this->ext;

        return $filename . "-" . $date . $this->ext;
    }

    /**
     * Add Custom Wing Log
     * @param $content
     * @param $status
     * @param null $filename
     * @internal param $log
     */
    public function write($content, $status = SHIPOX_LOG_STATUS::INFO, $filename = null)
    {
        $file = $this->check_file_name($filename, $status, date("Y-m-d"));
        $log_time = '[' . date('Y-m-d H:i:s') . '] - ';

        if (is_array($content) || is_object($content)) {
            error_log($log_time . print_r($content, true) . PHP_EOL, 3, trailingslashit(SHIPOX_LOGS) . $file);
        } else {
            error_log($log_time . $content . PHP_EOL, 3, trailingslashit(SHIPOX_LOGS) . $file);
        }
    }
}