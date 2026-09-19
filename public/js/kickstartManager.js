function kickstartManager() {

    populateDbDropdown = (data) => {

        var resources = JSON.parse(data);

        var $dropdown = $("#resource-field");

        for (i = 0; i < resources['databases'].length; i++) {
            if ($dropdown.find("option[value='" + resources['databases'][i] + "']").length === 0) {
                $dropdown.append("<option value=" + resources['databases'][i] + ">" + resources['databases'][i] + "</option>");
            }
        }

    }

    populateSavedSettings = (data) => {

        var settings = data['data'];
        var $dropdown = $("#resource-field");

        if (settings.resource && $dropdown.find("option[value='" + settings.resource + "']").length === 0) {
            $dropdown.append("<option value=" + settings.resource + ">" + settings.resource + "</option>");
        }

        $("#resource-field").val(settings.resource);
        $("#host-field").val(settings.host);
        $("#port-field").val(settings.port);
        $("#user-field").val(settings.username);
        $("#password-field").val(settings.password);

    }

    processError = (error) => {

        errorHandler(error);

    }

    startFormListeners = () => {
        $('form').submit(function (data) {

            data.preventDefault();

            var formData = $("form.settings-form").serializeArray();

            var settingsPromise = storeSettings(formData).then(testSettings, processError)

        });

    }

    testSettings = () => {

        success = () => {

            $('#notifications').append().html('<li class="success fade-out">Settings Saved Successfully</li>');

            setTimeout(() =>{
                window.location.replace('./network')
            }, 1000)
        }

        var hostPromise = getHosts().then(success, processError)
    }

    var resourcePromise = getIcingaResourceDatabases().then(populateDbDropdown, processError)
    var settingsPromise = getModuleSettings().then(populateSavedSettings, processError)

    Promise.all([resourcePromise, settingsPromise]).then(startFormListeners);

}
