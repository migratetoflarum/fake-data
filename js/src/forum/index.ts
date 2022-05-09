import app from 'flarum/forum/app';
import {extend} from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import GenerateRepliesModal from './components/GenerateRepliesModal';

app.initializers.add('migratetoflarum-fake-data', function () {
    extend(DiscussionControls, 'moderationControls', function (items: ItemList<any>, discussion: any) {
        // Don't show the button to non-admins
        if (!app.forum.attribute('adminUrl')) {
            return;
        }

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
