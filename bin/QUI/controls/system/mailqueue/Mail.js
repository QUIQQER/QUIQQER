define('controls/system/mailqueue/Mail', [
    'qui/controls/desktop/Panel',
    'qui/controls/elements/Sandbox',
    'Ajax',
    'Locale',
    'css!controls/system/mailqueue/Mail.css'
], function (QUIPanel, Sandbox, QUIAjax, QUILocale) {
    'use strict';

    const lg = 'quiqqer/core';

    const createRow = function (label, value) {
        const Row = new Element('div', {
            'class': 'mailqueue-mailPanel-row'
        });

        new Element('div', {
            text: label + ':',
            'class': 'mailqueue-mailPanel-rowLabel'
        }).inject(Row);

        new Element('div', {
            text: value || '-',
            'class': 'mailqueue-mailPanel-rowValue'
        }).inject(Row);

        return Row;
    };

    const createBadge = function (label, value, extraClass) {
        const classes = ['badge', 'badge-pill', 'badge-sm'];

        if (extraClass) {
            classes.push(extraClass);
        } else {
            classes.push('badge-dark-light');
        }

        return new Element('span', {
            'class': classes.join(' '),
            text: label + ': ' + (value || '-')
        });
    };

    const getStatusBadgeClass = function (status) {
        status = parseInt(status, 10);

        switch (status) {
            case 0:
                return 'badge-info-light';

            case 1:
                return 'badge-success-light';

            case 2:
                return 'badge-warning-light';

            case 3:
                return 'badge-danger-light';

            case 4:
                return 'badge-dark-light';

            default:
                return 'badge-dark-light';
        }
    };

    return new Class({
        Extends: QUIPanel,
        Type: 'controls/system/mailqueue/Mail',

        Binds: [
            '$onCreate'
        ],

        options: {
            mailId: '',
            title: QUILocale.get(lg, 'mailqueue.mail.panel.title'),
            icon: 'fa fa-envelope-open'
        },

        initialize: function (options) {
            this.setAttributes({
                title: QUILocale.get(lg, 'mailqueue.mail.panel.title'),
                icon: 'fa fa-envelope-open'
            });

            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate
            });
        },

        $onCreate: function () {
            const mailId = this.getAttribute('mailId');
            const Body = this.getBody();

            Body.set('class', 'mailqueue-mailPanel-body');

            if (!mailId) {
                Body.set('text', QUILocale.get(lg, 'mailqueue.mail.panel.error.no_mail_id'));
                return;
            }

            this.Loader.show();

            QUIAjax.get('ajax_system_mailqueue_get', (mail) => {
                if (!mail || !mail.id) {
                    this.Loader.hide();
                    Body.set('text', QUILocale.get(lg, 'mailqueue.mail.panel.error.not_found'));
                    return;
                }

                this.setAttribute('title', mail.subject || mail.id);
                Body.set('html', '');

                const Layout = new Element('div', {
                    'class': 'mailqueue-mailPanel-layout'
                }).inject(Body);

                const HeaderCard = new Element('div', {
                    'class': 'mailqueue-mailPanel-card'
                }).inject(Layout);

                new Element('h2', {
                    text: mail.subject || QUILocale.get(lg, 'mailqueue.mail.panel.title'),
                    'class': 'mailqueue-mailPanel-title'
                }).inject(HeaderCard);

                const BadgeRow = new Element('div', {
                    'class': 'mailqueue-mailPanel-badges'
                }).inject(HeaderCard);

                createBadge('ID', String(mail.id), 'badge-primary-light').inject(BadgeRow);
                createBadge(
                    QUILocale.get(lg, 'mailqueue.mail.field.status'),
                    mail.status_label,
                    getStatusBadgeClass(mail.status)
                ).inject(BadgeRow);
                createBadge(
                    QUILocale.get(lg, 'mailqueue.mail.field.retry'),
                    String(mail.retry || 0),
                    'badge-secondary-light'
                ).inject(BadgeRow);
                createBadge(
                    QUILocale.get(lg, 'mailqueue.mail.field.lastsend'),
                    mail.lastsend_display,
                    'badge-dark-light'
                ).inject(BadgeRow);

                const HeaderMeta = new Element('div', {
                    'class': 'mailqueue-mailPanel-meta'
                }).inject(HeaderCard);

                createRow(QUILocale.get(lg, 'mailqueue.mail.field.from'), mail.from_display || '-').inject(HeaderMeta);
                createRow(QUILocale.get(lg, 'mailqueue.mail.field.to'), mail.mail_to_display).inject(HeaderMeta);
                createRow(QUILocale.get(lg, 'mailqueue.mail.field.reply_to'), mail.reply_to_display).inject(HeaderMeta);
                createRow(QUILocale.get(lg, 'mailqueue.mail.field.cc'), mail.mail_cc_display).inject(HeaderMeta);
                createRow(QUILocale.get(lg, 'mailqueue.mail.field.bcc'), mail.mail_bcc_display).inject(HeaderMeta);

                const ErrorWrap = new Element('div', {
                    'class': 'mailqueue-mailPanel-card'
                }).inject(Layout);

                new Element('h3', {
                    text: QUILocale.get(lg, 'mailqueue.mail.field.errors'),
                    'class': 'mailqueue-mailPanel-sectionTitle'
                }).inject(ErrorWrap);

                new Element('pre', {
                    text: mail.errors || '-',
                    'class': 'mailqueue-mailPanel-text'
                }).inject(ErrorWrap);

                const BodyWrap = new Element('div', {
                    'class': 'mailqueue-mailPanel-card'
                }).inject(Layout);

                new Element('h3', {
                    text: QUILocale.get(lg, 'mailqueue.mail.field.body_html'),
                    'class': 'mailqueue-mailPanel-sectionTitle'
                }).inject(BodyWrap);

                const HtmlViewport = new Element('div', {
                    'class': 'mailqueue-mailPanel-htmlViewport'
                }).inject(BodyWrap);

                new Sandbox({
                    content: (mail.body && String(mail.body).trim() !== '') ? mail.body : '-',
                    styles: {
                        width: '100%',
                        height: '100%',
                        border: '0'
                    }
                }).inject(HtmlViewport);

                const TextWrap = new Element('div', {
                    'class': 'mailqueue-mailPanel-card'
                }).inject(Layout);

                new Element('h3', {
                    text: QUILocale.get(lg, 'mailqueue.mail.field.body_text'),
                    'class': 'mailqueue-mailPanel-sectionTitle'
                }).inject(TextWrap);

                new Element('pre', {
                    text: mail.text || '-',
                    'class': 'mailqueue-mailPanel-text'
                }).inject(TextWrap);

                this.Loader.hide();
            }, {
                id: mailId
            });
        }
    });
});
