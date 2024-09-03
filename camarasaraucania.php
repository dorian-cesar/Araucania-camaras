<?php
//include "./login/login-lascondes.php";
// include "login/conexion.php";

//require_once './login/login-lascondes.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");





$user="AraucaniaSur";

$pasw="123";

include "conexion.php";

$consulta="SELECT hash FROM masgps.hash where user='$user' and pasw='$pasw'";

$resutaldo= mysqli_query($mysqli,$consulta);

$data=mysqli_fetch_array($resutaldo);

$cap=$data['hash'];


//header("refresh:2");
$listado = '';
$i=0;

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://www.trackermasgps.com/api-v2/tracker/list',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{"hash": "'.$cap.'"}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);

curl_close($curl);




$json = json_decode($response);

$array = $json->list;


//echo '[';
foreach ($array as $item) {

 
   $id = $item->id;
   $patente=$item->label;
   $id_source=$item->source->id;
   $imei =$item->source->device_id;


   $curl = curl_init();
   
   curl_setopt_array($curl, array(
     CURLOPT_URL => 'http://www.trackermasgps.com/api-v2/tracker/multimedia/video/live_stream/create',
     CURLOPT_RETURNTRANSFER => true,
     CURLOPT_ENCODING => '',
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 0,
     CURLOPT_FOLLOWLOCATION => true,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => 'POST',
     CURLOPT_POSTFIELDS =>'{"tracker_id":'.$id.',"cameras":["front_camera","inward_camera"],"hash":"'.$cap.'"}',
     CURLOPT_HTTPHEADER => array(
       'Accept: application/json, text/plain, */*',
       'Accept-Language: es-US,es-419;q=0.9,es;q=0.8',
       'Connection: keep-alive',
       'Content-Type: application/json',
       'Cookie: locale=es; _ga=GA1.2.1422351035.1702561449; _gid=GA1.2.1320991348.1702561449; session_key=838dd2ac3bc781a07abc8b9c74351380; check_audit=838dd2ac3bc781a07abc8b9c74351380; _ga_XXFQ02HEZ2=GS1.2.1702565553.2.1.1702565644.0.0.0',
       'Origin: http://www.trackermasgps.com',
       'Referer: http://www.trackermasgps.com/',
       'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'
     ),
   ));
   
   $response = curl_exec($curl);
   
   curl_close($curl);

   $arreglo=json_decode($response);

   if(isset($arreglo->video_streams[1]->link)){

    $front_camera=$arreglo->video_streams[1]->link;
    $inside_camera=$arreglo->video_streams[0]->link;



    

    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'http://www.trackermasgps.com/api-v2/tracker/get_state',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS =>'{"hash": "'.$cap.'", "tracker_id": '.$id.'}',
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json'
      ),
    ));
    
    $response2 = curl_exec($curl);
    
    $arreglo2= json_decode( $response2);
    
    curl_close($curl);
    
    $estado=$arreglo2->state->connection_status;
    
    

      $json =array (

        'patente'=>$patente,
        'front'=>$front_camera,
        'inside'=>$inside_camera,
        'estado'=>$estado
    
           
      );
      $total[$i]=$json;

      $i++;


    
    




   }
   
   
}

echo json_encode(["list"=>$total], http_response_code(200));