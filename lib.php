<?php
    function city_add_instance($city){};
    function city_update_instance($city){};
    function city_delete_instance($id){};

    /**
     * Возвращает список страниц для меню новой активности/ресурса курса.
     *
     * @param stdClass $defaultitem
     */
    function city_get_shortcuts($defaultitem) {
        global $DB, $CFG;
        $pages = array();

        // Мой игровой профиль
        $page = new stdClass;
        $page->archetype = MOD_CLASS_ACTIVITY;  // Тип страницы: Активность. Обязательная строчка
        $page->type = 'city&type=myprofile';    // подстрока адреса при обращении к файлу view.php
        $page->name = 'myprofile';              // системное название типа для меню создания активности
        $page->title = get_string('pluginname', 'city').' - '.get_string('myprofile', 'city'); // Название типа для меню активности
        $page->link = new moodle_url($defaultitem->link, array('type' => $page->name));
        $page->help = get_string('myprofilehelp', 'city');
        $pages[] = $page;

        return $pages;
    }
?>