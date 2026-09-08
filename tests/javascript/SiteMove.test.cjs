const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {resolve} = require('node:path');
const {test} = require('node:test');
const vm = require('node:vm');

const root = resolve(__dirname, '../..');

function definition(path, dependencies = {}) {
    let result;
    vm.runInNewContext(readFileSync(resolve(root, path), 'utf8'), {
        define(name, names, factory) {
            factory(...names.map(name => dependencies[name] || {}));
        },
        Class: function (value) { result = value; return value; },
        QUIQQER: {Rewrite: {SUFFIX: '', URL_SPACE_CHARACTER: '-'}},
        window: {removeEvent() {}},
        document: {removeEventListener() {}},
        clearTimeout,
        Promise
    });
    return result;
}

function events(object) {
    const handlers = new Map();
    const normalize = name => name.replace(/^on([A-Z])/, (_, letter) => letter.toLowerCase());
    object.addEvent = (name, handler) => {
        name = normalize(name);
        handlers.set(name, [...(handlers.get(name) || []), handler]);
    };
    object.addEvents = values => Object.entries(values).forEach(([name, handler]) => object.addEvent(name, handler));
    object.removeEvent = (name, handler) => {
        name = normalize(name);
        handlers.set(name, (handlers.get(name) || []).filter(value => value !== handler));
    };
    object.fireEvent = (name, args) => (handlers.get(normalize(name)) || []).forEach(handler => handler(...args));
    return object;
}

function fixture() {
    const requests = [];
    const Ajax = {
        get(name, success, params) { requests.push({name, success, params}); },
        post(name, success, params) { requests.push({name, success, params}); }
    };
    const Site = definition('bin/QUI/classes/projects/project/Site.js', {Ajax});
    const Project = definition('bin/QUI/classes/projects/Project.js');
    const Panel = definition('bin/QUI/controls/projects/project/site/Panel.js', {
        'utils/Site': {notAllowedUrlSigns: () => ({})},
        'qui/controls/buttons/Button': function () { this.inject = () => {}; }
    });
    const project = events({});
    const site = id => {
        const object = events({
            ...Site,
            $urlRequest: 0,
            $url: id === 2 ? '/source' : '/source/child',
            $parentid: id === 2 ? 1 : 2,
            attributes: {name: id === 2 ? 'source' : 'child', content: 'unsaved content'},
            getProject: () => project,
            getId: () => id,
            ajaxParams: () => ({id, project: 'test-project'}),
            hasWorkingStorage: () => false,
            clearWorkingStorage() {}
        });
        object.getAttribute = name => object.attributes[name];
        object.addEvent('move', (Site, parentId) => Project.$onSiteMove.call(project, Site, parentId));
        return object;
    };
    const panel = Site => {
        const input = {value: Site.getAttribute('name'), events: {}, set(values) { Object.assign(this, values); }};
        const display = {textContent: Site.getUrl()};
        const elements = {siteName: input, siteUrl: display, siteUrlEdit: {}};
        const object = {
            ...Panel,
            $Container: {querySelector: selector => elements[selector.match(/"(.*)"/)[1]] || null},
            getSite: () => Site,
            Loader: {show() {}},
            $buildPanel: () => new Promise(() => {}),
            $lockRequest: () => Promise.resolve(),
            refresh() { this.refreshCount = (this.refreshCount || 0) + 1; }
        };
        for (const name of Panel.Binds) {
            if (typeof object[name] === 'function') object[name] = object[name].bind(object);
        }
        object.$onInject();
        object.$bindNameInputUrlFilter();
        return {object, input, display};
    };
    return {requests, site, panel};
}

test('moving a page refreshes open descendants and preserves drafts and name editing', async () => {
    const {requests, site, panel} = fixture();
    const source = site(2);
    const child = site(3);
    const sourcePanel = panel(source);
    const childPanel = panel(child);
    sourcePanel.input.value = 'unsaved-source';
    childPanel.input.value = 'unsaved-child';
    let callbackUrl;
    const moved = source.move(42, () => { callbackUrl = source.getUrl(); });
    assert.equal(requests[0].name, 'ajax_site_move');
    requests.shift().success(null);
    assert.equal(requests[0].name, 'ajax_site_getUrl');
    requests.shift().success({url: '/target/source', parentid: 42});
    await moved;
    assert.equal(callbackUrl, '/target/source');
    assert.equal(source.$parentid, 42);
    assert.equal(sourcePanel.display.textContent, '/target/unsaved-source');
    assert.equal(requests.length, 1);
    assert.equal(requests[0].params.id, 3);
    requests.shift().success({url: '/target/source/child', parentid: 2});
    assert.equal(childPanel.display.textContent, '/target/source/unsaved-child');
    assert.equal(childPanel.input.value, 'unsaved-child');
    assert.equal(child.attributes.content, 'unsaved content');
    assert.equal(source.attributes.name, 'source');
    assert.equal(childPanel.object.refreshCount, 1);
    childPanel.input.value = 'edited-again';
    childPanel.input.events.keyup.call(childPanel.input);
    assert.equal(childPanel.display.textContent, '/target/source/edited-again');
});

test('an older URL response cannot replace newer location metadata', async () => {
    const {requests, site} = fixture();
    const source = site(2);
    const first = source.refreshUrl();
    const second = source.refreshUrl();
    requests[1].success({url: '/new/source', parentid: 43});
    requests[0].success({url: '/old/source', parentid: 42});
    await Promise.all([first, second]);
    assert.equal(source.getUrl(), '/new/source');
    assert.equal(source.$parentid, 43);
});

test('URL refresh failure does not suppress a completed move or its callback', async () => {
    const {requests, site} = fixture();
    const source = site(2);
    let callbackCalled = false;
    let moveCalled = false;
    source.addEvent('move', () => { moveCalled = true; });
    const moved = source.move(42, () => { callbackCalled = true; });
    requests.shift().success(null);
    requests.shift().params.onError(new Error('Connection failed'));
    await moved;
    assert.equal(callbackCalled, true);
    assert.equal(moveCalled, true);
    assert.equal(source.$parentid, 42);
});

test('a rejected move does not modify the URL or start a refresh', async () => {
    const {requests, site} = fixture();
    const source = site(2);
    const moved = source.move(42);
    requests.shift().params.onError(new Error('Move denied'));
    await assert.rejects(moved, /Move denied/);
    assert.equal(source.getUrl(), '/source');
    assert.equal(source.$parentid, 1);
    assert.equal(requests.length, 0);
});

test('destroyed panels stop reacting to project moves and pending URL responses', async () => {
    const {requests, site, panel} = fixture();
    const child = site(3);
    const childPanel = panel(child);
    const pending = child.refreshUrl();
    childPanel.object.$onDestroy();
    requests.shift().success({url: '/new/source/child', parentid: 2});
    await pending;
    child.getProject().fireEvent('siteMove', [child.getProject(), site(2), 42]);
    assert.equal(requests.length, 0);
    assert.equal(childPanel.display.textContent, '/source/child');
});

test('URL updates tolerate another editor category and render names as text', () => {
    const {site, panel} = fixture();
    const child = site(3);
    const childPanel = panel(child);
    childPanel.input.value = '<draft>';
    childPanel.object.$onSiteUrlChange();
    assert.equal(childPanel.display.textContent, '/source/<draft>');
    childPanel.object.$Container.querySelector = () => null;
    assert.doesNotThrow(() => childPanel.object.$onSiteUrlChange(child));
});
