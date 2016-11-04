<?php

add_action('phpmailer_init', function ($phpmailer) {
    $phpmailer->Host = getenv('MAILCATCHER_PORT_1025_TCP_ADDR');
    $phpmailer->Port = (int)getenv('MAILCATCHER_PORT_1025_TCP_PORT');
    $phpmailer->SMTPAuth = false;
    $phpmailer->isSMTP();
});
