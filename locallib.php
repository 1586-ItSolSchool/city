<?php
/**
 * Возвращает сумму в часах, хранящуюся на кошельке.
 * @param $cid -- ид  курса $course->id 
 * @param $walletid
 * @return int
 */
function city_get_wallet_amount($cid, $walletid = -1){
    global $DB;
    $conditions = array('walletid' => $walletid);
    
    $records = $DB->get_records('city_transaction_details',$conditions);

    $sum = 0;

    foreach ($records as $record) {
        $sum += $record->currentamount;
    }

    return $sum;
}
?>