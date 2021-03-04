import app from 'flarum/app';
import FakeDataPage from './components/FakeDataPage';

app.initializers.add('migratetoflarum-fake-data', app => {
    app.extensionData
        .for('migratetoflarum-fake-data')
        .registerPage(FakeDataPage);
});
