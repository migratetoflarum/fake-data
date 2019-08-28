import app from 'flarum/app';
import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

/* global m */

const translationPrefix = 'migratetoflarum-fake-data.admin.generator.';

export default class FakeDataModal extends Modal {
    constructor() {
        super();

        this.userCount = 0;
        this.discussionCount = 0;
        this.postCount = 0;
        this.dirty = false;
    }

    title() {
        return app.translator.trans(translationPrefix + 'title');
    }

    content() {
        return m('.Modal-body', [
            m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'user-count')),
                m('input.FormControl', {
                    type: 'number',
                    min: '0',
                    value: this.userCount + '',
                    oninput: m.withAttr('value', value => {
                        this.userCount = parseInt(value);
                        this.dirty = true;
                    }),
                }),
            ]),
            m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'discussion-count')),
                m('input.FormControl', {
                    type: 'number',
                    min: '0',
                    value: this.discussionCount + '',
                    oninput: m.withAttr('value', value => {
                        this.discussionCount = parseInt(value);
                        this.dirty = true;
                    }),
                }),
            ]),
            m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'post-count')),
                m('input.FormControl', {
                    type: 'number',
                    min: '0',
                    value: this.postCount + '',
                    oninput: m.withAttr('value', value => {
                        this.postCount = parseInt(value);
                        this.dirty = true;
                    }),
                }),
            ]),
            m('.Form-group', [
                Button.component({
                    disabled: !this.dirty,
                    className: 'Button Button--primary',
                    children: app.translator.trans(translationPrefix + 'send'),
                    onclick: () => {
                        app.request({
                            url: '/api/fake-data',
                            method: 'POST',
                            data: {
                                user_count: this.userCount,
                                discussion_count: this.discussionCount,
                                post_count: this.postCount,
                            },
                        }).then(() => {
                            this.userCount = 0;
                            this.discussionCount = 0;
                            this.postCount = 0;
                            this.dirty = false;

                            m.redraw();
                        });
                    },
                }),
            ]),
        ]);
    }
}
