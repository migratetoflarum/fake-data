import app from 'flarum/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';
import icon from 'flarum/common/helpers/icon';

/* global m, flarum */

const translationPrefix = 'migratetoflarum-fake-data.admin.generator.';

export default class FakeDataPage extends ExtensionPage {
    oninit(vnode) {
        super.oninit(vnode);

        this.bulk = false;
        this.userCount = 0;
        this.discussionCount = 0;
        this.discussionTag = 'none';
        this.postCount = 0;
        this.dateStart = '';
        this.dateInterval = '';
        this.dirty = false;
        this.loading = false;
    }

    content() {
        return m('.ExtensionPage-settings', m('.container', [
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
                m('label', app.translator.trans(translationPrefix + 'discussion-count')),
                m('input.FormControl', {
                    type: 'number',
                    min: '0',
                    value: this.discussionCount + '',
                    oninput: event => {
                        this.discussionCount = parseInt(event.target.value);
                        this.dirty = true;
                    },
                }),
            ]),
            flarum.extensions['flarum-tags'] ? m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'discussion-tags')),
                m('span.Select', [
                    m('select.Select-input.FormControl', {
                        onchange: event => {
                            this.discussionTag = event.target.value;
                        },
                        value: this.discussionTag,
                    }, [
                        m('option', {
                            value: 'none',
                        }, app.translator.trans(translationPrefix + 'discussion-tags-none')),
                        m('option', {
                            value: 'random',
                        }, app.translator.trans(translationPrefix + 'discussion-tags-random')),
                        flarum.core.compat['tags/utils/sortTags'](app.store.all('tags')).map(tag => {
                            let label = tag.name();
                            const ids = [tag.id()];

                            if (tag.isChild()) {
                                const parent = tag.parent();
                                label = parent.name() + ' / ' + label;
                                ids.push(parent.id());
                            }

                            // Sort IDs in the comma-separated value so we can compare two values and know it's the same
                            const value = ids.sort().join(',');

                            return m('option', {
                                value,
                            }, label);
                        }),
                    ]),
                    icon('fas fa-sort', {className: 'Select-caret'}),
                ]),
            ]) : null,
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

                        let tag_ids = [];

                        if (this.discussionTag === 'random') {
                            tag_ids = 'random';
                        } else if (this.discussionTag !== 'none') {
                            tag_ids = this.discussionTag.split(',');
                        }

                        app.request({
                            url: app.forum.attribute('apiUrl') + '/fake-data',
                            method: 'POST',
                            body: {
                                bulk: this.bulk,
                                user_count: this.userCount,
                                discussion_count: this.discussionCount,
                                tag_ids,
                                post_count: this.postCount,
                                date_start: this.dateStart,
                                date_interval: this.dateInterval,
                            },
                        }).then(() => {
                            this.userCount = 0;
                            this.discussionCount = 0;
                            this.discussionTag = 'none';
                            this.postCount = 0;
                            this.dirty = false;
                            this.loading = false;

                            m.redraw();
                        }).catch(e => {
                            this.loading = false;
                            m.redraw();
                            throw e;
                        });
                    },
                }, app.translator.trans(translationPrefix + 'send')),
            ]),
        ]));
    }
}
