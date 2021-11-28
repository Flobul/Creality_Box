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

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
//$("#table_info").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

$('#bt_webcreality_Box').on('click', function () {
  var nodeId = $('.eqLogicAttr[data-l1key=configuration][data-l2key=IP]').value();
  $('#md_modal').dialog({title: "{{Interface Creality Box}}"});
  $('#md_modal').load('index.php?v=d&plugin=Creality_Box&modal=web&ip=' + nodeId).dialog('open');
});

 function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
     var _cmd = {configuration: {}};
   }
   if (!isset(_cmd.configuration)) {
     _cmd.configuration = {};
   }

   var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
   tr += '<td style="width:60px;">';
   tr += '<span class="cmdAttr" data-l1key="id"></span>';
   tr += '</td>';
   tr += '<td style="min-width:300px;width:350px;">';
   tr += '<div class="row">';
   tr += '<div class="col-xs-7">';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
   tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{Commande information liée}}">';
   tr += '<option value="">{{Aucune}}</option>';
   tr += '</select>';
   tr += '</div>';
   tr += '<div class="col-xs-5">';
   tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> {{Icône}}</a>';
   tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
   tr += '</div>';
   tr += '</div>';
   tr += '</td>';
   tr += '<td>';
   tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
   tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
   tr += '</td>';
   tr += '<td>';
   if (init(_cmd.type != "action")) {
       tr += '<textarea class="form-control input-sm" data-key="value" style="height:60px;disabled" placeholder="{{Valeur}}" readonly=true></textarea>';
   }
   tr += '</td>';

   tr += '<td style="min-width:150px;width:350px;">';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min.}}" title="{{Min.}}" style="width:30%;display:inline-block;"/> ';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max.}}" title="{{Max.}}" style="width:30%;display:inline-block;"/> ';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;"/>';
   //tr += '</td>';
   //tr += '<td style="min-width:80px;width:350px;">';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
   tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label>';
   tr += '</td>';
   tr += '<td style="min-width:80px;width:200px;">';
  tr += '<div class="input-group">';
  if (is_numeric(_cmd.id) && _cmd.id != '') {
    tr += '<a class="btn btn-default btn-xs cmdAction roundedLeft" data-action="configure" title="{{Configuration de la commande}} ' + _cmd.type + '"><i class="fa fa-cogs"></i></a>';
    tr += '<a class="btn btn-warning btn-xs cmdAttr" data-action="configureCommand" title="{{Modification de la commande}} ' + _cmd.type + '"><i class="fas fa-wrench"></i></a>';
    tr += '<a class="btn btn-success btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fa fa-rss"></i> {{Tester}}</a>';
  }
  tr += '<a class="btn btn-danger btn-xs cmdAction roundedRight" data-action="remove" title="{{Suppression de la commande}} ' + _cmd.type + '"><i class="fas fa-minus-circle"></i></a>';
   tr += '</tr>';

   $('#table_cmd tbody').append(tr);
   var tr = $('#table_cmd tbody tr').last();
   jeedom.eqLogic.builSelectCmd({
     id:  $('.eqLogicAttr[data-l1key=id]').value(),
     filter: {type: 'info'},
     error: function (error) {
       $('#div_alert').showAlert({message: error.message, level: 'danger'});
     },
     success: function (result) {
       tr.find('.cmdAttr[data-l1key=value]').append(result);
       tr.setValues(_cmd, '.cmdAttr');
       jeedom.cmd.changeType(tr, init(_cmd.subType));
     }
   });
   
    function refreshValue(val) {
        $('#table_cmd [data-cmd_id="' + _cmd.id + '"] .form-control[data-key=value]').value(val);
    }

    if (_cmd.id != undefined && _cmd.type != "action") {
        jeedom.cmd.execute({
            id: _cmd.id,
            cache: 0,
            notify: false,
            success: function(result) {
                refreshValue(result);
            }
        });
        jeedom.cmd.update[_cmd.id] = function(_options) {
            refreshValue(_options.display_value);
        }
    }
}

$('.changeIncludeState').on('click', function () {
	var el = $(this);
	var state = $(this).attr('data-state');
	amxDeviceDiscovery(state);
	jeedom.config.save({
		plugin : 'Creality_Box',
		configuration: {include_mode: el.attr('data-state')},
		error: function (error) {
			$('#div_alert').showAlert({message: error.message, level: 'danger'});
		},
		success: function () {
			if (el.attr('data-state') == 1) {
				$.hideAlert();
				$('.changeIncludeState:not(.card)').removeClass('btn-default').addClass('btn-success');
				$('.changeIncludeState').attr('data-state', 0);
				$('.changeIncludeState.card').css('background-color','#8000FF');
				$('.changeIncludeState.card span center').text('{{Arrêter l\'inclusion}}');
				$('.changeIncludeState:not(.card)').html('<i class="fa fa-sign-in fa-rotate-90"></i> {{Arrêter l\'inclusion}}');
				$('#div_inclusionAlert').showAlert({message: '{{Vous êtes en mode inclusion. Cliquez à nouveau sur le bouton d\'inclusion pour sortir de ce mode}}', level: 'warning'});
			} else {
				$.hideAlert();
				$('.changeIncludeState:not(.card)').addClass('btn-default').removeClass('btn-success btn-danger');
				$('.changeIncludeState').attr('data-state', 1);
				$('.changeIncludeState:not(.card)').html('<i class="fa fa-sign-in fa-rotate-90"></i> {{Mode inclusion}}');
				$('.changeIncludeState.card span center').text('{{Mode inclusion}}');
				$('.changeIncludeState.card').css('background-color','#ffffff');
				$('#div_inclusionAlert').hideAlert();
			}
		}
	});
});

$('body').on('Creality_Box::includeDevice', function (_event,_options) {
  if (modifyWithoutSave) {
    $('#div_inclusionAlert').showAlert({message: '{{Un périphérique vient d\'être inclu/exclu. Veuillez réactualiser la page}}', level: 'warning'});
  } else {
    if (_options == '') {
      window.location.reload();
    } else {
      window.location.href = 'index.php?v=d&p=Creality_Box&m=Creality_Box&id=' + _options;
    }
  }
});

$('#bt_healthcreality_Box').on('click', function () {
    $('#md_modal').dialog({title: "{{Santé Creality_Box}}"});
    $('#md_modal').load('index.php?v=d&plugin=Creality_Box&modal=health').dialog('open');
});

$('#bt_documentationCreality_Box').off('click').on('click', function() {
    window.open($(this).attr("data-location"), "_blank", null);
});


function printEqLogic(_eqLogic) {

    printEqLogicTab(_eqLogic); //affiche les info de l'équipement
    $('body').setValues(_eqLogic, '.eqLogicAttr');
    //initCheckBox();
    modifyWithoutSave = false;
}

function printEqLogicTab(_eqLogic) {

    $('#table_infoseqlogic tbody').empty();

    //affichage des configurations du device
    printEqLogicHelper("{{Type}}", "type", _eqLogic);
    printEqLogicHelper("{{Adresse IP}}", "IP", _eqLogic);
    printEqLogicHelper("{{Modèle}}", "model", _eqLogic);
    printEqLogicHelper("{{Adresse MAC}}", "MAC", _eqLogic);
    printEqLogicHelper("{{Nom d'hôte}}", "hostname", _eqLogic);

    if (isset(_eqLogic.configuration.model) && _eqLogic.configuration.model !== undefined) {
        $('#img_device').attr("src", 'plugins/Creality_Box/core/config/img/' + _eqLogic.configuration.model + '.png');
    }
}

function printEqLogicHelper(_label, _name, _eqLogic) {

    if (isset(_eqLogic.result)) {
        var eqLogic = _eqLogic.result;
    } else {
        var eqLogic = _eqLogic;
    }
    if (isset(eqLogic.configuration[_name])) {
        if (eqLogic.configuration[_name] !== undefined) {
            var trm = '<tr>';
            trm += '	<td class="col-sm-4">';
            trm += '		<span style="font-size : 1em;">' + _label + '</span>';
            trm += '	</td>';
            trm += '	<td>';
            trm += '		<span class="label label-default" style="font-size:1em;white-space:unset !important">';
            trm += '			<span class="eqLogicAttr" data-l1key="configuration" data-l2key="' + _name + '">';
            trm += '			</span>';
            trm += '		</span>';
            trm += '	</td>';
            trm += '</tr>';
            $('#table_infoseqlogic tbody').append(trm);
            $('#table_infoseqlogic tbody tr:last').setValues(eqLogic, '.eqLogicAttr');
        }
    }
}
