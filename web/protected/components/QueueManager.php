<?php

class QueueManager extends CComponent
{
    private static $redisPrefix = 'api.scaffolding.queue.';

    private static function getRedisKey($name)
    {
        return self::$redisPrefix.md5($name);
    }

    public static function push($queue_name, $data)
    {
        $list = new ARedisList(self::getRedisKey($queue_name), Yii::app()->redisQueue);
        $list->add(json_encode($data));
        return true;
    }

    public static function pop($queue_name)
    {
        $list = new ARedisList(self::getRedisKey($queue_name), Yii::app()->redisQueue);
        return $list->pop();
    }
}
?>