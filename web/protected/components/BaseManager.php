<?php
/**
 * BaseManager
 */
class BaseManager
{
    /**
     * 用户的ip
     * @return string
     */
    public static function userIp()
    {
        $IPaddress = '127.0.0.1';
        if (isset($_SERVER)){
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
                $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $IPaddress = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $IPaddress = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $IPaddress = getenv("HTTP_CLIENT_IP");
            } else {
                $IPaddress = getenv("REMOTE_ADDR");
            }
        }

        if (strstr($IPaddress, ','))
        {
            $ips = explode(',', $IPaddress);
            $IPaddress = $ips[0];
        }

        return $IPaddress;
    }
}