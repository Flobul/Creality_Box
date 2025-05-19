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

document.querySelector('.bt_refreshPluginInfo').insertAdjacentHTML('afterend', '<a class="btn btn-success btn-sm" target="_blank" href="https://market.jeedom.com/index.php?v=d&p=market_display&id=4217"><i class="fas fa-comment-dots "></i> Donner mon avis</a>');

function printPluginConfiguration() {
    var divPluginLGConfiguration = document.getElementById('configuration_plugin_creality_box');
    var btnSavePluginConfig = document.getElementById('bt_savePluginConfig');
    var configInputs = divPluginLGConfiguration?.querySelectorAll('.configKey');
    var modificationCount = 0;
    var initialValues = new Map();
    var modificationMessage = document.createElement('i');
    modificationMessage.classList.add('modificationWithoutSave', 'label', 'label-warning', 'pull-right');
    modificationMessage.innerHTML = '{{Modification en cours...}}';
    modificationMessage.unseen();
    btnSavePluginConfig.parentNode.insertBefore(modificationMessage, btnSavePluginConfig.nextSibling);

    function resetStyle(element) {
        element.style.setProperty('background-color', '', 'important');
        element.style.setProperty('color', '', 'important');
    }

    function setModifiedStyle(element) {
        element.style.setProperty('background-color', 'var(--al-warning-color)', 'important');
        element.style.setProperty('color', 'var(--sc-lightTxt-color)', 'important');
    }

    function updateModificationStatus() {
        if (modificationCount > 0) {
            modificationMessage.seen();
        } else {
            modificationMessage.unseen();
        }
    }

    configInputs?.forEach(function (input) {
        resetStyle(input); // Reset du style au démarrage
        if (input.type === 'checkbox') {
            initialValues.set(input, input.checked);
        } else {
            initialValues.set(input, input.value);
        }
    });

    configInputs?.forEach(function (input) {
        if (input.type === 'checkbox') {
            input.addEventListener('change', function() {
                const initialValue = initialValues.get(this);
                const isModified = this.checked !== initialValue;

                if (isModified && !this.hasAttribute('data-modified')) {
                    setModifiedStyle(this);
                    this.setAttribute('data-modified', '');
                    modificationCount++;
                } else if (!isModified && this.hasAttribute('data-modified')) {
                    resetStyle(this);
                    this.removeAttribute('data-modified');
                    modificationCount--;
                }
                updateModificationStatus();
            });
        } else {
            const eventType = input.nodeName === 'SELECT' ? 'change' : 'input';
            input.addEventListener(eventType, function() {
                const initialValue = initialValues.get(this);
                const isModified = this.value !== initialValue;

                if (isModified && !this.hasAttribute('data-modified')) {
                    setModifiedStyle(this);
                    this.setAttribute('data-modified', '');
                    modificationCount++;
                } else if (!isModified && this.hasAttribute('data-modified')) {
                    resetStyle(this);
                    this.removeAttribute('data-modified');
                    modificationCount--;
                }
                updateModificationStatus();
            });
        }
    });

    btnSavePluginConfig.addEventListener('click', function() {
        configInputs.forEach(input => {
            if (input.type === 'checkbox') {
                initialValues.set(input, input.checked);
            } else {
                initialValues.set(input, input.value);
            }
            resetStyle(input);
            input.removeAttribute('data-modified');
        });
        modificationCount = 0;
        modificationMessage.unseen();
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", printPluginConfiguration);
} else {
    setTimeout(function() {
        printPluginConfiguration();
    }, 100);
}