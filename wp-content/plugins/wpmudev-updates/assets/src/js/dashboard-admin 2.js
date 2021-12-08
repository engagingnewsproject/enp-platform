import '../scss/dashboard-admin.scss';

// shared ui
import "@wpmudev/shared-ui/dist/js/_src/dropdowns";
import "@wpmudev/shared-ui/dist/js/_src/select2.full";
import "@wpmudev/shared-ui/dist/js/_src/select2";
import "@wpmudev/shared-ui/dist/js/_src/password";
import "@wpmudev/shared-ui/dist/js/_src/notifications";
import "@wpmudev/shared-ui/dist/js/_src/modal-dialog";
import "@wpmudev/shared-ui/dist/js/_src/tabs";
import "@wpmudev/shared-ui/dist/js/_src/side-tabs";
import "ajaxq";
import "./admin/plugins";
import "./admin/support";
import "./admin/tools";
import "./admin/settings";
import "./admin/login";
import "./admin/dashboard";
import "./admin/common";
import "./admin/settings/data";

// export A11yDialog
jQuery(document).ready(function () {
	jQuery("body.wpmud-plugins").wpmudevDashboardAdminPluginsPage();
	jQuery("body.wpmud-support").wpmudevDashboardAdminSupportPage();
	jQuery("body.wpmud-analytics").wpmudevDashboardAdminToolsPage();
	jQuery("body.wpmud-whitelabel").wpmudevDashboardAdminToolsPage();
	jQuery("body.wpmud-settings").wpmudevDashboardAdminSettingsPage();
	jQuery("body.wpmud-login").wpmudevDashboardAdminLoginPage();
	jQuery("body.wpmud-dashboard").wpmudevDashboardAdminDashboardPage();
	jQuery("body.wpmudevdash").wpmudevDashboardAdminCommon();

	jQuery(document).trigger("wpmud.ready");
});
