import Extend from 'flarum/common/extenders';
import FakeDataPage from './components/FakeDataPage';

export const extend = [
    new Extend.Admin()
        .page(FakeDataPage),
];
