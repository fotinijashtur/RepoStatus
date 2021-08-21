<?
// exit();
$start = microtime(true);
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/ext_www/vgrd.ru';
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/sdsdds.php");
 // header("Content-Type: text/html; charset=utf-8");
 // 
$timeStart = file_get_contents($_SERVER['DOCUMENT_ROOT']."/upload/sdf.txt");
if($timeStart && $timeStart < time() - 60*60*2){
	//скрипт работает долго
}elseif($timeStart){
	echo "1\n";
    exit();
}

$fp = fopen($_SERVER['DOCUMENT_ROOT']."/upload/sdf.txt", "w");
if (flock($fp, LOCK_EX)) { 
    fwrite($fp, time());
    flock($fp, LOCK_UN);
} else {
    echo "File lock";
    exit();
}

fclose($fp);
?>
<?CModule::IncludeModule("iblock");
CModule::IncludeModule("highloadblock");



$arHLBlockProv = Bitrix\Highloadblock\HighloadBlockTable::getById(52)->fetch();
$obEntityProv = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arHLBlockProv);
$strEntityDataClassProv = $obEntityProv->getDataClass();


// $arHLBlock = \Bitrix\Highloadblock\HighloadBlockTable::getById(66)->fetch();
// $obEntity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arHLBlock);
// $strEntityDataClass = $obEntity->getDataClass();
echo  '<pre>';
$numPost = 0;
while (true) {
    $numPost++;

    $res = $strEntityDataClassProv::getList(array(
         'select' => ['UF_LINK', 'UF_ID_PRODUCT', 'ID', 'UF_DATA_CHECK'],
         'order' => array('ID' => 'ASC'),
         'filter' => [
            '!UF_ID_PRODUCT' => false, 
            '>UF_ID_PRODUCT' => 2309916,
            '<UF_DATA_CHECK' => date('d.m.Y H:i:s', time() - 60*60*2),
            // 'UF_DATA_CHECK' => false,
            'UF_STATUS' => '',
            // 'ID' => 948736 
        ],
         // 'filter' => ['ID' => 65454],
         // 'filter' => ['UF_COMMENT' => 'NO One of the parameters specified was missing or invalid: owner_id not integer'],
         'limit' => 200
    ));
    $a = 0;
    $arIds = [];
    $arPosts = [];
    while ($arItem = $res->fetch()) {

        // $arrAll[$arItem['ID']] = $arItem;
        // print_r($arItem);
        // continue;
        $arQuery = parse_url($arItem['UF_LINK'], PHP_URL_QUERY);
        parse_str($arQuery, $output);

        $param = str_replace('wall', '', $output['w']);

        // echo $param;

        // print_r($arParam);
        // print_r($output);
        // echo '<hr>';
        $arIds[] = $param;

        $arPosts[$param] = $arItem;
        // $arItem['UF_LINK'] = 'https://m.vk.com/dnshop2g58';

        // $arDomain = explode('/', $arItem['UF_LINK']);
        // $domain = end($arDomain);

    }
    if(!$arIds){

        break;
    }
    // print_r($arIds);
    // 6156615,525001837_608,-178248951_1
    $arIdsChank = array_chunk($arIds, 100);
    // print_r($arIdsChank);
    $params['code'] = 'var result = [];'."\n";
    $params['code'] .= 'var a = "";'."\n";
    $params['code'] .= 'var arr = [];'."\n";
    $params['code'] .= 'var num = 0;'."\n";
    $params['code'] .= 'var num_a = 0;'."\n";
    $params['code'] .= 'var i = 0;';
    $params['code'] .= 'var i_a = 0;';
    $params['code'] .= 'var str = "";';
    $params['code'] .= 'var arr_a = [];'."\n";

    foreach ($arIdsChank as $key => $value) {
        $params['code'] .= 'a = "";'."\n";
        $params['code'] .= 'arr = [];'."\n";
        $params['code'] .= 'num = 0;'."\n";
        $params['code'] .= 'num_a = 0;'."\n";
        $params['code'] .= 'i = 0;'."\n";
        $params['code'] .= 'i_a = 0;'."\n";
        $params['code'] .= 'arr_a = [];'."\n";
        $params['code'] .= 'str = "'.implode(',', $value).'";'."\n";
        $params['code'] .= 'arr = str.split(",");'."\n";
        $params['code'] .= 'a = API.wall.getById({"posts": str});'."\n";
        $params['code'] .= 'num = arr.length;'."\n";
        $params['code'] .= 'num_a = a.length;'."\n";
        $params['code'] .= '
        while(i_a < num_a){
            arr_a.push(a[i_a].from_id + "_" + a[i_a].id);
            i_a = i_a + 1;

        }
        while(i < num ){
            if(arr_a.indexOf(arr[i]) == -1){
                result.push(arr[i]);
            }

            i = i + 1;
        };'."\n";
    }
    $params['code'] .= 'return [result, "ok"];';
    // print_r($params)

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.vk.com/method/execute',
        CURLOPT_POST => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => [
            'code' => $params['code'],
            'access_token' => $access_token,
            'v' => '5.102'
        ]
    ]);
    $response = curl_exec($curl);
    $out = $response;
    // print_r($response);
    $response = json_decode($response, true);
    // print_r($response['response']);

    // foreach ($response['response'] as $key => $value) {
    //     print_r($arPosts[$value]);

    //     // $strEntityDataClassProv::update($arPosts[$value]['ID'], ['UF_STATUS' => 'DEL', 'UF_DATE_CHECK' => date('d.m.Y H:i:s')]);
    // }
    if(!$response['response']){
        print_r($out);
        print_r($response);
        echo 'Time work: '.round(microtime(true) - $start, 4).' s '.$numPost."\n";
        break;
    }


    foreach ($arPosts as $key => $value) {
        if(in_array($key, $response['response'][0])){
            $arFields = ['UF_STATUS' => 'DEL', 'UF_DATA_CHECK' => date('d.m.Y H:i:s')];
        }else{
            $arFields = ['UF_DATA_CHECK' => date('d.m.Y H:i:s')];
        }
        // print_r($key);
        // print_r($value);
        // print_r($arFields);
        // echo '<hr>';
        $strEntityDataClassProv::update($value['ID'], $arFields);
    }

    // usleep(400000);
    echo $numPost."\n";
    // break;
}
// очищаем файл блокировки
$fp = fopen($_SERVER['DOCUMENT_ROOT']."/upload/lockGetStatusPostVk.txt", "w");
fclose($fp);
echo 'Time work: '.round(microtime(true) - $start, 4).' s '.$numPost."\n";

require '/home/bitrix/ext_www/optid.ru/local/scripts/parserSadovod/deleteProduct.php';

require '/home/bitrix/ext_www/optid.ru/local/scripts/parserSadovod/statTodayDelete.php';

?>
<!-- <meta http-equiv="refresh" content="1; URL=https://optid.ru/local/scripts/parserSadovod/getStatusPostVk.php?i=<?=++$_REQUEST['i']?>">  -->
