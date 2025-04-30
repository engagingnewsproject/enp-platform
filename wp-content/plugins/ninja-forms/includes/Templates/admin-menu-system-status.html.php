<div class="wrap">

    <h1><?php esc_html_e('Get Help', 'ninja-forms'); ?></h1>
    <h2><?php esc_html_e('Before requesting help from our support team please review:', 'ninja-forms'); ?></h2>
    <ol>
        <li><a href='https://ninjaforms.com/documentation/?utm_source=Ninja+Forms+Plugin&utm_medium=Get+Help&utm_campaign=Support+Materials&utm_content=Get+Help+Documentation'><?php esc_html_e('Ninja Forms Documentation', 'ninja-forms'); ?></a></li>
        <li><a href='https://ninjaforms.com/docs/troubleshooting-email-problems/?utm_source=Ninja+Forms+Plugin&utm_medium=Get+Help&utm_campaign=Support+Materials&utm_content=Get+Help+Email+Troubleshooting'><?php esc_html_e('Ninja Forms Email troubleshooting', 'ninja-forms'); ?></a></li>
        <!-- <li><a href='https://ninjaforms.com/docs/basic-troubleshooting/?utm_source=plugin&utm_medium=get-help&utm_campaign=documentation'><?php esc_html_e('What to try before contacting support', 'ninja-forms'); ?></a></li> -->
        <li><a href='https://ninjaforms.com/scope-of-support/?utm_source=Ninja+Forms+Plugin&utm_medium=Get+Help&utm_campaign=Support+Materials&utm_content=Get+Help+Scope+of+Support'><?php esc_html_e('Our Scope of Support', 'ninja-forms'); ?></a></li>
    </ol>

    <div class="nf-box">
        <div class="submit debug-report">
            <h3><?php esc_html_e('To Get Help:', 'ninja-forms'); ?></h3>
            <ol>
                <li><?php esc_html_e('Copy your System Report first with the button below', 'ninja-forms'); ?> </li>
                <li><?php esc_html_e('Click "Submit a Support Request" to be directed to our site.', 'ninja-forms'); ?> </li>
                <li><?php esc_html_e('Include this information in your support request by pasting in the "System Status" portion of the form. (right click, choose "Paste" or use Ctrl+V)', 'ninja-forms'); ?> </li>
            </ol>
            <p><?php esc_html_e('This information is vital for addressing your issue in a timely manner.', 'ninja-forms'); ?></p>
            <a href="#" id="copy-system-status" class="button-primary"><?php esc_html_e('Copy System Report', 'ninja-forms'); ?></a>
            <a href="https://ninjaforms.com/contact/?utm_source=Ninja+Forms+Plugin&utm_medium=Get+Help&utm_campaign=Support+Request" class="button-secondary"><?php esc_html_e('Submit a Support Request', 'ninja-forms'); ?></a>
            <p>
                <em><?php esc_html_e('For your security, do not post this information in public places, such as the WordPress.org support forums.', 'ninja-forms'); ?></em>
            </p>
        </div>
        <div id="debug-report">
            <textarea readonly="readonly"></textarea>
        </div>
    </div>

</div>
<br />
<table class="nf-status-table" cellspacing="0">
    <thead>
        <tr>
            <th colspan="2"><?php esc_html_e('Environment', 'ninja-forms'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($environment as $key => $value): ?>
            <tr>
                <td><?php echo esc_html($key) . ':'; ?></td>
                <td><?php echo esc_html($value); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <thead>
        <tr>
            <th colspan="2"><?php esc_html_e('PHP Extensions', 'ninja-forms'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($php_extensions as $key => $value): ?>
            <tr>
                <td><?php echo esc_html($key) . ':'; ?></td>
                <td><?php echo esc_html($value); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <thead>
        <tr>
            <th colspan="2"><?php esc_html_e('Ninja Forms Settings', 'ninja-forms'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($NF_environment as $key => $value): ?>
            <tr>
                <td><?php echo esc_html($key) . ':'; ?></td>
                <td><?php echo esc_html($value); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <thead>
        <tr>
            <th colspan="2"><?php esc_html_e('Themes', 'ninja-forms'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php esc_html_e('Active theme', 'ninja-forms'); ?>:</td>
            <td><?php echo $current_theme . ' v' . $current_theme_version; ?></td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td><?php esc_html_e('All installed themes', 'ninja-forms'); ?>:</td>
            <td><?php echo $all_installed_themes; ?></td>
        </tr>
    </tbody>
    <thead>
        <tr>
            <th colspan="2"><?php esc_html_e('Plugins', 'ninja-forms'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php esc_html_e('Activated Plugins', 'ninja-forms'); ?>:</td>
            <td><?php echo $site_wide_plugins; ?></td>
        </tr>
    </tbody>
    <tbody>
        <tr>
            <td><?php esc_html_e('Inactive Plugins', 'ninja-forms'); ?>:</td>
            <td class=scroll><?php echo $site_wide_inactive_plugins; ?></td>
        </tr>
    </tbody>
    <thead>
        <tr>
            <th colspan="2"><?php esc_html_e('Error logs', 'ninja-forms'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?php esc_html_e('PHP errors', 'ninja-forms'); ?>:</td>
            <td class=scroll><?php echo nl2br(implode("\n", $errorLog)); ?>
            </td>
        </tr>
    </tbody>
    <?php if (! empty($addons_comm_status)) : ?>
        <?php foreach ($addons_comm_status as $addon) : ?>
    <thead>
        <tr>
            <th colspan="2"><?php echo esc_html($addon['name']) . " Comm Data"; ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td></td>
            <td class="scroll"><?php echo esc_html(is_string($addon['data']) ? $addon['data'] : serialize($addon['data'])); ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>