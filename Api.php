<?php
class Api{
    public $url;
    public $entorno;
    public $version;
    public $repositorio;
    public $codigo_de_aplicacion;
    public $habilitarLog;
    public $nombreLog;
    private $usuario = '****';
    private $password = '**';
    function __construct($entorno = false, $codigo_de_aplicacion = '', $version = '', $repositorio = ''){
        $this->version = $version;
        $this->repositorio = $repositorio;
        $this->codigo_de_aplicacion = $codigo_de_aplicacion;
        $this->entorno = ($entorno) ? "production" : "development";
        $this->url = "https://kpionline10.bitam.com/$version/api/v1/$repositorio/";
        $this->habilitarLog = false;
        $this->nombreLog = "";
    }
    public function obtenerDatosEbio($formulario, $campos = [], $filtro = "", $ordenar = "", $agrupar = []){
        $url = $this->url.'data/'.$formulario;
        $url .= '?$select='.implode(",", $campos).'&$filter='.urlencode($filtro).'&$orderby='.urlencode($ordenar).'&$groupby='.implode(",", $agrupar);
        $response = $this->mandarPeticion($url, 'GET');
        if($response[0]){
            return $response[1];
        }else{
            return false;
        }
    }
    public function subirFoto($formulario, $archivo){
        $url = $this->url.$this->codigo_de_aplicacion.'/actions/uploadphoto?sectionname='.$formulario;
        $response = $this->mandarPeticion($url, 'POST', array(), $archivo);
        if($response[0]){
            return $response[1];
        }else{
            return false;
        }
    }
    public function subirArchivo($formulario, $archivo, $campo, $id){
        $url = $this->url.$this->codigo_de_aplicacion.'/actions/uploaddocument?sectionname='.$formulario.'&rowid='.$id.'&fieldid='.$campo;
        $response = $this->mandarPeticion($url, 'POST', array(), $archivo);
        if($response[0]){
            return $response[1];
        }else{
            return false;
        }
    }
    public function eliminarDatos($formulario, $id){
        $url = $this->url.'data/'.$formulario;
        if(is_array($id)){
            $url .= '/remove';
            $response = $this->mandarPeticion($url, 'POST', $id);
        }else{
            $url .= '/'.$id;
            $response = $this->mandarPeticion($url, 'DELETE');
        }
        if($response[0]){
            return $response[1];
        }else{
            return false;
        }
    }
    public function enviarDatos($formulario, $camposValores, $idCampo = -1){
        $data = array();
        $data['applicationCodeName'] = $this->codigo_de_aplicacion;
        $data['environment'] = $this->entorno;
        $data['data'] = array();
        $data['data'][$formulario] = array();
        $data['data'][$formulario][1] = array();
        $data['data'][$formulario][1]["id_$formulario"] = $idCampo;
        foreach($camposValores as $campoValor){
            $data['data'][$formulario][1][$campoValor[0]] = $campoValor[1];
        }
        $url = $this->url.'data';
        $response = $this->mandarPeticion($url, 'POST', $data);
        if($response[0]){
            return $response[1];
        }else{
            return false;
        }
    }
    public function obtenerDefiniciones($formulario, $tipo = true){
        $url = $this->url.'definitions';
        if(!$tipo){
            $url .= '/'.$formulario.'/formslist';
        }else{
            $url .= '/'.$formulario.'/fieldslist';
        }
        $response = $this->mandarPeticion($url, 'GET');
        if($response[0]){
            return $response[1];
        }else{
            return false;
        }
    }
    public function ejecutarBatch($id){
        $url = $this->url.'eBavel/actions/executeBatchProcess/'.$this->repositorio;
        $postData = array();
        $postData['applicationCodeName'] = $this->codigo_de_aplicacion;
        $postData['environment'] = $this->entorno;
        $postData['id_batch'] = $id;
        $response = $this->mandarPeticion($url, 'POST', $postData);
        if($response[0]){
            return $response[1];
        }else{
            return false;
        }
    }
    private function mandarPeticion($url, $metodo = "GET", $datos = array(), $archivo = ""){
        $archivoPost = false;
        if($metodo == "POST"){
            if(count($datos) > 0){//Si se envian datos por POST
                $post = json_encode($datos);
            }elseif($archivo != ""){//Si el archivo no esta vacio
                if(function_exists('curl_file_create')){// php 5.5+
                    $cFile = curl_file_create($archivo,'','');
                }else{
                    $cFile = '@' . realpath($archivo);
                }
                $post = array('qqfile' => $cFile);
                $archivoPost = true;
            }else{
                return array(false, 'Error en los datos POST');
            }
        }
        $response = '';
        $this->escribirLog('=======GENERANDO PETICION AL EBIO=======');
        $this->escribirLog('METODO: '.$metodo);
        $this->escribirLog('URL: '.$url);
        $this->escribirLog('DATOS: ');
        $this->escribirLog($datos);
        $this->escribirLog('JSON Datos: ');
        $this->escribirLog(@$post);
        $ch = curl_init();
        if($metodo == 'POST'){
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if($metodo == 'DELETE'){
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($metodo == 'POST'){
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $header = array();
        $header[] = 'PHP_AUTH_USER: '.$this->usuario;
        $header[] = 'PHP_AUTH_PW: '.$this->password;
        $header[] = 'ENVIRONMENT: '.$this->entorno;
        if(!$archivoPost){
            $header[] = 'Content-Type: application/json';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36');
        @curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        $response = curl_exec($ch);
        if($response === false){
            $this->escribirLog('CURL Request Error No='.curl_errno($ch));
            $this->escribirLog('CURL Request Error Msg='.curl_error($ch));
            return array(false, 'Failed to perform CURL request!');
        }
        if(!is_object(json_decode($response))){
            curl_close($ch);
            return array(false, 'Error, No es un formato JSON valido!');
        } 
        curl_close($ch);
        unset($ch);
        $responseData = json_decode($response, true);
        if(isset($responseData['error'])){
            if($responseData['error'] != 0){
                $this->escribirLog('EBavel Response errorSummary:'.@$responseData['errorSummary']);
                $this->escribirLog('EBavel Response errorDescription:'.@$responseData['errorDescription']);
                return array(false, 'EBavel Response Error!');
            }else{
                $this->escribirLog('EBavel Response:');
                $this->escribirLog($responseData);
                return array(true, $responseData);
            }
        }elseif(isset($responseData['success'])){
            if($responseData['success']){
                $this->escribirLog('EBavel Response:');
                $this->escribirLog($responseData);
                return array(true, $responseData);
            }else{
                $this->escribirLog('EBavel Response errorSummary:'.@$responseData['errorSummary']);
                $this->escribirLog('EBavel Response errorDescription:'.@$responseData['errorDescription']);
                return array(false, 'EBavel Response Error!');
            }
        }else{
            $htmlFile = date('YmdHis').'_EbavelErrorRequest.html';
            error_log($response, 3, $htmlFile);
            $this->escribirLog('EBavel Request error. HTML file response: '.$htmlFile);
            return array(false, 'EBavel Request Error!');
        }
    }
    private function escribirLog($mensaje){
        if($this->habilitarLog){
            $archivoLog = fopen($this->nombreLog, 'a');
            fwrite($archivoLog, date('Y-m-d H:i:s').' -> '.print_r($mensaje, true).PHP_EOL);
            fclose($archivoLog);
            
        }
    }
}
?>
