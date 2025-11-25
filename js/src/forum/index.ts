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

    try {
        // At the time of writing, the extension is not yet compatible with Flarum 2.x
        // No idea whether this import path will be valid
        // The try/catch block should prevent a crash if the extension exists but doesn't follow this pattern
        const BlogPostController = flarum.reg.get('v17development-blog', 'forum/components/BlogPostController');

        if (BlogPostController) {
            extend(BlogPostController.prototype, 'manageArticleButtons', function (items) {
                addControls(items, this.attrs.article);
            });
        }
    } catch (error) {
        console.warn('[fake-data] Could not extend v17development-blog', error);
    }
});
