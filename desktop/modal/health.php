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

if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}
$eqLogics = Creality_Box::byType('Creality_Box');
?>

<table class="table table-condensed tablesorter" id="table_healthcreality_Box">
	<thead>
		<tr>
			<th>{{Appareil}}</th>
			<th>{{IP}}</th>
      <th>{{Nom}}</th>
			<th>{{Statut}}</th>
			<th>{{Impression en cours}}</th>
			<th>{{Début impression}}</th>
			<th>{{Progression}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Date création}}</th>
		</tr>
	</thead>
	<tbody>
      <?php
        foreach ($eqLogics as $eqLogic) {
          echo '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';

          //echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getId() . '</span></td>';

          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('IP') . '</span></td>';

          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('hostname') . '</span></td>';

          $status = '<span class="label label-success" style="font-size : 1em; cursor : default;">{{OK}}</span>';
          if ($eqLogic->getStatus('state') == 'nok') {
              $status = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{NOK}}</span>';
          }
          echo '<td>' . $status . '</td>';

          $printing = $eqLogic->getCmd('info', 'state');
          $print = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{NON}}</span>';
          if (is_object($printing) && $printing->execCmd() > "0") {
              $print = '<span class="label label-success" style="font-size : 1em; cursor : default;">{{OUI}} (' . $printing->execCmd() . ')</span>';
          }
          echo '<td>' . $print . '</td>';

          $printStartTime = $eqLogic->getCmd('info', 'printStartTime');
          $startTime = "N/A";
          if (is_object($printStartTime) && $printStartTime->execCmd() != "0") {
              $startTime =  gmdate("Y-m-d H:i:s", $printStartTime->execCmd());
          }
          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $startTime . '</span></td>';

          $printProgress = $eqLogic->getCmd('info', 'printProgress');
          $progress = "0";
          if (is_object($printProgress) && $printProgress->execCmd() > "0") {
              $progress = $printProgress->execCmd();
          }
          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $progress . ' %</span></td>';

          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getStatus('lastCommunication') . '</span></td>';
          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
        }
      ?>
	</tbody>
</table>
