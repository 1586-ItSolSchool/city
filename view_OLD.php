<?php 
require_once('../../config.php');
require_once('lib.php');
require_once('locallib.php');
require_once("$CFG->libdir/formslib.php");
 
$id = required_param('id', PARAM_INT);    // Course Module ID
$action = optional_param('action', 'overview', PARAM_NOTAGS); //https://docs.moodle.org/dev/lib/formslib.php_Form_Definition#Most_Commonly_Used_PARAM_.2A_Types
    // https://lmstech.ru/blog/articles/obrabotka-dannyh-pri-programmirovanii-pod-moodle/

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'city');
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$city = $DB->get_record('city', array('id'=> $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/city/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($city->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext); 
echo $OUTPUT->header(); // ^ магия из view.mustache плагина конструктора плагинов

$transferTypes = Array(
    'Инициация', 'Передача бюджета', 'Выплата гранта', 'Выплата зарплаты', 'Уплата налогов'
);

/**
 * Класс для создания и обработки формы передачи бюджета
 * @see https://docs.moodle.org/dev/lib/formslib.php_Form_Definition#definition.28.29
 */
class operatebudget_form extends moodleform {

    function definition() {
        global $CFG, $course;
 
        $mform = $this->_form;
 
        $mform->addElement('hidden','action','operateBudgetProcess');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden','id',required_param('id', PARAM_INT));
        $mform->setType('id', PARAM_INT);

        $wallets = city_get_wallets_by_course_id($course->id);
        $selectWalletsOptions = Array();
        foreach ($wallets as $wallet) {
            $selectWalletsOptions[$wallet['walletid']] = sprintf("%s: %s %s (№%d / %d часов)", $wallet['username'], $wallet['firstname'], $wallet['lastname'], $wallet['walletid'], $wallet['amount']);
        }

        $mform->addElement('select','wallettotransfer','Кошелёк для перевода',$selectWalletsOptions);
        $mform->setType('wallettotransfer', PARAM_INT);
        $mform->addElement('text','amounttotransfer','Сумма к переводу');
        $mform->setType('amounttotransfer', PARAM_INT);
        
        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        //$buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }
}

/**
 * Класс для создания и обработки формы передачи гранта/выплаты зп
 */
class ordinarytransfer_form extends moodleform {

    function definition() {
        global $CFG, $course, $transferTypes;
 
        $mform = $this->_form;
 
        $mform->addElement('hidden','action','transferProcess');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden','id',required_param('id', PARAM_INT));
        $mform->setType('id', PARAM_INT);

        $wallets = city_get_wallets_by_course_id($course->id);
        $selectWalletsOptions = Array();
        foreach ($wallets as $wallet) {
            $selectWalletsOptions[$wallet['walletid']] = sprintf("%s: %s %s (№%d / %d часов)", $wallet['username'], $wallet['firstname'], $wallet['lastname'], $wallet['walletid'], $wallet['amount']);
        }

        $mform->addElement('select','wallettotransfer','Кошелёк для перевода',$selectWalletsOptions);
        $mform->setType('wallettotransfer', PARAM_INT);

        $mform->addElement('select','transfertype','Тип перевода',Array(
            2 => $transferTypes['2'],
            3 => $transferTypes['3'],
        ));
        $mform->setType('transfertype', PARAM_INT);
        $mform->addElement('text','amounttotransfer','Сумма к переводу');
        $mform->setType('amounttotransfer', PARAM_FLOAT);
        
        $buttonarray=array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Перевести!');
        //$buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }
}

// Создаем кошелек, если у человека его ещё нет
$myWallet = $DB->get_record('city_wallets', array('course' => $course->id, 'ownerid' => $USER->id, 'type' => 0),'*', IGNORE_MISSING);

if(!$myWallet){
    // создадим кошель
    $record = new stdClass;
    $record->ownerid = $USER->id;
    $record->course = $course->id;
    $record->type = 0;
    $myWalletId = $DB->insert_record('city_wallets',$record);
    $myWallet = $DB->get_record('city_wallets', array('course' => $course->id, 'ownerid' => $USER->id, 'type' => 0),'*', MUST_EXIST);
}

echo 'В казне сейчас: '.city_get_wallet_amount($course->id, -1).' часа(ов).<br>';

if(has_capability('mod/city:operatebudget',$modulecontext))
    echo 'У вас есть право <a href="'. new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'operateBudgetStart')) .'">управлять казной</a>.<br>';
if(has_capability('mod/city:editalltransactions',$modulecontext))
    echo 'У вас есть право <a href="'. new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'viewTransactions')) .'">просматривать все переводы всех жителей</a>.<br>';

if(has_capability('mod/city:editalltransactions',$modulecontext) && 'viewTransactions' == $action){
    $allwallets = city_get_wallets_by_course_id($course->id);
    if($allwallets){
        echo '<table width="100%" border="1">
        <tr>
            <td>№</td>
            <td>Владелец кошелька</td>
            <td>Текущий баланс</td>
            <td>Действия</td>
        </tr>
        <tr>
            <td>-1</td>
            <td>__КАЗНА__ и Уплата налогов</td>
            <td>'.city_get_wallet_amount($course->id, -1).'</td>
            <td><a href="'.new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'viewTransactionsByWalletId','walletid'=>-1)).'" target="_blank">Перейти к транзакциям</a></td>
        </tr>';
        foreach( $allwallets as $cwallet ){
            printf('<tr><td>%d</td><td>%s: %s %s</td><td>%f</td><td><a href="%s" target="_blank">Перейти к транзакциям</a></td></tr>', $cwallet['walletid'], $cwallet['username'], $cwallet['firstname'], $cwallet['lastname'], $cwallet['amount'], new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'viewTransactionsByWalletId','walletid'=>$cwallet['walletid'])));
        }
        echo '</table>';
    } else echo 'Не найдены.<br>';
}

if(has_capability('mod/city:editalltransactions',$modulecontext) && 'viewTransactionsByWalletId' == $action){
    $walletid = $action = optional_param('walletid', '-1', PARAM_INT);
    if($walletid > -1){
        $wallet = $DB->get_record('city_wallets', Array('id' => $walletid),'*',MUST_EXIST);
        $cUser = $DB->get_record('user',Array('id' => $wallet->ownerid),'id,username,firstname,lastname',MUST_EXIST);
        printf('<br><b>История денежных переводов %s: %s %s кошелёк номер %d </b>:<br>',$cUser->username,$cUser->firstname,$cUser->lastname,$wallet->id);
    } else echo '<br><b>История движения денег в казне</b><br>';
    $transactions = $DB->get_records('city_transaction_details', Array('walletid' => $walletid), $sort='transactionid', $fields='*', $strictness=IGNORE_MISSING);
    if($transactions){
        echo '<table width="100%" border="1">
        <tr>
            <td>№</td>
            <td>Время перевода</td>
            <td>Тип перевода</td>
            <td>Сумма</td>
            <td>Технический комментарий</td>
        </tr>';
        foreach( $transactions as $mytr ){
            $cTr = $DB->get_record('city_transactions', Array('id' => $mytr->transactionid),'*',MUST_EXIST);
            printf('<tr><td>%d</td><td>%s</td><td>%s</td><td>%f</td><td>%s</td></tr>',$cTr->id, date("Y-m-d H:i:s", $cTr->time), $transferTypes[$cTr->type], $cTr->amount, $cTr->techcomment);
        }
        echo '</table>';
    } else echo 'Не найдены.<br>';
}

if(has_capability('mod/city:operatebudget',$modulecontext) && strstr($action,'operateBudget')) {
    $mform = new operatebudget_form();
    $mform->display();
    if ($fromform = $mform->get_data()) {
        $cWallet = $DB->get_record('city_wallets',Array('id' => $fromform->wallettotransfer),'*',MUST_EXIST);
        $cUser = $DB->get_record('user',Array('id' => $cWallet->ownerid),'id,username,firstname,lastname',MUST_EXIST);
        $ctechcomment = sprintf('Выдача бюджета пользователем %s: %s %s в размере %d часов из казны №%d (%f в казне) пользователю %s: %s %s на кошелёк №%d (%f в кошельке)',
            $USER->username, $USER->firstname, $USER->lastname, $fromform->amounttotransfer, -1, 
            city_get_wallet_amount($course->id, -1), $cUser->username, $cUser->firstname, 
            $cUser->lastname, $fromform->wallettotransfer, 
            city_get_wallet_amount($course->id, $fromform->wallettotransfer));
        $trId = $DB->insert_record('city_transactions', Array(
            'course'    => $course->id,
            'time'      => time(),
            'type'      => 1, // передача бюджета
            'amount'    => $fromform->amounttotransfer,
            'techcomment'   => $ctechcomment,
        ));
        $DB->insert_record('city_transaction_details', Array(
            'walletid'  => $fromform->wallettotransfer,
            'currentamount' => $fromform->amounttotransfer,
            'transactionid' => $trId
        ));
        $DB->insert_record('city_transaction_details', Array(
            'walletid'  => -1,
            'currentamount' => -$fromform->amounttotransfer,
            'transactionid' => $trId
        ));
        echo $ctechcomment.'<br>';
        //redirect($nexturl);
    }
} else {
    $amount = city_get_wallet_amount($course->id, $myWallet->id);
    echo 'Ваш кошелёк: №'.$myWallet->id.' ('.$amount.' часа(ов) по состоянию на '.date("Y-m-d H:i:s").')<br>';
    if('taxPayment' == $action){
        $payment = optional_param('money', 3, PARAM_INT);
        if ($payment < 1 OR $payment > 3){
            echo 'Некорректный размер налога';
        }
        else 
            if ($payment > $amount) {
                echo '<b>Не могу уплатить налог: недостаточно средств.</b><br>';
            } else {
                echo '<b>Плачу налог...</b>';
                $trId = $DB->insert_record('city_transactions', Array(
                    'course'    => $course->id,
                    'time'      => time(),
                    'type'      => 4, // уплата налогов
                    'amount'    => $payment,
                    'techcomment'   => sprintf('Уплата налогов пользователем %s: %s %s в размере %d часов с кошелька №%d (%f в кошельке)',
                                            $USER->username, $USER->firstname, $USER->lastname, $payment, $myWallet->id, $amount),
                ));
                $DB->insert_record('city_transaction_details', Array(
                    'walletid'  => $myWallet->id,
                    'currentamount' => -$payment,
                    'transactionid' => $trId
                ));
                $DB->insert_record('city_transaction_details', Array(
                    'walletid'  => -1,
                    'currentamount' => $payment,
                    'transactionid' => $trId
                ));
                echo ' уплачено с номером чека: '.$trId.'<br><a href="'.new moodle_url('/mod/city/view.php', array('id'=>$id)).'"><b>Продолжить работу<b></a>.';
            }
        
    }
    else if(strstr($action,'transfer')){
        $mform = new ordinarytransfer_form();
        $mform->display();
        if ($fromform = $mform->get_data()) {
            if(!(2 == $fromform->transfertype OR 3 == $fromform->transfertype))
                echo '<b>Некорректный тип перевода</b>.<br>';
            else {
                if($amount < $fromform->amounttotransfer or $fromform->amounttotransfer <= 0)
                    echo '<b>Некорректный размер перевода</b>';
                else {
                    $cWallet = $DB->get_record('city_wallets',Array('id' => $fromform->wallettotransfer),'*',MUST_EXIST);
                    $cUser = $DB->get_record('user',Array('id' => $cWallet->ownerid),'id,username,firstname,lastname',MUST_EXIST);
                    $ctechcomment = sprintf('%s пользователем %s: %s %s в размере %f часов с кошелька №%d (%f в кошельке) пользователю %s: %s %s на кошелёк №%d (%f в кошельке)',
                        $transferTypes[$fromform->transfertype], $USER->username, $USER->firstname, $USER->lastname, $fromform->amounttotransfer, $myWallet->id, 
                        city_get_wallet_amount($course->id, $myWallet->id), $cUser->username, $cUser->firstname, 
                        $cUser->lastname, $fromform->wallettotransfer, 
                        city_get_wallet_amount($course->id, $fromform->wallettotransfer));
                    $trId = $DB->insert_record('city_transactions', Array(
                        'course'    => $course->id,
                        'time'      => time(),
                        'type'      => $fromform->transfertype,
                        'amount'    => $fromform->amounttotransfer,
                        'techcomment'   => $ctechcomment,
                    ));
                    $DB->insert_record('city_transaction_details', Array(
                        'walletid'  => $fromform->wallettotransfer,
                        'currentamount' => $fromform->amounttotransfer,
                        'transactionid' => $trId
                    ));
                    $DB->insert_record('city_transaction_details', Array(
                        'walletid'  => $myWallet->id,
                        'currentamount' => -$fromform->amounttotransfer,
                        'transactionid' => $trId
                    ));
                    echo $ctechcomment.'<br>';
                }
            }
        }
    }
    else {
        echo 'Баланс: '.$amount.' часов. Уплатить налог: ';
        if ($amount >= 3) {
            $taxPayment3 = new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'taxPayment','money'=>3));
            echo '<a href="'.$taxPayment3.'">3 часа (отметка Отлично)</a>, ';
        }
        if ($amount >= 2) {
            $taxPayment2 = new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'taxPayment','money'=>2));
            echo '<a href="'.$taxPayment2.'">2 часа (отметка Хорошо)</a>, ';
        }
        if ($amount >= 1) {
            $taxPayment1 = new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'taxPayment','money'=>1));
            echo '<a href="'.$taxPayment1.'">1 час (отметка Удовлетворительно)</a>.<br>';
        }
        if (0 == $amount) {
            echo 'нечем :-(<br>';
        }

        if($amount > 0){
            echo '<a href="'.new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'transferStart')).'">Перевести деньги другому жителю</a>.<br>';
        }

        echo '<br><b>История моих денежных переводов</b>:<br>';
        $mytransactions = $DB->get_records('city_transaction_details', Array('walletid' => $myWallet->id), $sort='transactionid', $fields='*', $strictness=IGNORE_MISSING);
        if($mytransactions){
            echo '<table width="100%" border="1">
            <tr>
                <td>№</td>
                <td>Время перевода</td>
                <td>Тип перевода</td>
                <td>Сумма</td>
                <td>Технический комментарий</td>
            </tr>';
            foreach( $mytransactions as $mytr ){
                $cTr = $DB->get_record('city_transactions', Array('id' => $mytr->transactionid),'*',MUST_EXIST);
                printf('<tr><td>%d</td><td>%s</td><td>%s</td><td>%f</td><td>%s</td></tr>',$cTr->id, date("Y-m-d H:i:s", $cTr->time), $transferTypes[$cTr->type], $cTr->amount, $cTr->techcomment);
            }
            echo '</table>';
        } else echo 'Не найдены.<br>';
    }
}


echo $OUTPUT->footer();
?>