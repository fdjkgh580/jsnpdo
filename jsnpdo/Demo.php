<?php
// 
// 請把三個主要元件 jsnpdo、jsnao、phpfastcache
// 依序放進你的主機裡的不同路徑。
// 此處路徑僅作參考
// 
define("_BASEPATH", "C:/xampp/htdocs/www/CI_jsn/");
include_once("jsnpdo.php"); 
include_once(_BASEPATH . "application/libraries/jsnclass/jsnao/jsnao.php");
include_once(_BASEPATH . "plugin/PHP/phpfastcache_v2.1_release/phpfastcache.php");
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

try 
{




    
    echo "<h1>打造工廠來產生模型</h1>";

    //指定別名
        Jsnpdo_factory::map(array
        (
            "jsntable_2"              =>  "jsntable_second"
        ));

    //若想建立實體工廠的方法，須設定建立好的工廠要存在哪張php？
        Jsnpdo_factory::path("db_model.php");

    //開始建構工廠, 讓你直接使用資料表名稱當作物件
        Jsnpdo_factory::build_virtual_physical("virtual"); //虛擬 virtual 或 實體 physical (不建議使用，日後將會刪除)

    //指定存放 cache 路徑。可以不指定，將自動存入伺服器暫存路徑。
        // Jsnpdo_factory::cache_path(".");

    //連接資料庫 (若確定只有一筆可以省略 db_house() )
        Jsnpdo_factory::connect("mysql", "localhost", "ci_jsn", "root", "");

        // // 若要使用資料庫切換, 並可選用 db_resp() 存到一個自訂資料庫名稱
        //     Jsnpdo_factory::connect("mysql", "localhost", "ci_jsn", "root", "")->db_house("DB");
        //     Jsnpdo_factory::connect("mysql", "localhost", "sport", "root", "")->db_house("DB2");

        //     // 先 DB     操作
        //     Jsnpdo_factory::switch_db("DB");
        //     article::sel("limit 1");

        //     // 切換到 DB2
        //     Jsnpdo_factory::switch_db("DB2");
        //     article::sel("limit 1");

        //     //還原。在切換到 DB
        //     Jsnpdo_factory::switch_db("DB"); 




    // 建立資料表並產生模型
        $sql = "CREATE TABLE IF NOT EXISTS `jsntable` (
                  `id` int(10) NOT NULL auto_increment,
                  `title` varchar(500) NOT NULL,
                  `content` varchar(500) NOT NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        $result = Jsnpdo_factory::create($sql);
        if ($result->queryString == $sql)
            echo "資料表建立成功，工廠模型完成 <br>";
        else
            throw new Exception("資料表建立發生錯誤");


    //簡化測試資料表建立與刪除, 
    //檢查是否自動生成工廠中的 class 名稱, 並使用別名操作
        $sql_create = "CREATE TABLE IF NOT EXISTS `jsntable_2` (
                  `id` int(10) NOT NULL auto_increment,
                  `title` varchar(500) NOT NULL,
                  `content` varchar(500) NOT NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        Jsnpdo_factory::create($sql_create);

        //假使別名操作
        jsntable_second::ary("title", "標題", true); //第三個參數用來判斷是否使用'' 預設true 可省略不寫。
        jsntable_second::ary("content", " now() ", false); // 使用 MySQL 內建函數時
        jsntable_second::iary("POST");

        $sql_drop = "drop table `jsntable_2`";
        Jsnpdo_factory::drop($sql_drop);



    //新增 長名 insert()
    //資料表名稱::欄位 = 值;
        // jsntable::debug("str"); //可由外部指定debug方式
        jsntable::ary("title", "標題");
        $result = jsntable::iary();
        if ($result > 0) echo "新增{$result}筆成功 <br>";
        else throw new Exception("修改發生錯誤");

        $_POST['title'] = "經由POST的標題";
        jsntable::ary("title", NULL);
        $result = jsntable::iary("POST"); //若要debug 可指定第二個參數如 iary("POST", 1);
        if ($result > 0) echo "新增{$result}筆成功 <br>";
        else throw new Exception("新增發生錯誤");
    
    
    // 多筆執行
        unset($ary);
        jsntable::get_string(1);
        jsntable::ary("title", "多筆新增_1");
        $with[] = jsntable::iary();
        jsntable::ary("title", "多筆新增_2");
        $with[] = jsntable::iary();
        jsntable::get_string(0);
        $result = jsntable::with($with);
        if ($result->queryString == jsntable::sql())
            echo "多筆執行成功 <br>";
        else
            throw new Exception("多筆執行發生錯誤");
        unset($with);


    //修改 長名 update()
        unset($_POST);
        $_POST['content'] = "由POST給予, 目前是修改動作" . time();
        
        jsntable::ary("title", "自動對應標題, 目前是修改動作");
        jsntable::ary("content", NULL);
        jsntable::_id(1);
        $result = jsntable::uary("where id = :id", "POST");
        if ($result > 0) echo "修改{$result}筆資料 <br>";
        else throw new Exception("修改發生錯誤");

    //查詢 長名 select()
        jsntable::_id(2);
        $DataList     = jsntable::sel("where id <= 1"); //可以直接寫條件 + 選用 debug
        $DataList2    = jsntable::sel("id as my_id", "where id = :id"); //或顯示欄位 + 條件 + 選用debug
        $select_num   = jsntable::select_num();
        if (count($DataList) == count($DataList2) and count($DataList2) == $select_num and $select_num == 1)
            echo "查詢通過 <br>";
        else 
            throw new Exception("查詢發生錯誤");

        // where in ...
        $place_holder = jsntable::in(array(1, 2));
        jsntable::sel("id, title", "where id in ($place_holder)");


    //查詢單筆 長名 select_one()
        jsntable::_id(2);
        $DataInfo   = jsntable::selone("where id = :id");
        $DataInfo2  = jsntable::selone("id as my_id", "where id = :id");
        $select_num = jsntable::select_num();
        if ($select_num == 1)
            echo "查詢單筆通過 <br>";
        else 
            throw new Exception("查詢單筆發生錯誤");
        

    //查詢快取
        $cache_life   = jsntable::cache_life(20);
        jsntable::cache(true); // 開啟快取
        $DataList     = jsntable::sel("limit 3");
        jsntable::cache(false); // 停止快取
        if ( substr_count(jsntable::cache_set_get(), "set") > 0 or substr_count(jsntable::cache_set_get(), "get") > 0)
            echo "快取存活時間：{$cache_life} 秒, 取得查詢快取的狀態成功：" . jsntable::cache_set_get() . "<br>";
        else
            throw new Exception("取得查詢快取的狀態失敗");

        if (is_object($DataList))
            echo "查詢快取成功 <br>";
        else
            throw new Exception("查詢快取發生錯誤");

    //取得快取鍵
        $cache_key = jsntable::cache_key_get();
        if (is_string($cache_key) and !empty($cache_key))
            echo "取得快取鍵：{$cache_key} 成功 <br>";
        else
            throw new Exception("取得快取鍵發生錯誤");
       
    //取得快取內容
        $result = jsntable::cache_get($cache_key);
        if (is_object($result))
            echo "取得快取內容成功 <br>";
        else
            throw new Exception("取得快取內容發生錯誤");


    //用快取鍵刪除快取
        jsntable::cache_clean($cache_key);
        $result = jsntable::cache_get($cache_key);
        if (!is_object($result))
            echo "刪除快取 $cache_key 成功，快取已不存在 <br>";
        else
            throw new Exception("刪除快取 $cache_key 發生錯誤");

    //清除所有快取
        $result = jsntable::cache_clean();
        if ($result === true) echo "清除所有快取成功 <br>";
        else throw new Exception("清除所有快取發生錯誤");


    //刪除
        jsntable::_id(2);
        $result                 =   jsntable::delete("id = :id");
        if ($select_num == 1)       echo "刪除{$result}筆 <br>";
        else                        throw new Exception("刪除發生錯誤");

    //清空
        $result = jsntable::truncate();
        if ($result->queryString == jsntable::sql()) echo "清空成功 <br>";
        else                        throw new Exception("清空發生錯誤");

    //刪除資料表
        $sql = "drop table `jsntable`";
        $result = Jsnpdo_factory::drop($sql);
        if ($result->queryString == jsntable::sql()) echo "資料表刪除成功 <br>";
        else                        throw new Exception("資料表刪除發生錯誤");
    
    // /*************************************************************************************/
    // /*************************************************************************************/
    // /*************************************************************************************/
    // /*************************************************************************************/
    // /*************************************************************************************/
    // /*************************************************************************************/
    // /*************************************************************************************/
    // /*************************************************************************************/
    // /*************************************************************************************/


    echo "<h1>傳統寫法，不支援工廠模型</h1>";

    // 若有多筆資料庫切換使用
        $j = new jsnpdo;
        $DB = $j->connect("mysql", "localhost", "ci_jsn", "root", "");
        $DB2= $j->connect("mysql", "localhost", "test", "root", "");

        // 那麼要改成這般操作
            // $DB->_id(3);
            // $DB->sel("*", "jsntable", "where id > :id");

            // $DB2->_id(3);
            // $DB2->sel("*", "type_base", "where id > :id limit 1000");

    // 連接資料庫
        $j->connect("mysql", "localhost", "ci_jsn", "root", "");

    //建立資料庫
        $sql = "CREATE TABLE IF NOT EXISTS `jsntable` (
                      `id` int(10) NOT NULL auto_increment,
                      `title` varchar(500) NOT NULL,
                      `content` varchar(500) NOT NULL,
                      PRIMARY KEY  (`id`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        $resul = $j->query($sql, NULL);


    //insert 或 iary
        unset($ary);
        $ary['title']            =        $j->quo("傳統寫法 iary 1");
        $result                  =        $j->iary("jsntable", $ary, NULL);
        if ($result > 0) echo "新增成功 <br>";
        else throw new Exception("新增發生錯誤");
        

        $_POST['title']          =        $j->quo("經由POST iary 2");
        $ary['title']            =        NULL;
        $result                  =        $j->iary("jsntable", $ary, "POST");
        // 若要 debug 的參數, 可在第四個參數指定 1 str chk
            // $result                  =        $j->iary("jsntable", $ary, "POST", "chk");
        if ($result > 0) echo "新增成功 <br>";
        else throw new Exception("新增發生錯誤");


    //select 或 sel 多種用法 
        $j->_id(10);
        $DataList = $j->sel("*", "jsntable", "where id < :id ");

        // in ... or not in ....
        $place_holder            = $j->in("id", array(1, 3));
        $DataList   = $j->sel("*", "jsntable", "where id in ({$place_holder})");
        if ($DataList != 0) echo "查詢 in 成功<br>";
        else throw new Exception("查詢 in 發生錯誤");

        // like
        $j->_id("%1%");
        $DataList   = $j->sel("*", "jsntable", "where id like :id");
        if ($DataList != 0) echo "查詢 like 成功<br>";
        else throw new Exception("查詢 like 發生錯誤");
        
        // beteween
        $j->_start(0);
        $j->_end(3);
        $DataList   = $j->sel("*", "jsntable", "where id between :start and :end ");
        if ($DataList != 0) echo "查詢 beteween 成功<br>";
        else throw new Exception("查詢 beteween 發生錯誤");


    //select_one 或 selone
        $j->_id(1);
        $DataInfo = $j->selone("*", "jsntable", "where id = :id");
        if ($DataInfo != 0) echo "查詢單筆成功<br>";
        else throw new Exception("查詢單筆發生錯誤");

    //update 或 uary
        unset($_POST, $ary);
        $_POST['title']             =    $j->quo("經由 POST 修改" . time());
        $j->_id(1);
        $ary['title']               =    NULL;
        $ary['content']             =    $j->quo("內容修改" . time());
        $result                     =    $j->uary("jsntable", $ary, "where id = :id", "POST");
        if ($result > 0)                 echo "修改成功 <br>";
        else throw new Exception("修改發生錯誤");

    //delete
        unset($_POST, $ary);
        $j->_id(2);
        $result                     =    $j->delete("jsntable", "id = :id");
        if ($result > 0)                 echo "刪除成功 <br>";
        else throw new Exception("刪除發生錯誤");

    //多筆增加
        $j::$get_string             =    true;
        $i=0; while($i++ < 5)
        {
            $ary['content']         =    $j->quo("使用多筆新增 {$i}");
            $with[]                 =    $j->iary("jsntable", $ary, "POST");
        }
        $j::$get_string             =    false;

        //若要 debug 可以添加第二個參數 Jsnpdo::with($with, 1);
        $result                     =    $j->with($with);
        if ($result > 0)                 echo "一次多筆新增成功<br>";
        else throw new Exception("一次多筆新增發生錯誤");

    //快取
        $j::$cache_life             =     10; 
        $j::cache(true);
        $DataList                   =    $j->selone("count(id) as `num_3`", "jsntable", "");
        $j::cache(false);
        echo "快取存活時間" . $j::$cache_life . "秒, 取得查詢快取的狀態成功：" . $j->cache_set_get() . "<br>";
        if ($j::$select_num > 0)         echo "快取查詢成功<br>";
        else throw new Exception("快取查詢發生錯誤");


        $j::$cache_life             =     5; 
        $j::cache(true);
        $DataList                   =    $j->selone("count(id) as `num_4`", "jsntable", "");
        $j::cache(false);
        echo "快取存活時間" . $j::$cache_life . "秒, 取得查詢快取的狀態成功：" . $j->cache_set_get() . "<br>";
        if ($j::$select_num > 0)         echo "快取查詢成功<br>";
        else throw new Exception("快取查詢發生錯誤");

    //取得快取鍵
        $cache_key = $j->cache_key_get();
        if (is_string($cache_key) and !empty($cache_key))
            echo "取得快取鍵：{$cache_key} 成功 <br>";
        else
            throw new Exception("取得快取鍵發生錯誤");

    //取得快取內容
        $result = $j->cache_get($cache_key);
        if (is_object($result))
            echo "取得快取內容成功 <br>";
        else
            throw new Exception("取得快取內容發生錯誤");

    //用快取鍵刪除快取
        $j->cache_clean($cache_key);
        $result = $j->cache_get($cache_key);
        if (!is_object($result))
            echo "刪除快取 $cache_key 成功，快取已不存在 <br>";
        else
            throw new Exception("刪除快取 $cache_key 發生錯誤");

    //清除所有快取
        $result = $j->cache_clean();
        if ($result === true) echo "清除所有快取成功 <br>";
        else throw new Exception("清除所有快取發生錯誤");

    //truncate
        $result                     =    $j->truncate("jsntable");
        if (!empty($result))             echo "清空成功<br>";
        else throw new Exception("清空發生錯誤");

    // 刪除資料表
        $sql                        =     "DROP TABLE `jsntable`";
        $result                     =     $j->query($sql, NULL);
        if ($result->queryString == $sql)
            echo "刪除資料表成功";
        else
            throw new Exception("刪除資料表錯誤");

    //end
        echo "<h1>測試成功</h1>";

}
catch(Exception $e)
{
    echo "<h2>獲取異常！</h2>";
    echo $e->getMessage() . "<br>";
    echo $e->getFile() . "<br>";
    echo $e->getLine() . "行<br>";
}



?>