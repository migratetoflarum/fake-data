import app from 'flarum/admin/app';
import FakeDataPage from './components/FakeDataPage';

app.initializers.add('migratetoflarum-fake-data', function () {
    app.extensionData
        .for('migratetoflarum-fake-data')
        .registerPage(FakeDataPage);
});
