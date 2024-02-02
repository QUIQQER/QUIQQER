/**
 * History Handling for Tabs
 * - mouse back and forward changes the tabs
 *
 * @author www.pcsg.de (Henning Leutz)
 */
define('libs/HistoryTabs', [
    'HistoryEvents',
], function () {
    "use strict";

    var CHANGING = false;

    var getTasks = function () {
        var tasks = window.QUI.Controls.getByType('qui/controls/desktop/Tasks');
        return tasks.length ? tasks[0] : null;
    };

    history.pushState({}, '', window.location.pathname + '#');

    window.addEventListener('changestate', function () {
        if (CHANGING) {
            CHANGING = false;
            return;
        }

        var newPath;

        if (window.location.toString().indexOf('#') === -1) {
            newPath = window.location.pathname + '#';
        } else {
            newPath = window.location.toString().replace('#', '');
        }

        CHANGING = true;
        history.pushState({}, '', newPath);
    }, false);

    document.addEvent('mouseup', function (e) {
        var Tasks, Current, Next, NextNode;

        // zurück
        if (e.event.which === 4) {
            Tasks = getTasks();

            if (!Tasks) {
                return;
            }

            Current  = Tasks.$Active;
            NextNode = Current.getElm().getPrevious('.qui-task');

            if (NextNode) {
                Next = window.QUI.Controls.getById(NextNode.get('data-quiid'));
            } else {
                Next = Tasks.lastChild();
            }

            if (Next) {
                Next.click();
            }
            return;
        }

        // vor
        if (e.event.which === 5) {
            Tasks = getTasks();

            if (!Tasks) {
                return;
            }

            Current  = Tasks.$Active;
            NextNode = Current.getElm().getNext('.qui-task');

            if (NextNode) {
                Next = window.QUI.Controls.getById(NextNode.get('data-quiid'));
            } else {
                Next = Tasks.firstChild();
            }

            if (Next) {
                Next.click();
            }
            return;
        }
    });

});
