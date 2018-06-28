<?php

class TOOLS {
    public function toVmessLink($params, $uuid){
        $config = [
            "ps" => $params['name'],
            "add" => $params['server'],
            "port" => $params['port'],
            "id" => $uuid,
            "aid" => "2",
            "net" => "tcp",
            "type" => $params['sec'],
            "host" => "",
            "tls" => (int)$params['tls']?"tls":""
        ];
        return "vmess://".base64_encode(json_encode($config));
    }
}