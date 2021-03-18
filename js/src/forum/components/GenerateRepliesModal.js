import app from 'flarum/app';
import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';
import Switch from 'flarum/components/Switch';

/* global m */

const translationPrefix = 'migratetoflarum-fake-data.forum.generator.';

export default class GenerateRepliesModal extends Modal {
    oninit(vnode) {
        super.oninit(vnode);

        this.bulk = false;
        this.userCount = 0;
        this.postCount = 0;
        this.dateStart = '';
        this.dateInterval = '';
        this.dirty = false;
        this.loading = false;
    }

    title() {
        return app.translator.trans(translationPrefix + 'title');
    }

    content() {
        return m('.Modal-body', [
            m('.Form-group', [
                Switch.component({
                    state: this.bulk,
                    onchange: value => {
                        this.bulk = value;
                    },
                }, app.translator.trans(translationPrefix + 'bulk-mode')),
                m('.helpText', app.translator.trans(translationPrefix + 'bulk-mode-description')),
            ]),
            m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'user-count')),
                m('input.FormControl', {
                    type: 'number',
                    min: '0',
                    value: this.userCount + '',
                    oninput: event => {
                        this.userCount = parseInt(event.target.value);
                        this.dirty = true;
                    },
                }),
            ]),
            m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'post-count')),
                m('input.FormControl', {
                    type: 'number',
                    min: '0',
                    value: this.postCount + '',
                    oninput: event => {
                        this.postCount = parseInt(event.target.value);
                        this.dirty = true;
                    },
                }),
            ]),
            m('.Form-group.FakeData-Date', [
                m('label', app.translator.trans(translationPrefix + 'date')),
                m('input.FormControl', {
                    type: 'text',
                    value: this.dateStart + '',
                    oninput: event => {
                        this.dateStart = event.target.value;
                        this.dirty = true;
                    },
                    placeholder: app.translator.trans(translationPrefix + 'date-start-placeholder'),
                }),
                m('input.FormControl', {
                    type: 'text',
                    value: this.dateInterval + '',
                    oninput: event => {
                        this.dateInterval = event.target.value;
                        this.dirty = true;
                    },
                    placeholder: app.translator.trans(translationPrefix + 'date-interval-placeholder'),
                }),
            ]),
            m('.Form-group', [
                Button.component({
                    disabled: !this.dirty,
                    loading: this.loading,
                    className: 'Button Button--primary',
                    onclick: () => {
                        this.loading = true;

                        app.request({
                            url: app.forum.attribute('apiUrl') + '/fake-data',
                            method: 'POST',
                            body: {
                                bulk: this.bulk,
                                user_count: this.userCount,
                                discussion_count: 0,
                                discussion_ids: [this.attrs.discussion.id()],
                                post_count: this.postCount,
                                date_start: this.dateStart,
                                date_interval: this.dateInterval,
                            },
                        }).then(() => {
                            this.loading = false;
                            app.modal.close();

                            window.location.reload();
                        }).catch(e => {
                            this.loading = false;
                            m.redraw();
                            throw e;
                        });
                    },
                }, app.translator.trans(translationPrefix + 'send')),
            ]),
        ]);
    }
}
