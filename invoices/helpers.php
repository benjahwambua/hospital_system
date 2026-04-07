<?php
function generate_invoice_number() {
    return 'PH-'.date('Ymd').'-'.rand(1000,9999);
}
