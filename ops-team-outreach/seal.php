<?php
$plain=stream_get_contents(STDIN);$key=random_bytes(32);$iv=random_bytes(12);$tag='';
$cipher=openssl_encrypt(gzencode($plain),'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);
if($cipher===false||!openssl_public_encrypt($key,$wrapped,file_get_contents('release-sponsor/shared-client-public.pem'),OPENSSL_PKCS1_OAEP_PADDING))exit(1);
echo json_encode(['key'=>base64_encode($wrapped),'iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'data'=>base64_encode($cipher)]);
