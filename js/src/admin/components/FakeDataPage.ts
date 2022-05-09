import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import GeneratorForm from '../../common/components/GeneratorForm';

export default class FakeDataPage extends ExtensionPage {
    content() {
        return m('.ExtensionPage-settings', m('.container', GeneratorForm.component()));
    }
}
