<?php
// Этот файл позволяет создать/отредактировать данные элементы курса

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
 
require_once($CFG->dirroot.'/course/moodleform_mod.php'); // магия
 
class mod_city_mod_form extends moodleform_mod { // магия
    
    function definition() { // магия
        global $CFG, $DB, $OUTPUT; // подключаем настройки сайта, работу с базой и работу со страницей вывода
 
        $mform =& $this->_form; // теперь $mform это "сокращенная запись" для $this->_form
        $id = $this->_instance; // id экземпляра модуля, не пуст если мы Редактируем игру и пуст если это новое создание
 
        $mform->addElement('html','Базовые настройки казны (их нельзя редактировать после запуска игры)');  // очень плохо и так делать не надо
        $mform->addElement('text', 'initialammount', 'Изначальный размер казны', array('size'=>'10'));      // очень плохо и так делать не надо
        $mform->setType('initialammount', PARAM_INT); // https://docs.moodle.org/dev/lib/formslib.php_Form_Definition#setType
        $mform->addRule('initialammount', null, 'required', null);

        $type = required_param('type', PARAM_ALPHA); // таким образом можно обработать тип создаваемой страницы активностей
        $mform->addElement('hidden', 'type', $type);
        $mform->setDefault('type', $type);
        $mform->setType('type', PARAM_ALPHA);

        $this->standard_coursemodule_elements(); // остальная портянка формы
        $this->add_action_buttons(); // кнопка "сохранить" и остальные крутые кнопки 
    }
}
?>