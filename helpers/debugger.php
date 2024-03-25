<?php

function dd($variable) {
    echo json_encode($variable, JSON_PRETTY_PRINT);
    die();
}
