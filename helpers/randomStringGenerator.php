<?php
function generateRandomString($length) {
    $randomBytes = random_bytes($length);
    $randomString = bin2hex($randomBytes);

    return $randomString;
}