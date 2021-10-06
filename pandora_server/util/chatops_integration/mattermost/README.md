# Pandora FMS Mattermost Plugin

A plugin for [Pandora FMS](https://github.com/pandorafms/pandorafms) to enable notifications to Mattermost Open Source Chat.

# Usage

Assuming you are using Pandora FMS 6.0, the steps are:

1. Create the [Alert command](https://barivion.com/manual/en/documentation/04_using/01_alerts#the_alert_command) going to Alerts -> Commands and clicking on "Create". Then:
   ![create command](help/images/1-set-up-the-mattermost-command.png?raw=true "Set up Mattermost Command")

2. Define the [Alert Action](https://barivion.com/manual/en/documentation/04_using/01_alerts#alert_actions) going to Alerts -> Actions and clicking on "Create". Then:
   ![create action](help/images/2-set-up-the-mattermost-action.png?raw=true "Set up Mattermost Action")

3. Assign the action to an existing module under Alerts -> List of alerts:
   ![assign template to module](../help/images/3-assign-template-to-module.png?raw=true "Assign a template to a module")

4. Optinionally, go to your agent and verify the alert has been created:
   ![Verify the alert creation](../help/images/4-verify.png?raw=true "Verify the alert creation")

When the alert triggers, the result would be something like this:
![Mattermost-real-example](../help/images/5-mattermost-result.png?raw=true "Mattermost real example")
