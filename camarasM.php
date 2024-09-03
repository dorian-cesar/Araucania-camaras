<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$user = "AraucaniaSur";
$pasw = "123";

include __DIR__."/conexion.php";

$consulta = "SELECT hash FROM masgps.hash WHERE user='$user' AND pasw='$pasw'";
$resultado = mysqli_query($mysqli, $consulta);
$data = mysqli_fetch_array($resultado);
$cap = $data['hash'];

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'http://www.trackermasgps.com/api-v2/tracker/list',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => json_encode(['hash' => $cap]),
    CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
));
$response = curl_exec($curl);
curl_close($curl);

$json = json_decode($response);
$array = $json->list;

$mh = curl_multi_init();
$handles = [];
$total = [];

foreach ($array as $item) {
    $id = $item->id;

    // Primer CURL para obtener el video stream
    $curl1 = curl_init();
    curl_setopt_array($curl1, array(
        CURLOPT_URL => 'http://www.trackermasgps.com/api-v2/tracker/multimedia/video/live_stream/create',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode([
            'tracker_id' => $id,
            'cameras' => ["front_camera", "inward_camera"],
            'hash' => $cap
        ]),
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    ));
    curl_multi_add_handle($mh, $curl1);
    $handles[$id]['curl1'] = $curl1;

    // Segundo CURL para obtener el estado
    $curl2 = curl_init();
    curl_setopt_array($curl2, array(
        CURLOPT_URL => 'http://www.trackermasgps.com/api-v2/tracker/get_state',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode(['hash' => $cap, 'tracker_id' => $id]),
        CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
    ));
    curl_multi_add_handle($mh, $curl2);
    $handles[$id]['curl2'] = $curl2;
}

// Ejecutar las solicitudes
$running = null;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

// Recopilar las respuestas
foreach ($array as $item) {
    $id = $item->id;
    $patente = $item->label;

    $response1 = curl_multi_getcontent($handles[$id]['curl1']);
    $response2 = curl_multi_getcontent($handles[$id]['curl2']);

    curl_multi_remove_handle($mh, $handles[$id]['curl1']);
    curl_multi_remove_handle($mh, $handles[$id]['curl2']);

    $arreglo1 = json_decode($response1);
    $arreglo2 = json_decode($response2);

    if (isset($arreglo1->video_streams[1]->link)) {
        $front_camera = $arreglo1->video_streams[1]->link;
        $inside_camera = $arreglo1->video_streams[0]->link;
        $estado = $arreglo2->state->connection_status;

        $total[] = [
            'patente' => $patente,
            'front' => $front_camera,
            'inside' => $inside_camera,
            'estado' => $estado
        ];
    }
}

// Cerrar multi handle
curl_multi_close($mh);

echo json_encode(["list" => $total], http_response_code(200));
?>
