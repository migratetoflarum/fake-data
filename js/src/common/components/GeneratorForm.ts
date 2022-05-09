import app from 'flarum/common/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';
import Discussion from 'flarum/common/models/Discussion';
import icon from 'flarum/common/helpers/icon';

const translationPrefix = 'migratetoflarum-fake-data.lib.generator.';

interface GeneratorFormAttrs {
    discussion?: Discussion
}

export default class GeneratorForm extends Component<GeneratorFormAttrs> {
    bulk: boolean = false
    userCount: number = 0
    discussionCount: number = 0
    discussionTag: string = 'none'
    postCount: number = 0
    dateStart: string = ''
    dateInterval: string = ''
    dirty: boolean = false
    loading: boolean = false
    output: string | null = null

    view() {
        const disabled = this.loading || this.output;

        return [
            m('.Form-group', [
                Switch.component({
                    state: this.bulk,
                    onchange: (value: boolean) => {
                        this.bulk = value;
                    },
                    disabled,
                }, app.translator.trans(translationPrefix + 'bulk-mode')),
                m('.helpText', app.translator.trans(translationPrefix + 'bulk-mode-description')),
            ]),
            m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'user-count')),
                m('input.FormControl', {
                    type: 'number',
                    min: '0',
                    value: this.userCount + '',
                    oninput: (event: Event) => {
                        this.userCount = parseInt((event.target as HTMLInputElement).value);
                        this.dirty = true;
                    },
                    disabled,
                }),
            ]),
            this.attrs.discussion ? null : [
                m('.Form-group', [
                    m('label', app.translator.trans(translationPrefix + 'discussion-count')),
                    m('input.FormControl', {
                        type: 'number',
                        min: '0',
                        value: this.discussionCount + '',
                        oninput: (event: Event) => {
                            this.discussionCount = parseInt((event.target as HTMLInputElement).value);
                            this.dirty = true;
                        },
                        disabled,
                    }),
                ]),
                flarum.extensions['flarum-tags'] ? m('.Form-group', [
                    m('label', app.translator.trans(translationPrefix + 'discussion-tags')),
                    m('span.Select', [
                        m('select.Select-input.FormControl', {
                            onchange: (event: Event) => {
                                this.discussionTag = (event.target as HTMLInputElement).value;
                            },
                            value: this.discussionTag,
                            disabled,
                        }, [
                            m('option', {
                                value: 'none',
                            }, app.translator.trans(translationPrefix + 'discussion-tags-none')),
                            m('option', {
                                value: 'random',
                            }, app.translator.trans(translationPrefix + 'discussion-tags-random')),
                            flarum.core.compat['tags/utils/sortTags'](app.store.all('tags')).map((tag: any) => {
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
            ],
            m('.Form-group', [
                m('label', app.translator.trans(translationPrefix + 'post-count')),
                m('input.FormControl', {
                    type: 'number',
                    min: '0',
                    value: this.postCount + '',
                    oninput: (event: Event) => {
                        this.postCount = parseInt((event.target as HTMLInputElement).value);
                        this.dirty = true;
                    },
                    disabled,
                }),
            ]),
            m('.Form-group.FakeData-Date', [
                m('label', app.translator.trans(translationPrefix + 'date')),
                m('input.FormControl', {
                    type: 'text',
                    value: this.dateStart + '',
                    oninput: (event: Event) => {
                        this.dateStart = (event.target as HTMLInputElement).value;
                        this.dirty = true;
                    },
                    disabled,
                    placeholder: app.translator.trans(translationPrefix + 'date-start-placeholder'),
                }),
                m('input.FormControl', {
                    type: 'text',
                    value: this.dateInterval + '',
                    oninput: (event: Event) => {
                        this.dateInterval = (event.target as HTMLInputElement).value;
                        this.dirty = true;
                    },
                    disabled,
                    placeholder: app.translator.trans(translationPrefix + 'date-interval-placeholder'),
                }),
            ]),
            m('.Form-group', [
                this.output === null ? Button.component({
                    disabled: !this.dirty,
                    loading: this.loading,
                    className: 'Button Button--primary',
                    onclick: () => {
                        this.loading = true;

                        const body: any = {
                            bulk: this.bulk,
                            user_count: this.userCount,
                            post_count: this.postCount,
                            date_start: this.dateStart,
                            date_interval: this.dateInterval,
                        };

                        if (this.attrs.discussion) {
                            body.discussion_count = 0;
                            body.discussion_ids = [this.attrs.discussion.id()];
                        } else {
                            body.discussion_count = this.discussionCount;

                            if (this.discussionTag === 'random') {
                                body.tag_ids = 'random';
                            } else if (this.discussionTag !== 'none') {
                                body.tag_ids = this.discussionTag.split(',');
                            }
                        }

                        app.request<any>({
                            url: app.forum.attribute('apiUrl') + '/fake-data',
                            method: 'POST',
                            body,
                        }).then(response => {
                            this.dirty = false;
                            this.loading = false;
                            this.output = response && response.output || '<no output>';

                            m.redraw();
                        }).catch(e => {
                            this.loading = false;
                            m.redraw();
                            throw e;
                        });
                    },
                }, app.translator.trans(translationPrefix + 'send')) : [
                    Button.component({
                        className: 'Button',
                        onclick: () => {
                            this.userCount = 0;
                            this.discussionCount = 0;
                            this.discussionTag = 'none';
                            this.postCount = 0;
                            this.output = null;
                        },
                    }, app.translator.trans(translationPrefix + 'reset')),
                    this.attrs.discussion ? [
                        Button.component({
                            className: 'Button Button--primary',
                            onclick: () => {
                                app.modal.close();

                                window.location.reload();
                            },
                        }, app.translator.trans(translationPrefix + 'refresh')),
                    ] : null,
                    m('pre', this.output),
                ],
            ]),
        ];
    }
}
