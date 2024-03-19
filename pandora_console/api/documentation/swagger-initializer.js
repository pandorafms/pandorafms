window.onload = function() {
  //<editor-fold desc="Changeable Configuration Block">
  const UrlMutatorPlugin = system => ({
    rootInjects: {
      setServer: server => {
        const jsonSpec = system.getState().toJSON().spec.json;
        const endpoint = window.location.pathname.replace(
          "/api/documentation/",
          ""
        );
        const servers = [
          {
            url: endpoint + jsonSpec.servers[0].url,
            description: "Pandora Fms Api"
          }
        ];
        const newJsonSpec = Object.assign({}, jsonSpec, { servers });

        return system.specActions.updateJsonSpec(newJsonSpec);
      }
    }
  });

  // the following lines will be replaced by docker/configurator, when it runs in a docker-container
  const ui = SwaggerUIBundle({
    url: "../v2/swagger.json",
    dom_id: "#swagger-ui",
    docExpansion: "none",
    deepLinking: true,
    presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
    plugins: [SwaggerUIBundle.plugins.DownloadUrl, UrlMutatorPlugin],
    layout: "StandaloneLayout",
    onComplete: () => {
      window.ui.setServer();
    }
  });

  window.ui = ui;

  //</editor-fold>
};
