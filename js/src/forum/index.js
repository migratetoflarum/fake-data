import app from 'flarum/app';
import {extend} from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import GenerateRepliesModal from './components/GenerateRepliesModal';

app.initializers.add('migratetoflarum-fake-data', app => {
    extend(DiscussionControls, 'moderationControls', function (items, discussion) {
        items.add('migratetoflarum-fake-data', Button.component({
            icon: 'fas fa-database',
            onclick() {
                app.modal.show(GenerateRepliesModal, {
                    discussion,
                });
            },
        }, app.translator.trans('migratetoflarum-fake-data.forum.link.generate-replies')));
    })
});
