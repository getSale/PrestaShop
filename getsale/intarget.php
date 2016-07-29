<?php
/**
 * 2016 getSale
 *
 * @author    getSale RU <http://getsale.io/>
 * @copyright 2016 getSale
 * @license   GNU General Public License, version 3
 */

class Getsale extends Module
{
    public function __construct()
    {
        $this->name = 'getsale';
        $this->tab = 'analytics_stats';
        $this->config_form = 'password';
        $this->version = '1.0.0';
        $this->author = 'getSale Team';
        $this->need_instance = 1;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('getSale');
        $this->description = $this->l('Профессиональный инструмент для создания popup-окон');
        $this->confirmUninstall = $this->l('Вы действительно хотите удалить модуль getSale?');
    }

    public function install()
    {
        //задаём значение переменных по умолчанию
        if (!Configuration::get('getsale_email')) {
            Configuration::updateValue('getsale_email', '');
        }
        if (!Configuration::get('getsale_key')) {
            Configuration::updateValue('getsale_key', '');
        }
        if (!Configuration::get('getsale_id')) {
            Configuration::updateValue('getsale_id', '');
        }

        return parent::install() &&
        $this->registerHook('displayTop') &&
        $this->registerHook('displayHeader') &&
        $this->registerHook('displayFooter') &&
        $this->installDB();
    }

    public function installDB()
    {
        $return = Db::getInstance()->execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'getsale_table` (
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id` int(10) unsigned NOT NULL ,
				`user-reg` int(10),
				PRIMARY KEY (`id`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;');
        return $return;
    }

    public function uninstall()
    {
        if (Configuration::get('getsale_id')) {
            Configuration::updateValue('getsale_id', '');
        }
        if (Configuration::get('getsale_key')) {
            Configuration::updateValue('getsale_key', '');
        }
        if (Configuration::get('getsale_email')) {
            Configuration::updateValue('getsale_email', '');
        }

        return parent::uninstall() && $this->uninstallDB();
    }

    public function uninstallDB()
    {
        $return = Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'getsale_table`');
        return $return;
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $getsale_email = Tools::getValue('getsale_email');
            $getsale_key = Tools::getValue('getsale_key');
            if (!empty($getsale_key) && !empty($getsale_email)) {
                Configuration::updateValue('getsale_key', $getsale_key);
                Configuration::updateValue('getsale_email', $getsale_email);
                $output = $this->displayConfirmation($this->l('Поздравляем, сайт успешно привязан к аккаунту') . ' <a href="http://getsale.io" target="_blank">getSale</a>') . $this->displayFormSuccess() . $output;
            }
        }
        if (!Configuration::get('getsale_key') && !Configuration::get('getsale_email')) {
            return $this->displayError($this->l('Заполните обязательные поля')) . $this->displayForm()
            . $output;
        } else {
            $key = Configuration::get('getsale_key');
            $email = Configuration::get('getsale_email');
            $host = $this->currentUrl();

            if (!Configuration::get('getsale_id')) {
                $result = $this->GetInfoFromGetsale($key, $email, $host);
                if ($result['error']) {
                    $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
                    $output = $this->displayError($result['error']) . $this->displayForm() . $output;
                } else {
                    if ($result['ok']) {
                        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
                        $output = $this->displayConfirmation($this->l('Поздравляем, сайт успешно привязан к
                         аккаунту') . ' <a href="http://getsale.io" target="_blank">getSale</a>') . $this->displayFormSuccess() . $output;
                    }
                }
            } else {
                $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
                $output = $this->displayConfirmation($this->l('Поздравляем, сайт успешно привязан к аккаунту') . ' <a href="http://getsale.io" target="_blank">getSale</a>') . $this->displayFormSuccess() . $output;
            }

            return $output;
        }
    }

    /**
     * Возвращает url
     */
    public function currentUrl()
    {
        $url = 'http';
        if (isset($_SERVER['HTTPS'])) {
            if ($_SERVER['HTTPS'] == 'on') {
                $url .= 's';
            }
        }
        $url .= '://';
        if ($_SERVER['SERVER_PORT'] != '80') {
            $url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'];
        } else {
            $url .= $_SERVER['SERVER_NAME'];
        }

        return $url;
    }

    public function displayForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        //ширина полей в админке модуля
        $getsale_col = '6';
        // Описываем поля формы для страници настроек
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('getSale') . '  &mdash; ' . $this->l('Профессиональный инструмент для создания popup-окон'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('Оцените принципиально новый подход к созданию popup-окон.'),
                    'name' => 'text'),
                array(
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => 'getsale_email',
                    'required' => true,
                    'col' => $getsale_col,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Ключ API'),
                    'name' => 'getsale_key',
                    'required' => true,
                    'col' => $getsale_col,
                ),
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('Введите email и ключ API из личного кабинета ')
                        . '<a href="http://getsale.io" target="_blank">getSale.ru</a>',
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('Если вы ещё не зарегистрировались в сервисе getSale это можно
                    сделать по ссылке ') . '<a href="http://getsale.io" target="_blank">getSale.ru</a>',
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('Служба технической поддержки: ')
                        . '<a href="mailto:plugins@getsale.io">plugins@getsale.io</a>',
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('PrestaShop getSale ver.') . $this->version,
                    'name' => 'text')
            ),
            'submit' => array('title' => $this->l('Сохранить настройки'),
                'class' => 'button'));
        $helper = new HelperForm();
        // Module, token и currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        // Язык
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        // Заголовок и toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false - убирает toolbar
        $helper->toolbar_scroll = true;      // toolbar виден всегда наверху экрана.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Сохранить'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save'
                    . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules')
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token='
                    . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Венуться к списку')
            )
        );

        // Загружаем нужные нам значения из базы
        $helper->fields_value['getsale_key'] = Configuration::get('getsale_key');
        $helper->fields_value['getsale_email'] = Configuration::get('getsale_email');
        return $helper->generateForm($fields_form);
    }

    public function displayFormSuccess()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        //ширина полей в админке модуля
        $getsale_col = '6';
        // Описываем поля формы для страници настроек
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('getSale') . '  &mdash; ' . $this->l('Профессиональный инструмент для создания popup-окон'),
                'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('Оцените принципиально новый подход к созданию popup-окон'),
                    'name' => 'text'),
                array(
                    'type' => 'text',
                    'label' => $this->l('Email'),
                    'name' => 'getsale_email',
                    'col' => $getsale_col,
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Ключ API'),
                    'name' => 'getsale_key',
                    'col' => $getsale_col,
                ),
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('Войдите в личный кабинет ')
                        . '<a href="http://getsale.io" target="_blank">getSale.ru</a>'
                        . $this->l(' для просмотра статистики.'),
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('Служба технической поддержки: ')
                        . '<a href="mailto:plugins@getsale.io">plugins@getsale.io</a>',
                    'name' => 'text'),
                array(
                    'type' => 'free',
                    'col' => $getsale_col,
                    'desc' => $this->l('PrestaShop getSale ver.') . $this->version,
                    'name' => 'text')
            ),
            'submit' => array('title' => $this->l('Сохранить настройки'),
                'class' => 'button'));
        $helper = new HelperForm();
        // Module, token и currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        // Язык
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        // Заголовок и toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false - убирает toolbar
        $helper->toolbar_scroll = true;      // toolbar виден всегда наверху экрана.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Сохранить'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save'
                    . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules')
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token='
                    . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Венуться к списку')
            )
        );

        // Загружаем нужные нам значения из базы
        $helper->fields_value['getsale_key'] = Configuration::get('getsale_key');
        $helper->fields_value['getsale_email'] = Configuration::get('getsale_email');
        return $helper->generateForm($fields_form) . "<style>
        .gtsl_ok{
            right: 5px;
            top: 6px;
            position: absolute;
            padding-right: 10px;
        }
        </style>
        <script>
        $(document).ready(function () {
                $('input[id=getsale_email]').attr('disabled', 'disabled');
                $('input[id=getsale_key]').attr('disabled', 'disabled');
                $('input[id=getsale_email]').before(\"<img title='Введен правильный email!' class='gtsl_ok' src='$this->_path/views/img/ok.png' >\");
                $('input[id=getsale_key]').before(\"<img title='Введен правильный email!' class='gtsl_ok' src='$this->_path/views/img/ok.png' >\");
             });
            </script>";
    }

    /**
     * Получает Id площадки
     *
     * @param bool $token
     *
     * @return bool|mixed|string
     */
    public function getInfoFromGetsale($key, $email, $host)
    {
        $ch = curl_init();
        $jsondata = Tools::jsonEncode(array(
            'email' => $email,
            'key' => $key,
            'url' => $host,
            'cms' => 'prestashop'));

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json', 'Accept: application/json'));
        curl_setopt($ch, CURLOPT_URL, "http://edge.getsale.io/api/registration.json");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsondata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $server_output = curl_exec($ch);

        $json_result = Tools::jsonDecode($server_output);
        curl_close($ch);

        if (isset($json_result->status)) {
            if (($json_result->status == 'OK')) {
                Configuration::updateValue('getsale_id', $json_result->payload->projectId);
                return array('ok' => $json_result->payload->projectId);
            } elseif ($json_result->status == 'error') {
                if ($json_result->code == '403') {
                    $json_result->message = $this->l('Введен неверный ключ API!');
                }
                if ($json_result->code == '500') {
                    $json_result->message = $this->l('Невозможно создать проект. Возможно, он уже создан.');
                }
                if ($json_result->code == '404') {
                    $json_result->message = $this->l('Данный email ') . $email . $this->l(' не
                    зарегистрирован на сайте http://getsale.io');
                }
                if (!isset($json_result->code)) {
                    $json_result->message = $this->l('Неверный формат данных.');
                }
                return array('error' => $json_result->message);
            }
        }
        return true;
    }

    public function getsalejscode($id)
    {
        $jscode = "<script type='text/javascript'>
                    (function(d, w, c) {
                      w[c] = {
                        projectId:" . $id . "
                      };
                      var n = d.getElementsByTagName('script')[0],
                      s = d.createElement('script'),
                      f = function () { n.parentNode.insertBefore(s, n); };
                      s.type = 'text/javascript';
                      s.async = true;
                      s.src = '//rt.edge.getsale.io/loader.js';
                      if (w.opera == '[object Opera]') {
                          d.addEventListener('DOMContentLoaded', f, false);
                      } else { f(); }
                    })(document, window, 'getSaleInit');
                </script>";
        return $jscode;
    }


    /**
     * Save form data.
     */
    protected function _postProcess()
    {
    }

    /* Вывод кода в шапке */
    public function hookDisplayTop($params)
    {
        $getsalejscode = '';

        $currcontroller = Tools::strtolower(get_class($this->context->controller));
        if (Configuration::get('getsale_id')) {
            $getsalejscode .= $this->getsalejscode(Configuration::get('getsale_id'));
        }
        $this->context->smarty->assign('getsalejscode', $getsalejscode);
        return $this->display(__FILE__, 'getsale.tpl');
    }
}