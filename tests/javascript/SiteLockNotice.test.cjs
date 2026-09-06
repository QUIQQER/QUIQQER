const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

function fixture(isSU) {
    class Element extends EventTarget {
        constructor(tagName) {
            super();
            this.tagName = tagName;
            this.dataset = {};
            this.children = [];
            this.attributes = {};
        }
        setAttribute(name, value) { this.attributes[name] = value; }
        append(child) { this.children.push(child); }
    }

    const notices = [];
    const clearedTimers = [];
    let definition;
    vm.runInNewContext(readFileSync(resolve(__dirname, '../../bin/QUI/controls/projects/project/site/Panel.js'), 'utf8'), {
        USER: {isSU},
        document: {createElement: name => new Element(name)},
        clearTimeout: timer => clearedTimers.push(timer),
        Class: function (value) { definition = value; return value; },
        define(name, dependencies, factory) {
            factory(...dependencies.map(dependency => dependency === 'Locale' ? {get: (group, key) => key} : {}));
        }
    });
    let unlocks = 0;
    const save = {disabled: false, disable() { this.disabled = true; }};
    const panel = {
        ...definition,
        $ownsEditingLock: true,
        $lockTimer: 1,
        $lockExpiryTimer: 2,
        $Container: {before: notice => notices.push(notice)},
        getContent: () => ({querySelector: () => notices[0] || null}),
        getButtons: () => [save],
        unlockSite: () => unlocks++
    };
    return {panel, notices, save, clearedTimers, unlocks: () => unlocks};
}

test('a superuser can explicitly unlock after repeated lease loss while saving remains blocked', () => {
    const {panel, notices, save, clearedTimers, unlocks} = fixture(true);
    panel.$loseEditingLock();
    panel.$loseEditingLock();
    assert.equal(notices.length, 1);
    assert.equal(notices[0].attributes.role, 'alert');
    assert.equal(notices[0].children[0].dataset.name, 'editingLockText');
    const button = notices[0].children[1];
    assert.equal(button.tagName, 'button');
    assert.equal(button.type, 'button');
    assert.equal(button.textContent, 'projects.project.site.panel.unlock');
    assert.equal(unlocks(), 0);
    button.dispatchEvent(new Event('click'));
    assert.equal(unlocks(), 1);
    assert.equal(save.disabled, true);
    assert.equal(panel.$ownsEditingLock, false);
    assert.deepEqual(clearedTimers, [1, 2, 1, 2]);
});

test('ordinary users see the warning without a forced unlock action', () => {
    const {panel, notices, save, unlocks} = fixture(false);
    panel.$loseEditingLock();
    assert.equal(notices[0].children.length, 1);
    assert.equal(save.disabled, true);
    assert.equal(unlocks(), 0);
});

test('a destroyed editor does not add a lock warning', () => {
    const {panel, notices} = fixture(true);
    panel.$lockDestroyed = true;
    panel.$loseEditingLock();
    assert.equal(notices.length, 0);
});
