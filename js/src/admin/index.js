import app from "flarum/app";
import FakeDataModal from "./components/FakeDataModal";

app.initializers.add("migratetoflarum-fake-data", (app) => {
    app.extensionData
        .for("migratetoflarum-fake-data")
        .registerPage(FakeDataModal);
});
