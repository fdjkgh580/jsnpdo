jsnpdo
======

快速溝通PDO
<a href="http://jsnwork.kiiuo.com/archives/1657/php-jsnpdo-%E5%BF%AB%E9%80%9F%E7%94%A8pdo%E8%88%87mysql%E6%BA%9D%E9%80%9A">
    我的部落格
</a>

<!-- <p>
    Jsnpdo 影片：<a target="_blank" href="http://www.youtube.com/playlist?list=PLffFiEWYrQrRgujdCXB-NsVMXdDo_YYUk">youtube 範例教學</a>
</p> -->

<a href="https://github.com/fdjkgh580/jsnpdo/blob/master/jsnpdo/jsnpdo.php">
    Changelog 點我查看
</a>



###說明書

<pre>
    /** v3.4 重要更新 **/
    - 修正 CSS 除錯樣式色彩
    - 添加不使用 '' 的寫法。處理需要使用 MySQL 的內建函數如 NOW() 的時候
    - 修正某些時候 PDO 執行發生錯誤不會顯示錯誤訊息
    - quote() 不再使用 PDO::quote()。
 
    /** 3.3 建議直接跑 Demo.php **/
    - 切換資料庫功能
    - 工廠模式建議使用虛擬方法。日後將考慮移除實體工廠
    - 修改核心為 prepare() + execute()
    - 修正指令樣式
    
    // 工廠模式快速介紹

        // 操作多筆資料庫。使用並記錄到倉儲，倉儲取名為 DB 與 DB2
        Jsnpdo_factory::connect("mysql", "localhost", "ci_jsn", "root", "")->db_house("DB");
        Jsnpdo_factory::connect("mysql", "localhost", "sport", "root", "")->db_house("DB2");
        
        //使用DB
        Jsnpdo_factory::switch_db("DB");
        article::sel("limit 1");

        //使用DB2
        Jsnpdo_factory::switch_db("DB2");
        article::sel("limit 1");

        // 以下操作格式：
        // 直接資料表名稱或自訂別名::操作指令();
        // 如：
        // 
        // jsntable::sel("*", "where id = :id")
        // 有一張資料表叫做 jsntable，對它做查詢

        //新增資料表 jsntable 並在欄位 title 填入 標題
        jsntable::ary("title", "標題");
        $result = jsntable::iary(); 

        //修改資料表 jsntable, 欄位 title 填指定的值，欄位 content 經由 POST 接收對應的值
        $_POST['content'] = "由POST給予, 目前是修改動作" . time();
        jsntable::ary("title", "自動對應標題, 目前是修改動作");
        jsntable::ary("content", NULL);
        jsntable::_id(1);
        $result = jsntable::uary("where id = :id", "POST"); //指定POST將自動尋找對應的 $_POST key
        
        //查詢資料表 jsntable, 條件是當欄位 id 等於 2 ，將顯示欄位 id 並自訂別名為 my_id
        jsntable::_id(2);
        $DataList     = jsntable::sel("id as my_id", "where id = :id"); //顯示欄位 + 條件 + 選用debug

        // where in 的條件用法
        // 查詢資料表 jsntable, 條件是當 id 為 1 或 2 時, 顯示 欄位 id 與 title
        $place_holder = jsntable::in(array(1, 2));
        jsntable::sel("id, title", "where id in ($place_holder)");
        
    // 將物件實體化的傳統寫法
        
        //若有多筆資料庫操作
        $DB = $j->connect("mysql", "localhost", "ci_jsn", "root", "");
        $DB2= $j->connect("mysql", "localhost", "test", "root", "");

        // 那麼要改成這般操作
        // $DB->_id(3);
        // $DB->sel("*", "jsntable", "where id > :id");

        // $DB2->_id(3);
        // $DB2->sel("*", "type_base", "where id > :id limit 1000");

        //新增
        unset($ary);
        $ary['title']            =        "傳統寫法 iary 1";
        $result                  =        $j->iary("jsntable", $ary);

        //select 或 sel 多種用法 
        $j->_id(10);
        $DataList = $j->sel("*", "jsntable", "where id < :id ");

        // in
        $place_holder            = $j->in("id", array(1, 3));
        $DataList                = $j->sel("*", "jsntable", "where id in ({$place_holder})");
        
        // like
        $j->_id("%1%");
        $DataList   = $j->sel("*", "jsntable", "where id like :id");
        
        // beteween
        $j->_start(0);
        $j->_end(3);
        $DataList   = $j->sel("*", "jsntable", "where id between :start and :end ");

        //修改
        unset($_POST, $ary);
        $_POST['title']             =    "經由 POST 修改" . time();
        $j->_id(1);
        $ary['title']               =    NULL;
        $ary['content']             =    "內容修改" . time();
        $result                     =    $j->uary("jsntable", $ary, "where id = :id", "POST");
        
</pre>


###使用方法

- 前往 jsnpdo/jsnpdo/Demo.php 
- include_once 對應你的所有路徑設定
- 設定你的資料庫資料Jsnpdo::connect("mysql", "localhost", "ci_jsn", "root", "");
- 重新整理就會看到極簡的單元測試

<pre>
    打造工廠來產生模型

    資料表建立成功，工廠模型完成 
    新增1筆成功 
    新增1筆成功 
    多筆執行成功 
    修改1筆資料 
    查詢通過 
    查詢單筆通過 
    快取存活時間：20 秒, 取得查詢快取的狀態成功：set
    查詢快取成功 
    取得快取鍵：2623d70e0f6c5ffc5153417532d47682cd8e1ca0 成功 
    取得快取內容成功 
    刪除快取 2623d70e0f6c5ffc5153417532d47682cd8e1ca0 成功，快取已不存在 
    清除所有快取成功 
    刪除1筆 
    清空成功 
    資料表刪除成功 
    傳統寫法，不支援工廠模型

    新增成功 
    新增成功 
    查詢 in 成功
    查詢 like 成功
    查詢 beteween 成功
    查詢單筆成功
    修改成功 
    刪除成功 
    一次多筆新增成功
    快取存活時間10秒, 取得查詢快取的狀態成功：set
    快取查詢成功
    快取存活時間5秒, 取得查詢快取的狀態成功：set
    快取查詢成功
    取得快取鍵：228fa87647561490f78f1a9632e68c1104a6a9ea 成功 
    取得快取內容成功 
    刪除快取 228fa87647561490f78f1a9632e68c1104a6a9ea 成功，快取已不存在 
    清除所有快取成功 
    清空成功
    刪除資料表成功
</pre>
