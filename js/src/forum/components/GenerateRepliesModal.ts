import app from 'flarum/forum/app';
import Modal, {IInternalModalAttrs} from 'flarum/common/components/Modal';
import Discussion from 'flarum/common/models/Discussion';
import GeneratorForm from '../../common/components/GeneratorForm';

const translationPrefix = 'migratetoflarum-fake-data.forum.generator.';

interface GenerateRepliesModalAttrs extends IInternalModalAttrs {
    discussion: Discussion
}

export default class GenerateRepliesModal extends Modal<GenerateRepliesModalAttrs> {
    className() {
        return 'GenerateRepliesModal';
    }

    title() {
        return app.translator.trans(translationPrefix + 'title');
    }

    content() {
        return m('.Modal-body', GeneratorForm.component({
            discussion: this.attrs.discussion,
        }));
    }
}
