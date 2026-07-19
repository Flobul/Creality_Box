<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__ . "/../../../../core/php/core.inc.php";
require_once __DIR__ . "/../../../../plugins/Creality_Box/3rdparty/telnet.php";

class Creality_Box extends eqLogic
{
    /*     * *************************Attributs****************************** */
    public static $_pluginVersion = '0.91';
    public static $_widgetPossibility = array('custom' => true);

    /*     * ***********************Methode statique*************************** */

    /*
     * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
     * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
     */
     public static $_encryptConfigKey = array('password');


    /**
     * Récupère les infos du démon dans les processus
     * @return array Etat du démon
     */
    public static function deamon_info()
    {
        $return = array();
        $return['log'] = __CLASS__ . '_Daemon';
        $return['state'] = 'nok';
        $pid = trim(shell_exec('ps ax | grep "/Creality_Boxd.php" | grep -v "grep" | wc -l'));
        if ($pid != '' && $pid != '0') {
            $return['state'] = 'ok';
        }
        if (config::byKey('listenport', __CLASS__) > '1') {
            $return['launchable'] = 'ok';
        } else {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le port n\'est pas configuré.', __FILE__);
        }
        return $return;
    }

    /**
     * Démarre le démon pendant 30 secondes, ou le redémarre si déjà démarré
     * @param  boolean $_debug [description]
     * @return boolean         Vrai si OK, faux si erreur.
     */
    public static function deamon_start($_debug = false)
    {
		self::deamon_stop();
        log::add(__CLASS__ . '_Daemon', 'info', __('Lancement du service Creality_Box', __FILE__));
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        if ($deamon_info['state'] == 'ok') {
            self::deamon_stop();
            sleep(2);
        }
        log::add(__CLASS__ . '_Daemon', 'info', __('Lancement du démon Creality_Box', __FILE__));
        $cmd = substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), '/core/class')).'/resources/Creality_Boxd.php';

        $result = exec('sudo php ' . $cmd . ' >> ' . log::getPathToLog(__CLASS__ . '_Daemon') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add(__CLASS__ . '_Daemon', 'error', $result);
            return false;
        }
        sleep(1);
        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add(__CLASS__ . '_Daemon', 'error', __('Impossible de lancer le démon Creality_Box_Daemon', __FILE__), 'unableStartDaemon');
            return false;
        }
        log::add(__CLASS__ . '_Daemon', 'info', __('Démon Creality_Box_Daemon lancé', __FILE__));
        return true;
    }

    /**
     * Arrête le démon
     * @return boolean Vrai si arrêté
     */
    public static function deamon_stop()
    {
        log::add(__CLASS__ . '_Daemon', 'info', __('Arrêt du service Creality_Box', __FILE__));
        foreach (system::ps('Creality_Boxd.php') as $process) {
            if (isset($process['pid']) && is_numeric($process['pid'])) {
                system::kill((int) $process['pid']);
            }
        }
        sleep(1);
        return self::deamon_info()['state'] !== 'ok';
    }

    public static function getData($_RAW)
    {
        $lines = explode(PHP_EOL,$_RAW);
        $_RAW = array_slice($lines, 1, count($lines)-2);
        log::add(__CLASS__, 'info', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Information reçue : ', __FILE__) . $_RAW[0]);
        return trim($_RAW[0]);
    }

    /**
     * Créé l'équipement avec les valeurs du buffer
     * @param array $_data Tableau des valeurs récupérées dans le buffer
     * @param string $_IP   IP relevée à la réception du buffer
     * @return object $Optoma Retourne l'équipement créé
     */
    public static function addEquipement($_ip)
    {
        $Creality = new Creality_Box();
        $Creality->setName('Creality Box ' . trim($_ip));
        $Creality->setLogicalId($_ip);
        $Creality->setObject_id(null);
        $Creality->setEqType_name(__CLASS__);
        $Creality->setIsEnable(1);
        $Creality->setIsVisible(1);
        $Creality->setConfiguration('IP', trim($_ip));
        $Creality->save();
        event::add('jeedom::alert', array(
					'level' => 'warning',
					'page' => __CLASS__,
					'message' => __('L\'équipement ', __FILE__) . $Creality->getHumanName() . __(' vient d\'être créé', __FILE__),
				));
        return $Creality;
    }

    /**
     * Méthode appellée avant la création de l'objet
     * Active et affiche l'objet
     */
    public function preInsert()
    {
        $this->setIsEnable(1);
        $this->setIsVisible(1);
        config::save('heartbeat::delay::' . __CLASS__, 720, __CLASS__);
        config::save('heartbeat::restartDeamon::' . __CLASS__, 1, __CLASS__);
    }

    /**
     * Méthode appellée après la création de l'objet
     */
    public function postInsert()
    {
        if (!is_object($this->getCmd('action', 'halt'))) {
            $this->loadCmdFromConf('halt');
        }
        if (!is_object($this->getCmd('action', 'reboot'))) {
            $this->loadCmdFromConf('reboot');
        }
        if (!is_object($this->getCmd('action', 'refresh'))) {
            $this->loadCmdFromConf('refresh');
        }
        if (!is_object($this->getCmd('action', 'stop::set'))) {
            $this->loadCmdFromConf('stop::set');
        }
        if (!is_object($this->getCmd('action', 'pause::on'))) {
            $this->loadCmdFromConf('pause::on');
        }
        if (!is_object($this->getCmd('action', 'pause::off'))) {
            $this->loadCmdFromConf('pause::off');
        }
        if (!is_object($this->getCmd('action', 'autohome::set'))) {
            $this->loadCmdFromConf('autohome::set');
        }
    }

    /**
     * Méthode appellée avant la sauvegarde (creation et mise à jour donc) de l'objet
     * Si telnet, récupère modèle, type et versions
     */
    public function preSave()
    {
        log::add(__CLASS__, 'info', 'L.' . __LINE__ . ' F.' . __FUNCTION__);
        $ipadr = config::byKey('ip', __CLASS__, '');
        if ($ipadr != '') {
            $errno = '';
            $errstr = '';
            $listen = config::byKey('listenport', __CLASS__);
            $id = config::byKey('id', __CLASS__);
            $pwd = config::byKey('password', __CLASS__);

            $this->requestGet('http://' . $this->getConfiguration('IP') . ':81/protocal.csp?fname=Info&opt=main&function=get');

            $telnet = new telnet_Creality_Box();
            $connect = $telnet->telnetConnect($ipadr, $listen, $errno, $errstr);
            if ($connect) {
                sleep(2);
                $telnet->telnetReadResponse($result);
                if (!preg_match('/login:/i', $result, $matches)) {
                    $telnet->telnetDisconnect();
                    log::add(__CLASS__, 'debug', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez l\'IP ou le port) : ', __FILE__) . $result);
                }
                $telnet->telnetSendCommand($id, $resp);
                if (!preg_match('/Password:/i', $resp, $matches)) {
                    $telnet->telnetDisconnect();
                    log::add(__CLASS__, 'debug', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez l\'identifiant) : ', __FILE__) . $resp);
                }
                sleep(2);
                $telnet->telnetSendCommand($pwd, $resp); // BusyBox v1.12.1 (2020-12-16 14:52:12 CST) built-in shell (ash) \nEnter 'help' for a list of built-in commands.\n# "
                if (!preg_match('/^BusyBox/i', trim($resp), $matches)) {
                    $telnet->telnetDisconnect();
                    log::add(__CLASS__, 'debug', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez le mot de passe) : ', __FILE__) . $resp);
                }

                $telnet->telnetSendCommand('hostname', $resp);
                $this->setConfiguration('hostname', Creality_Box::getData($resp));
                $telnet->telnetDisconnect();
            }
        }
        if (!is_object($this->getCmd('action', 'halt'))) {
            $this->loadCmdFromConf('halt');
        }
        if (!is_object($this->getCmd('action', 'reboot'))) {
            $this->loadCmdFromConf('reboot');
        }
        if (!is_object($this->getCmd('action', 'refresh'))) {
            $this->loadCmdFromConf('refresh');
        }
        if (!is_object($this->getCmd('action', 'stop::set'))) {
            $this->loadCmdFromConf('stop::set');
        }
        if (!is_object($this->getCmd('action', 'pause::on'))) {
            $this->loadCmdFromConf('pause::on');
        }
        if (!is_object($this->getCmd('action', 'pause::off'))) {
            $this->loadCmdFromConf('pause::off');
        }
        if (!is_object($this->getCmd('action', 'autohome::set'))) {
            $this->loadCmdFromConf('autohome::set');
        }
    }

    /**
     * Met à jour les équipements selon la configuration de l'auto-rafraîchissement.
     */
    public static function update()
    {
        $autorefresh = config::byKey('autorefresh', __CLASS__, 'never');
        $eqLogics = eqLogic::byType(__CLASS__);
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('Démarrage du cron ', __FILE__) . $autorefresh);
        if ($autorefresh != 'never') {
            try {
                $c = new Cron\CronExpression(checkAndFixCron($autorefresh), new Cron\FieldFactory);
                if ($c->isDue()) {
                    try {
                        foreach ($eqLogics as $eqLogic) {
                            if ($eqLogic->getIsEnable()) {
                                $time_start = microtime(true);
                                $eqLogic->requestGet('http://' . $eqLogic->getConfiguration('IP') . ':81/protocal.csp?fname=Info&opt=main&function=get');
                                $time_end = microtime(true);
                                $time = round($time_end - $time_start, 2);
                                log::add(__CLASS__, 'info', __FUNCTION__ . ' : ' . __('Fin du cron équipement ', __FILE__) . $eqLogic->getName() . ' en ' . $time . ' secondes');
                                $eqLogic->createAndUpdateFileListCommand();
                                $eqLogic->checkAndCreateActionCommands();
                            }
                        }
                    } catch (Exception $exc) {
                        log::add(__CLASS__, 'error', __('Erreur : ', __FILE__) . $exc->getMessage());
                    }
                }
            } catch (Exception $exc) {
                log::add(__CLASS__, 'error', __('Expression cron non valide : ', __FILE__) . $autorefresh);
            }
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin', __FILE__));
    }

    public function requestGet($_url)
    {
        $request_http = new com_http($_url);
        $result = $request_http->exec();
        $array = json_decode($result, true);
        if (is_array($array)) {
        log::add(__CLASS__, 'debug', 'TETTT ' . json_encode($array));
            $this->setConfiguration('ssid', $array['ssid']);
            $this->setConfiguration('wifipasswd', $array['wifipasswd']);
            $this->setConfiguration('model', $array['model']);
            $this->setConfiguration('boxVersion', $array['boxVersion']);
            $this->setConfiguration('modelVersion', $array['modelVersion']);
        }
        foreach ($array as $key => $value) {
            $existing_cmd = $this->getCmd('info', $key);
            $cmd = is_object($existing_cmd) ? $existing_cmd : $this->loadCmdFromConf($key);

            log::add(__CLASS__, 'info', __('Information', __FILE__) . ' : ' . $key."=". $value . __(' renseignée dans : ', __FILE__) . $this->getName());
            if (is_object($cmd)) {
                $cmd->event($value);
            }
        }
        log::add(__CLASS__, 'debug', 'TETTT2 ' . $this->getConfiguration('modelVersion'));
//{ "opt": "main", "fname": "Info", "function": "get", "wanmode": "DHCP", "wanphy_link": 1, "link_status": 1, "wanip": "192.168.23.241", "ssid": "CXSWBox-FCEEE6007F34", "channel": 6, "security": 1, "wifipasswd": "12345678", "apclissid": "", "iot_type": "tb", "connect": 1, "model": "Ender-3 V2 ", "fan": 0, "nozzleTemp": 23, "bedTemp": 24, "_1st_nozzleTemp": 0, "_2nd_nozzleTemp": 0, "chamberTemp": 0, "nozzleTemp2": 0, "bedTemp2": 0, "_1st_nozzleTemp2": 0, "_2nd_nozzleTemp2": 0, "chamberTemp2": 0, "print": "localhost", "printProgress": 0, "stop": 0, "printStartTime": "0", "state": 4, "err": 1, "boxVersion": "V3.04b06", "upgrade": "", "upgradeStatus": 0, "tfCard": 1, "dProgress": 0, "layer": 0, "pause": 0, "reboot": 0, "video": 1, "DIDString": "", "APILicense": "", "InitString": "", "printedTimes": 0, "timesLeftToPrint": 0, "ownerId": "C", "curFeedratePct": 100, "curPosition": "X:5.00 Y:20.00 Z:0.30", "autohome": 2, "repoPlrStatus": 0, "modelVersion": "printer hw ver:unknow;printer sw ver:Marlin E3V2-2.0.x-17-Smith3D.M (Mar 18 2021 13:23:44);DWIN hw ver:unknow;DWIN sw ver:unknow;", "mcu_is_print": 0, "printLeftTime": 50, "printJobTime": 213, "netIP": "192.168.23.241", "FilamentType": "", "ConsumablesLen": "", "TotalLayer": 0, "led_state": 0, "error": 0 }

    }

    /**
     * Recherche la configuration dans le dossier du modèle
     * et créé les commandes si elles sont inexistantes
     * @param  $param		paramètre info remonté
     * @return $object       Renvoi la cmd
     */
    public function loadCmdFromConf($param)
    {
        if (!is_file(dirname(__FILE__) . '/../../core/config/devices/Creality_Box.json')) {
            log::add(__CLASS__, 'debug', __("Fichier introuvable : ", __FILE__) . dirname(__FILE__) . '/config/devices/Creality_Box.json');
            return false;
        }
        $content = file_get_contents(dirname(__FILE__) . '/../../core/config/devices/Creality_Box.json');
        if (!is_json($content)) {
            log::add(__CLASS__, 'debug', __("JSON invalide : ", __FILE__) . __CLASS__ . '.json');
            return false;
        }
        $device = json_decode($content, true);
        if (!is_array($device) || !isset($device['commands'])) {
            log::add(__CLASS__, 'debug', __("Tableau incorrect : ", __FILE__) . __CLASS__ . '.json');
            return false;
        }

        $param_cmd = null;
        $cmd = new Creality_BoxCmd();
        $cmd->setEqLogic_id($this->getId());
        foreach ($device['commands'] as $command) {
            if ($command['logicalId'] == $param) { // si le paramètre est dans les conf connues
                $param_cmd = $command;
                break;
            }
        }
        if ($param_cmd === null || !is_array($param_cmd)) { // si on a pas récupéré les paramètres, on ajoute manuellement
            $cmd->setLogicalId($param);
            $cmd->setName($param);
            $cmd->setType('info');
            $cmd->setSubtype('string');
        } else { // si on a récupéré les paramètres de cmd, on les applique à la commande
            log::add(__CLASS__, 'debug', __("Création de commande : ", __FILE__) . json_encode($param_cmd));
            utils::a2o($cmd, $param_cmd);
        }
        $cmd->save();
        if ($link = $cmd->getConfiguration('command', '') != '') {
            $cmdLinked = eqLogic::byLogicalId($link, $this->getId());
            if (is_object($cmdLinked)) {
                $cmd->setValue($cmdLinked->getId());
                $cmd->save();
                log::add(__CLASS__, 'debug', '-> Valeur lié depuis : '.$link." (".$cmdLinked->getId().")");
            } else {
                log::add(__CLASS__, 'warning', '-> Liaison impossible objet introuvable : '.$link);
            }
        }
        return $cmd;
    }

    public function checkAndCreateActionCommands() {

    }

    public function createAndUpdateFileListCommand() {
        $ftp = ftp_connect($this->getConfiguration('IP'));
        $login_result = ftp_login($ftp, 'anonymous', '');
        $contents = ftp_nlist($ftp, "/mmcblk0p1/creality/gztemp/");
        $cmd = $this->getCmd('action', 'print::set');
        $listValue = 'localhost|Locale;';
        foreach ($contents as $file) {
            $listValue .= $file . '|'. basename($file) . ';';
        }
        $listValue = substr($listValue, 0, -1);
        if (!is_object($cmd)) {
            $cmd = new Creality_BoxCmd();
			$cmd->setName(__("Lancer l'impression", __FILE__));
			$cmd->setEqLogic_id($this->getId());
			$cmd->setType('action');
			$cmd->setSubType('select');
			$cmd->setLogicalId('print::set');
            $cmd->setConfiguration('listValue', $listValue);
            $cmd->setDisplay("icon", "<i class=\"icon fas fa-play icon_green\"><\/i>");
            $cmd->setConfiguration('updateCmdToValue', '#select#');
            $printCmd = $this->getCmd('info', 'print');
            if (is_object($printCmd)) {
                $cmd->setConfiguration('updateCmdId', $printCmd->getId());
                $cmd->setValue($printCmd->getId());
            }
			$cmd->save();
        }
        if (is_object($cmd) && $cmd->getConfiguration('listValue') != $listValue) {
            $cmd->setConfiguration('listValue', $listValue)->save();
        }

        log::add(__CLASS__, 'info', __FUNCTION__ . ' : ' . __('Fin du cron $contents ', __FILE__) .json_encode($contents));
    }

    public function toHtml($_version = 'dashboard') {

        if ($this->getDisplay('widgetTmpl') != 1) {
            return parent::toHtml($_version);
        }
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
		$_version = jeedom::versionAlias($_version);

        // informations de l'equipement
        $replace['#device_ip#'] = $this->getConfiguration('IP', "");
        $replace['#device_hostname#'] = $this->getConfiguration('hostname', "");

        foreach ($this->getCmd('info', null) as $cmd) {
            $replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            $replace['#cmd_' . $cmd->getLogicalId() . '_name#'] = $cmd->getName();
            $replace['#cmd_' . $cmd->getLogicalId() . '_value#'] = $cmd->execCmd();
            $replace['#cmd_' . $cmd->getLogicalId() . '_display#'] = (is_object($cmd) && $cmd->getIsVisible()) ? '#cmd_' . $cmd->getLogicalId() . '_display#' : "none";
            $replace['#cmd_' . $cmd->getLogicalId() . '_collectDate#'] = $cmd->getCollectDate();
            $replace['#cmd_' . $cmd->getLogicalId() . '_valueDate#'] = $cmd->getValueDate();
            $replace['#cmd_' . $cmd->getLogicalId() . '_unite#'] = $cmd->getUnite();
            $replace['#cmd_' . $cmd->getLogicalId() . '_maxValue#'] = $cmd->getConfiguration('maxValue');
            $replace['#cmd_' . $cmd->getLogicalId() . '_minValue#'] = $cmd->getConfiguration('minValue');
        }
        $cmdHtml = '';
        foreach ($this->getCmd('action', null) as $cmd) {
            if (isset($replace['#refresh_id#']) && $cmd->getId() == $replace['#refresh_id#']) {
                continue;
            }
            if ($cmd->getSubType() == 'other') {
                $cmdHtml .= $cmd->toHtml($_version, '');
            } else {
                $replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
                $replace['#cmd_' . $cmd->getLogicalId() . '_name#'] = $cmd->getName();
                $replace['#cmd_' . $cmd->getLogicalId() . '_display#'] = (is_object($cmd) && $cmd->getIsVisible()) ? '#cmd_' . $cmd->getLogicalId() . '_display#' : "none";
                $replace['#cmd_' . $cmd->getLogicalId() . '_unite#'] = $cmd->getUnite();
                if ($cmd->getConfiguration('listValue', '') != '') {
                    $listOption = '';
                    $elements = explode(';', $cmd->getConfiguration('listValue', ''));
                    $foundSelect = false;
                    foreach ($elements as $element) {
                        $coupleArray = explode('|', $element);
                        $cmdValue = $cmd->getCmdValue();
                        if (is_object($cmdValue) && $cmdValue->getType() == 'info') {
                            if (basename($cmdValue->execCmd()) == $coupleArray[0] || basename($cmdValue->execCmd()) == $coupleArray[1]) {
                                $listOption .= '<option value="' . $coupleArray[0] . '" selected>' . $coupleArray[1] . '</option>';
                                $foundSelect = true;
                            } else {
                                $listOption .= '<option value="' . $coupleArray[0] . '">' . $coupleArray[1] . '</option>';
                            }
                        } else {
                            if (isset($coupleArray[1])) {
                                $listOption .= '<option value="' . $coupleArray[0] . '">' . $coupleArray[1] . '</option>';
                            } else {
                                $listOption .= '<option value="' . $coupleArray[0] . '">' . $coupleArray[0] . '</option>';
                            }
                        }
                    }
                    if (!$foundSelect) {
                        $listOption = '<option value="">Aucun</option>' . $listOption;
                    }
                    $replace['#cmd_' . $cmd->getLogicalId() . '_listValue#'] = $listOption;
                }
                $cmdValue = $cmd->getCmdValue();
                if (is_object($cmdValue) && $cmdValue->getType() == 'info') {
                    $replace['#cmd_' . $cmd->getLogicalId() . '_value#'] = $cmdValue->execCmd();
                }
            }
        }
        $replace['#cmd#'] = $cmdHtml;
		$html = template_replace($replace, getTemplate('core', $_version, __CLASS__ . '.template',__CLASS__));
        $html = translate::exec($html, 'plugins/' . __CLASS__ . '/core/template/' . $_version . '/' . __CLASS__ . '.template.html');
        return $html;
    }
}

class Creality_BoxCmd extends cmd
{
    public function execute($_options = array())
    {
        $eqLogic = $this->getEqlogic();
        log::add('Creality_Box', 'debug', __("Action sur ", __FILE__) . $this->getLogicalId());

        switch ($this->getSubType()) {
            case 'slider':
                $replace['#slider#'] = intval($_options['slider']);
                break;
            case 'color':
                $replace['#color#'] = $_options['color'];
                break;
            case 'select':
                $replace['#select#'] = $_options['select'];
                break;
            case 'message':
                $replace['#title#'] = $_options['title'];
                $replace['#message#'] = $_options['message'];
                if ($_options['message'] == '' && $_options['title'] == '') {
                    throw new Exception(__('Le message et le sujet ne peuvent pas être vide', __FILE__));
                }
                break;
        }
        $value = str_replace(array_keys($replace),$replace,$this->getConfiguration('updateCmdToValue', ''));

        switch ($this->getLogicalId()) {
            case 'halt':
            case 'reboot':
                $errno  = '';
                $errstr = '';
                $listen = config::byKey('listenport', 'Creality_Box');
                $ipadr  = config::byKey('ip', 'Creality_Box');
                $id     = config::byKey('id', 'Creality_Box');
                $pwd    = config::byKey('password', 'Creality_Box');

                $telnet = new telnet_Creality_Box();
                $connect = $telnet->telnetConnect($ipadr, $listen, $errno, $errstr);
                if ($connect) {
                log::add('Creality_Box', 'info', 'L.' . __LINE__ . ' F.' . __FUNCTION__);

                    sleep(2);
                    $telnet->telnetReadResponse($result);
                    if (!preg_match('/login:/i', $result, $matches)) {
                        $telnet->telnetDisconnect();
                        log::add('Creality_Box', 'debug', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez l\'IP ou le port) : ', __FILE__) . $result);
                    }
                    $telnet->telnetSendCommand($id, $resp);
                    if (!preg_match('/Password:/i', $resp, $matches)) {
                        $telnet->telnetDisconnect();
                        log::add('Creality_Box', 'debug', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez l\'identifiant) : ', __FILE__) . $resp);
                    }
                    sleep(2);
                    $telnet->telnetSendCommand($pwd, $resp); // BusyBox v1.12.1 (2020-12-16 14:52:12 CST) built-in shell (ash) \nEnter 'help' for a list of built-in commands.\n# "
                    if (!preg_match('/^BusyBox/i', trim($resp), $matches)) {
                        $telnet->telnetDisconnect();
                        log::add('Creality_Box', 'debug', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez le mot de passe) : ', __FILE__) . $resp);
                    }

                    $telnet->telnetSendCommand($this->getLogicalId(), $resp);
                    $telnet->telnetDisconnect();
                }
                break;
            case 'refresh':
                $eqLogic->requestGet('http://' . $eqLogic->getConfiguration('IP') . ':81/protocal.csp?fname=Info&opt=main&function=get');
                break;
            case 'autohome::set':
            case 'stop::set':
            case 'pause::on':
            case 'pause::off':
                $eqLogic->requestGet('http://' . $eqLogic->getConfiguration('IP') . ':81/protocal.csp?fname=net&opt=iot_conf&function=set&'.$this->getConfiguration('command').'='.$this->getConfiguration('commandValue'));
                break;
            case 'nozzleTemp2::set':
            case 'bedTemp2::set':
            case 'curFeedratePct::set':
            case 'fan::set':
                $eqLogic->requestGet('http://' . $eqLogic->getConfiguration('IP') . ':81/protocal.csp?fname=net&opt=iot_conf&function=set&'.$this->getConfiguration('command').'='.$value);
                break;
          }
    }
}
