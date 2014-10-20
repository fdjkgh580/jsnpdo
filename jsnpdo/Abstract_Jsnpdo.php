<?php
/**
 * 抽象類別, 公用區域
 * 
 */
abstract class Abstract_Jsnpdo
{
    //快取物件
    protected static $cache;

    //啟用或不使用快取
    protected static $cache_status = 0;

    //hash 起來的辨識快取鍵
    protected static $cache_key;

    //目前快取是 set 還是 get
    protected static $cache_set_get;

    //快取存放位置
    protected static $cache_path;

    protected static function cache_path($path)
    {
        self::$cache_path = $path;
    } 

    //定義快取設定
    protected static function cache_init()
    {
        if (! class_exists("phpFastCache"))
        {
            throw new Exception("若要使用快取功能，請先引用 phpFastCache");
        }

        // files 速度較快
        phpFastCache::setup("storage", "files"); //auto, files, sqlite, xcache, memcache, apc, memcached, wincache

        if (!empty(self::$cache_path))
        {
            if (!file_exists(self::$cache_path))
            {
                throw new Exception("快取的存放目錄不存在，請先建立並設定為可寫入：" . self::$cache_path);
            }

            phpFastCache::setup("path", self::$cache_path); 
        }

        self::$cache            =   phpFastCache();
    }

    /**
     * 取得快取
     * @param   $key 快取的辨識鍵
     * @return       反回快取值   
     */
    protected static function cache_get($key)
    {
        $result                         =   self::$cache->get($key);

        self::$cache_set_get            =   "get";

        return $result;
    }

    /**
     * 設定快取
     * @param   $key            要設定的鍵
     * @param   $data           設定的值
     * @param   $sec            存活秒數
     * @return                  bool
     */
    protected static function cache_set($key, $data, $sec)
    {
        self::$cache->set($key, $data, $sec);

        // $result                         =   self::$cache->set($key, $data, $sec);

        // if (!$result)                       throw new Exception("快取製作發生錯誤");

        self::$cache_set_get            =   "set";

        return true;
    }

    // 清空所有快取
    protected static function cache_clean()
    {
        self::$cache->clean();

        $st = self::$cache->stats();

        if ($st['info']['total'] == 0) return true;

        return false;
    }

    /**
     * 刪除單一快取
     * @param   $keyword  快取鍵
     */
    protected static function cache_delete($keyword)
    {
        self::$cache->delete($keyword);

        return true;
    }

    // 取得快取鍵
    protected static function cache_key_get()
    {
        return self::$cache_key;
    }


}
?>