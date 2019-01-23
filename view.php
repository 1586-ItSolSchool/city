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

/**
 * Класс для создания и обработки формы передачи бюджета
 * @see https://docs.moodle.org/dev/lib/formslib.php_Form_Definition#definition.28.29
 */
class operatebudget_form extends moodleform {

    function definition() {
        global $CFG, $course;
 
        $mform = $this->_form;
 
        $mform->addElement('hidden','action','operateBudgetProcess');
        $mform->setType('action', PARAM_INT);
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
        $buttonarray[] = $mform->createElement('cancel');
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

echo 'В казне сейчас: '.city_get_wallet_amount($course->id, -1).' часов.<br>';

if(has_capability('mod/city:operatebudget',$modulecontext))
    echo 'У вас есть право <a href="'. new moodle_url('/mod/city/view.php', array('id'=>$id,'action'=>'operateBudgetStart')) .'">управлять казной</a>.<br>';
else 
    echo 'У вас нет прав управлять казной.<br>';

if(has_capability('mod/city:operatebudget',$modulecontext) && strstr($action,'operateBudget')) {
    $mform = new operatebudget_form();
    $mform->display();
    if ($fromform = $mform->get_data()) {
        $cWallet = $DB->get_record('city_wallets',Array('id' => $fromform->wallettotransfer),'*',MUST_EXIST);
        $cUser = $DB->get_record('user',Array('id' => $cWallet->ownerid),'id,username,firstname,lastname',MUST_EXIST);
        $trId = $DB->insert_record('city_transactions', Array(
            'course'    => $course->id,
            'time'      => time(),
            'type'      => 1, // передача бюджета
            'amount'    => $fromform->amounttotransfer,
            'techcomment'   => sprintf('Выдача бюджета пользователем %s: %s %s в размере $d часов с кошелька №%d (%d в кошельке) пользователю %s: %s %s на номер кошелька %d',
                                        $USER->username, $USER->firstname, $USER->lastname, $fromform->amounttotransfer, -1, city_get_wallet_amount($corse->id, -1), $cUser->username, $cUser->firstname, $cUser->lastname, $fromform->wallettotransfer),
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
        echo sprintf('Выдача бюджета пользователем %s: %s %s в размере $d часов с кошелька №%d (%d в кошельке) пользователю %s: %s %s на номер кошелька %d',
        $USER->username, $USER->firstname, $USER->lastname, $fromform->amounttotransfer, $myWallet->id, $amount, $cUser->username, $cUser->firstname, $cUser->lastname, $fromform->wallettotransfer).'<br>';
        //redirect($nexturl);
    }
} else {
    echo 'Номер вашего кошелька: '.$myWallet->id.'<br>';
    $amount = city_get_wallet_amount($course->id, $myWallet->id);
    if('taxPayment' == $action){
        $payment = optional_param('money', 3, PARAM_INT);
        if ($payment > $amount) {
            echo '<b>Не могу уплатить налог: недостаточно средств.</b><br>';
        } else {
            echo '<b>Плачу налог...';
            $trId = $DB->insert_record('city_transactions', Array(
                'course'    => $course->id,
                'time'      => time(),
                'type'      => 4, // уплата налогов
                'amount'    => $payment,
                'techcomment'   => sprintf('Уплата налогов пользователем %s: %s %s в размере $d часов с кошелька №%d (%d в кошельке)',
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
            echo ' уплачено с номером чека: '.$trId.'</b>';
        }
        
    }

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
        echo '<a href="'.$taxPayment2.'">1 час (отметка Удовлетворительно)</a>.<br>';
    }
    if (0 == $amount) {
        echo 'нечем :-(<br>';
    }
}


echo $OUTPUT->footer();
?>