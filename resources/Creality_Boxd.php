#!/usr/
<?php

require_once dirname(__FILE__) . '/../core/class/Creality_Box.class.php';

log::add('Creality_Box_Daemon', 'info', __('Activation du service Creality_Box', __FILE__));

$listen  = config::byKey('listenport', 'Creality_Box');
$ipadr   = config::byKey('ip', 'Creality_Box');
$id      = config::byKey('id', 'Creality_Box', 'root');
$pwd     = config::byKey('password', 'Creality_Box', 'cxswprin'); // mot de passe par défaut hacked
$logmqtt = config::byKey('logmqtt', 'Creality_Box', '/media/mmcblk0p1/creality/log/iotlink.log');

$telnet = new telnet_Creality_Box();
$errno = '';
$errstr = '';

log::add('Creality_Box_Daemon', 'debug', __('Activation du daemon Creality_Box sur l\'IP ', __FILE__) . $ipadr . ' port : ' . $listen);

$eqLogics = eqLogic::byType('Creality_Box');
$refreshtime = 60;
$delai = time();
$resp = "";
$result = "";
$connect = $telnet->telnetConnect($ipadr, $listen, $errno, $errstr);

    if ($connect) {
        sleep(2);
        $telnet->telnetReadResponse($result);
        if (!preg_match('/login:/i', $result, $matches)) {
            $telnet->telnetDisconnect();
            log::add('Creality_Box_Daemon', 'error', "█ " . __('Erreur de connexion (vérifiez l\'IP ou le port) : ', __FILE__) . $result);
            Creality_Box::deamon_stop();
        }
        $telnet->telnetSendCommand($id, $resp);
        if (!preg_match('/Password:/i', $resp, $matches)) {
            $telnet->telnetDisconnect();
            log::add('Creality_Box_Daemon', 'error', "█ " . __('Erreur de connexion (vérifiez l\'identifiant) : ', __FILE__) . $resp);
            Creality_Box::deamon_stop();
        }
        sleep(2);
        $telnet->telnetSendCommand($pwd, $resp); // BusyBox v1.12.1 (2020-12-16 14:52:12 CST) built-in shell (ash) \nEnter 'help' for a list of built-in commands.\n# "
        if (!preg_match('/^BusyBox/i', trim($resp), $matches)) {
            $telnet->telnetDisconnect();
            log::add('Creality_Box_Daemon', 'error', "█ " . __('Erreur de connexion (vérifiez le mot de passe) : ', __FILE__) . $resp);
            Creality_Box::deamon_stop();
        }

      	$Creality_Box = Creality_Box::byLogicalId(config::byKey('ip', 'Creality_Box'), 'Creality_Box');
		if (!is_object($Creality_Box)) {
            log::add('Creality_Box_Daemon', 'debug', "▄ " . __(' L\'équipement n\'existe pas : ', __FILE__) . $resp);
            Creality_Box::addEquipement(config::byKey('ip', 'Creality_Box'));
        }
        sleep(2);
        $path_parts = pathinfo($logmqtt);

        if ($path_parts['basename'] == 'iotlink.log') {
            $telnet->telnetSendCommand("tail -f " . $logmqtt . " | grep Payload", $resp);
        } else if ($path_parts['basename'] == 'iot_tb.log') {
            //$telnet->telnetSendCommand("tail -f " . $logmqtt . " | grep 'data:{'", $resp);
            $telnet->telnetSendCommand("tail -f " . $logmqtt, $resp);
        }

        if ($resp != '') {
            log::add('Creality_Box_Daemon', 'info', '	╔====================================================================================');
            log::add('Creality_Box_Daemon', 'info', '	╠ ' . __('Information reçue : ', __FILE__) . $resp);
        }

        $connected = true;
        while ($connected) {
            $established = 1;
            $telnet->telnetReadResponse($resp); // {"id":"11098","version":"1.0","params":{"printProgress":30},"method":"thing.event.property.post"}
            $state = '';
            $array = array();

            if ($path_parts['basename'] == 'iotlink.log') {

                if (preg_match('/Payload:+(.*)/i', $resp, $matches)) {
                    $payload = json_decode($matches[1],true); // {"printProgress":30}
                    if (is_array($payload) && array_key_exists('params', $payload)) {
                       foreach ($payload['params'] as $param => $value) {
                           $array += array($param => $value);  // {"printProgress":30}
                       }
                       log::add('Creality_Box_Daemon', 'info', '	╠' . '=================[ID='.$payload['id'].']=====[v='.$payload['version'].']=================');
                       log::add('Creality_Box_Daemon', 'debug', '	╠ ' . __('Information récupérée : ', __FILE__) . json_encode($payload['params']) . " devient : " . json_encode($array));
                    }

                } elseif (trim($resp) != '') {
                    log::add('Creality_Box_Daemon', 'debug', '	╠ ' . __('Nouvelle information non implémentée : ', __FILE__) . json_encode($resp));
                } elseif ($resp == '') {
                    log::add('Creality_Box_Daemon', 'debug', '	╠ ' . __('Aucune info (déconnecté ?) : ', __FILE__) . json_encode($resp));
                    //$telnet->telnetDisconnect();
                    $connected = false;
                }
            } else if ($path_parts['basename'] == 'iot_tb.log') {
                log::add('Creality_Box_Daemon', 'debug', '	╠ toutes les infos : ' . $resp);
                if (preg_match('/data:(\{.*\})/i', $resp, $matches)) {
                    $payload = $matches[1]; // {"printProgress":30}
                    $payload = json_decode($matches[1], true);
                    if (is_array($payload)) {
                       foreach ($payload as $param => $value) {
                           $array += array($param => $value);  // {"printProgress":30}
                       }
                       log::add('Creality_Box_Daemon', 'debug', '	╠ ' . __('Information récupérée : ', __FILE__) . json_encode($payload) . " devient : " . json_encode($array));
                    }
                } elseif ($resp == '') {
                    log::add('Creality_Box_Daemon', 'debug', '	╠ ' . __('Aucune info (déconnecté ?) : ', __FILE__) . json_encode($resp));
                    $connected = false;
                }
            }
            if (count($array) != 0) {
                log::add('Creality_Box_Daemon', 'debug', '	╠ $array : ' . json_encode($array));
                foreach ($eqLogics as $eqLogic) {
                    if ($eqLogic->getConfiguration('IP') == config::byKey('ip', 'Creality_Box')) {
                        foreach ($array as $param => $value) {
                            $existing_cmd = $eqLogic->getCmd('info',$param);
                            $cmd = is_object($existing_cmd) ? $existing_cmd : $eqLogic->loadCmdFromConf($param);

                            log::add('Creality_Box_Daemon', 'info', '	╠ ' . __('Information : ' . $param."=". $value . ' renseignée dans : ', __FILE__) . $eqLogic->getName());
                            log::add('Creality_Box_Daemon', 'info', '	╠====================================================================================');
                            if (is_object($cmd)) {
                                $cmd->event($value);
                            }
                        }
                    }
                }
            }
            if (exec('netstat -natp | grep '.config::byKey('ip', 'Creality_Box').' | grep ESTABLISHED -c') != 1) {
                $established = 0;
            }
            log::add('Creality_Box_Daemon', 'info', '	╠ Longue interrogation : ' . $established);
            if (!$established) {
                //$telnet->telnetDisconnect($resp);
                //log::add('Creality_Box_Daemon', 'info', '	╠ Déconnecté : ' . $resp);
            }
        }
    } else {
        $telnet->telnetDisconnect();
        log::add('Creality_Box_Daemon', 'error', 'L.' . __LINE__ . '█ ' . __('Erreur du démon : ', __FILE__) . $errstr . '(' . $errno . ')');
        Creality_Box::deamon_start();
    }
