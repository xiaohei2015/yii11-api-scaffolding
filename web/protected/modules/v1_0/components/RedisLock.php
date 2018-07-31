<?php

/**
 * RedisLock
 */
class RedisLock
{
	/**
	 * 获取锁
	 */
	static public function getLock($key, $timeout=8)
	{
        $waitime = 20000;
        $redis = Yii::app()->cache;

        // 不能获取锁
        while(!$is_lock = $redis->add($key, time()+$timeout)){
            usleep($waitime);
            // 判断锁是否过期
            $lock_time = $redis->get($key);
            // 锁已过期，删除锁，重新获取
            if(time()>$lock_time){
                self::releaseLock($key);
                $is_lock = $redis->add($key, time()+$timeout);
                break;
            }
        }

        return $is_lock? true : false;
	}


    /**
     * 释放锁
     */
    static public function releaseLock($key)
    {
        Yii::app()->cache->delete($key);
    }
}