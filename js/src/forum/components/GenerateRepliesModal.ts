import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import GeneratorForm from '../../common/components/GeneratorForm';

const translationPrefix = 'migratetoflarum-fake-data.forum.generator.';

export default class GenerateRepliesModal extends Modal {
    title() {
        return app.translator.trans(translationPrefix + 'title');
    }

    content() {
        return m('.Modal-body', GeneratorForm.component({
            discussion: this.attrs.discussion,
        }));
    }
}
