<?php
class WHMCS {
    public function __construct($cc)
    {
        $this->cc_encryption_hash = $cc;
    }
    
    public function _hash($string)
    {
        if( function_exists('sha1') )
        {
            $hash = sha1($string);
        }
        else
        {
            $hash = md5($string);
        }
        $out = '';
        $c = 0;
        while( $c < strlen($hash) )
        {
            $out .= chr(hexdec($hash[$c] . $hash[$c + 1]));
            $c += 2;
        }
        return $out;
    }
    
    public function decrypt($string)
    {
        $key = md5(md5($this->cc_encryption_hash)) . md5($this->cc_encryption_hash);
        $hash_key = $this->_hash($key);
        $hash_length = strlen($hash_key);
        $string = base64_decode($string);
        $tmp_iv = substr($string, 0, $hash_length);
        $string = substr($string, $hash_length, strlen($string) - $hash_length);
        $iv = $out = '';
        for( $c = 0; $c < $hash_length; $c++ )
        {
            $iv .= chr(ord($tmp_iv[$c]) ^ ord($hash_key[$c]));
        }
        $key = $iv;
        for( $c = 0; $c < strlen($string); $c++ )
        {
            if( $c != 0 && $c % $hash_length == 0 )
            {
                $key = $this->_hash($key . substr($out, $c - $hash_length, $hash_length));
            }
            $out .= chr(ord($key[$c % $hash_length]) ^ ord($string[$c]));
        }
        return $out;
    }
}