<?php

class WSHelpers{

    /**
     * Build a response header
     *
     * @param $buffer
     *    data to be used in response header
     * @return
     *    response header
     */
    private $initFrame;
    private $masks;
    static function getResponseHeaders($buffer = '', $uniqueOrigin = FALSE){
        list($resource, $host, $origin, $strkey1, $strkey2, $data,$key) = self::getRequestHeaders($buffer);
        if(!self::validOrigin($origin, $uniqueOrigin)){
            self::console('Refusing connection from origin %s. Allowed origin(s): %s', array($origin, implode(', ', $uniqueOrigin)));
            return FALSE;
        }

        // find numbers
        $pattern = '/[^\d]*/';
        $replacement = '';
        $numkey1 = preg_replace($pattern, $replacement, $strkey1);
        $numkey2 = preg_replace($pattern, $replacement, $strkey2);

        // find spaces
        $pattern = '/[^ ]*/';
        $replacement = '';
        $spaces1 = strlen(preg_replace($pattern, $replacement, $strkey1));
        $spaces2 = strlen(preg_replace($pattern, $replacement, $strkey2));

       if ($spaces1 == 0 || $spaces2 == 0 || $numkey1 % $spaces1 != 0 || $numkey2 % $spaces2 != 0) {
				$hash_data = self::calcKey($key);
         }else{       
           $ctx = hash_init('md5');
           hash_update($ctx, pack("N", $numkey1 / $spaces1));
           hash_update($ctx, pack("N", $numkey2 / $spaces2));
           hash_update($ctx, $data);
           $hash_data = hash_final($ctx, TRUE);
		}
        return "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" .
               "Upgrade: WebSocket\r\n" .
               "Connection: Upgrade\r\n" .
               "Sec-WebSocket-Origin: " . $origin . "\r\n" .
               "Sec-WebSocket-Accept: ".$hash_data. "\r\n".
               "Sec-WebSocket-Location: ws://" . $host . $resource . "\r\n" .
               "\r\n";
    }

    static function validOrigin($origin, $uniqueOrigin){
        if(is_array($uniqueOrigin)) {
            return in_array($origin, $uniqueOrigin);
        }
        return TRUE;
    }

    /**
     * Get the header values from the received request
     *
     * @param $req
     *    The header request
     * @return
     *    an array containing the following values:
     *    resource, host, origin, key1, key2, data
     */
    static function getRequestHeaders($req){
        $r = $h = $o = $key1 = $key2 = $data = $key = null;
        if(preg_match("/GET (.*) HTTP/"   , $req, $match))              { $r=$match[1];    }
        if(preg_match("/Host: (.*)\r\n/"  , $req, $match))              { $h=$match[1];    }
        if(preg_match("/Origin: (.*)\r\n/", $req, $match))              { $o=$match[1];    }
        if(preg_match("/Sec-WebSocket-Key2: (.*)\r\n/", $req, $match))  { $key2=$match[1]; }
        if(preg_match("/Sec-WebSocket-Key1: (.*)\r\n/", $req, $match))  { $key1=$match[1]; }
        if(preg_match("/\r\n(.*?)\$/", $req, $match))                   { $data=$match[1]; }
        if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$req,$match)){$key = $match[1]; }

        return array($r, $h, $o, $key1, $key2, $data,$key);
    }
    /**
     * Verify if a class exists and if it extends a base class
     *
     * @param $className
     *    The class to verify
     * @param $parentName
     *    The base class that must be extended by $className
     */
    static function validateClass($className, $parentName){

        if (!class_exists((string)$className)){
            throw new Exception('Non existing class given to extend '.$parentName.' class: '.$className);
        }
        
        $class = new ReflectionClass($className);
        $parents = array($className);

        while ($parent = $class->getParentClass()) {
            $class = $parent;
            array_push($parents, $parent->getName());
        }
        if(!in_array($parentName, $parents)){
            throw new Exception($className.' does not extend '.$parentName);
        }
    }
    
    /**
     * Prepare a message for sending of websocket
     *
     * @param $msg
     *    The message to be prepared
     * @return
     *    The message wrapped up for sending
     */
    static function wrap($msg= ''){
        if(is_object($msg) || is_array($msg)){
            $msg = json_encode($msg);
        }
        return self::encode($msg);
    }
	
    /**
     * Remove wrapper characters from received message
     *
     * @param $msg
     *    The message received over the socket
     * @return
     *    The unwrapped string
     */
    static function unwrap($msg = ''){
        $msg = self::decode($msg);
        if(isset($msg['type']) AND $msg['type'] == "text"){
        	$msg = $msg['payload'];
        }
        else{
        if(json_decode($msg) !== null){
            $msg = json_decode($msg);
        }
        }
        return $msg;
    }

    /**
     * Output debugging message
     *
     * @param $msg
     *    The message to ouput to terminal
     */
    static function console($msg = '', $vars = array()){
        vprintf($msg . "\n", $vars);
    }
    function calcKey($key){
     $CRAZY = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
     $sha = sha1($key.$CRAZY,true);
     return base64_encode($sha);
	}
	private function encode($data)
	{
	$databuffer = array();
	$sendlength = strlen($data);
	$rawBytesSend = $sendlength + 2;
	$packet;
	if ($sendlength > 65535) {
    // 64bit
    array_pad($databuffer, 10, 0);
    $databuffer[1] = 127;
    $lo = $sendlength | 0;
    $hi = ($sendlength - $lo) / 4294967296;

    $databuffer[2] = ($hi >> 24) & 255;
    $databuffer[3] = ($hi >> 16) & 255;
    $databuffer[4] = ($hi >> 8) & 255;
    $databuffer[5] = $hi & 255;

    $databuffer[6] = ($lo >> 24) & 255;
    $databuffer[7] = ($lo >> 16) & 255;
    $databuffer[8] = ($lo >> 8) & 255;
    $databuffer[9] = $lo & 255;

    $rawBytesSend += 8;
	} else if ($sendlength > 125) {
    // 16 bit
    array_pad($databuffer, 4, 0);
    $databuffer[1] = 126;
    $databuffer[2] = ($sendlength >> 8) & 255;
    $databuffer[3] = $sendlength & 255;

    $rawBytesSend += 2;
	} else {
    array_pad($databuffer, 2, 0);
    $databuffer[1] = $sendlength;
	}

	// Set op and find
	$databuffer[0] = (128 + ($binary ? 2 : 1));
	$packet = pack('c', $databuffer[0]);
	// Clear masking bit
	 $databuffer[1] &= ~128;
	// write out the packet header
	for ($i = 1; $i < count($databuffer); $i++) {
    //$packet .= $databuffer[$i];
    $packet .= pack('c', $databuffer[$i]);
	}

	// write out the packet data
	for ($i = 0; $i < $sendlength; $i++) {
    $packet .= $data[$i];
	}
	return $packet;
	}
	private function decode($data)
	/// Decoding 
	
	{
		$payloadLength = '';
		$mask = '';
		$unmaskedPayload = '';
		$decodedData = array();

		// estimate frame type:
		$firstByteBinary = sprintf('%08b', ord($data[0]));		
		$secondByteBinary = sprintf('%08b', ord($data[1]));
		$opcode = bindec(substr($firstByteBinary, 4, 4));
		$isMasked = ($secondByteBinary[0] == '1') ? true : false;
		$payloadLength = ord($data[1]) & 127;

		// close connection if unmasked frame is received:
		if($isMasked === false)
		{
			$this->close(1002);
		}

		switch($opcode)
		{
			// text frame:
			case 1:
				$decodedData['type'] = 'text';				
			break;

			case 2:
				$decodedData['type'] = 'binary';
			break;

			// connection close frame:
			case 8:
				$decodedData['type'] = 'close';
			break;

			// ping frame:
			case 9:
				$decodedData['type'] = 'ping';				
			break;

			// pong frame:
			case 10:
				$decodedData['type'] = 'pong';
			break;

			default:
				// Close connection on unknown opcode:
				$this->close(1003);
			break;
		}

		if($payloadLength === 126)
		{
		   $mask = substr($data, 4, 4);
		   $payloadOffset = 8;
		   $dataLength = bindec(sprintf('%08b', ord($data[2])) . sprintf('%08b', ord($data[3]))) + $payloadOffset;
		}
		elseif($payloadLength === 127)
		{
			$mask = substr($data, 10, 4);
			$payloadOffset = 14;
			$tmp = '';
			for($i = 0; $i < 8; $i++)
			{
				$tmp .= sprintf('%08b', ord($data[$i+2]));
			}
			$dataLength = bindec($tmp) + $payloadOffset;
			unset($tmp);
		}
		else
		{
			$mask = substr($data, 2, 4);	
			$payloadOffset = 6;
			$dataLength = $payloadLength + $payloadOffset;
		}

		/**
		 * We have to check for large frames here. socket_recv cuts at 1024 bytes
		 * so if websocket-frame is > 1024 bytes we have to wait until whole
		 * data is transferd. 
		 */
		if(strlen($data) < $dataLength)
		{			
			return false;
		}

		if($isMasked === true)
		{
			for($i = $payloadOffset; $i < $dataLength; $i++)
			{
				$j = $i - $payloadOffset;
				if(isset($data[$i]))
				{
					$unmaskedPayload .= $data[$i] ^ $mask[$j % 4];
				}
			}
			$decodedData['payload'] = $unmaskedPayload;
		}
		else
		{
			$payloadOffset = $payloadOffset - 4;
			$decodedData['payload'] = substr($data, $payloadOffset);
		}

		return $decodedData;
	}

}