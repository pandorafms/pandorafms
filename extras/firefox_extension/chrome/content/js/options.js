var OptionsListener = {
    prefs: null,
    tickerSymbol: "",

    // Initialize the extension

    startup: function()
    {
        // Register to receive notifications of preference changes
        this.prefs = Components.classes["@mozilla.org/preferences-service;1"]
            .getService(Components.interfaces.nsIPrefService)
            .getBranch("pandora.");
        this.prefs.QueryInterface(Components.interfaces.nsIPrefBranch2);
        this.prefs.addObserver("", this, false);
        OptionsListener.onChangeSound(this.prefs.getBoolPref("sound_alert"));

    },

    // Clean up after ourselves and save the prefs

    shutdown: function()
    {
        this.prefs.removeObserver("", this);
    },

    // Called when events occur on the preferences

    // Switches to watch a different stock, by symbol
    onChangeSound: function(value){
        if(value){
            document.getElementById('critical').disabled=false;
            document.getElementById('informational').disabled=false;
            document.getElementById('maintenance').disabled=false;
            document.getElementById('normal').disabled=false;
            document.getElementById('warning').disabled=false;
        }
        else{
            document.getElementById('critical').disabled=true;
            document.getElementById('informational').disabled=true;
            document.getElementById('maintenance').disabled=true;
            document.getElementById('normal').disabled=true;
            document.getElementById('warning').disabled=true;
        }
    },

    watchStock: function(newSymbol)
    {
        this.prefs.setCharPref("symbol", newSymbol);
    }
}

// Install load and unload handlers

window.addEventListener("load", function(e) { OptionsListener.startup(); }, false);
window.addEventListener("unload", function(e) { OptionsListener.shutdown(); }, false);