<?php

/**
 * v3.4.5
 * - 添加 __call() 第三個參數，決定是否自動 quote()。主要可用在 
 *   $j->_id("col + 1", false); //使用如 where id = col + 1
 *   $j->_id("col + 1"); //則會是 where id = 'col + 1' 。
 * - 解決 iary() 在 debug 的時候會出現無法替換 POST/GET 的問題。
 * - 簡化 _call __callStatic
 *
 * 
 * v3.4.4
 * - 解決 where in 在 debug 的時候，欄位值無法正常替換顯示
 * - 解決 update 在 where 子句的欄位值多了 ''
 * - 修正如 where in 在使用陣列指定時，原本如 array(1, 3) 轉換為 where id in ('1', '3')時，
 *   會自動添加 '' 的問題，如今修改為不自動添加 ''。這樣當使用SQL函數時如 array("now()", 3) 時，
 *   才會被轉換為 where id in (now(), 3)。
 *   
 *   
 * 
 * v3.4.3
 * - 解決當欄位名稱出現部分雷同文字時，在debug模式下的值出現取代錯誤
 * - 修正上述修正後的併發狀況，出現在iary()與uary()是否有where子句時，是否自動添加 ''
 * 
 * v3.4.2
 * - 修正in()裡 uniqid()的bug 因版本的問題 造成無法給定唯一值
 *
 * 
 * v3.4.1
 * - 添加除錯樣式 deubg()
 * - 修正 jsnpdo.php 的開頭出現空白，造成提早輸出的問題
 *
 *
 * v3.4
 * - 修正 CSS 除錯樣式色彩
 * - 添加不使用 '' 的寫法。處理需要使用 MySQL 的內建函數如 NOW() 的時候
 * - 修正某些時候 PDO 執行發生錯誤不會顯示錯誤訊息
 * - quote() 不再使用 PDO::quote()。
 *
 * v3.3
 * - 切換資料庫功能
 * - 工廠模式建議使用虛擬方法。日後將考慮移除實體工廠
 * - 修改核心為 prepare() + execute()
 * - 修正指令樣式, 包含傳統寫法與工廠寫法
 *
 * v3.2
 * 增加工廠模型寫法。
 * 擴充快取功能，可以取得快取鍵、快取內容、刪除單一快取、清空快取、指定快取存放位置
 *
 * v3.1
 * 增加原名寫法。
 *   select() 等同 sel()
 *   select_one() 等同 selone()
 *   insert() 等同 iary()
 *   uary() 等同 uary()
 *
 *
 * v3.0
 * 必須使用 Jsnao ArrayObject
 * 若使用快取，需引用 phpfastcache.php
 */

// 抽象類別，定義了公用程序如快取
include_once ("Abstract_Jsnpdo.php");

// 工廠產生模型。需在較新的PHP方可運作。
include_once ("Jsnpdo_factory.php");

class Jsnpdo extends Abstract_Jsnpdo
{
    // 放置當前與PDO溝通的連接資源
    public static $PDO;

    // 使用PDO的fetch()或fetchAll()的參數，
    // PDO::FETCH_ASSOC 為陣列，PDO預設設 PDO::FETCH_BOTH提取兩種型態，設 PDO::FETCH_OBJ 為物件
    // 提取物件的效能最高，但我們取出陣列，再透過 Jsnao 轉換成 ArrayObject
    public static $fetch_type = PDO::FETCH_ASSOC;

    // 預設使用try catche系統設置
    public static $is_trycatch = "1";

    // 代表字串 與 實際值 的轉換表, 最後將轉換如 array(":id" => "100")
    // 但是在此 $select_condition 則是記錄如 array("id" => "100")
    // 鍵不會有 『:』
    public static $select_condition = array();

    //查詢總數量
    public static $select_num;

    //除錯模式：
    //0: 不使用
    //1:停止只顯示文字             全部方法
    //str: 顯示查詢表並停止     sel() selone() iary() uary() delete()
    //chk: 顯示查詢表並繼續        sel() selone()
    public static $debug;

    // 偵錯要顯示的文字。當該屬性被設定時，偵錯顯示將優先採用。
    public static $debug_msg;

    // 除錯的CSS顯示方式。
    public static $debug_style = "block";

    //設定 1 會直接返回SQL字串，而不會執行
    public static $get_string = 0;

    //執行SQL前的字串
    public static $sql;

    //快取的存活間
    public static $cache_life = 3;

    //$_POST 或 $_GET, 在 query() 時可以呼叫已取得設定的是 POST 或 GET 陣列
    public static $request_ary;

    public function __construct()
    {
    }

    /**
     * 連線
     * @param   $sql_database     資料庫類型如 mysql
     * @param   $hostname         主機位置
     * @param   $dbname           資料庫名稱
     * @param   $user             使用者名稱
     * @param   $password         使用者密碼
     * @return                    返回實體化的物件
     */
    public static function connect($sql_database, $hostname, $dbname, $user, $password)
    {
        try

        {
            $pdo = new PDO("{$sql_database}:host={$hostname};dbname={$dbname}", $user, $password);

            $pdo->query("SET NAMES 'UTF8'");

            self::$PDO = $pdo;

            return new Jsnpdo;
        }
         catch (PDOException $e) {
            self::warning('stop', '資料庫連接錯誤: ' . $e->getMessage());
        }
    }

    /**
     * PDO執行
     * @param   $sql          SQL 指令
     * @param   $status_debug 除錯模式
     * @param   $debug_quote
     * @return                PDO狀態的資源物件 或 SQL 字串
     */
    public static function query($sql, $status_debug, $debug_quote)
    {

        //可外部讀取檢視
        self::$sql = $sql;

        if (self::$get_string == 1) {
            return $sql;
        }

        // debug 純文字
        if (self::get_debug($status_debug) == 1) {

            $msg = self::sql_replace_condition($sql, $debug_quote);

            self::warning('stop', $msg);
        }

        //正確執行
        else{
            // 不使用 php 本地模式, 避免造成 sql injection。
            // php 5.3.6 以上已經處理這個問題了。無論 true 或 false 都可以
            // self::$PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            $result = self::$PDO->prepare($sql);

            if (!$result) {
                $error_ary = $result->errorinfo();

                throw new Exception("PDO 執行 query 發生錯誤：{$error_ary[2]}");
            }

            // 若有要替換值的轉換表, 也就是將如 id = :id 轉換為 id = 1
            // 所以 $execute_map 希望得到的是如 array(":id" => 1)
            $execute_map = self::condition_replace_request();

            // 過濾掉不存在SQL中的項目。
            // 如 array(:myid => 1) 不存在SQL字串，將被剔除, 否則多餘的參數會錯誤
            foreach ($execute_map as $token => $token_val) {
                if (substr_count($sql, $token) == 0) {
                    unset($execute_map[$token]);
                }
            }

            foreach ($execute_map as $token => $token_val) {
                $execute_map[$token] = trim($token_val, "'");
            }

            $bool = $result->execute($execute_map);

            if ($bool == true) {
                return $result;
            }

            // 準備給 PDO 的 execute() 對應陣列
            $error_ary = $result->errorinfo();

            throw new Exception("PDO 執行 query 發生錯誤：{$error_ary[2]}");
        }
    }

    /**
     * 新增
     * @param   $table_name   資料表名稱
     * @param   $ary          添加的資料
     * @param   $post_get     POST | GET
     * @param   $status_debug 除錯模式
     * @return                回傳增加數量
     */
    public static function iary($table_name, array $ary, $post_get = null, $status_debug = null)
    {
        self::check_param_post_get($post_get);

        // $ary 原型如 $ary['title'] = "標題";
        foreach ($ary as $key => $val) 
        {
            $key_trim = trim($key);

            //欄位名稱陣列
            $col_name[] = "`{$key_trim}`";

            // 欄位值陣列
            $col_val[] = self::raw_protection($key_trim, $val);

            self::$select_condition[$key] = $val;
        }


        $col_name_str = implode(" , ", $col_name);

        $col_val_str = implode(" , ", $col_val);

        $sql = " insert into `{$table_name}` ( {$col_name_str} ) values ( {$col_val_str} ); ";

        //debug str
        if (self::get_debug($status_debug) == "str") {

            //轉換
            self::$debug_msg = self::sql_replace_condition($sql);

            $pk = self::primary_key($table_name);

            self::sel("*", $table_name, "order by `{$pk}` desc", "str");
        }

        $result = self::query($sql, $status_debug);

        if (self::$get_string == 1) {
            return $result;
        }

        return $result->rowCount();
    }

    // 同 iary()
    public static function insert()
    {
        $ary = func_get_args();
        return self::iary($ary[0], $ary[1], $ary[2], $ary[3]);
    }

    /**
     * 修改
     * @param   $table_name   資料表名稱
     * @param   $ary          修改陣列
     * @param   $else         其他條件
     * @param   $post_get     POST | GET
     * @param   $status_debug
     * @return                返回影響數量
     */
    public static function uary($table_name, array $ary, $else, $post_get = null, $status_debug = null)
    {
        self::check_param_post_get($post_get);

        $table_name = trim($table_name);

        $else = trim($else);

        // 修改的欄位與值。
        // $ary 原型如 $ary['title'] = "標題";
        foreach ($ary as $key => $val) {

            $uk = self::raw_protection($key, $val);

            $str[] = " `{$key}` = $uk ";

            //紀錄轉換對應表
            self::$select_condition[$key] = $val;
        }

        // 製作修改字串如 『title = :title, content = :content』
        $cond = implode(", ", $str);

        $sql = "update `{$table_name}` set {$cond} {$else}; ";

        //debug str
        if (self::get_debug($status_debug) == "str") {
            
            //轉換
            self::$debug_msg = self::sql_replace_condition($sql);

            //把要修改的欄位做上色動作
            self::update_bgcolor($ary);

            self::sel("*", $table_name, "{$else}", "str");
        }

        $result = self::query($sql, $status_debug, true);

        if (self::$get_string == 1) {

            return $result;
 
        }

        return $result->rowCount();
    }

    // 同 uary()
    public static function update()
    {
        $ary = func_get_args();
        return self::uary($ary[0], $ary[1], $ary[2], $ary[3], $ary[4]);
    }

    public static function __callStatic($name, $arguments)
    {
        self::for_call($name, $arguments);
    }

    //使用在where中, 如 $j->_id(1); 代表形成字串 id = :id, 且要將 :id 代表為 1
    public function __call($name, $arguments)
    {
        self::for_call($name, $arguments);
    }

    protected static function for_call($name, $arguments)
    {
        //是欄位嗎
        if (substr($name, 0, 1) == "_") {
            $bool = isset($arguments[1]) ? false : true;
            self::execute_ary($name, $arguments[0], $bool);
        }
    }

    /**
     * 多筆查詢
     * @param   $column       查詢欄位
     * @param   $table_name   資料表
     * @param   $else         其他條件
     * @param   $status_debug 除錯語句
     * @return                返回 ArrayObject 或 SQL 字串 或 0
     */
    public static function sel($column, $table_name, $else = null, $status_debug = null)
    {

        $mix = self::select_run("sel", $column, $table_name, $else, $status_debug, true);

        if ($mix == "get_string") {
            return self::$sql;
        }

        //若沒有資料回傳0
        return ($mix->count == 0) ? "0" : $mix->data;
    }

    // 同 sel()
    public static function select()
    {
        $ary = func_get_args();
        return self::sel($ary[0], $ary[1], $ary[2], $ary[3]);
    }

    /**
     * 多筆查詢
     * @param   $column       查詢欄位
     * @param   $table_name   資料表
     * @param   $else         其他條件
     * @param   $status_debug 除錯語句
     * @return                返回 ArrayObject 或 SQL 字串 或 0
     */
    public static function selone($column, $table_name, $else, $status_debug = null)
    {

        $mix = self::select_run("selone", $column, $table_name, $else, $status_debug);

        if ($mix == "get_string") {
            return self::$sql;
        }

        if ($mix->count > 1) {
            throw new Exception("查詢指令錯誤，數量多於一筆");
        }

        return ($mix->count == 0) ? "0" : $mix->data;
    }

    // 同 selone()
    public static function select_one()
    {
        $ary = func_get_args();
        return self::selone($ary[0], $ary[1], $ary[2], $ary[3]);
    }

    /**
     * 刪除
     * @param   $table_name   資料表名稱
     * @param   $where        where 指令
     * @param   $status_debug 除錯字串
     * @return                返回 ArrayObject 或 SQL 字串 或 0
     */
    public static function delete($table_name, $where, $status_debug = null)
    {
        $table_name = trim($table_name);

        $where = trim($where);

        if (empty($where)) {
            throw new Exception("delete 方法務必指定 where 條件");
        }

        $sql = "delete from {$table_name} where {$where}; ";

        //debug str
        if (self::get_debug($status_debug) == "str") {
            self::$debug_msg = self::sql_replace_condition($sql);

            self::sel("*", $table_name, "where {$where} ", "str");
        }

        $result = self::query($sql, $status_debug);

        if (self::$get_string == 1) {
            return $result;
        }

        return $result->rowCount();
    }

    /**
     * 清空資料表
     * @param   $table_name   資料表名稱
     * @param   $status_debug 除錯語句
     * @return
     */
    public static function truncate($table_name, $status_debug = null)
    {
        $table_name = trim($table_name);

        $sql = "truncate table `{$table_name}`;";

        //debug str
        if (self::get_debug($status_debug) == "str") {
            self::$debug_msg = $sql;

            self::sel("*", $table_name, "", "str");
        }

        $result = self::query($sql, $status_debug);

        if (self::$get_string == 1) {
            return $result;
        }

        return $result;
    }

    /**
     * 多筆SQL一次執行
     * @param   $ary          多筆要執行的SQL語句
     * @param   $status_debug 除錯語句
     * @return                   返回PDO狀態資源
     */
    public static function with(array $ary, $status_debug = null)
    {
        $sql = implode(null, $ary);

        $result = self::query($sql, $status_debug);

        if (self::$get_string == 1) {
            return $result;
        }

        //必須釋放多筆緩存
        $result->closeCursor();

        return $result;
    }

    /**
     * 在字串左右添加 '' 。並不使用 quote() 因為無法解除。
     * 這裡並不做SQL語句執行避免注入的安全性問題。
     * 只適合用在除錯的表示而已。
     *
     * @param   $str 字串
     * @return       返回 '' 包圍
     */
    public static function quo($str)
    {
        // return self::$PDO->quote($str);
        return "'{$str}'";
    }

    // quo() 別名
    public static function quote($str)
    {
        return self::quo($str);
    }

    /**
     * 最後一筆新增的編號
     * @return  新增的編號
     */
    public static function last_insert_id()
    {
        return self::$PDO->lastInsertId();
    }

    /**
     * 啟用 select 快取
     * @param  $bool 預設不啟用
     */
    public static function cache($bool = 0)
    {
        if ($bool == 0) {
            self::$cache_status = 0;
        } else {
            self::$cache_status = 1;
        }
    }

    public static function cache_path($path)
    {
        return parent::cache_path($path);
    }

    //取得快取的使用狀態是設定set 還是讀取get
    public static function cache_set_get()
    {
        return parent::$cache_set_get;
    }

    /**
     * 刪除快取
     * @param   $key 快取鍵。若不指定快取鍵，將刪除所有快取
     * @return       bool
     */
    public static function cache_clean($key = null)
    {
        if (empty($key)) {
            return parent::cache_clean();
        } else {
            return parent::cache_delete($key);
        }
    }

    //取得快取鍵
    public static function cache_key_get()
    {
        return parent::cache_key_get();
    }

    //取得快取內容
    public static function cache_get($key)
    {
        return parent::cache_get($key);
    }

    // HHHHHHHHH     HHHHHHHHHEEEEEEEEEEEEEEEEEEEEEELLLLLLLLLLL             PPPPPPPPPPPPPPPPP
    // H:::::::H     H:::::::HE::::::::::::::::::::EL:::::::::L             P::::::::::::::::P
    // H:::::::H     H:::::::HE::::::::::::::::::::EL:::::::::L             P::::::PPPPPP:::::P
    // HH::::::H     H::::::HHEE::::::EEEEEEEEE::::ELL:::::::LL             PP:::::P     P:::::P
    //   H:::::H     H:::::H    E:::::E       EEEEEE  L:::::L                 P::::P     P:::::P
    //   H:::::H     H:::::H    E:::::E               L:::::L                 P::::P     P:::::P
    //   H::::::HHHHH::::::H    E::::::EEEEEEEEEE     L:::::L                 P::::PPPPPP:::::P
    //   H:::::::::::::::::H    E:::::::::::::::E     L:::::L                 P:::::::::::::PP
    //   H:::::::::::::::::H    E:::::::::::::::E     L:::::L                 P::::PPPPPPPPP
    //   H::::::HHHHH::::::H    E::::::EEEEEEEEEE     L:::::L                 P::::P
    //   H:::::H     H:::::H    E:::::E               L:::::L                 P::::P
    //   H:::::H     H:::::H    E:::::E       EEEEEE  L:::::L         LLLLLL  P::::P
    // HH::::::H     H::::::HHEE::::::EEEEEEEE:::::ELL:::::::LLLLLLLLL:::::LPP::::::PP
    // H:::::::H     H:::::::HE::::::::::::::::::::EL::::::::::::::::::::::LP::::::::P
    // H:::::::H     H:::::::HE::::::::::::::::::::EL::::::::::::::::::::::LP::::::::P
    // HHHHHHHHH     HHHHHHHHHEEEEEEEEEEEEEEEEEEEEEELLLLLLLLLLLLLLLLLLLLLLLLPPPPPPPPPP

    //取得該資料表的 primarykey 名稱
    protected static function primary_key($table_name)
    {
        $showres = self::query("show index from `{$table_name}`", null);

        $indexinfo = $showres->fetch(PDO::FETCH_ASSOC);

        return $indexinfo['Column_name'];
    }

    /**
     * 確認使用是否設定 POST 或 GET 參數，若有就設定屬性供後續使用
     * @param   $post_get       "POST" | "GET" | NULL
     */
    protected static function check_param_post_get($post_get)
    {
        if ($post_get == "POST") {
            self::$request_ary = $_POST;
        } elseif ($post_get == "GET") {
            self::$request_ary = $_GET;
        } else {
            if (isset($post_get)) {
                throw new Exception("請指定指定 POST 或 GET");
            }

            self::$request_ary = null;
        }
    }

    //使用CSS 把要修改的欄位上色
    public static function update_bgcolor(array $ary)
    {
        foreach ($ary as $column => $value) {
            $cssclass[] = ".php_jsnao_warning_style .db tbody td.{$column} ";
        }

        $cssclass = implode(", ", $cssclass);

        echo

        "
        <style>
            {$cssclass}
            {
                background: #DE4343 ;
                color: white !important;
            }
        </style>
        ";
    }

    /**
     * 準備給 PDO 的 execute() 對應陣列
     * 提供如 array(id => 1, title = "標題")
     *
     * @param   $name       想要轉換字符的名稱作為鍵, 不可包含前贅字符 『:』。如 id
     * @param   $val        如 1
     * @param   $isquote    是否自動添加 ''。
     * @return              bool
     */
    protected static function execute_ary($name, $val, $isquote)
    {
        //實際欄位名稱
        $coln = ltrim($name, "_");

        $val  = $isquote == true ? self::quo($val) : $val;

        self::$select_condition[$coln] = $val;

        return true;
    }

    /**
     * 使用在組合 where ... in ... 語句, 產生如 where id in (?, ?, ?)
     *
     * @param   $name    要替換的辨識字符。 如 where id in (1, 3, 5), 那就是填 id
     * @param   $inary   in 的陣列。 如 array(1, 3, 5)
     * @return [type]    回傳一個佔位的變數字串。可以給SQL語句使用如 where id in ($in), 將使用如
     *                   where id in (:54448c5f6dd78, :54448c5f6dd80, :54448c5f6dd86)
     */
    public static function in($name, array $inary)
    {


        // 組合一個對應表, 如 array(:54448c5f6dd78 => 1)
        foreach ($inary as $key => $val) {
    
            $uniqid = uniqid('u'). '_' . $key;

            // 提供返回文字使用, 前後須保留空白
            $return[] = " :{$uniqid} ";

            $map[$uniqid] = $val;
        }



        //提交給 execute_ary 陣列表
        foreach ($map as $key => $val) {
            self::execute_ary($key, $val, false);
        }

        //組合給返回SQL時可使用的字串
        $str = implode(", ", $return);

        return $str;
    }

    /**
     * 檢查有無where語句的替換值。
     *
     * 例如將 array("title" => "標題") 形成為 array(":title" => "標題") 給 PDO::execute()
     * 提供 PDO::prepare() 使用 where 條件時可以透過指定 『where title = :title』將 :title 對應到 標題
     * 若指定使用 POST 或 GET 將自訂引用
     */
    protected static function condition_replace_request()
    {
       
        foreach (self::$select_condition as $column_name => $column_val) {
            if (!empty($column_val)) {
                $befcondi[":" . $column_name] = $column_val;

            }

            // 若不存在POST/GET
            elseif (!isset(self::$request_ary)) {
                $befcondi[":" . $column_name] = $column_val;
            } else {
                //自動添加 '' 供後續判斷
                $befcondi[":" . $column_name] = Jsnpdo::quo(self::$request_ary[$column_name]);
                // $befcondi[":" . $column_name] = Jsnpdo::quo(self::$request_ary[$column_name]);
            }
        }

        //重設
        self::$request_ary = null;

        return $befcondi;
    }

    // 返回原始文字(如再使用 mysql 內建函式)或是跳脫
    protected static function raw_protection($key, $val)
    {



        //使用 POST/GET
        if (!isset($val)) {

            return ":{$key}";
        
        } else {
            //前後是否包含 '
            if (substr($val, "0", 1) == "'" and substr($val, "-1", 1) == "'") {
                
                //前後添加空白，方便 debug 的時候替換文字的判別
                return " :{$key} ";
            }

            // 如 now()
            return $val;
        }

    }

    /**
     * 除錯 CSS樣式
     * @param   $type block(方框) | fixed(固定位置在螢幕頂端) | string (純文字。適合ajax偵錯)
     */
    public static function debug_style($type)
    {
        self::$debug_style = $type;
    }

    //產生查詢字串
    protected static function select_string($column, $table_name, $else)
    {
        $column = trim($column);

        $table_name = "`" . trim($table_name) . "`";

        $else = trim($else);

        return "select {$column} from {$table_name} {$else}; ";
    }

    //除錯時避免資料量過大，將自動添加 limit
    protected static function debug_auto_limit($else, $status_debug)
    {
        if (self::get_debug($status_debug) == "str" or self::get_debug($status_debug) == "chk") {
            if (!empty($else)) {
                if (substr_count($else, "limit") == 0) {
                    $else .= " limit 10 ";
                }
            } else {
                $else .= " limit 10 ";
            }
        }

        return $else;
    }

    /**
     * 運行 select 並提取資料
     * @param   $select_type  查詢的類型。sel | selone
     * @param   $column       查詢的欄位
     * @param   $table_name   資料表
     * @param   $else         其他條件
     * @param   $status_debug
     * @param   $debug_quote
     * @return                返回 Jsnao 的陣列物件 (ArrayObject) 或 字串 "get_string";
     */
    protected static function select_run($select_type, $column, $table_name, $else, $status_debug, $debug_quote)
    {

        if (!class_exists("Jsnao")) {
            throw new Exception("請先引用 jsnao");
        }

        //除錯時的安全限制處理
        $else = self::debug_auto_limit($else, $status_debug);

        $sql = self::select_string($column, $table_name, $else);

        // 啟用快取
        if (self::$cache_status == 1) {
            parent::cache_init();

            //cache 辨識 key, 並記錄起來
            $cache_sql_key = hash("sha1", $sql);
            parent::$cache_key = $cache_sql_key;
            $cache_obj = parent::cache_get($cache_sql_key);

            // 若曾製作快取
            if (!empty($cache_obj)) {

                //設定數量
                self::$select_num = $cache_obj->count;

                return $cache_obj;
            }
        }

        // 該query資源會提供給 debug str 或 正常運行多筆資料、單筆資料
        $result = self::query($sql, $status_debug, $debug_quote);

        if (self::$get_string == 1) {
            return "get_string";
        }

        //數量
        $obj->count = self::$select_num = $result->rowCount();

        // debug str
        if (self::get_debug($status_debug) == "str") {

            // 轉換
            $msg = self::sql_replace_condition($sql);

            $data = new jsnao($result->fetchAll(PDO::FETCH_ASSOC));

            self::warning('stop', $msg, $data);
        }

        //若沒資料
        if (self::$select_num == 0) {
            return $obj;
        }

        //多筆列表
        if ($select_type == "sel") {
            // 再一次query, 避免空陣列。且使用陣列。因為陣列的 key 會有欄位名稱
            $result = self::query($sql, $status_debug);

            $data = $result->fetchAll(self::$fetch_type);

            $obj->data = new jsnao($data);
        }

        //單筆列表
        else{
            $data = $result->fetch(self::$fetch_type);

            $obj->data = new jsnao($data);
        }

        // debug chk
        if (self::get_debug($status_debug) == "chk") {
            if (self::$select_num > 0) {
                // 再一次query
                $result = self::query($sql, $status_debug);
                $data = new jsnao($result->fetchAll(PDO::FETCH_ASSOC));
            }
            self::warning("continue", $sql, $data);
        }

        //啟用快取
        if (self::$cache_status == 1 and empty($cache_obj)) {
            $cache_r = parent::cache_set($cache_sql_key, $obj, self::$cache_life);

            if (!$cache_r) {
                throw new Exception("快取製作發生錯誤");
            }
        }

        return $obj;
    }

    /**
     * 將有條件陣列的參數轉換為實際的參數值, 僅作為debug用
     * 如同將 id = :id 轉換為 id = 5
     *
     * @param   $sql            SQL語句
     * @param   $debug_quote    是否在 debug 顯示 ''
     * @return                  轉換後的SQL語句
     */
    protected static function sql_replace_condition($sql, $debug_quote = false)
    {

        $ary                = self::condition_replace_request();

        // 若有 where 子句
        if (substr_count($sql, "where") > 0)
        {
            // v5.3.0之後才支援取得前方字串
            $cv             =   phpversion();
            if (version_compare($cv, "5.3.0", ">="))
            // if (false)
            {
                $before     =   strstr($sql, "where", true);
                $after      =   strstr($sql, "where");
            }
            else
            {
                list($before, $after) = explode("where", $sql);
                $after      =  " where {$after}";
            }

            $before = self::match_format(true, $before);
            $after = self::match_format(true, $after);

            foreach ($ary as $key => $val) 
            {
                $before         = self::replace_holderspace($key, $val, $before, false);
                $after          = self::replace_holderspace($key, $val, $after, false);
            }

            $newsql = $before . $after;

        }

        //若沒有 where 子句，SQL全句查找替換，不必添加 ''
        else
        {

            $newsql             = self::match_format(true, $sql);

            foreach ($ary as $key => $val) {
                $newsql         = self::replace_holderspace($key, $val, $newsql, false);
            }
        }


        


        //還原結尾
        $newsql             = self::match_format(false, $newsql);

        return (count($ary) > 0) ? $newsql : $sql;
    }

    /**
     * 替換SQL字句，依需求並將欄位值自動添加 ''
     * @param   $key            原始欄位名稱
     * @param   $val            原始欄位值
     * @param   $sql            要替換的SQL語句
     * @param   $debug_quote    是否在 debug 顯示 ''
     * @return                  替換後的SQL語句
     */
    protected static function replace_holderspace($key, $val, $sql, $debug_quote)
    {
        $space_key      = " {$key} ";

        if ($debug_quote == true)
        {
            $val            =  self::quo($val);
            $space_val      = " {$val} ";
        }
        else
            $space_val      = " {$val} ";

        $newsql            = str_replace($space_key, $space_val, $sql);


        return $newsql;
    }


    /**
     * 處理替換SQL條件字串的格式
     * @param   $bool       true: 建立 | false: 還原
     * @param   $sql        SQL 語句
     * @return              SQL 語句
     */
    protected static function match_format($bool, $sql)
    {
        //建立
        if ($bool == true) {
            $newsql = trim($sql);
            $newsql = rtrim($newsql, ";");
            $newsql .= " ";
        }

        //還原
        else {
            $newsql = rtrim($sql) . ";";
        }

        return $newsql;
    }



    //可供外部設定debug
    public static function debug($status_debug)
    {
        self::$debug = $status_debug;
    }

    //取得debug字串
    protected static function get_debug($status_debug)
    {
        $result = empty($status_debug) ? self::$debug : $status_debug;
        return $result;
    }

    // 警告輸出
    protected static function warning($continue_stop, $msg, ArrayObject $table = null)
    {
        //若屬性已被指定，將優先使用
        if (!empty(self::$debug_msg)) {
            $pm = self::$debug_msg;
            $msg = "<div class='orgmsg'>{$pm}</div><div class='defmsg'>{$msg}</div>";
        }

        if ($table) {
            foreach ($table as $key => $list) {
                unset($mix_body);

                // thead 與 tfoot，取得每個th
                if ($key == 0) {
                    foreach ($list as $column => $val) {
                        $mix_head .= "<th>{$column}</th>";
                    }

                    $thead_foot .= "<tr>{$mix_head}</tr>";
                }

                //tbody, 取得每個td
                foreach ($list as $column => $val) {
                    $mix_body .= "<td class='{$column}'><div class='kjjsi_z77100_01'>{$val}</div></td>";
                }

                $tbody .= "<tr>{$mix_body}</tr>";
            }
        }

        if (self::$debug_style != "string") {
            echo

            "
                <style>
                .php_jsnao_warning_style
                {
                    border: 1px solid rgb(156, 151, 151);
                    background: #E6E6E6;
                    font-family: consolas, '微軟正黑體';
                    line-height: 1.7em;
                    margin-top: 0.1em;
                    margin-bottom: 0.1em;
                    padding: 1em;
                    border-radius: 4px;
                    font-size: 18px;
                    word-break: break-all;
                }
                .php_jsnao_warning_style .orgmsg
                {
                    background: #0CC09F;
                    color: white;
                    padding:1em;
                }
                .php_jsnao_warning_style .defmsg
                {
                    background: #446CB3;
                    font-size: 16px;
                    color: white;
                    padding:1em 4em;
                }

                .php_jsnao_warning_style .db
                {
                    width: 100% !important;
                    min-height: 240px;
                    table-layout: fixed;
                    border-collapse: collapse;
                }
                .php_jsnao_warning_style .db td,
                .php_jsnao_warning_style .db th
                {
                    border: 1px solid #616467;
                    padding: 0.3em 1em;
                }
                .php_jsnao_warning_style .db th
                {
                    background: #4a4d50;
                    color:white;
                    padding-top: 1em;
                    padding-bottom: 1em;
                }
                .php_jsnao_warning_style .db tbody tr
                {
                    background: rgb(250, 243, 253);
                    color: #424242;
                }
                .php_jsnao_warning_style .db td .kjjsi_z77100_01
                {
                    max-height:90px;
                    min-height:50px;
                    overflow:hidden;
                    font-size: 16px;
                }

                </style>
            ";
        }

        if (self::$debug_style == "fixed") {
            echo

            "
                <style>
                .php_jsnao_warning_style
                {
                    position: fixed;
                    top: 0px;
                    left: 0px;
                    right: 0px;
                    opacity: 0.1;
                    transition: 0.2s all;
                }
                .php_jsnao_warning_style:hover
                {
                    opacity: 1;
                }
                </style>
            ";
        }

        echo

        "
            <div class='php_jsnao_warning_style'>

                <div class='sql'>{$msg}</div>

                <table class='db'>
                    <thead>
                        {$thead_foot}
                    </thead>
                    <tbody>
                        {$tbody}
                    </tbody>
                    <tfoot>
                        {$thead_foot}
                    </tfoot>
                </table>

            </div>
        ";
        if ($continue_stop == "stop") {
            die;
        }

    }
}