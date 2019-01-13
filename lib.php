<?php
    function city_add_instance($city){
        global $DB, $course;

        $city->timecreated = time();

        $id = $DB->insert_record('city', $city);
        $trId = $DB->insert_record('city_transactions', Array(
            'course'    => $course->id,
            'time'      => time(),
            'type'      => 0, // инициализация
            'amount'    => 10000,
        ));
        $DB->insert_record('city_transaction_details', Array(
            'walletid'  => -1,
            'currentamount' => 10000,
            'transactionid' => $trId
        ));

        return $id;
    };
    function city_update_instance($city){};
    function city_delete_instance($id){
        global $DB;
    
        $exists = $DB->get_record('city', array('id' => $id));
        if (!$exists) {
            return false;
        }
    
        $DB->delete_records('city', array('id' => $id));
    
        return true;
    };

    /**
     * Возвращает список страниц для меню новой активности/ресурса курса.
     * После каждого изменения в функции надо полностью очищать кеш moodle через Администрирование / Разработка / Очистить все кэши
     * 
     * @param stdClass $defaultitem
     */
    function city_get_shortcuts($defaultitem) {
        global $DB, $CFG;
        $pages = array();

        /* // сделаю всё в одной странице пока
        // Мой игровой профиль
        $page = new stdClass;
        $page->archetype = MOD_CLASS_ACTIVITY;  // Тип страницы: Активность. Обязательная строчка
        $page->type = 'city&type=myprofile';    // подстрока адреса при обращении к файлу view.php
        $page->name = 'myprofile';              // системное название типа для меню создания активности
        $page->title = get_string('modulename', 'city').' - '.get_string('myprofile', 'city'); // Название типа для меню активности
        $page->link = new moodle_url($defaultitem->link, array('type' => $page->name));
        $page->help = get_string('myprofilehelp', 'city');  // подключаем текст справки для меню активности
        $pages[] = $page;
        unset($page); //*/

        // Обзор экономики
        $page = new stdClass;
        $page->archetype = MOD_CLASS_ACTIVITY;  // Тип страницы: Активность. Обязательная строчка
        $page->type = 'city&type=overview';
        $page->name = 'overview';              
        $page->title = get_string('modulename', 'city').' - '.get_string('overview', 'city'); 
        $page->link = new moodle_url($defaultitem->link, array('type' => $page->name));
        $page->help = get_string('overviewhelp', 'city');  
        $pages[] = $page;

        return $pages;
    }
?>