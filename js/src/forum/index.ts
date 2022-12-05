import app from 'flarum/forum/app';
import {extend} from 'flarum/common/extend';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import Discussion from 'flarum/common/models/Discussion';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import GenerateRepliesModal from './components/GenerateRepliesModal';

function addControls(items: ItemList<any>, discussion: Discussion) {
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
}

app.initializers.add('migratetoflarum-fake-data', function () {
    extend(DiscussionControls, 'moderationControls', function (items: ItemList<any>, discussion: any) {
        addControls(items, discussion);
    });

    if ('v17development/blog/components/BlogPostController' in (flarum.core as any).compat) {
        extend(flarum.core.compat['v17development/blog/components/BlogPostController'].prototype, 'manageArticleButtons', function (items) {
            addControls(items, this.attrs.article);
        });
    }
});
