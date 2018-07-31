<?php

/**
 * 封装实用公共函数
 */

class Helper
{
    public static function get_basename($filename){
        return preg_replace('/^.+[\\\\\\/]/', '', $filename);
    }

    /**
     * 处理下载乱码
     * @param $filename
     */
    public static function getDownloadFilename($filename){
        $ua = $_SERVER["HTTP_USER_AGENT"];
        $encoded_filename = urlencode($filename);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);
        return '="' . $encoded_filename . '"';
        /*if (preg_match("/MSIE/", $ua)) {
            return '="' . $encoded_filename . '"';
        } else if (preg_match("/Firefox/", $ua)) {
            return '*="utf8\'\'' . $filename . '"';
        } else {
            return '="' . $filename . '"';
        }*/
    }
}

?>