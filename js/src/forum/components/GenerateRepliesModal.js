import app from 'flarum/app';
import Modal from 'flarum/components/Modal';
import Button from 'flarum/components/Button';

/* global m */

const translationPrefix = 'migratetoflarum-fake-data.forum.generator.';

export default class GenerateRepliesModal extends Modal {
    init() {
        super.init();

        this.userCount = 0;
        this.postCount = 0;
        this.dirty = false;
        this.loading = false;
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
                        this.loading = true;

                        app.request({
                            url: '/api/fake-data',
                            method: 'POST',
                            data: {
                                user_count: this.userCount,
                                discussion_count: 0,
                                discussion_ids: [this.props.discussion.id()],
                                post_count: this.postCount,
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
                }),
            ]),
        ]);
    }
}
