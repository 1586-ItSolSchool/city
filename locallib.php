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

/**
 * Возвращает массив вида USERID => Array('ownerid' => ..,'walletid' => .., 'username' => .., 'userprettyname' => USER->FIRSTNAME+USER->LASTNAME, 'amount' => ..)
 * @param $cid Course ID
 */
function city_get_wallets_by_course_id($cid){
    global $DB;
    $res = Array(Array(
        'walletid'  => -1,
        'username'  => '__Казна__',
        'firstname' => '',
        'lastname' => '',
        'amount'    => city_get_wallet_amount($cid, $walletid = -1),
        'ownerid'    => -1,
    ));

    $wallets = $DB->get_records('city_wallets', Array('course' => $cid, 'type' => 0));

    foreach ($wallets as $wallet) {
        $cUser = $DB->get_record('user',Array('id' => $wallet->ownerid),'id,username,firstname,lastname',IGNORE_MISSING);
        $res[$cUser->id] = Array(
           'ownerid'    => $cUser->id,
           'username'   => $cUser->username,
           'firstname'  => $cUser->firstname,
           'lastname'  => $cUser->lastname,
           'amount'     => city_get_wallet_amount($cid, $wallet->id),
           'walletid'   => $wallet->id,
        );
    }

    return $res;
}
?>