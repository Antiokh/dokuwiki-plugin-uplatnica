<?php
/**
 * DokuWiki Plugin (Action Component) для подключения библиотек
 * qrcode.min.js и uplatnica.js.
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Your Name
 */

if (!defined('DOKU_INC')) die();

class action_plugin_uplatnica extends \dokuwiki\Extension\ActionPlugin
{
    /** @inheritDoc */
    public function register(Doku_Event_Handler $controller)
    {
        // Регистрация хука для добавления скриптов и стилей на страницу
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'load');
    }

    public function load(Doku_Event $event, $param)
    {
        // Проверяем, был ли подключен скрипт qrcode.min.js
        $qrcodeScriptAdded = false;

        // Проверяем, есть ли qrcode.min.js в папке плагина
        $localQrcodePath = DOKU_BASE . 'lib/plugins/uplatnica/qrcode.js';
        if (file_exists($localQrcodePath)) {
            $qrcodeScriptAdded = true;
            $event->data['script'][] = array(
                'type'    => 'text/javascript',
                'charset' => 'utf-8',
                'src'     => DOKU_BASE . 'lib/plugins/uplatnica/qrcode.js'
            );
        }

        // Если qrcode.min.js нет в папке плагина, загружаем его с CDN
        if (!$qrcodeScriptAdded) {
            $event->data['script'][] = array(
                'type'    => 'text/javascript',
                'charset' => 'utf-8',
                'src'     => 'https://unpkg.com/qrcode-generator@1.4.4/qrcode.js'
            );
        }

        // Проверяем, был ли уже добавлен скрипт uplatnica.js
        $alreadyAdded = false;
        foreach ($event->data['script'] as $script) {
            if (isset($script['src']) && strpos($script['src'], 'uplatnica.js') !== false) {
                $alreadyAdded = true;
                break;
            }
        }

        // Если скрипт uplatnica.js ещё не добавлен, подключаем его
        if (!$alreadyAdded) {
            $event->data['script'][] = array(
                'type'    => 'text/javascript',
                'charset' => 'utf-8',
                'src'     => DOKU_BASE . 'lib/plugins/uplatnica/uplatnica.js'
            );
        }
    }
}
