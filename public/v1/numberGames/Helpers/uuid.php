<?php
function generate_uuid() {
    $randomBytes = random_bytes(16);
    
    $randomBytes[6] = chr(ord($randomBytes[6]) & 0x0f | 0x40);
    $randomBytes[8] = chr(ord($randomBytes[8]) & 0x3f | 0x80);
    
    $uuid = bin2hex($randomBytes);
    $formattedUuid = sprintf(
        '%08s-%04s-%04s-%02s%02s-%012s',
        substr($uuid, 0, 8),
        substr($uuid, 8, 4),
        substr($uuid, 12, 4),
        substr($uuid, 16, 2),
        substr($uuid, 18, 2),
        substr($uuid, 20, 12)
    );

    return $formattedUuid;
}