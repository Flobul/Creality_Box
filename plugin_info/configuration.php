
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

   require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
   include_file('core', 'authentification', 'php');
   if (!isConnect()) {
       include_file('desktop', '404', 'php');
       die();
   }
   $plugin = plugin::byId('Creality_Box');
   sendVarToJS('version', Creality_Box::$_pluginVersion);

   ?>
<form class="form-horizontal">
   <fieldset>
      <legend>
         <i class="fa fa-list-alt"></i> {{Général}}
      </legend>
      <div class="form-group">
         <?php
            $update = $plugin->getUpdate();
            if (is_object($update)) {
                echo '<div class="col-lg-3">';
                echo '<div>';
                echo '<label>{{Branche}} :</label> '. $update->getConfiguration('version', 'stable');
                echo '</div>';
                echo '<div>';
                echo '<label>{{Source}} :</label> ' . $update->getSource();
                echo '</div>';
                echo '<div>';
                echo '<label>{{Version}} :</label> v' . ((Creality_Box::$_pluginVersion)?Creality_Box::$_pluginVersion:' '). ' (' . $update->getLocalVersion() . ')';
                echo '</div>';
                echo '</div>';
            }
            ?>
         <div class="col-lg-5">
            <div>
               <i><a class="btn btn-primary btn-xs" target="_blank" href="https://flobul-domotique.fr/presentation-du-plugin-Creality_Box-pour-jeedom/"><i class="fas fa-book"></i><strong> {{Présentation du plugin}}</strong></a></i>
               <i><a class="btn btn-success btn-xs" target="_blank" href="<?=$plugin->getDocumentation()?>"><i class="fas fa-book"></i><strong> {{Documentation complète du plugin}}</strong></a></i>
            </div>
            <div>
               <i> {{Les dernières actualités du plugin}}<a class="btn btn-label btn-xs" target="_blank" href="https://community.jeedom.com/t/plugin-Creality_Box-documentation-et-actualites/39994"><i class="icon jeedomapp-home-jeedom icon-Creality_Box"></i><strong>{{sur le community}}</strong></a>.</i>
            </div>
            <div>
               <i> {{Les dernières discussions autour du plugin}}<a class="btn btn-label btn-xs" target="_blank" href="https://community.jeedom.com/tags/plugin-Creality_Box"><i class="icon jeedomapp-home-jeedom icon-Creality_Box"></i><strong>{{sur le community}}</strong></a>.</i></br>
               <i> {{Pensez à mettre le tag}} <b><font font-weight="bold" size="+1">#plugin-Creality_Box</font></b> {{et à fournir les log dans les balises préformatées}}.</i>
            </div>
            <style>
               .icon-Creality_Box {
               font-size: 1.3em;
               color: #94CA02;
               }
            </style>
         </div>
      </div>

      <div class="form-group">
         <legend>
            <i class="icon loisir-darth"></i> {{Configuration du démon}}
         </legend>

        <div class="form-group">
          <label class="col-sm-4 control-label"><strong> {{Adresse IP de la Creality Box}}</strong>
              <sup><i class="fas fa-question-circle" title="{{Entrez l'adresse IP de la de la Creality Box.</br>}}"></i></sup>
          </label>
          <div class="col-sm-2">
              <input type="text" class="configKey form-control deviceir" data-l1key="ip" ><br>
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-4 control-label"> {{Port d'écoute Telnet}}
             <sup><i class="fas fa-question-circle" title="{{Port 23 sauf s'il a été modifié.}}"></i></sup>
          </label>
          <div class="col-sm-2">
            <input type="text" class="configKey form-control" data-l1key="listenport" placeholder="23"><br>
          </div>
        </div>

        <div class="form-group">
          <label class="col-sm-4 control-label"><strong> {{Identifiant}}</strong>
              <sup><i class="fas fa-question-circle" title="{{Entrez l'identifiant.}} {{Par défaut}} : root"></i></sup>
          </label>
          <div class="col-sm-2">
              <input type="text" class="configKey form-control deviceir" data-l1key="id" placeholder="root"></input>
          </div>
          <label class="col-sm-2 control-label"><strong> {{Mot de passe}}</strong>
              <sup><i class="fas fa-question-circle" title="{{Entrez le mot de passe.}} {{Par défaut}} : cxswprin"></i></sup>
          </label>
          <div class="col-sm-2">
              <input type="password" class="configKey form-control deviceir" data-l1key="password" placeholder="cxswprin"><br>
          </div>
        </div>
        
        <div class="form-group">
           <label class="col-sm-4 control-label"> {{Fichier de log}}
              <sup><i class="fas fa-question-circle" title="{{Fichier de log d'où seront récupérées les infos.}}"></i></sup>
           </label>
           <div class="form-group">
              <div class="col-sm-4">
                 <input type="text" class="configKey form-control " data-l1key="logmqtt" placeholder="/media/mmcblk0p1/creality/log/iotlink.log"><br>
              </div>
           </div>
        </div>
      </div>
      </div>
   </fieldset>
</form>

<?php include_file('desktop', 'configuration', 'js', 'Creality_Box'); ?>