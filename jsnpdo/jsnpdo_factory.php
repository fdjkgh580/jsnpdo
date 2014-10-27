<?php
/**
 * 工廠模型運作。
 * 可以呼叫 Jsnpdo，
 * 並讓資料表名稱轉為類別，方便快速操作。
 * 
 */
class Jsnpdo_factory 
{
    //記錄是誰用靜態方式做呼叫, 例如 jsntable::iary() 代表資料表名稱叫做 jsntable
    public static $table_name;

    //紀錄 $column[欄位名稱] = 欄位值; 通常用在新增修改時。
    protected static $column = array();

    // 若使用工廠模型，就填寫要建置的php script 檔案路徑
    public static $path;

    //資料表名稱對應物件的陣列
    public static $table_object_map = array();

    //資料庫命名倉儲
    public static $resp = array();

    // 資料庫連接成功的儲存位置 array("_自訂資料庫名" => PDO資源)
    public static $dbh = array();

    /**
     * 切換資料庫
     * @param   $db   切換存放在倉儲中的資料庫命名。可以使用任何的單字。
     */
    public static function switch_db($db)
    {
        $pdost = self::$dbh[$db];

        if (empty($pdost)) throw new Exception("找不到可使用的 PDO 連接資源名稱：{$db}, 請先使用 Jsnpdo_factory::connect()->db_house()");
        
        jsnpdo::$PDO = self::$dbh[$db];
    }


    //工廠建置路徑
    //路徑檔案需事先建立。並開啟寫入功能。
    public static function path($build_path)
    {
        self::$path = $build_path;
    }

    //掛載工廠執行檔
    public static function autoload_script_file()
    {

        $path = self::$path;

        if (!file_exists($path))
        {
            throw new Exception("檔案不存在：$path ");
        }

        //讓工廠初始化模型並寫入到檔案
        self::table_to_class_to_file();

        include_once($path);
    }

    // 動態執行物件程式碼
    public static function autoload_eval($name)
    {

        $tbname = trim($name);

        // 若存在地圖裡，將使用別名
        $tbname = self::table_class_name_get($tbname);

        $script = " class $tbname extends Jsnpdo_factory {} ";

        eval($script);
    }

    // 取得資料表的名稱或別名，有指定別名，別名優先
    protected static function table_class_name_get($table_name)
    {
        $map = self::$table_object_map;

        if (array_key_exists($table_name, $map) === true)
        {
            $table_name = $map[$table_name];
        }

        return $table_name;
    }

    // 使用資料表物件的別名取得原名
    protected static function table_name_org($name)
    {
        foreach (self::$table_object_map as $org => $custom_alias)
        {
            if ($name == $custom_alias) return $org;
        }
        return $name;
    }

    /**
     * 決定工廠要使用 虛擬 或 實體 產生資料表物件的方式。
     * 
     * @param   $type     使用虛擬工廠 virtual 或 實體工廠 physical 
     *                    建議使用虛擬工廠，在增加資料表的時候，彈性非常的高。
     *                    實體工廠則在初始化、建立或刪除資料表時才會被建置。
     *                    但是要能使用 eval() 涵式。
     *                    
     * @return            返回實體物件
     */
    public static function build_virtual_physical($type)
    {
        if ($type == "virtual")
        {
            $fun = "autoload_eval";
        }
        elseif ($type == "physical")
        {
            $fun = "autoload_script_file";
        }
        else
        {
            throw new Exception("務必指定工廠的建制方式為虛擬 virtual 或 實體 physical");
        }

        spl_autoload_register(array("Jsnpdo_factory", $fun));

        return new Jsnpdo_factory;
    }

    // 自訂資料表物件的別名。
    // 如 array("jsntable_2" => "jsntable_second") 代表資料表 jsntable_2 的別名是 jsntable_second
    // 對其操作時，將使用別名而不可使用原名。如 jsntable_second::iary()
    public static function map(array $ary)
    {
        foreach ($ary as $key => $val)
        {
            if (!class_exists($val)) continue;

            throw new Exception("資料表 {$key} 指定的別名 {$val} 無法作為物件，因為物件名稱已存在。", 1);
        }

        self::$table_object_map = array_filter($ary);
    }


    /**
     * 操作SQL語言，並重建工廠模型。
     * 因為會重建工廠模型，所以建議用在新增或刪除資料表
     * 
     * @param   $sql            SQL語言
     * @param   $status_debug   除錯模式
     * @return                  若執行PHP指令將返回 false , 若載入工廠模型檔案, 返回PDO資源物件
     */
    public static function query_factory($sql, $status_debug)
    {
        $query = Jsnpdo::query($sql, $status_debug);

        self::table_to_class_to_file();

        //還原sql文字, 避免被 factory() 影響
        Jsnpdo::$sql = $sql;

        return $query;
    }

    // 調出資料庫所有資料表，並產生資料表模型檔
    public static function table_to_class_to_file()
    {
        //所有資料表
        $result         =   Jsnpdo::query("show tables");
        $DataList       =   $result->fetchAll(PDO::FETCH_ASSOC);
        $table_ary      =   self::get_table_ary($DataList);
        self::factory($table_ary);
    }


    /**
     * 產出資料表模型
     * @param   $table_ary 資料表名稱如 array("member", "news")
     * 
     */
    protected static function factory(array $table_ary)
    {
        foreach ($table_ary as $tb)
        {
            $factory_string .= "class {$tb} extends Jsnpdo_factory {} \r\n";
        }

        //產生對應資料表存入文件
        $phpscript = '<?php '. "\r\n" . $factory_string . '?>';

        file_put_contents("db_model.php", $phpscript);
    }

    /**
     * 當前資料庫的所有資料表名稱
     * @param   $DataList   資料庫的列表
     * @return              資料表名稱陣列
     */
    protected static function get_table_ary($DataList)
    {
        if (count($DataList) > 0) foreach ($DataList as $DataInfo)
        {
            //取得表格名稱
            foreach ($DataInfo as $for_class_name)
            {
                //要使用別名? 有就使用
                $org_alias = self::table_class_name_get($for_class_name);

                $table_ary[] = $org_alias;

                break;
            }
        }
        return $table_ary;
    }

    /**
     * 資料表使用新增或刪除時，填入欄位的陣列。值不必添加跳脫。
     * @param   $coln       欄位名稱
     * @param   $val        欄位值。
     * @param   $raw_data   保護字串？如使用 mysql 函數 now() 時需要指定為 true
     */
    public static function ary($coln, $val, $protection_data = true)
    {
        $val                 = ($protection_data == true) ? jsnpdo::quo($val) : $val;

        self::$column[$coln] = $val;
    }

    public static function __callStatic($name, array $arguments)
    {
        //是where 使用的條件替代嗎? 例如當呼叫了 jsntable::_id(1);
        if (substr($name, 0, 1) == "_")
        {
            Jsnpdo::$name($arguments[0]);

            return true;
        }

        //是SQL方法的話...
        return self::sql_method($name, $arguments);
    }

    /**
     * 把當前連接資料庫的PDO資源，放入自訂名稱的倉儲
     * @param   $database_name     名稱
     */
    public static function db_house($database_name)
    {
        $befclass = $database_name;

        self::$dbh[$befclass] = Jsnpdo::$PDO;

        self::$resp[] = $befclass;
    }




    //SQL 溝通方法
    protected static function sql_method($name, $arguments)
    {

        //get_called_class() 可以取得是誰用靜態方式做呼叫
        self::$table_name = get_called_class();
        
        //檢查是否在別名內, 若是在別名，將使用原名來做SQL命令
        self::$table_name = self::table_name_org(self::$table_name);

        //連接資料庫
        if ($name == "connect")
        {
            self::$column = array();

            Jsnpdo::connect($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
            
            return new Jsnpdo_factory;
        }

        //新增
        elseif ($name == "iary" or $name == "insert")
        {

            return Jsnpdo::iary(self::$table_name, self::$column, $arguments[0], $arguments[1]);
        }

        //修改
        elseif ($name == "uary" or $name == "update")
        {
            return Jsnpdo::uary(self::$table_name, self::$column, $arguments[0], $arguments[1], $arguments[2]);
        }

        //查詢
        elseif ($name == "sel" or $name == "select")
        {

            $mixobj = self::select_colums_arement($arguments);

            $DataList = Jsnpdo::sel($mixobj->column, self::$table_name, $mixobj->else, $mixobj->status_debug);
            
            return $DataList;
        }

        //查詢單筆
        elseif ($name == "selone" or $name == "select_one")
        {
            $mixobj = self::select_colums_arement($arguments);
            
            $DataInfo = Jsnpdo::selone($mixobj->column, self::$table_name, $mixobj->else, $mixobj->status_debug);
           
            return $DataInfo;
        }

        //查詢總數量
        elseif ($name == "select_num")
        {
            return Jsnpdo::$select_num;
        }

        //刪除
        elseif ($name == "delete")
        {
            return Jsnpdo::delete(self::$table_name, $arguments[0], $arguments[1]);
        }

        //清空
        elseif ($name == "truncate")
        {
            return Jsnpdo::truncate(self::$table_name, $arguments[0]);
        }

        // in 輔助
        elseif ($name == "in")
        {
            return Jsnpdo::in($name, $arguments[0]);
        }

        //快取
        elseif ($name == "cache")
        {
            return Jsnpdo::cache($arguments[0]);
        }

        //快取存放位置
        elseif ($name == "cache_path")
        {
            return Jsnpdo::cache_path($arguments[0]);
        }
        
        //取得快取狀態
        elseif ($name == "cache_set_get")
        {
            return Jsnpdo::cache_set_get();
        }

        //快取存活時間
        elseif ($name == "cache_life")
        {
            return Jsnpdo::$cache_life = $arguments[0];
        }

        //取得快取鍵
        elseif ($name == "cache_key_get")
        {
            return Jsnpdo::cache_key_get();
        }

        //取得快取內容
        elseif ($name == "cache_get")
        {
            return Jsnpdo::cache_get($arguments[0]);
        }

        elseif ($name == "cache_clean")
        {
            return Jsnpdo::cache_clean($arguments[0]);
        }

        //建立或刪除資料表
        elseif ($name == "create" or $name == "drop")
        {
            return self::query_factory($arguments[0], $arguments[1]);
        }

        // quote, 自動添加 '' 並將危險字元跳脫。使用PDO prepare 不建議直接指定。
        //僅做 debug 的添加
        elseif ($name == "quo" or $name == "quote")
        {
            return Jsnpdo::quo($arguments[0]);
        }

        //設定返回執行字串
        elseif ($name == "get_string")
        {
            return Jsnpdo::$get_string = $arguments[0];
        }

        //執行SQL
        elseif ($name == "query")
        {
            return Jsnpdo::query($arguments[0], $arguments[1]);
        }

        //多筆執行
        elseif ($name == "with")
        {
            return Jsnpdo::with($arguments[0], $arguments[1]);
        }

        //調閱執行SQL的字串
        elseif ($name == "sql")
        {
            return Jsnpdo::$sql;
        }

        //設定debug
        elseif ($name == "debug")
        {
            return jsnpdo::debug($arguments[0]);
        }

        //debug 樣式
        elseif ($name == "debug_style")
        {
            return Jsnpdo::debug_style($arguments[0]);
        }

        else
        {
            throw new Exception("找不到可以對應的方法： {$name}");
        }
    }


    /**
     * 匹配查詢的參數位置，用以取得查詢的欄位、其他條件、除錯模式
     * @param   $arguments 接收的參數陣列
     * @return             反回物件
     */
    protected static function select_colums_arement($arguments)
    {
        //ex. sel("where 1 = 1")
        if (count($arguments) == 1)
        {
            $column       = "*";
            $else         = $arguments[0];
            $status_debug = NULL;
        }
        elseif (count($arguments) == 2)
        {
            //ex. sel("where 1 = 1", 1)
            if ($arguments[1] == 1 or $arguments[1] == "str" or $arguments[1] == "chk")
            {
                $column       = "*";
                $else         = $arguments[0];
                $status_debug = $arguments[1];
            }

            //ex. sel("id, title", "where 1 = 1")
            else
            {
                $column       = $arguments[0];
                $else         = $arguments[1];
                $status_debug = NULL;
            }
            
        }
        else
        {
            //ex. sel("id, title", "where 1 = 1", 1)
            $column       = $arguments[0];
            $else         = $arguments[1];
            $status_debug = $arguments[2];
        }

        $obj->column       = $column;
        $obj->else         = $else;
        $obj->status_debug = $status_debug;

        return $obj;
    }



}
?>