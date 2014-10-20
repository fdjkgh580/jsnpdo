<pre>
<?

/*
	<< PHP 提供的PDO用法 >>
	
	每使用一次fetchAll() 或 fetch()之前，一定要先query()一次。
	有時候我們需要在同一頁輸出兩次資料，若連續使用兩次的 fetchAll()，那麼第二次將會得到空陣列，切記！
	以下做個示範.....
	
	
	fetch()	一次取單筆資料，取完後指標會指向下一筆資料。可搭配while()一筆一筆取取到結束。
	fetchAll()比較好用，一次取出所有陣列。直接用foreach ()搭配就可以了。
	fetch()與fatchAll()
		預設參數是PDO::FETCH_BOTH，可不寫(同時取得陣列key的編號與SQL欄位名稱，我習慣用這個)
		另有	PDO::FETCH_ASSOC () 只取得欄位名稱，還有其他參數可用，參考 http://php.net/manual/en/pdostatement.fetch.php
		
*/
$pdo = new PDO("mysql:host=localhost;dbname=jsntest", 'root', '1234');
$q = $pdo->query("set names utf8");

/*--------------簡單範例 開始-----------------*/
	$SQL = "select * from jsntable limit 2";
	
	$q = $pdo->query($SQL);
	$list = $q->fetchAll(); //取得所有陣列
	echo "第一次:<br>";
	print_r($list); 
	/*
	輸出結果: 
	Array
	(
		[0] => Array
			(
				[id] => 1
				[0] => 1
				[title] => 蘋果
				[1] => 蘋果
				[content] => AAA
				[2] => AAA
			)
	
		[1] => Array
			(
				[id] => 2
				[0] => 2
				[title] => 蔬菜
				[1] => 蔬菜
				[content] => BBB
				[2] => BBB
			)
	
	)
	*/
	
	$q = $pdo->query($SQL); //注意！ 這裡務必再執行一次query()的動作，可以嘗試把這行註解起來，那麼下面fetch時將會得到空陣列
	$list = $q->fetchAll();
	echo "第二次:<br>";
	print_r($list);
	/*
	有使用query()輸出結果：
	Array
	(
		[0] => Array
			(
				[id] => 1
				[0] => 1
				[title] => 蘋果
				[1] => 蘋果
				[content] => AAA
				[2] => AAA
			)
	
		[1] => Array
			(
				[id] => 2
				[0] => 2
				[title] => 蔬菜
				[1] => 蔬菜
				[content] => BBB
				[2] => BBB
			)
	
	)
	未再次使用query()輸出結果：
	Array
	(
	)	
	*/
	
/*--------------簡單範例 結束-----------------*/




/*--------------進階範例 開始-----------------*/
	$title	=	$pdo->quote("蘋果"); //避免SQL Injection
	$SQL	=	"select * from jsntable where title = $title";
	$res	=	$pdo->query($SQL);
	$list	=	$res->fetchAll(PDO::FETCH_ASSOC); 
	echo "進階範例:<br>";
	foreach ($list as $row) {
		echo "標題 -- {$row[title]}";
		}
	/*
	輸出結果：
	標題 -- 蘋果
	*/	
/*--------------進階範例 結束-----------------*/
?>
</pre>

