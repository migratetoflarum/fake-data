import app from 'flarum/app';
import FakeDataModal from './components/FakeDataModal';

app.initializers.add('migratetoflarum-fake-data', app => {
    app.extensionSettings['migratetoflarum-fake-data'] = () => app.modal.show(FakeDataModal);
});
