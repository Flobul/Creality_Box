<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('Creality_Box');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>
<div class="row row-overflow">
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i>
        <br>
        <span>{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fas fa-wrench"></i>
        <br>
        <span>{{Configuration}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" id="bt_healthcreality_Box">
        <i class="fas fa-medkit"></i>
        <br>
        <span>{{Santé}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" id="bt_documentationCreality_Box" data-location="<?=$plugin->getDocumentation()?>">
        <i class="fas icon loisir-livres"></i>
        <br>
        <span>{{Documentation}}</span>
      </div>
    </div>
    <legend><i class="fas fa-photo-video"></i> {{Ma box Creality}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Creality Box trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			// Champ de recherche
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			// Liste des équipements du plugin
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
                $hostname = $eqLogic->getConfiguration('hostname', '{{Aucun}}');
                $IP = $eqLogic->getConfiguration('IP','{{Aucune IP}}');

				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '" title="{{Nom}} : ' . $eqLogic->getName() . '</br>
                      {{Nom d\'hôte}} : ' . $hostname . '</br>
                      IP : ' . $IP . '">';
				echo '<img src="' . $eqLogic->getImage() . '"/>';
				echo '<br>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '<span class="hiddenAsCard displayTableRight hidden">';
				echo ($eqLogic->getIsVisible() == 1) ? '<i class="fas fa-eye" title="{{Equipement visible}}"></i>' : '<i class="fas fa-eye-slash" title="{{Equipement non visible}}"></i>';
				echo '</span>';
				echo '</div>';
			}
			echo '</div>';
		}
		?>

  </div>

  <div class="col-xs-12 eqLogic" style="display: none;">
    <div class="input-group pull-right" style="display:inline-flex">
      <span class="input-group-btn">
        <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
        <a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
        <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
        <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
      </span>
    </div>
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Équipement}}</a></li>
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <div class="tab-content">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <form class="form-horizontal">
          <fieldset>
            <div class="col-lg-6">
              <legend><i class="fas fa-sitemap icon_green"></i> {{Général}}</legend>
              <div class="form-group">
                <label class="col-sm-4 control-label">{{Nom du vidéoprojecteur}}</label>
                <div class="col-sm-5">
                  <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                  <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la box}}" />
                </div>
              </div>
              <div class="form-group">
                <label class="col-sm-4 control-label">{{Objet parent}}</label>
                <div class="col-sm-5">
                  <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                    <option value="">{{Aucun}}</option>
                    <?php
                      $options = '';
                      foreach ((jeeObject::buildTree(null, false)) as $object) {
                          $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration("parentNumber")) . $object->getName() . '</option>';
                      }
                      echo $options;
                    ?>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-4 control-label">{{Catégorie}}</label>
                <div class="col-sm-8">
                  <?php
                      foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                          echo '<label class="checkbox-inline">';
                          echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                          echo '</label>';
                      }
                  ?>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-4 control-label">{{Options}}</label>
                <div class="col-sm-8">
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
                  <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
                </div>
              </div>

              <div class="form-group">
                <label class="col-sm-4 control-label help" data-help="{{Cocher la case pour utiliser le widget associé au type de l'appareil.}}</br>{{Laissez décoché pour laisser le core générer le widget par défaut.}}">{{Widget équipement}}
                </label>
                <div class="col-sm-8">
                  <input type="checkbox" class="eqLogicAttr form-control" id="widgetTemplate" data-l1key="display" data-l2key="widgetTmpl" />
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <legend><i class="fas fa-cogs icon_blue"></i> {{Paramètres de la box}}</legend>
              <div class="form-group">
                <label class="col-sm-3 control-label">{{Accès à la page web}}</label>
                <div class="col-sm-3">
                  <a class="btn btn-default  pull-left" id="bt_webcreality_Box"><i class="fa fa-cogs"></i> {{Interface web Creality_Box}}</a>
                </div>
              </div>
            </div>

            <div class="col-lg-6">
              <legend><i class="fas fa-info-circle icon_yellow"></i> {{Informations}}</legend>
                <div class="form-group">
                  <table id="table_infoseqlogic" class="col-sm-9 table-bordered table-condensed" style="border-radius: 10px;">
                    <thead>
                    </thead>
                    <tbody>
                      <tr>
                        <td class="col-sm-4">
                          <span style="font-size : 1em;">{{Type}}</span>
                        </td>
                        <td>
                          <span class="label label-default" style="font-size:1em;white-space:unset !important">
                            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="type">
                            </span>
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <td class="col-sm-4">
                          <span style="font-size : 1em;">{{Modèle}}</span>
                        </td>
                        <td>
                          <span class="label label-default" style="font-size:1em;white-space:unset !important">
                            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="model">
                            </span>
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <td class="col-sm-4">
                          <span style="font-size : 1em;">{{Adresse MAC}}</span>
                        </td>
                        <td>
                          <span class="label label-default" style="font-size:1em;white-space:unset !important">
                            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="MAC">
                            </span>
                          </span>
                        </td>
                      </tr>
                      <tr>
                        <td class="col-sm-4">
                          <span style="font-size : 1em;">{{Nom d'hôte}}</span>
                        </td>
                        <td>
                          <span class="label label-default" style="font-size:1em;white-space:unset !important">
                            <span class="eqLogicAttr" data-l1key="configuration" data-l2key="hostname">
                            </span>
                          </span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="form-group">
                  <div class="col-sm-10">
                    <center>
                      <img src="plugins/Creality_Box/core/config/img/Creality_Box.png" data-original=".svg" id="img_device" class="img-responsive" style="max-height:450px;max-width:400px" onerror="this.src='core/img/no_image.gif'" />
                    </center>
                  </div>
                </div>
              </div>
            </fieldset>
          </form>
      </div>
      <div role="tabpanel" class="tab-pane" id="commandtab">
        <div class="table-responsive">
          <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th>{{Nom}}</th>
                <th data-sortable="false" data-filter="false">{{Afficher/Historiser}}</th>
                <th>{{Type}}</th>
                <th>{{Paramètres}}</th>
                <th>{{Valeur}}</th>
                <th>{{Action}}</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
include_file('desktop', 'Creality_Box', 'js', 'Creality_Box');
include_file('core', 'plugin.template', 'js');
?>