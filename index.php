<?php

/**
 *
 *
 * 大众点评采集地址
 *
 *
 *
 */


require 'QueryList/vendor/autoload.php';
use QL\QueryList;

$city_info = include ('citys.php');

set_time_limit(0);
$city_id    = 0;
while($city_id<=2000){
    //地址
    $city_id = $city_id + 1;
    $url    = 'http://www.dianping.com/search/category/' . $city_id . '/10';
    $city_data_file         = './city_data/' . $city_id . '.txt';

    if (!file_exists($city_data_file)) {
        $i = 1;
        //$content = get_html($url);
        //每个地址最多采集 10 页一页为：15
        while ($i < 21) {
	        $url_cur    = $url . '/p' . $i;

            $i = $i + 1;
            $content = get_html($url_cur);
            $main_city_name = QueryList::Query($content,array(
                'main_city_name' => array('a.J-city','text')
            ))->getData(function($item){
                return $item['main_city_name'];
            });
			if (empty( $main_city_name)){
				echo "no city name break \n";
				break;
			}
            $city_name = $main_city_name[0] ;
            if (isset($city_info["{$city_name}"])) {
                touch($city_data_file);
                $city_data_name_file    = './city_data/' . iconv('utf-8', 'gbk', $city_name) . '.data';
                $fp = fopen($city_data_name_file,'a+');
                //如果在地址表里有信息就取出下来
                //$address_prefix = $city_info["{$city_name}"] ."," . $city_name. '市';
                $address_list   = QueryList::Query($content,array(
                    'tag' => array('div.tag-addr > a:nth-child(3)','text'),
                    'addr' => array('div.tag-addr > span.addr','text'),
                ))->getData(function($item){
                    return $item;
                });
                $string = "";
                /*
                $address_list = array(
                   array( 
                        'tag' => '奉贤市',
                        'addr' => '南桥镇杭州湾综合市场15号楼109号'
                        ),
                );*/

                if (!empty($address_list)) {
                    foreach ($address_list as $val) {
                        $string    .=  deal_address($city_info["{$city_name}"], $city_name,$val);
                        //$string .= $address_prefix . "," . $val . "\r\n";
                    }
                    if (flock($fp,LOCK_EX)) {
                        fwrite($fp,$string);
                        flock($fp,LOCK_UN);
                        fclose($fp);
                    }
                    echo "url:{$url_cur} \n";
                    echo 'by '.date('Y-m-d H:i:s') . ' '. iconv('utf-8', 'gbk', $city_name) . " ok \n\n";
                }
            } else {
				$ffp = fopen('dataa.txt','a+');
				if (flock($ffp,LOCK_EX)) {
					fwrite($ffp,$city_name . "\n");
					flock($ffp,LOCK_UN);
					fclose($ffp);
				}
				echo "no city for city data file break \n";
                //没有就跳出
                break;
            }
        }

    }

}



/**
 *
 * @param $pro      string 省份
 * @param $city     string 城市
 * @param $address  array  array('地区'，'地址') 
 * @return string
 *
 */
function deal_address($pro="",$city="",$address=array('tag'=>'','addr'=>'')) {
    //设置地区
    if ($city=='' || $city == '地区') {
        $city = "地区";
    }
	$pro= str_replace('市', '', $pro);
    //替换没用的的文字
    $city= str_replace('其他', '', $city);
    $city= str_replace('中心城区', '', $city);
    
	
	//市处理
    if (strpos($city,'区') ||  strpos($city,'县') || strpos($city,'旗')) {
       $city = $city;       
    } else {
       $city = $city . '市';
    }

	//区处理
    if ((strpos($address['tag'],'区') ||  strpos( $address['tag'],'县') || strpos( $address['tag'],'旗')) ) {
       $address['tag'] = str_replace('其他', '',$address['tag']); 
	   $address['tag'] = str_replace('中心城区', '',$address['tag']); 
	   if (strpos( $address['tag'],'/')) {
			 $address['addr']	= $address['tag'] . $address['addr'];
			 $address['tag']	= '地区';   
		}
    } else {
        $address['tag'] = '地区';
    }

    return  $pro . ',' . $city . ',' . $address['tag'] . ',' . $address['addr'] . "\r\n";
}




/**
 *
 * @param $url
 * @return mixed
 *
 */

function get_html($url) {
    $ch = curl_init();
    $cookie = '_hc.v=3ff452a4-9c89-c608-0fb4-374b02d571d9.1476939792; __utma=205923334.435740626.1477025607.1477025607.1477028094.2; __utmb=205923334.2.10.1477028094; __utmc=205923334; __utmz=205923334.1477025607.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); JSESSIONID=D081D2C86D28975C88E20D1FEA0391E8; cy=1; cye=shanghai';
    $header = array ();
    $header [] = 'Host:www.dianping.com';
    $header [] = 'Connection: keep-alive';

    $header [] = 'Cookie:' . $cookie;
    $header [] = 'Content-Type:application/x-www-form-urlencoded';
    $header [] = 'X-Requested-With:XMLHttpRequest';
    $header [] = 'User-Agent:Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)';
    //应该有做源头设置
    $header [] = 'Origin:www.dianping.com';

    //curl_setopt($ch,CURLOPT_PROXY,'127.0.0.1:8888');//设置代理服务器
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);//若PHP编译时不带openssl则需要此行
    //curl_setopt($ch,CURLOPT_REFERER,'www.dianping.com');
    curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
    //curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);

    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}











