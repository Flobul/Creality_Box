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
    public static $_pluginVersion = '0.50';
    public static $_widgetPossibility = array('custom' => true);


    /*     * ***********************Methode statique*************************** */

    /**
     * Récupère les infos du démon dans les processus
     * @return array Etat du démon
     */
    public static function deamon_info()
    {
        $return = array();
        $return['log'] = 'Creality_Box_Daemon';
        $return['state'] = 'nok';
        $pid = trim(shell_exec('ps ax | grep "/Creality_Boxd.php" | grep -v "grep" | wc -l'));
        if ($pid != '' && $pid != '0') {
            $return['state'] = 'ok';
        }
        if (config::byKey('listenport', 'Creality_Box') > '1') {
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
        log::add('Creality_Box_Daemon', 'info', __('Lancement du service Creality_Box', __FILE__));
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        if ($deamon_info['state'] == 'ok') {
            self::deamon_stop();
            sleep(2);
        }
        log::add('Creality_Box_Daemon', 'info', __('Lancement du démon Creality_Box', __FILE__));
        $cmd = substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), '/core/class')).'/resources/Creality_Boxd.php';

        $result = exec('sudo php ' . $cmd . ' >> ' . log::getPathToLog('Creality_Box_Daemon') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add('Creality_Box_Daemon', 'error', $result);
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
            log::add('Creality_Box_Daemon', 'error', __('Impossible de lancer le démon Creality_Box_Daemon', __FILE__), 'unableStartDaemon');
            return false;
        }
        log::add('Creality_Box_Daemon', 'info', __('Démon Creality_Box_Daemon lancé', __FILE__));
        return true;
    }

    /**
     * Arrête le démon
     * @return boolean Vrai si arrêté
     */
    public static function deamon_stop()
    {
        log::add('Creality_Box_Daemon', 'info', __('Arrêt du service Creality_Box', __FILE__));
        $cmd='/Creality_Boxd.php';
        exec('sudo kill -9 $(ps aux | grep "'.$cmd.'" | awk \'{print $2}\')');
        sleep(1);
        exec('sudo kill -9 $(ps aux | grep "'.$cmd.'" | awk \'{print $2}\')');
        sleep(1);
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            exec('sudo kill -9 $(ps aux | grep "'.$cmd.'" | awk \'{print $2}\')');
            sleep(1);
        } else {
            return true;
        }
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            exec('sudo kill -9 $(ps aux | grep "'.$cmd.'" | awk \'{print $2}\')');
            sleep(1);
            return true;
        }
    }

    /**
     * Méthode appellée avant la création de l'objet
     * Active et affiche l'objet
     */
    public function preInsert()
    {
        $this->setIsEnable(1);
        $this->setIsVisible(1);
        config::save('heartbeat::delay::Creality_Box', 720, 'Creality_Box');
        config::save('heartbeat::restartDeamon::Creality_Box', 1, 'Creality_Box');
    }

    /**
     * Méthode appellée après la création de l'objet
     */
    public function postInsert()
    {
        $halt = $this->getCmd('action', 'halt');
        if (!is_object($halt) ) {
            $halt = new Creality_BoxCmd();
			$halt->setName('Éteindre la box');
			$halt->setEqLogic_id($this->getId());
			$halt->setType('action');
			$halt->setSubType('other');
			$halt->setLogicalId('halt');
            $halt->setGeneric_type('ENERGY_OFF');
			$halt->save();
		}
        $reboot = $this->getCmd('action', 'reboot');
        if (!is_object($reboot) ) {
            $reboot = new Creality_BoxCmd();
			$reboot->setName('Redémarrer la box');
			$reboot->setEqLogic_id($this->getId());
			$reboot->setType('action');
			$reboot->setSubType('other');
			$reboot->setLogicalId('reboot');
            $reboot->setGeneric_type('REBOOT');
			$reboot->save();
		}
    }

    public static function getData($_RAW)
    {
        $lines = explode(PHP_EOL,$_RAW);
        $_RAW = array_slice($lines, 1, count($lines)-2);
        log::add('Creality_Box', 'info', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Information reçue : ', __FILE__) . $_RAW[0]);
        return trim($_RAW[0]);
    }

    /**
     * Méthode appellée avant la sauvegarde (creation et mise à jour donc) de l'objet
     * Si telnet, récupère modèle, type et versions
     */
    public function preSave()
    {
        log::add('Creality_Box', 'info', 'L.' . __LINE__ . ' F.' . __FUNCTION__);
        if ($this->getConfiguration('hostname', '') == '') {
            $errno = '';
            $errstr = '';
            $listen = config::byKey('listenport', 'Creality_Box', '23');
            $ipadr = config::byKey('ip', 'Creality_Box');
            $id = config::byKey('id', 'Creality_Box', 'root');
            $pwd = config::byKey('password', 'Creality_Box', 'cxswprin');

            $telnet = new telnet_Creality_Box();
            $connect = $telnet->telnetConnect($ipadr, $listen, $errno, $errstr);
            if ($connect) {
            log::add('Creality_Box', 'info', 'L.' . __LINE__ . ' F.' . __FUNCTION__);
                sleep(2);
                $telnet->telnetReadResponse($result);
                if (!preg_match('/login:/i', $result, $matches)) {
                    $telnet->telnetDisconnect();
                    log::add('Creality_Box', 'error', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez l\'IP ou le port) : ', __FILE__) . $result);
                }
                $telnet->telnetSendCommand($id, $resp);
                if (!preg_match('/Password:/i', $resp, $matches)) {
                    $telnet->telnetDisconnect();
                    log::add('Creality_Box', 'error', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez l\'identifiant) : ', __FILE__) . $resp);
                }
                sleep(2);
                $telnet->telnetSendCommand($pwd, $resp); // BusyBox v1.12.1 (2020-12-16 14:52:12 CST) built-in shell (ash) \nEnter 'help' for a list of built-in commands.\n# "
                if (!preg_match('/^BusyBox/i', trim($resp), $matches)) {
                    $telnet->telnetDisconnect();
                    log::add('Creality_Box', 'error', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez le mot de passe) : ', __FILE__) . $resp);
                }

                $telnet->telnetSendCommand('hostname', $resp);
                $this->setConfiguration('hostname', Creality_Box::getData($resp));
                $telnet->telnetDisconnect();
            }
        }
    }

    /**
     * Méthode appellée avant la mise à jour de l'objet
     */
    public function preUpdate()
    {
    }

    /**
     * Méthode appellée après la mise à jour de l'objet
     */
    public function postUpdate()
    {
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
        $Creality->setName("Creality Box " . trim($_ip));
        $Creality->setLogicalId($_ip);
        $Creality->setObject_id(null);
        $Creality->setEqType_name('Creality_Box');
        $Creality->setIsEnable(1);
        $Creality->setIsVisible(1);
        $Creality->setConfiguration('IP', trim($_ip));
        $Creality->save();
        event::add('jeedom::alert', array(
					'level' => 'warning',
					'page' => 'Creality_Box',
					'message' => __('L\'équipement ', __FILE__) . $Creality->getHumanName() . __(' vient d\'être créé', __FILE__),
				));
        return $Creality;
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
            log::add(__CLASS__, 'debug', __("JSON invalide : ", __FILE__) . 'Creality_Box.json');
            return false;
        }
        $device = json_decode($content, true);
        if (!is_array($device) || !isset($device['commands'])) {
            log::add(__CLASS__, 'debug', __("Tableau incorrect : ", __FILE__) . 'Creality_Box.json');
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
            utils::a2o($cmd, $param_cmd);
        }
        $cmd->save();
        return $cmd;
    }

    public function toHtml($_version = 'dashboard') {

        if ($this->getConfiguration('widgetTemplate') != 1) {
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
        }

		$html = template_replace($replace, getTemplate('core', $_version, 'Creality_Box.template',__CLASS__));
        $html = translate::exec($html, 'plugins/Creality_Box/core/template/' . $_version . '/Creality_Box.template.html');
        return $html;
    }
}

class Creality_BoxCmd extends cmd
{
    public function execute($_options = array())
    {
        log::add('Creality_Box', 'debug', __("Action sur ", __FILE__) . $this->getLogicalId());
        switch ($this->getLogicalId()) {
            case 'halt':
            case 'reboot':
                $errno  = '';
                $errstr = '';
                $listen = config::byKey('listenport', 'Creality_Box', '23');
                $ipadr  = config::byKey('ip', 'Creality_Box');
                $id     = config::byKey('id', 'Creality_Box', 'root');
                $pwd    = config::byKey('password', 'Creality_Box', 'cxswprin');

                $telnet = new telnet_Creality_Box();
                $connect = $telnet->telnetConnect($ipadr, $listen, $errno, $errstr);
                if ($connect) {
                log::add('Creality_Box', 'info', 'L.' . __LINE__ . ' F.' . __FUNCTION__);

                    sleep(2);
                    $telnet->telnetReadResponse($result);
                    if (!preg_match('/login:/i', $result, $matches)) {
                        $telnet->telnetDisconnect();
                        log::add('Creality_Box', 'error', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez l\'IP ou le port) : ', __FILE__) . $result);
                    }
                    $telnet->telnetSendCommand($id, $resp);
                    if (!preg_match('/Password:/i', $resp, $matches)) {
                        $telnet->telnetDisconnect();
                        log::add('Creality_Box', 'error', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez l\'identifiant) : ', __FILE__) . $resp);
                    }
                    sleep(2);
                    $telnet->telnetSendCommand($pwd, $resp); // BusyBox v1.12.1 (2020-12-16 14:52:12 CST) built-in shell (ash) \nEnter 'help' for a list of built-in commands.\n# "
                    if (!preg_match('/^BusyBox/i', trim($resp), $matches)) {
                        $telnet->telnetDisconnect();
                        log::add('Creality_Box', 'error', 'L.' . __LINE__ . ' F.' . __FUNCTION__ . __(' Erreur de connexion (vérifiez le mot de passe) : ', __FILE__) . $resp);
                    }

                    $telnet->telnetSendCommand($this->getLogicalId(), $resp);
                    $telnet->telnetDisconnect();
                }
                break;
          }
    }
}