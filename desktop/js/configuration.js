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

   // attribuer le même niveau de log à tous les logs du plugin
   $('#bt_savePluginLogConfig').off('click').on('click', function () {
       // id du plugin
       var plugin = $('#span_plugin_id').text();
       // object du level des log du nom de l'id
       var logPluginLevel = $('#div_plugin_log').getValues('.configKey')[0];
       // string du level des log du nom de l'id
       var logPluginLeveltoStr = JSON.stringify(logPluginLevel);

       // liste de tous les log de l'id du plugin
       $('.bt_plugin_conf_view_log').each(function () {
           // remplacer log::level::Creality_Box par log::level::Optooma_Daemon
           logPluginLeveltoStr = logPluginLeveltoStr.replace("log::level::" + plugin, "log::level::" + $(this).attr('data-log'));
           // converti en objet
           newLogPluginLevel = JSON.parse(logPluginLeveltoStr);
           // save dans la config jeedom
           jeedom.config.save({
               configuration: newLogPluginLevel,
               error: function(error) {
                   alert_div_plugin_configuration.showAlert({message: error.message, level: 'danger'})
               },
               success: function() {
                   alert_div_plugin_configuration.showAlert({message: '{{Sauvegarde de la configuration des logs dev effectuée}}', level: 'success'})
                   modifyWithoutSave = false
               }
           });
       });
   });

   // afficher juste avant la version, la véritable version contenue dans le plugin
   var dateVersion = $("#span_plugin_install_date").html();
   $("#span_plugin_install_date").empty().append("v" + version + " (" + dateVersion + ")");


   $('.bt_refreshPluginInfo').after('<a class="btn btn-success btn-sm" target="_blank" href="https://market.jeedom.com/index.php?v=d&p=market_display&id=4099"><i class="fas fa-comment-dots "></i> Donner mon avis</a>');