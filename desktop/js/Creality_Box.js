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

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
       var _cmd = {
           configuration: {}
       };
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
    tr += '<td class="hidden-xs">'
    tr += '    <span class="cmdAttr" data-l1key="id" style="display:none;"></span>'
    tr += '    <div class="input-group">'
    tr += '        <input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
    tr += '        <span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
    tr += '        <span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;background:var(--btn-default-color) !important";width:2%;></span>'
    tr += '    </div>'
    tr += '    <select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;float:right;margin-top:5px;max-width:50%" title="{{Commande info liée}}">'
    tr += '        <option value="">{{Aucune}}</option>'
    tr += '    </select>'
    tr += '</td>'

    tr += '<td>';
    tr += '    <span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '    <span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
  
    tr += '<td>';
    if (init(_cmd.type) == 'info') {
        tr += '<span class="cmdAttr" data-l1key="htmlstate" style="display:block;text-align:center;"></span>';
    }
    if (init(_cmd.subType) == 'select') {
        tr += '    <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="listValue" placeholder="{{Liste de valeur|texte séparé par ;}}" title="{{Liste}}">';
    }
    if (['select', 'slider', 'color'].includes(init(_cmd.subType)) || init(_cmd.configuration.updateCmdId) != '') {
        tr += '    <select class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdId" title="{{Commande d\'information à mettre à jour}}">';
        tr += '        <option value="">{{Aucune}}</option>';
        tr += '    </select>';
        tr += '    <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdToValue" placeholder="{{Valeur de l\'information}}">';
    }
    tr += '</td>';

    tr += '<td>';
    tr += '    <label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
    tr += '    <label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
    tr += '    <label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label>';
    tr += '    <div style="margin-top:7px;">';
    tr += '        <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
    tr += '        <input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
    tr += '        <input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">';
    tr += '    </div>';
    tr += '</td>';

    tr += '<td style="min-width:80px;width:200px;">';
    tr += '    <div class="input-group">';
    if (is_numeric(_cmd.id) && _cmd.id != '') {
        tr += '        <a class="btn btn-default btn-xs cmdAction roundedLeft" data-action="configure" title="{{Configuration de la commande}} ' + _cmd.type + '"><i class="fa fa-cogs"></i></a>';
        tr += '        <a class="btn btn-success btn-xs cmdAction" data-action="test" title="{{Tester}}"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '        <a class="btn btn-danger btn-xs cmdAction roundedRight" data-action="remove" title="{{Suppression de la commande}} ' + _cmd.type + '"><i class="fas fa-minus-circle"></i></a>';
    tr += '    </div>';
    tr += '</tr>';

    let newRow = document.createElement('tr')
    newRow.innerHTML = tr
    newRow.addClass('cmd')
    newRow.setAttribute('data-cmd_id', init(_cmd.id))
    document.getElementById('table_cmd').querySelector('tbody').appendChild(newRow)

    jeedom.eqLogic.buildSelectCmd({
        id: document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue(),
        filter: { type: 'info' },
        error: function(error) {
            jeedomUtils.showAlert({ message: error.message, level: 'danger' })
        },
        success: function(result) {
            newRow.querySelector('.cmdAttr[data-l1key="value"]').insertAdjacentHTML('beforeend', result)
            newRow.querySelector('.cmdAttr[data-l1key="configuration"][data-l2key="updateCmdId"]')?.insertAdjacentHTML('beforeend', result)
            newRow.setJeeValues(_cmd, '.cmdAttr')
            jeedom.cmd.changeType(newRow, init(_cmd.subType))
        }
    });
}

document.getElementById('div_creality_box').addEventListener('click', function(event) {
    var _target = null
    if (_target = event.target.closest('#bt_webcreality_Box')) {
        jeeDialog.dialog({
            title: '{{Interface Creality Box}}',
            contentUrl: 'index.php?v=d&plugin=Creality_Box&modal=web&ip=' + document.querySelector('.eqLogicAttr[data-l1key="id"]').jeeValue()
        });
        return;
    }
    if (_target = event.target.closest('#bt_healthcreality_Box')) {
        jeeDialog.dialog({
            title: '{{Santé Creality Box}}',
            contentUrl: 'index.php?v=d&plugin=Creality_Box&modal=health'
        });
        return;
    }
    if (_target = event.target.closest('#bt_documentationCreality_Box')) {
        window.open(_target.getAttribute("data-location"), "_blank", null);
        return;
    }
});

function printEqLogic(_eqLogic) {

    printEqLogicTab(_eqLogic); //affiche les info de l'équipement
}

function printEqLogicTab(_eqLogic) {

    document.querySelectorAll('#idTableEqLogicConfig tbody').forEach(function(tbody) {
        tbody.innerHTML = '';
    });
}