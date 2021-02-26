import app from "flarum/app";
import FakeDataExtensionSettingsPage from "./components/FakeDataExtensionSettingsPage";

app.initializers.add("migratetoflarum-fake-data", (app) => {
    app.extensionData
        .for("migratetoflarum-fake-data")
        .registerPage(FakeDataExtensionSettingsPage);
});
