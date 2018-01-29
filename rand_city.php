<?php
	$data_file	= './city_data/all.csv';
	$all_file	= file($data_file);
	//echo iconv('utf-8','gbk',$all_file[10]);
	shuffle($all_file);
	$string = "";
	foreach($all_file as $val){
		echo $val;
		$string = $string . $val ;
	}

	$fp		= fopen($data_file,'w');
	if (flock($fp,LOCK_EX)) {
		fwrite($fp,$string);
		flock($fp,LOCK_UN);
	}
	fclose($fp);
	unset($all_file);