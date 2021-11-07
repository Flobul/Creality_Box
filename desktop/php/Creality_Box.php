<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('Creality_Box');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>
<div class="row row-overflow">
  <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend><i class="fa fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
      <style>

        .eqLogicThumbnailDisplay .eqLogicThumbnailContainer .fas.fa-sign-in-alt.fa-rotate-90 {
          font-size: 38px !important;
          color: #ea1b39;
        }

        .fas.fa-question-circle.tooltips.tooltipstered {
          color: var(--al-info-color) !important;
        }

        .eqLogicDisplayCard.cursor {
          height: 180px !important;
          text-align: center;
          background-color: rgb(255, 255, 255);
          margin-bottom: 10px;
          padding: 5px;
          border-top-left-radius: 2px;
          border-top-right-radius: 2px;
          border-bottom-right-radius: 2px;
          border-bottom-left-radius: 2px;
          width: 160px;
          margin-left: 10px;
          left: 0px;
          top: 0px;
        }
      </style>

      <div class="cursor eqLogicAction logoPrimary" data-action="add">
        <i class="fas fa-plus-circle"></i>
        <br>
        <span>{{Ajouter}}</span>
      </div>
      <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
        <i class="fa fa-wrench"></i>
        <br>
        <span>{{Configuration}}</span>
      </div>
      <div class="cursor logoSecondary" id="bt_healthcreality_Box">
        <i class="fas fa-medkit"></i>
        <span>
          <center>{{Santé}}</center>
        </span>
      </div>
      <div class="cursor logoSecondary" id="bt_documentationCreality_Box" data-location="<?=$plugin->getDocumentation()?>">
        <i class="icon loisir-livres"></i>
        <br><br>
        <span>{{Documentation}}</span>
      </div>
    </div>
    <legend><i class="fas fa-photo-video"></i> {{Ma box Creality}}</legend>
    <div class="input-group" style="margin:5px;">
      <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
      <div class="input-group-btn">
        <a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
      </div>
    </div>
    <div class="eqLogicThumbnailContainer">
      <?php
          foreach ($eqLogics as $eqLogic) {
              $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
              $hostname = $eqLogic->getConfiguration('hostname', '{{Aucun}}');
              $IP = $eqLogic->getConfiguration('IP','{{Aucune IP}}');

              echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
              echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95"
                      title="{{Nom}} : ' . $eqLogic->getName() . '</br>
                      {{Nom d\'hôte}} : ' . $hostname . '</br>
                      IP : ' . $IP . '">';
              echo "<br>";
              echo '<span class="name" style="font-size : 14px;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
              echo '</div>';
          }
      ?>
    </div>
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
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <div class="col-xs-6">
          <form class="form-horizontal">
            <fieldset>
              <div class="form-group">
                <legend><i class="fas fa-sitemap icon_green"></i> {{Général}}</legend>
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
                  <input type="checkbox" class="eqLogicAttr form-control" id="widgetTemplate" data-l1key="configuration" data-l2key="widgetTemplate" />
                </div>
              </div>

            </fieldset>
            <legend><i class="fas fa-cogs icon_blue"></i> {{Paramètres de la box}}
            </legend>
            <fieldset>

              <div class="form-group">
                <label class="col-sm-3 control-label">{{Accès à la page web}}</label>
                <div class="col-sm-3">
                  <a class="btn btn-default  pull-left" id="bt_webcreality_Box"><i class="fa fa-cogs"></i> {{Interface web Creality_Box}}</a>
                </div>
              </div>

            </fieldset>
          </form>
        </div>
        <div class="col-sm-6">
          <form class="form-horizontal">
            <legend><i class="fas fa-info-circle icon_yellow"></i> {{Informations}}</legend>
            <fieldset>
              <div class="form-group">
                <table id="table_infoseqlogic" class="col-sm-9 table-bordered table-condensed" style="border-radius: 10px;">
                  <thead>
                  </thead>
                  <tbody>
                  </tbody>
                </table>
                </br>
              </div>
              <div class="form-group">
                <div class="col-sm-10">
                  <center>
                    <img src="plugins/Creality_Box/core/config/img/Creality_Box.png" data-original=".svg" id="img_device" class="img-responsive" style="max-height:450px;max-width:400px" onerror="this.src='core/img/no_image.gif'" />
                  </center>
                </div>
              </div>
            </fieldset>
          </form>
        </div>
      </div>
      <div role="tabpanel" class="tab-pane" id="commandtab">
        <table id="table_cmd" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th>{{Id}}</th>
              <th>{{Nom}}</th>
              <th>{{Type}}</th>
              <th>{{Valeur}}</th>
              <th>{{Paramètres}}</th>
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

<?php
include_file('desktop', 'Creality_Box', 'js', 'Creality_Box');
include_file('core', 'plugin.template', 'js');
?>
