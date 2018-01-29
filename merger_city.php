<?php
/**
 *
 *
 * 合并所采集到的城市
 *
 *
 */
merger_city();
function merger_city($data_dir = './city_data',$new_data= './city_data/all.csv'){
    static $city_list   = array();
    static $city_keys   = array();
    static $total       = 0;
    if (empty($city_list)) {
        $city_list  = include_once('citys.php');
        $city_keys  = array_keys($city_list);
        $total      = count($city_list) - 1;
    }
    $i = 0;
    $icount = 1;	
	$all_data = scandir($data_dir);
	$file_content = '';
    foreach($all_data as $val) {
		if ($val!='..' && $val!='.') {
			echo $val;
			$address    = './city_data/' . $val;
			$address_content    = file_get_contents($address);
			$file_content	   .= $address_content;
		}
    }
	$fp			= fopen($new_data,'w');
	if (flock($fp,LOCK_EX)) {
		fwrite($fp,$file_content);
		flock($fp,LOCK_UN);
	}
	fclose($fp);

}
